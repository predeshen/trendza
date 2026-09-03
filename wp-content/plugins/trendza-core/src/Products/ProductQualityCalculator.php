<?php
namespace Trendza\Products;

final class ProductQualityCalculator {
    public function calculate($product): float {
        $score = 0.0;
        $score += $product->get_description() !== '' ? 20 : 0;
        $score += $product->get_short_description() !== '' ? 10 : 0;
        $score += $product->get_image_id() ? 15 : 0;
        $score += $product->get_sku() !== '' ? 5 : 0;
        $score += $product->get_price() !== '' ? 10 : 0;
        $score += $product->is_in_stock() ? 10 : 0;
        $score += $product->get_average_rating() > 0 ? 15 : 0;
        $score += $product->get_review_count() > 0 ? 5 : 0;
        $score += count($product->get_category_ids()) > 0 ? 5 : 0;
        return round(min(100, $score), 2);
    }
}
