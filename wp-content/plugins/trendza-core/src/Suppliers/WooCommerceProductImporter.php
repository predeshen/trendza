<?php
namespace Trendza\Suppliers;

use Trendza\Products\ProductMeta;

final class WooCommerceProductImporter {
    public function import(array $data, string $supplierCode, bool $updatePrice = true, bool $updateStock = true): int {
        if (!function_exists('wc_get_product_id_by_sku')) throw new \RuntimeException('WooCommerce is required.');

        $externalId = sanitize_text_field((string) ($data['external_id'] ?? ''));
        $sku = sanitize_text_field((string) ($data['sku'] ?? ''));
        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        $supplierCode = sanitize_key($supplierCode);
        if ($name === '' || ($externalId === '' && $sku === '')) throw new \InvalidArgumentException('Product requires a name and external_id or SKU.');

        $id = $sku !== '' ? (int) wc_get_product_id_by_sku($sku) : 0;
        if (!$id && $externalId !== '') {
            $ids = get_posts(['post_type'=>'product','post_status'=>'any','numberposts'=>1,'fields'=>'ids','meta_query'=>[
                ['key'=>ProductMeta::EXTERNAL_ID,'value'=>$externalId],
                ['key'=>ProductMeta::SUPPLIER_CODE,'value'=>$supplierCode],
            ]]);
            $id = (int) ($ids[0] ?? 0);
        }

        $hasVariants = !empty($data['variants']) && is_array($data['variants']);
        if ($id) {
            $product = $hasVariants ? new \WC_Product_Variable($id) : wc_get_product($id);
        } else {
            $product = $hasVariants ? new \WC_Product_Variable() : new \WC_Product_Simple();
        }
        if (!$product) throw new \RuntimeException('Unable to load WooCommerce product.');

        $product->set_name($name);
        if (array_key_exists('sku', $data)) {
            $currentSku = (string) $product->get_sku();
            if ($sku !== $currentSku) $product->set_sku($sku);
        }

        if (array_key_exists('description', $data)) {
            $description = wp_kses_post((string) $data['description']);
            $product->set_description($description);
            $short = sanitize_textarea_field(wp_trim_words(wp_strip_all_tags($description), 35));
            $product->set_short_description($short);
        }

        if (!$hasVariants && $updatePrice && array_key_exists('price', $data)) {
            $price = max(0, (float) $data['price']);
            $product->set_regular_price(wc_format_decimal($price));
            $salePrice = array_key_exists('sale_price', $data) ? max(0, (float) $data['sale_price']) : 0;
            $product->set_sale_price($salePrice > 0 && $salePrice < $price ? wc_format_decimal($salePrice) : '');
        }

        if (!$hasVariants && $updateStock && array_key_exists('in_stock', $data)) {
            $product->set_manage_stock(false);
            $product->set_stock_status(!empty($data['in_stock']) ? 'instock' : 'outofstock');
        }

        if (array_key_exists('attributes', $data) || $hasVariants) {
            $this->syncAttributes($product, (array) ($data['attributes'] ?? []), $hasVariants ? $this->variantAttributeOptions((array) $data['variants']) : [], $hasVariants);
        }
        $productId = $product->save();

        if ($externalId !== '') update_post_meta($productId, ProductMeta::EXTERNAL_ID, $externalId);
        update_post_meta($productId, ProductMeta::SUPPLIER_CODE, $supplierCode);
        if (array_key_exists('cost', $data)) {
            update_post_meta($productId, ProductMeta::SUPPLIER_COST, max(0, (float) $data['cost']));
        }
        if (array_key_exists('rrp', $data)) {
            update_post_meta($productId, ProductMeta::SUPPLIER_RRP, max(0, (float) $data['rrp']));
        }
        update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced');
        update_post_meta($productId, ProductMeta::LAST_SYNC, current_time('mysql', true));

        if (array_key_exists('brand', $data)) {
            $brand = sanitize_text_field((string) $data['brand']);
            if ($brand !== '') update_post_meta($productId, ProductMeta::BRAND, $brand);
            else delete_post_meta($productId, ProductMeta::BRAND);
        }
        if (array_key_exists('categories', $data)) {
            $this->syncCategories($productId, (array) $data['categories']);
        }
        if (array_key_exists('image', $data)) {
            $this->syncImage($productId, (string) $data['image']);
        }
        if ($hasVariants) {
            $this->syncVariants($productId, (array) $data['variants'], $updatePrice, $updateStock);
        }
        return $productId;
    }

    private function syncCategories(int $productId, array $categories): void {
        // Empty supplier category data must never wipe existing taxonomy.
        $categories = array_values(array_filter($categories, static fn ($category): bool => trim((string) $category) !== ''));
        if (!$categories) return;

        $previousSupplierIds = array_values(array_filter(array_map(
            'intval',
            (array) get_post_meta($productId, ProductMeta::SUPPLIER_CATEGORIES, true)
        )));
        $existingIds = array_map('intval', wp_get_object_terms($productId, 'product_cat', ['fields' => 'ids']));
        $termIds = [];

        foreach ($categories as $category) {
            $category = trim(sanitize_text_field((string) $category));
            if ($category === '') continue;
            $parent = 0;
            $parts = array_values(array_filter(array_map('trim', preg_split('/\s*(?:>|\/)\s*/', $category) ?: [])));
            foreach ($parts as $part) {
                $termId = 0;
                $existing = get_terms([
                    'taxonomy' => 'product_cat',
                    'name' => $part,
                    'parent' => $parent,
                    'hide_empty' => false,
                    'number' => 1,
                ]);
                if (!is_wp_error($existing) && !empty($existing)) {
                    $termId = (int) $existing[0]->term_id;
                } else {
                    $created = wp_insert_term($part, 'product_cat', ['parent' => $parent]);
                    if (is_wp_error($created)) continue 2;
                    $termId = (int) $created['term_id'];
                }
                $parent = $termId;
            }
            if ($parent) $termIds[] = $parent;
        }

        $termIds = array_values(array_unique(array_filter($termIds)));
        $manualIds = array_values(array_diff($existingIds, $previousSupplierIds));
        $finalIds = array_values(array_unique(array_merge($manualIds, $termIds)));
        wp_set_object_terms($productId, $finalIds, 'product_cat', false);
        update_post_meta($productId, ProductMeta::SUPPLIER_CATEGORIES, $termIds);
    }

    private function syncAttributes(\WC_Product $product, array $attributes, array $variantOptions = [], bool $variationEnabled = false): void {
        $previousSupplierNames = array_values(array_filter(array_map(
            'strval',
            (array) get_post_meta($product->get_id(), ProductMeta::SUPPLIER_ATTRIBUTES, true)
        )));

        $existing = $product->get_attributes();
        foreach ($previousSupplierNames as $name) {
            $key = sanitize_title($name);
            if (isset($existing[$key])) unset($existing[$key]);
        }

        if ($variationEnabled) {
            foreach ($variantOptions as $name => $options) {
                if (!array_key_exists($name, $attributes) && !empty($options)) {
                    $attributes[$name] = $options[0];
                }
            }
        }

        $managedNames = [];
        $position = count($existing);

        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                if (!is_array($value) || empty($value['name'])) continue;
                $name = $value['name'];
                $value = $value['value'] ?? '';
            }

            $name = sanitize_text_field((string) $name);
            $value = sanitize_text_field(is_array($value) ? implode(', ', $value) : (string) $value);
            if ($name === '' || $value === '') continue;

            $attribute = new \WC_Product_Attribute();
            $attribute->set_id(0);
            $attribute->set_name($name);
            $options = $variationEnabled && isset($variantOptions[$name]) ? $variantOptions[$name] : [$value];
            $options = array_values(array_unique(array_filter(array_map(static fn ($option): string => sanitize_text_field((string) $option), $options))));
            if (!$options) $options = [$value];
            $attribute->set_options($options);
            $attribute->set_position($position++);
            $attribute->set_visible(true);
            $attribute->set_variation($variationEnabled);
            $existing[sanitize_title($name)] = $attribute;
            $managedNames[] = $name;
        }

        $product->set_attributes(array_values($existing));
        update_post_meta($product->get_id(), ProductMeta::SUPPLIER_ATTRIBUTES, array_values(array_unique($managedNames)));
    }


    private function variantAttributeOptions(array $variants): array {
        $options = [];
        foreach ($variants as $variant) {
            if (!is_array($variant) || !is_array($variant['attributes'] ?? null)) continue;
            foreach ($variant['attributes'] as $name => $value) {
                $name = sanitize_text_field((string) $name);
                $value = sanitize_text_field(is_array($value) ? implode(', ', $value) : (string) $value);
                if ($name === '' || $value === '') continue;
                $options[$name][] = $value;
            }
        }
        foreach ($options as $name => $values) {
            $options[$name] = array_values(array_unique($values));
        }
        return $options;
    }

    private function syncVariants(int $productId, array $variants, bool $updatePrice, bool $updateStock): void {
        if (!class_exists('WC_Product_Variable') || !class_exists('WC_Product_Variation')) return;

        $parent = new \WC_Product_Variable($productId);
        $previousIds = array_values(array_filter(array_map(
            'intval',
            (array) get_post_meta($productId, ProductMeta::SUPPLIER_VARIATIONS, true)
        )));
        $managedIds = [];

        foreach ($variants as $variantData) {
            if (!is_array($variantData)) continue;
            $externalId = sanitize_text_field((string) ($variantData['external_id'] ?? ''));
            $sku = sanitize_text_field((string) ($variantData['sku'] ?? ''));
            if ($externalId === '' && $sku === '') continue;

            $variationId = $sku !== '' ? (int) wc_get_product_id_by_sku($sku) : 0;
            if ($variationId && (int) wp_get_post_parent_id($variationId) !== $productId) $variationId = 0;
            if (!$variationId && $externalId !== '') {
                $ids = get_posts([
                    'post_type' => 'product_variation',
                    'post_status' => 'any',
                    'numberposts' => 1,
                    'fields' => 'ids',
                    'meta_query' => [
                        ['key' => ProductMeta::EXTERNAL_ID, 'value' => $externalId],
                        ['key' => ProductMeta::SUPPLIER_CODE, 'value' => sanitize_key((string) get_post_meta($productId, ProductMeta::SUPPLIER_CODE, true))],
                    ],
                ]);
                $candidateId = (int) ($ids[0] ?? 0);
                $variationId = $candidateId && (int) wp_get_post_parent_id($candidateId) === $productId ? $candidateId : 0;
            }

            $variation = $variationId ? new \WC_Product_Variation($variationId) : new \WC_Product_Variation();
            $variation->set_parent_id($productId);
            $variationName = sanitize_text_field((string) ($variantData['name'] ?? ''));
            if ($variationName !== '') $variation->set_description($variationName);

            if ($sku !== '') {
                $currentSku = (string) $variation->get_sku();
                if ($sku !== $currentSku) $variation->set_sku($sku);
            }

            if ($updatePrice && array_key_exists('price', $variantData)) {
                $price = max(0, (float) $variantData['price']);
                $variation->set_regular_price(wc_format_decimal($price));
                $salePrice = max(0, (float) ($variantData['sale_price'] ?? 0));
                $variation->set_sale_price($salePrice > 0 && $salePrice < $price ? wc_format_decimal($salePrice) : '');
            }

            if ($updateStock && array_key_exists('in_stock', $variantData)) {
                $variation->set_manage_stock(false);
                $variation->set_stock_status(!empty($variantData['in_stock']) ? 'instock' : 'outofstock');
            }

            $variationAttributes = [];
            foreach ((array) ($variantData['attributes'] ?? []) as $name => $value) {
                $name = sanitize_title((string) $name);
                $value = sanitize_title(is_array($value) ? implode(', ', $value) : (string) $value);
                if ($name !== '' && $value !== '') $variationAttributes[$name] = $value;
            }
            if ($variationAttributes) $variation->set_attributes($variationAttributes);

            $savedId = $variation->save();
            if (!$savedId) continue;
            $managedIds[] = (int) $savedId;
            if ($externalId !== '') update_post_meta($savedId, ProductMeta::EXTERNAL_ID, $externalId);
            update_post_meta($savedId, ProductMeta::SUPPLIER_CODE, sanitize_key((string) get_post_meta($productId, ProductMeta::SUPPLIER_CODE, true)));
            if (array_key_exists('cost', $variantData)) update_post_meta($savedId, ProductMeta::SUPPLIER_COST, max(0, (float) $variantData['cost']));
            if (array_key_exists('rrp', $variantData)) update_post_meta($savedId, ProductMeta::SUPPLIER_RRP, max(0, (float) $variantData['rrp']));
        }

        foreach (array_diff($previousIds, $managedIds) as $staleId) {
            if ($staleId > 0) wp_delete_post($staleId, true);
        }

        update_post_meta($productId, ProductMeta::SUPPLIER_VARIATIONS, array_values(array_unique($managedIds)));
        $parent->set_default_attributes([]);
        $parent->save();
    }

    private function syncImage(int $productId, string $imageUrl): void {
        $imageUrl = esc_url_raw(trim($imageUrl));
        if ($imageUrl === '' || !wp_http_validate_url($imageUrl)) return;

        $previous = (string) get_post_meta($productId, ProductMeta::SOURCE_IMAGE, true);
        if ($previous === $imageUrl && has_post_thumbnail($productId)) return;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $response = wp_safe_remote_get($imageUrl, [
            'timeout' => 15,
            'redirection' => 3,
            'limit_response_size' => 8 * 1024 * 1024,
        ]);

        if (is_wp_error($response)) {
            update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced_image_error');
            return;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $type = strtolower(trim((string) wp_remote_retrieve_header($response, 'content-type')));
        if ($status < 200 || $status >= 300 || !str_starts_with($type, 'image/')) {
            update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced_image_error');
            return;
        }

        $body = wp_remote_retrieve_body($response);
        if ($body === '' || strlen($body) > 8 * 1024 * 1024) {
            update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced_image_error');
            return;
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
        ];
        $extension = $extensions[$type] ?? 'jpg';
        $tmp = wp_tempnam('trendza-product-' . $productId . '.' . $extension);
        if (!$tmp || file_put_contents($tmp, $body) === false) {
            if ($tmp && file_exists($tmp)) @unlink($tmp);
            update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced_image_error');
            return;
        }

        $fileName = sanitize_file_name('trendza-product-' . $productId . '.' . $extension);
        $fileType = wp_check_filetype_and_ext($tmp, $fileName, $type);
        if (!empty($fileType['type']) && !str_starts_with(strtolower((string) $fileType['type']), 'image/')) {
            @unlink($tmp);
            update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced_image_error');
            return;
        }

        $file = [
            'name' => $fileName,
            'type' => !empty($fileType['type']) ? $fileType['type'] : $type,
            'tmp_name' => $tmp,
            'error' => 0,
            'size' => filesize($tmp),
        ];

        $attachmentId = media_handle_sideload($file, $productId);
        @unlink($tmp);

        if (is_wp_error($attachmentId)) {
            update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced_image_error');
            return;
        }

        $oldAttachmentId = (int) get_post_thumbnail_id($productId);
        set_post_thumbnail($productId, (int) $attachmentId);
        update_post_meta($productId, ProductMeta::SOURCE_IMAGE, $imageUrl);

        if ($oldAttachmentId > 0 && $oldAttachmentId !== (int) $attachmentId && (int) get_post_field('post_parent', $oldAttachmentId) === $productId) {
            wp_delete_attachment($oldAttachmentId, true);
        }
    }
}
