<?php
use PHPUnit\Framework\TestCase;
use Trendza\Trend\TrendSignal;
use Trendza\Trend\TrendScoreCalculator;

final class TrendScoreCalculatorTest extends TestCase {
    public function testWeightedScore(): void {
        $calculator = new TrendScoreCalculator();
        $this->assertSame(72.0, $calculator->calculate([new TrendSignal('sales',80,.6),new TrendSignal('views',60,.4)]));
    }
    public function testValuesAreClamped(): void {
        $this->assertSame(100.0, (new TrendScoreCalculator())->calculate([new TrendSignal('signal',140,1)]));
    }
}
