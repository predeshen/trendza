<?php
namespace Trendza\Products;

final class ProductData {
    public static function fromProduct($product): array {
        $id = $product->get_id();
        $image = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail');
        $categories = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
        return [
            'id' => $id,
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'url' => get_permalink($id),
            'sku' => $product->get_sku(),
            'price' => (float) $product->get_price(),
            'regular_price' => (float) $product->get_regular_price(),
            'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
            'stock_status' => $product->get_stock_status(),
            'in_stock' => $product->is_in_stock(),
            'image' => $image ?: '',
            'categories' => is_array($categories) ? $categories : [],
            'brand' => ProductMeta::get($id, ProductMeta::BRAND),
            'trend_score' => (float) ProductMeta::get($id, ProductMeta::TREND_SCORE, 0),
            'trend_status' => ProductMeta::get($id, ProductMeta::TREND_STATUS, 'stable'),
            'quality_score' => (float) ProductMeta::get($id, ProductMeta::QUALITY_SCORE, 0),
            'value_score' => (float) ProductMeta::get($id, ProductMeta::VALUE_SCORE, 0),
            'summary' => ProductMeta::get($id, ProductMeta::SUMMARY),
            'ai_summary' => ProductMeta::get($id, ProductMeta::AI_SUMMARY),
            'updated_at' => ProductMeta::get($id, ProductMeta::TREND_UPDATED),
        ];
    }
}
