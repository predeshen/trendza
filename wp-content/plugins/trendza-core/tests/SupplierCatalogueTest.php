<?php
use PHPUnit\Framework\TestCase;
use Trendza\Suppliers\CatalogueSynchronizer;
use Trendza\Suppliers\PricingEngine;
use Trendza\Suppliers\ProductDeduplicator;
use Trendza\Suppliers\SupplierProduct;

final class SupplierCatalogueTest extends TestCase {
    public function testPricingAndNormalisationProduceStableSupplierPayload(): void {
        $item = new SupplierProduct(
            'ABC-123',
            '  Smart Lamp  ',
            250.0,
            399.0,
            true,
            'SKU-001',
            'Acme',
            '  Useful lamp  ',
            'https://example.com/lamp.jpg',
            ['Smart Home', 'Lighting > Smart Lights'],
            ['Colour' => 'White']
        );

        $result = (new CatalogueSynchronizer(new PricingEngine(), new ProductDeduplicator()))->normalise($item);

        self::assertSame('ABC-123', $result['external_id']);
        self::assertSame('SKU-001', $result['sku']);
        self::assertSame('Smart Lamp', $result['name']);
        self::assertSame(333.33, $result['price']);
        self::assertSame('Acme', $result['brand']);
        self::assertSame('Smart Home', $result['categories'][0]);
        self::assertSame('sku:sku-001', $result['dedupe_key']);
    }

    public function testPricingClampsInvalidMargin(): void {
        $pricing = new PricingEngine();

        self::assertSame(100.0, $pricing->calculate(100, 0));
        self::assertSame(200.0, $pricing->calculate(100, 50));
        self::assertSame(1000.0, $pricing->calculate(100, 90));
        self::assertSame(0.0, $pricing->calculate(0, 25));
    }

    public function testDeduplicatorFallsBackToExternalId(): void {
        $product = new SupplierProduct('External-42', 'Product', 10, 20, true);

        self::assertSame('external:external-42', (new ProductDeduplicator())->key($product));
    }
}
