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
     * [--margin=<percent>] Override the supplier's configured target gross margin.
     * [--max-products=<count>] Override the supplier's configured product safety limit.
     * [--force-shrink] Allow a feed below the configured retention threshold.
     * [--dry-run] Validate and normalize without writing products.
     *
     * @when after_wp_load
     */
    public static function sync(array $args, array $assocArgs): void {
        [$code, $url] = $args;
        $format = strtolower((string) ($assocArgs['format'] ?? 'csv'));
        if (!in_array($format, ['csv', 'xml'], true)) {
            \WP_CLI::error('Invalid format. Use csv or xml.');
        }

        $config = SupplierConfigRegistry::resolve($code);
        $margin = isset($assocArgs['margin']) ? (float) $assocArgs['margin'] : $config->marginPercent;
        if ($margin < 0 || $margin >= 90) {
            \WP_CLI::error('Margin must be at least 0 and below 90 percent.');
        }

        $maxProducts = isset($assocArgs['max-products']) ? (int) $assocArgs['max-products'] : $config->maxProducts;
        if ($maxProducts < 1) {
            \WP_CLI::error('Maximum products must be at least 1.');
        }
        $config = $config->withOverrides($margin, $maxProducts);

        $parser = $format === 'xml' ? new XmlFeedParser() : new CsvFeedParser();
        $supplier = new RemoteFeedSupplier($code, $url, $parser);
        $normalizer = new CatalogueSynchronizer(new PricingEngine(), new ProductDeduplicator());

        if (isset($assocArgs['dry-run'])) {
            $seen = 0;
            $valid = 0;
            $skipped = 0;
            $errors = [];

            foreach ($supplier->fetch() as $item) {
                $seen++;
                try {
                    if ($seen > $config->maxProducts) {
                        throw new \RuntimeException(sprintf('Feed exceeds the %d-product safety limit.', $config->maxProducts));
                    }
                    if (!$item instanceof SupplierProduct) {
                        throw new \InvalidArgumentException('Supplier returned an invalid product.');
                    }

                    $data = $normalizer->normalise($item, $config->marginPercent);
                    if ($data['name'] === '' || $data['dedupe_key'] === '') {
                        $skipped++;
                        $errors[] = [
                            'external_id' => $item->externalId,
                            'message' => 'Missing product name or external_id/SKU.',
                        ];
                        continue;
                    }
                    $valid++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'external_id' => $item instanceof SupplierProduct ? $item->externalId : '',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            \WP_CLI::success(sprintf(
                'Dry run complete: %d seen, %d valid, %d skipped, %d errors. No products were written.',
                $seen, $valid, $skipped, count($errors)
            ));
            foreach ($errors as $error) {
                \WP_CLI::warning(($error['external_id'] !== '' ? $error['external_id'] . ': ' : '') . $error['message']);
            }
            return;
        }

        $service = new SupplierSyncService($normalizer, new WooCommerceProductImporter());
        $result = $service->sync(
            $supplier,
            $config->marginPercent,
            $config->updatePrice,
            $config->updateStock,
            $config,
            isset($assocArgs['force-shrink'])
        );

        \WP_CLI::success(sprintf(
            'Sync complete: %d seen, %d created, %d updated, %d skipped, %d errors.',
            $result->seen, $result->created, $result->updated, $result->skipped, count($result->errors)
        ));
        foreach ($result->errors as $error) {
            \WP_CLI::warning($error['external_id'] . ': ' . $error['message']);
        }
    }
}
