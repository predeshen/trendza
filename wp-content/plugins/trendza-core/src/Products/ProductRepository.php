<?php
namespace Trendza\Products;

final class ProductRepository {
    public function discover(int $limit = 8, string $mode = 'trending'): array {
        $limit = max(1, min(50, $limit));
        $metaKey = ProductMeta::TREND_SCORE;
        $order = 'DESC';
        if ($mode === 'rising') {
            $metaKey = ProductMeta::TREND_SCORE;
        } elseif ($mode === 'quality') {
            $metaKey = ProductMeta::QUALITY_SCORE;
        } elseif ($mode === 'best-value') {
            $metaKey = ProductMeta::VALUE_SCORE;
        } elseif ($mode === 'recent') {
            $args = ['status' => 'publish', 'limit' => $limit, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects'];
            return array_map([ProductData::class, 'fromProduct'], wc_get_products($args));
        }
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => $limit,
            'orderby' => 'meta_value_num',
            'meta_key' => $metaKey,
            'order' => $order,
            'return' => 'objects',
        ]);
        return array_map([ProductData::class, 'fromProduct'], $products);
    }
}
