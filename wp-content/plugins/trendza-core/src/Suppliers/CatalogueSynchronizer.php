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
            'variants' => $this->normaliseVariants($item->variants, $marginPercent),
        ];
    }

    private function normaliseVariants(array $variants, float $marginPercent): array {
        $normalised = [];
        foreach ($variants as $variant) {
            if (!is_array($variant)) continue;
            $externalId = trim((string) ($variant['external_id'] ?? $variant['id'] ?? ''));
            $sku = trim((string) ($variant['sku'] ?? ''));
            if ($externalId === '' && $sku === '') continue;
            $cost = max(0, (float) ($variant['cost'] ?? 0));
            $price = $this->pricing->calculate($cost, $marginPercent);
            $suppliedPrice = max(0, (float) ($variant['price'] ?? 0));
            if ($suppliedPrice > 0) $price = $suppliedPrice;
            $salePrice = max(0, (float) ($variant['sale_price'] ?? $variant['saleprice'] ?? 0));
            $normalised[] = [
                'external_id' => $externalId,
                'sku' => $sku,
                'name' => trim((string) ($variant['name'] ?? $variant['title'] ?? '')),
                'cost' => round($cost, 2),
                'price' => round($price, 2),
                'sale_price' => $salePrice > 0 && $salePrice < $price ? round($salePrice, 2) : 0.0,
                'rrp' => round(max(0, (float) ($variant['rrp'] ?? 0)), 2),
                'in_stock' => !empty($variant['in_stock']),
                'attributes' => is_array($variant['attributes'] ?? null) ? $variant['attributes'] : [],
                'image' => trim((string) ($variant['image'] ?? '')),
            ];
        }
        return $normalised;
    }
}
