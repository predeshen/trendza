<?php
namespace Trendza\Suppliers;

final class SupplierConfig {
    public function __construct(
        public readonly string $code,
        public readonly float $marginPercent = 25.0,
        public readonly float $minimumFeedRetention = 0.50,
        public readonly int $maxProducts = 300,
        public readonly bool $updatePrice = true,
        public readonly bool $updateStock = true,
        public readonly bool $allowEmptyFeed = false,
    ) {
        if (trim($this->code) === '') {
            throw new \InvalidArgumentException('Supplier code cannot be empty.');
        }
        if ($this->marginPercent < 0 || $this->marginPercent >= 90) {
            throw new \InvalidArgumentException('Margin must be at least 0 and below 90 percent.');
        }
        if ($this->minimumFeedRetention < 0 || $this->minimumFeedRetention > 1) {
            throw new \InvalidArgumentException('Minimum feed retention must be between 0 and 1.');
        }
        if ($this->maxProducts < 1) {
            throw new \InvalidArgumentException('Maximum products must be at least 1.');
        }
    }

    public function withOverrides(?float $marginPercent = null, ?int $maxProducts = null, ?bool $updatePrice = null, ?bool $updateStock = null): self {
        return new self(
            $this->code,
            $marginPercent ?? $this->marginPercent,
            $this->minimumFeedRetention,
            $maxProducts ?? $this->maxProducts,
            $updatePrice ?? $this->updatePrice,
            $updateStock ?? $this->updateStock,
            $this->allowEmptyFeed,
        );
    }
}
