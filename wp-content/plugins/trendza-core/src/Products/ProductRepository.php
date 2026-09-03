<?php
namespace Trendza\Products;

final class ProductRepository {
    public function discover(int $limit = 8, string $mode = 'trending'): array {
        $limit = max(1, min(50, $limit));
        $mode = sanitize_key($mode);

        if ($mode === 'recent') {
            return $this->map(wc_get_products([
                'status' => 'publish',
                'limit' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'return' => 'objects',
            ]));
        }

        $metaKey = match ($mode) {
            'quality' => ProductMeta::QUALITY_SCORE,
            'best-value' => ProductMeta::VALUE_SCORE,
            default => ProductMeta::TREND_SCORE,
        };

        $args = [
            'status' => 'publish',
            'limit' => $limit,
            'orderby' => 'meta_value_num',
            'meta_key' => $metaKey,
            'order' => 'DESC',
            'return' => 'objects',
        ];

        if (in_array($mode, ['trending', 'rising', 'stable', 'declining'], true)) {
            $args['meta_query'] = [[
                'key' => ProductMeta::TREND_STATUS,
                'value' => $mode,
                'compare' => '=',
            ]];
        }

        $products = wc_get_products($args);

        if (!$products && in_array($mode, ['trending', 'rising', 'stable', 'declining'], true)) {
            $products = wc_get_products([
                'status' => 'publish',
                'limit' => $limit,
                'orderby' => 'meta_value_num',
                'meta_key' => ProductMeta::TREND_SCORE,
                'order' => 'DESC',
                'return' => 'objects',
            ]);
        }

        return $this->map($products);
    }

    private function map(array $products): array {
        return array_map([ProductData::class, 'fromProduct'], $products);
    }
}
