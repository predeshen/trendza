<?php
namespace Trendza\Suppliers;

final class ProductDeduplicator {
    public function key(SupplierProduct $product): string {
        if ($product->sku !== '') return 'sku:' . strtolower(trim($product->sku));
        return 'external:' . strtolower(trim($product->externalId));
    }
}
