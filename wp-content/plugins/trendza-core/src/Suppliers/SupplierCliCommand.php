<?php
namespace Trendza\Suppliers;

final class SupplierCliCommand {
    public static function register(): void {
        if (!defined('WP_CLI') || !WP_CLI) return;
        \WP_CLI::add_command('trendza supplier-sync', [self::class, 'sync']);
    }

    /**
     * Sync a remote CSV/XML feed.
     *
     * ## OPTIONS
     * <code> Supplier code.
     * <url> Feed URL.
     * [--format=<csv|xml>] Feed format. Defaults to csv.
     * [--margin=<percent>] Target gross margin. Defaults to 25.
     * [--dry-run] Validate and normalize without writing products.
     *
     * @when after_wp_load
     */
    public static function sync(array $args, array $assocArgs): void {
        [$code, $url] = $args;
        $format = strtolower((string) ($assocArgs['format'] ?? 'csv'));
        $parser = $format === 'xml' ? new XmlFeedParser() : new CsvFeedParser();
        $supplier = new RemoteFeedSupplier($code, $url, $parser);
        $normalizer = new CatalogueSynchronizer(new PricingEngine(), new ProductDeduplicator());
        if (isset($assocArgs['dry-run'])) {
            $seen = 0;
            foreach ($supplier->fetch() as $item) { $normalizer->normalise($item, (float)($assocArgs['margin'] ?? 25)); $seen++; }
            \WP_CLI::success("Dry run completed: {$seen} products validated.");
            return;
        }
        $service = new SupplierSyncService($normalizer, new WooCommerceProductImporter());
        $result = $service->sync($supplier, (float)($assocArgs['margin'] ?? 25));
        \WP_CLI::success(sprintf('Sync complete: %d seen, %d created, %d updated, %d skipped, %d errors.', $result->seen, $result->created, $result->updated, $result->skipped, count($result->errors)));
        foreach ($result->errors as $error) \WP_CLI::warning($error['external_id'] . ': ' . $error['message']);
    }
}
