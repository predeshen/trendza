<?php
namespace Trendza\Suppliers;

use Trendza\Products\ProductMeta;

final class WooCommerceProductImporter {
    public function import(array $data, string $supplierCode, bool $updatePrice = true, bool $updateStock = true): int {
        if (!function_exists('wc_get_product_id_by_sku')) {
            throw new \RuntimeException('WooCommerce is required.');
        }

        $externalId = sanitize_text_field((string) ($data['external_id'] ?? ''));
        $sku = sanitize_text_field((string) ($data['sku'] ?? ''));
        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        $supplierCode = sanitize_key($supplierCode);

        if ($name === '' || ($externalId === '' && $sku === '')) {
            throw new \InvalidArgumentException('Product requires a name and external_id or SKU.');
        }

        $id = $sku !== '' ? (int) wc_get_product_id_by_sku($sku) : 0;
        if (!$id && $externalId !== '') {
            $ids = get_posts([
                'post_type' => 'product',
                'post_status' => 'any',
                'numberposts' => 1,
                'fields' => 'ids',
                'meta_query' => [
                    ['key' => ProductMeta::EXTERNAL_ID, 'value' => $externalId],
                    ['key' => ProductMeta::SUPPLIER_CODE, 'value' => $supplierCode],
                ],
            ]);
            $id = (int) ($ids[0] ?? 0);
        }

        $isNew = !$id;
        $product = $id ? wc_get_product($id) : new \WC_Product_Simple();
        if (!$product) {
            throw new \RuntimeException('Unable to load WooCommerce product.');
        }

        $product->set_name($name);
        if ($sku !== '' && ($isNew || $product->get_sku() !== $sku)) {
            $product->set_sku($sku);
        }

        $description = wp_kses_post((string) ($data['description'] ?? ''));
        if ($description !== '') {
            $product->set_description($description);
            $product->set_short_description(wp_trim_words(wp_strip_all_tags($description), 35));
        }

        if ($updatePrice && isset($data['price'])) {
            $price = max(0, (float) $data['price']);
            $product->set_regular_price(wc_format_decimal($price));
            $salePrice = isset($data['sale_price']) ? max(0, (float) $data['sale_price']) : 0;
            $product->set_sale_price($salePrice > 0 && $salePrice < $price ? wc_format_decimal($salePrice) : '');
        }

        if ($updateStock) {
            $product->set_manage_stock(true);
            $product->set_stock_status(!empty($data['in_stock']) ? 'instock' : 'outofstock');
        }

        $productId = $product->save();

        update_post_meta($productId, ProductMeta::EXTERNAL_ID, $externalId);
        update_post_meta($productId, ProductMeta::SUPPLIER_CODE, $supplierCode);
        update_post_meta($productId, ProductMeta::SUPPLIER_COST, (float) ($data['cost'] ?? 0));
        update_post_meta($productId, ProductMeta::SUPPLIER_RRP, (float) ($data['rrp'] ?? 0));
        update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced');
        update_post_meta($productId, ProductMeta::LAST_SYNC, current_time('mysql', true));

        if (!empty($data['brand'])) {
            update_post_meta($productId, ProductMeta::BRAND, sanitize_text_field((string) $data['brand']));
        }

        $this->syncCategories($productId, (array) ($data['categories'] ?? []));
        $this->syncAttributes($product, (array) ($data['attributes'] ?? []));
        $this->syncImage($productId, (string) ($data['image'] ?? ''));

        return $productId;
    }

    private function syncCategories(int $productId, array $categories): void {
        if (!function_exists('wp_set_object_terms')) return;

        $termIds = [];
        foreach ($categories as $category) {
            $category = trim(sanitize_text_field((string) $category));
            if ($category === '') continue;

            $parent = 0;
            $parts = array_values(array_filter(array_map('trim', preg_split('/\\s*(?:>|\\/)\\s*/', $category) ?: [])));
            foreach ($parts as $part) {
                $existing = get_term_by('name', $part, 'product_cat');
                if ($existing && !is_wp_error($existing)) {
                    $termId = (int) $existing->term_id;
                } else {
                    $created = wp_insert_term($part, 'product_cat', ['parent' => $parent]);
                    if (is_wp_error($created)) continue 2;
                    $termId = (int) $created['term_id'];
                }
                $parent = $termId;
            }
            if ($parent) $termIds[] = $parent;
        }

        if ($termIds) {
            wp_set_object_terms($productId, array_values(array_unique($termIds)), 'product_cat', false);
        }
    }

    private function syncAttributes(\WC_Product $product, array $attributes): void {
        if (!$attributes) return;

        $productAttributes = [];
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
            $attribute->set_options([$value]);
            $attribute->set_position(count($productAttributes));
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            $productAttributes[] = $attribute;
        }

        if ($productAttributes) $product->set_attributes($productAttributes);
    }

    private function syncImage(int $productId, string $imageUrl): void {
        $imageUrl = esc_url_raw(trim($imageUrl));
        if ($imageUrl === '' || !wp_http_validate_url($imageUrl)) return;

        $previous = (string) get_post_meta($productId, ProductMeta::SOURCE_IMAGE, true);
        if ($previous === $imageUrl && has_post_thumbnail($productId)) return;

        if (has_post_thumbnail($productId)) {
            update_post_meta($productId, ProductMeta::SOURCE_IMAGE, $imageUrl);
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachmentId = media_sideload_image($imageUrl, $productId, null, 'id');
        if (is_wp_error($attachmentId)) {
            update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced_image_error');
            return;
        }

        set_post_thumbnail($productId, (int) $attachmentId);
        update_post_meta($productId, ProductMeta::SOURCE_IMAGE, $imageUrl);
    }
}
