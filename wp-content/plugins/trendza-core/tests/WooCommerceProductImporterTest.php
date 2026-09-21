<?php
use PHPUnit\Framework\TestCase;
use Trendza\Products\ProductMeta;

final class WooCommerceProductImporterTest extends TestCase {
    public function testSupplierAttributeMetaKeyIsDefined(): void {
        self::assertSame('_trendza_supplier_attributes', ProductMeta::SUPPLIER_ATTRIBUTES);
    }

    public function testSupplierCategoryMetaKeyIsDefined(): void {
        self::assertSame('_trendza_supplier_categories', ProductMeta::SUPPLIER_CATEGORIES);
    }

    public function testSupplierManagedMetadataKeysRemainDistinct(): void {
        self::assertNotSame(ProductMeta::SUPPLIER_ATTRIBUTES, ProductMeta::SUPPLIER_CATEGORIES);
        self::assertNotSame(ProductMeta::SUPPLIER_COST, ProductMeta::SUPPLIER_RRP);
    }
}
