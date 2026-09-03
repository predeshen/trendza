<?php
namespace Trendza\Suppliers;

final class CatalogueSynchronizer {
    public function __construct(private PricingEngine $pricing, private ProductDeduplicator $deduplicator) {}

    public function normalise(SupplierProduct $item, float $marginPercent = 25.0): array {
        return [
            'external_id' => $item->externalId,
            'sku' => $item->sku,
            'name' => trim($item->name),
            'cost' => round(max(0, $item->cost), 2),
            'price' => $this->pricing->calculate($item->cost, $marginPercent),
            'rrp' => round(max(0, $item->rrp), 2),
            'in_stock' => $item->inStock,
            'brand' => trim($item->brand),
            'description' => trim($item->description),
            'image' => trim($item->image),
            'categories' => $item->categories,
            'attributes' => $item->attributes,
            'dedupe_key' => $this->deduplicator->key($item),
        ];
    }
}
