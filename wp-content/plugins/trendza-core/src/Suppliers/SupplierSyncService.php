<?php
namespace Trendza\Suppliers;

final class SupplierSyncService {
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

        $result = new SyncResult();
        foreach ($items as $item) {
            $result->seen++;
            try {
                $data = $this->normalizer->normalise($item, $marginPercent);
                $existing = $this->findExisting($supplier->getCode(), $data['external_id'], $data['sku']);
                $this->importer->import($data, $supplier->getCode(), $updatePrice, $updateStock);
                if ($existing) $result->updated++; else $result->created++;
            } catch (\Throwable $e) {
                $result->error($item instanceof SupplierProduct ? $item->externalId : '', $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Validate the complete feed before changing WooCommerce data.
     *
     * The importer intentionally fails closed for an empty or structurally
     * invalid feed. Duplicate parent/variant identifiers are especially
     * dangerous because they can make one supplier row overwrite another.
     */
    private function preflight(array $items, float $marginPercent): array {
        $errors = [];
        $parentKeys = [];
        $variantExternalIds = [];
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
                $externalId = (string) ($variant['external_id'] ?? '');
                $sku = (string) ($variant['sku'] ?? '');

                if ($externalId !== '') {
                    if (isset($variantExternalIds[$externalId])) {
                        $errors[] = sprintf(
                            'Duplicate variant external_id "%s" at rows %d and %d.',
                            $externalId,
                            $variantExternalIds[$externalId] + 1,
                            $row
                        );
                    } else {
                        $variantExternalIds[$externalId] = $index;
                    }
                }

                if ($sku !== '') {
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
        }

        return $errors;
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
