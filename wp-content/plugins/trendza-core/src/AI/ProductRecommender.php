<?php
namespace Trendza\AI;

final class ProductRecommender {
    /**
     * Rank catalogue candidates against a reference product using deterministic,
     * explainable signals. No external AI service is required.
     */
    public static function rank(array $reference, array $candidates, int $limit = 6): array {
        $ranked = [];
        foreach ($candidates as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === (int) ($reference['id'] ?? 0)) continue;
            if (empty($candidate['in_stock'])) continue;

            $categoryOverlap = self::categoryOverlap($reference['categories'] ?? [], $candidate['categories'] ?? []);
            $priceSimilarity = self::priceSimilarity((float) ($reference['price'] ?? 0), (float) ($candidate['price'] ?? 0));
            $quality = min(100, max(0, (float) ($candidate['quality_score'] ?? 0)));
            $trend = min(100, max(0, (float) ($candidate['trend_score'] ?? 0)));
            $value = min(100, max(0, (float) ($candidate['value_score'] ?? 0)));

            $score = ($categoryOverlap * 35) + ($priceSimilarity * 20) + ($quality * 20) + ($trend * 15) + ($value * 10);
            $candidate['recommendation_score'] = round($score / 100, 2);
            $candidate['why_recommended'] = self::reason($categoryOverlap, $priceSimilarity, $quality, $trend, $value);
            $ranked[] = $candidate;
        }

        usort($ranked, static fn(array $a, array $b): int => ($b['recommendation_score'] <=> $a['recommendation_score']));
        return array_slice($ranked, 0, max(1, $limit));
    }

    private static function categoryOverlap(array $left, array $right): float {
        $left = array_map('strtolower', array_map('strval', $left));
        $right = array_map('strtolower', array_map('strval', $right));
        if (!$left || !$right) return 0;
        $intersection = count(array_intersect($left, $right));
        return min(100, ($intersection / max(1, count(array_unique($left)))) * 100);
    }

    private static function priceSimilarity(float $reference, float $candidate): float {
        if ($reference <= 0 || $candidate <= 0) return 50;
        $ratio = abs($reference - $candidate) / max($reference, $candidate);
        return max(0, 100 - ($ratio * 100));
    }

    private static function reason(float $categoryOverlap, float $priceSimilarity, float $quality, float $trend, float $value): string {
        $reasons = [];
        if ($categoryOverlap >= 50) $reasons[] = 'similar category';
        if ($priceSimilarity >= 75) $reasons[] = 'similar price point';
        if ($quality >= 80) $reasons[] = 'strong quality signals';
        if ($trend >= 75) $reasons[] = 'currently trending';
        elseif ($trend >= 55) $reasons[] = 'gaining momentum';
        if ($value >= 80) $reasons[] = 'strong value score';
        return $reasons ? ucfirst(implode(', ', $reasons)) . '.' : 'A relevant alternative based on catalogue signals.';
    }
}
