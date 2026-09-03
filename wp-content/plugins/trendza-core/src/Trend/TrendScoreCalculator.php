<?php
namespace Trendza\Trend;

final class TrendScoreCalculator {
    public function calculate(array $signals): float {
        $weighted = 0.0; $weights = 0.0;
        foreach ($signals as $signal) {
            if (!$signal instanceof TrendSignal || $signal->weight <= 0) continue;
            $value = max(0, min(100, $signal->value));
            $weighted += $value * $signal->weight;
            $weights += $signal->weight;
        }
        return $weights > 0 ? round($weighted / $weights, 2) : 0.0;
    }
}
