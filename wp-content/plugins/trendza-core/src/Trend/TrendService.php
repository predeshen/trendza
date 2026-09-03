<?php
namespace Trendza\Trend;

use Trendza\Products\ProductMeta;
use Trendza\Products\ProductQualityCalculator;

final class TrendService {
    public static function registerSchedule(): void {
        if (!wp_next_scheduled('trendza_recalculate_trends')) {
            wp_schedule_event(time() + 300, 'twicedaily', 'trendza_recalculate_trends');
        }
    }

    public static function recalculatePublishedProducts(): void {
        if (!function_exists('wc_get_products')) return;
        $ids = wc_get_products(['status' => 'publish', 'limit' => -1, 'return' => 'ids']);
        foreach ($ids as $id) self::refreshProductQuality((int) $id);
    }

    public static function refreshProductQuality(int $productId, $post = null): void {
        if (wp_is_post_revision($productId) || !function_exists('wc_get_product')) return;
        $product = wc_get_product($productId);
        if (!$product) return;
        $quality = (new ProductQualityCalculator())->calculate($product);
        update_post_meta($productId, ProductMeta::QUALITY_SCORE, $quality);
        if (get_post_meta($productId, ProductMeta::TREND_SCORE, true) === '') {
            update_post_meta($productId, ProductMeta::TREND_SCORE, 0);
            update_post_meta($productId, ProductMeta::TREND_STATUS, TrendStatus::STABLE);
        }
        update_post_meta($productId, ProductMeta::TREND_UPDATED, current_time('mysql', true));
    }
}
