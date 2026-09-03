<?php
namespace Trendza\Admin;

use Trendza\Products\ProductMeta;

final class ProductFields {
    public static function register(): void {
        add_action('woocommerce_product_options_general_product_data', [self::class, 'fields']);
        add_action('woocommerce_process_product_meta', [self::class, 'save']);
    }
    public static function fields(): void {
        echo '<div class="options_group"><h4 style="padding-left:12px">Trendza Intelligence</h4>';
        woocommerce_wp_text_input(['id'=>ProductMeta::BRAND,'label'=>'Brand']);
        woocommerce_wp_text_input(['id'=>ProductMeta::MANUFACTURER,'label'=>'Manufacturer']);
        woocommerce_wp_textarea_input(['id'=>ProductMeta::SUMMARY,'label'=>'Trendza Summary']);
        woocommerce_wp_textarea_input(['id'=>ProductMeta::USE_CASES,'label'=>'Use Cases']);
        woocommerce_wp_textarea_input(['id'=>ProductMeta::PROS,'label'=>'Pros']);
        woocommerce_wp_textarea_input(['id'=>ProductMeta::CONS,'label'=>'Cons']);
        woocommerce_wp_textarea_input(['id'=>ProductMeta::SPECS,'label'=>'Specifications JSON']);
        woocommerce_wp_textarea_input(['id'=>ProductMeta::SHIPPING,'label'=>'Shipping Information']);
        woocommerce_wp_textarea_input(['id'=>ProductMeta::AI_SUMMARY,'label'=>'AI / GEO Summary']);
        woocommerce_wp_text_input(['id'=>ProductMeta::EXTERNAL_ID,'label'=>'Supplier External ID']);
        echo '</div>';
    }
    public static function save(int $productId): void {
        if (!current_user_can('edit_post', $productId)) return;
        $keys = [ProductMeta::BRAND,ProductMeta::MANUFACTURER,ProductMeta::SUMMARY,ProductMeta::USE_CASES,ProductMeta::PROS,ProductMeta::CONS,ProductMeta::SPECS,ProductMeta::SHIPPING,ProductMeta::AI_SUMMARY,ProductMeta::EXTERNAL_ID];
        foreach ($keys as $key) if (isset($_POST[$key])) update_post_meta($productId, $key, sanitize_textarea_field(wp_unslash($_POST[$key])));
    }
}
