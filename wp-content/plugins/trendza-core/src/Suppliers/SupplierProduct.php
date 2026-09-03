<?php
namespace Trendza\Suppliers;

final class SupplierProduct {
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly float $cost,
        public readonly float $rrp,
        public readonly bool $inStock,
        public readonly string $sku = '',
        public readonly string $brand = '',
        public readonly string $description = '',
        public readonly string $image = '',
        public readonly array $categories = [],
        public readonly array $attributes = [],
    ) {}
}
