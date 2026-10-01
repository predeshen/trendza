<?php
namespace Trendza\Support;

use Trendza\Admin\AdminDashboard;
use Trendza\Admin\ProductFields;
use Trendza\AI\ProductDiscoveryController;
use Trendza\Analytics\AnalyticsService;
use Trendza\API\AnalyticsController;
use Trendza\API\RestController;
use Trendza\Products\ProductRepository;
use Trendza\SEO\SchemaService;
use Trendza\Suppliers\SupplierCliCommand;
use Trendza\Trend\TrendService;

final class Plugin {
    public static function boot(): void {
        // Keep installation resilient when WooCommerce is activated after Trendza.
        // EventStore::install() is idempotent through WordPress dbDelta(), so this
        // also repairs a missing analytics table without requiring reactivation.
        \Trendza\Analytics\EventStore::install();

        ProductFields::register();
        RestController::register();
        AnalyticsController::register();
        ProductDiscoveryController::register();
        AnalyticsService::register();
        SchemaService::register();
        SupplierCliCommand::register();
        AdminDashboard::register();
        add_action('init', [TrendService::class, 'registerSchedule']);
        add_action('trendza_recalculate_trends', [TrendService::class, 'recalculatePublishedProducts']);
        add_action('save_post_product', [TrendService::class, 'refreshProductQuality'], 20, 2);
    }
    public static function discovery(int $limit = 8, string $mode = 'trending'): array { return (new ProductRepository())->discover($limit, $mode); }
}
