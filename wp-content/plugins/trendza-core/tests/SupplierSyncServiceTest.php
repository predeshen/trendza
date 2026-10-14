<?php
use PHPUnit\\Framework\\TestCase;
use Trendza\\Suppliers\\CatalogueSynchronizer;
use Trendza\\Suppliers\\PricingEngine;
use Trendza\\Suppliers\\ProductDeduplicator;
use Trendza\\Suppliers\\SupplierConfig;
use Trendza\\Suppliers\\SupplierProduct;
use Trendza\\Suppliers\\SupplierSyncService;
use Trendza\\Suppliers\\WooCommerceProductImporter;

final class SupplierSyncServiceTest extends TestCase {
    private function service(): SupplierSyncService {
        return new SupplierSyncService(
            new CatalogueSynchronizer(new PricingEngine(), new ProductDeduplicator()),
            new WooCommerceProductImporter()
        );
    }

    public function testDryRunValidationRejectsFeedAboveConfiguredLimit(): void {
        $items = [
            new SupplierProduct('P-1', 'Product one', 10, 20, true, 'SKU-1'),
            new SupplierProduct('P-2', 'Product two', 10, 20, true, 'SKU-2'),
        ];

        $errors = $this->service()->validateFeed($items, 25, new SupplierConfig('supplier-a', 25, 0.5, 1));

        self::assertCount(1, $errors);
        self::assertStringContainsString('exceeding the configured limit of 1', $errors[0]);
    }

    public function testDryRunValidationRejectsEmptyFeedByDefault(): void {
        $errors = $this->service()->validateFeed([], 25, new SupplierConfig('supplier-a'));

        self::assertSame(['Supplier feed returned no products.'], $errors);
    }
}
