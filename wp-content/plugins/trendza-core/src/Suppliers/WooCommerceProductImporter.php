<?php
namespace Trendza\Suppliers;

use Trendza\Products\ProductMeta;

final class WooCommerceProductImporter {
    public function import(array $data, string $supplierCode, bool $updatePrice = true, bool $updateStock = true): int {
        if (!function_exists('wc_get_product_id_by_sku')) throw new \RuntimeException('WooCommerce is required.');
        $externalId = sanitize_text_field((string) ($data['external_id'] ?? ''));
        $sku = sanitize_text_field((string) ($data['sku'] ?? ''));
        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        if ($name === '' || ($externalId === '' && $sku === '')) throw new \InvalidArgumentException('Product requires a name and external_id or SKU.');

        $id = $sku !== '' ? (int) wc_get_product_id_by_sku($sku) : 0;
        if (!$id && $externalId !== '') {
            $ids = get_posts(['post_type'=>'product','post_status'=>'any','numberposts'=>1,'fields'=>'ids','meta_query'=>[
                ['key'=>ProductMeta::EXTERNAL_ID,'value'=>$externalId],
                ['key'=>ProductMeta::SUPPLIER_CODE,'value'=>$supplierCode],
            ]]);
            $id = (int) ($ids[0] ?? 0);
        }

        $product = $id ? wc_get_product($id) : new \WC_Product_Simple();
        $product->set_name($name);
        if ($sku !== '') $product->set_sku($sku);
        $product->set_description(wp_kses_post((string) ($data['description'] ?? '')));
        if ($updatePrice && isset($data['price'])) $product->set_regular_price((string) max(0, (float) $data['price']));
        if ($updateStock) { $product->set_manage_stock(true); $product->set_stock_status(!empty($data['in_stock']) ? 'instock' : 'outofstock'); }
        $productId = $product->save();

        update_post_meta($productId, ProductMeta::EXTERNAL_ID, $externalId);
        update_post_meta($productId, ProductMeta::SUPPLIER_CODE, sanitize_key($supplierCode));
        update_post_meta($productId, ProductMeta::SUPPLIER_COST, (float) ($data['cost'] ?? 0));
        update_post_meta($productId, ProductMeta::SUPPLIER_RRP, (float) ($data['rrp'] ?? 0));
        update_post_meta($productId, ProductMeta::SYNC_STATUS, 'synced');
        update_post_meta($productId, ProductMeta::LAST_SYNC, current_time('mysql', true));
        if (!empty($data['brand'])) update_post_meta($productId, ProductMeta::BRAND, sanitize_text_field((string) $data['brand']));
        if (!empty($data['categories']) && function_exists('wp_set_object_terms')) wp_set_object_terms($productId, array_map('sanitize_text_field', (array) $data['categories']), 'product_cat', false);
        return $productId;
    }
}
