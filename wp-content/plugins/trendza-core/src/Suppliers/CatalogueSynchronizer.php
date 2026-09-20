<?php
namespace Trendza\Suppliers;

final class CatalogueSynchronizer {
    public function __construct(private PricingEngine $pricing, private ProductDeduplicator $deduplicator) {}

    public function normalise(SupplierProduct $item, float $marginPercent = 25.0): array {
        $price = $this->pricing->calculate($item->cost, $marginPercent);
        $salePrice = max(0, $item->salePrice);

        return [
            'external_id' => $item->externalId,
            'sku' => $item->sku,
            'name' => trim($item->name),
            'cost' => round(max(0, $item->cost), 2),
            'price' => $price,
            'sale_price' => $salePrice > 0 && $salePrice < $price ? round($salePrice, 2) : 0.0,
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
