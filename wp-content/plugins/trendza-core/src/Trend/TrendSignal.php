<?php
namespace Trendza\Trend;

final class TrendSignal {
    public function __construct(public readonly string $name, public readonly float $value, public readonly float $weight) {}
}
