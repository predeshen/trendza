<?php
use PHPUnit\Framework\TestCase;
use Trendza\Products\ProductMeta;

final class WooCommerceProductImporterTest extends TestCase {
    public function testSupplierAttributeMetaKeyIsDefined(): void {
        self::assertSame('_trendza_supplier_attributes', ProductMeta::SUPPLIER_ATTRIBUTES);
    }
}
