<?php
namespace Trendza\Trend;

final class TrendStatusResolver {
    public function resolve(float $score, float $momentum = 0): string {
        if ($score >= 75) return TrendStatus::TRENDING;
        if ($score >= 55 && $momentum > 0) return TrendStatus::RISING;
        if ($score < 35 || $momentum < -10) return TrendStatus::DECLINING;
        return TrendStatus::STABLE;
    }
}
