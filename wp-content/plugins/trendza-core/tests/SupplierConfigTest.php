<?php
use PHPUnit\Framework\TestCase;
use Trendza\Suppliers\SupplierConfig;
use Trendza\Suppliers\SupplierConfigRegistry;

final class SupplierConfigTest extends TestCase {
    public function testDefaultsAreSafeForCuratedCatalogue(): void {
        $config = new SupplierConfig('mustek');

        self::assertSame('mustek', $config->code);
        self::assertSame(25.0, $config->marginPercent);
        self::assertSame(0.50, $config->minimumFeedRetention);
        self::assertSame(300, $config->maxProducts);
        self::assertTrue($config->updatePrice);
        self::assertTrue($config->updateStock);
        self::assertFalse($config->allowEmptyFeed);
    }

    public function testOverridesCreateAnIndependentConfig(): void {
        $config = new SupplierConfig('supplier-a', 20.0, 0.70, 150, false, true);
        $override = $config->withOverrides(22.5, 100, true, false);

        self::assertSame(20.0, $config->marginPercent);
        self::assertSame(150, $config->maxProducts);
        self::assertSame(22.5, $override->marginPercent);
        self::assertSame(100, $override->maxProducts);
        self::assertTrue($override->updatePrice);
        self::assertFalse($override->updateStock);
        self::assertSame(0.70, $override->minimumFeedRetention);
    }

    public function testRegistryNormalizesSupplierCodes(): void {
        $config = SupplierConfigRegistry::resolve(' Mustek South Africa ');
        self::assertSame('mustek-south-africa', $config->code);
    }

    public function testInvalidSafetyValuesAreRejected(): void {
        $this->expectException(\InvalidArgumentException::class);
        new SupplierConfig('supplier-a', 25.0, 1.1);
    }
}
