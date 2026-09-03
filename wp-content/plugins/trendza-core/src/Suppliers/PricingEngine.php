<?php
namespace Trendza\Suppliers;

final class PricingEngine {
    public function calculate(float $cost, float $marginPercent = 25.0): float {
        if ($cost <= 0) return 0.0;
        $marginPercent = max(0.0, min(90.0, $marginPercent));
        return round($cost / (1 - ($marginPercent / 100)), 2);
    }
}
