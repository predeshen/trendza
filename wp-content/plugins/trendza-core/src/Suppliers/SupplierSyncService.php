<?php
namespace Trendza\Suppliers;

final class SupplierSyncService {
    private const SNAPSHOT_PREFIX = 'trendza_supplier_feed_';

    public function __construct(private CatalogueSynchronizer $normalizer, private WooCommerceProductImporter $importer) {}

    public function sync(SupplierInterface $supplier, float $marginPercent = 25.0, bool $updatePrice = true, bool $updateStock = true): SyncResult {
        if ($marginPercent < 0 || $marginPercent >= 90) {
            throw new \InvalidArgumentException('Margin must be at least 0 and below 90 percent.');
        }

        $items = [];
        foreach ($supplier->fetch() as $item) {
            $items[] = $item;
        }

        if (!$items) {
            throw new \RuntimeException('Supplier feed returned no products; import aborted to protect the existing catalogue.');
        }

        $preflightErrors = $this->preflight($items, $marginPercent);
        if ($preflightErrors) {
            throw new \RuntimeException(
                'Supplier feed preflight failed: ' . implode(' ', array_slice($preflightErrors, 0, 10))
                . (count($preflightErrors) > 10 ? ' Additional errors were found.' : '')
            );
        }

        $this->assertFeedChangeIsSafe($supplierCode, $items, $effective, $forceShrink);

        $result = new SyncResult();
        foreach ($items as $item) {
            $result->seen++;
            try {
                $data = $this->normalizer->normalise($item, $effective->marginPercent);
                $existing = $this->findExisting($supplierCode, $data['external_id'], $data['sku']);
                $this->importer->import($data, $supplierCode, $effective->updatePrice, $effective->updateStock);
                if ($existing) $result->updated++; else $result->created++;
            } catch (\Throwable $e) {
                $result->error($item instanceof SupplierProduct ? $item->externalId : '', $e->getMessage());
            }
        }

        if (!$result->errors) {
            $this->saveSnapshot($supplierCode, $items);
        }

        return $result;
    }

    private function preflight(array $items, float $marginPercent): array {
        $errors = [];
        $parentKeys = [];
        $variantSkus = [];

        foreach ($items as $index => $item) {
            $row = $index + 1;

            if (!$item instanceof SupplierProduct) {
                $errors[] = sprintf('Row %d is not a SupplierProduct.', $row);
                continue;
            }

            try {
                $data = $this->normalizer->normalise($item, $marginPercent);
            } catch (\Throwable $e) {
                $errors[] = sprintf('Row %d (%s): %s', $row, $item->externalId, $e->getMessage());
                continue;
            }

            if ($data['name'] === '') {
                $errors[] = sprintf('Row %d (%s): missing product name.', $row, $item->externalId);
            }
            if ($data['dedupe_key'] === '') {
                $errors[] = sprintf('Row %d (%s): missing external_id and SKU.', $row, $item->externalId);
            }

            $parentKey = (string) $data['dedupe_key'];
            if ($parentKey !== '') {
                if (isset($parentKeys[$parentKey])) {
                    $errors[] = sprintf(
                        'Duplicate product identifier "%s" at rows %d and %d.',
                        $parentKey,
                        $parentKeys[$parentKey] + 1,
                        $row
                    );
                } else {
                    $parentKeys[$parentKey] = $index;
                }
            }

            foreach ($data['variants'] as $variant) {
                $sku = (string) ($variant['sku'] ?? '');
                if ($sku === '') continue;

                if (isset($variantSkus[$sku])) {
                    $errors[] = sprintf(
                        'Duplicate variant SKU "%s" at rows %d and %d.',
                        $sku,
                        $variantSkus[$sku] + 1,
                        $row
                    );
                } else {
                    $variantSkus[$sku] = $index;
                }
            }
        }

        return $errors;
    }

    private function assertFeedChangeIsSafe(string $supplierCode, array $items, SupplierConfig $config, bool $forceShrink): void {
        $key = self::SNAPSHOT_PREFIX . sanitize_key($supplierCode);
        $previous = get_option($key, null);

        if (!is_array($previous) || empty($previous['count'])) return;

        $previousCount = max(1, (int) $previous['count']);
        $currentCount = count($items);
        $retention = $currentCount / $previousCount;

        if (!$forceShrink && $retention < $config->minimumFeedRetention) {
            throw new \RuntimeException(sprintf(
                'Supplier feed contains %d products versus %d in the previous successful feed (%.1f%% retained). Import aborted because the catalogue shrank below the %.0f%% safety threshold.',
                $currentCount,
                $previousCount,
                $retention * 100,
                $config->minimumFeedRetention * 100
            ));
        }
    }

    private function saveSnapshot(string $supplierCode, array $items): void {
        $keys = [];

        foreach ($items as $item) {
            if (!$item instanceof SupplierProduct) continue;
            $key = (new ProductDeduplicator())->key($item);
            if ($key !== '') $keys[] = $key;
        }

        sort($keys, SORT_STRING);

        update_option(
            self::SNAPSHOT_PREFIX . sanitize_key($supplierCode),
            [
                'count' => count($items),
                'fingerprint' => hash('sha256', implode("\n", $keys)),
                'updated_at' => current_time('mysql', true),
            ],
            false
        );
    }

    private function findExisting(string $supplierCode, string $externalId, string $sku): int {
        if ($sku !== '' && function_exists('wc_get_product_id_by_sku')) {
            $id = (int) wc_get_product_id_by_sku($sku);
            if ($id) return $id;
        }

        if ($externalId === '') return 0;

        $ids = get_posts([
            'post_type' => 'product',
            'post_status' => 'any',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => '_trendza_external_id', 'value' => $externalId],
                ['key' => '_trendza_supplier_code', 'value' => $supplierCode],
            ],
        ]);

        return (int) ($ids[0] ?? 0);
    }
}
