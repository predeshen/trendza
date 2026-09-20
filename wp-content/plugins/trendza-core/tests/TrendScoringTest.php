<?php
use PHPUnit\Framework\TestCase;
use Trendza\Trend\TrendScoreCalculator;
use Trendza\Trend\TrendSignal;
use Trendza\Trend\TrendStatus;
use Trendza\Trend\TrendStatusResolver;

final class TrendScoringTest extends TestCase {
    public function testWeightedScoreIsNormalised(): void {
        $calculator = new TrendScoreCalculator();

        self::assertSame(
            70.0,
            $calculator->calculate([
                new TrendSignal('sales', 100, 30),
                new TrendSignal('views', 50, 10),
            ])
        );
    }

    public function testSignalsAreClampedToValidRange(): void {
        $calculator = new TrendScoreCalculator();

        self::assertSame(
            50.0,
            $calculator->calculate([
                new TrendSignal('high', 150, 1),
                new TrendSignal('low', -20, 1),
            ])
        );
    }

    public function testZeroOrNegativeWeightsAreIgnored(): void {
        $calculator = new TrendScoreCalculator();

        self::assertSame(
            80.0,
            $calculator->calculate([
                new TrendSignal('valid', 80, 1),
                new TrendSignal('ignored', 100, 0),
                new TrendSignal('ignored-negative', 0, -1),
            ])
        );
    }

    public function testStatusThresholdsRemainDeterministic(): void {
        $resolver = new TrendStatusResolver();

        self::assertSame(TrendStatus::TRENDING, $resolver->resolve(75, -50));
        self::assertSame(TrendStatus::RISING, $resolver->resolve(60, 1));
        self::assertSame(TrendStatus::DECLINING, $resolver->resolve(34.99, 20));
        self::assertSame(TrendStatus::DECLINING, $resolver->resolve(60, -10.01));
        self::assertSame(TrendStatus::STABLE, $resolver->resolve(60, 0));
    }
}
