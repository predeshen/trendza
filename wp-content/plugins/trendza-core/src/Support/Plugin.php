<?php
namespace Trendza\Support;

use Trendza\Admin\ProductFields;
use Trendza\AI\ProductDiscoveryController;
use Trendza\Analytics\AnalyticsService;
use Trendza\API\AnalyticsController;
use Trendza\API\RestController;
use Trendza\Products\ProductRepository;
use Trendza\SEO\SchemaService;
use Trendza\Trend\TrendService;

final class Plugin {
    public static function boot(): void {
        ProductFields::register();
        RestController::register();
        AnalyticsController::register();
        ProductDiscoveryController::register();
        AnalyticsService::register();
        SchemaService::register();
        add_action('init', [TrendService::class, 'registerSchedule']);
        add_action('trendza_recalculate_trends', [TrendService::class, 'recalculatePublishedProducts']);
        add_action('save_post_product', [TrendService::class, 'refreshProductQuality'], 20, 2);
    }

    public static function discovery(int $limit = 8, string $mode = 'trending'): array {
        return (new ProductRepository())->discover($limit, $mode);
    }
}
