<?php
namespace Trendza\Suppliers;

final class SupplierSyncService {
    public function __construct(private CatalogueSynchronizer $normalizer, private WooCommerceProductImporter $importer) {}

    public function sync(SupplierInterface $supplier, float $marginPercent = 25.0, bool $updatePrice = true, bool $updateStock = true): SyncResult {
        $result = new SyncResult();
        foreach ($supplier->fetch() as $item) {
            $result->seen++;
            try {
                if (!$item instanceof SupplierProduct) throw new \InvalidArgumentException('Supplier returned an invalid product.');
                $data = $this->normalizer->normalise($item, $marginPercent);
                if ($data['name'] === '' || $data['dedupe_key'] === '') { $result->skipped++; continue; }
                $existing = $this->findExisting($supplier->getCode(), $data['external_id'], $data['sku']);
                $this->importer->import($data, $supplier->getCode(), $updatePrice, $updateStock);
                if ($existing) $result->updated++; else $result->created++;
            } catch (\Throwable $e) {
                $result->error($item instanceof SupplierProduct ? $item->externalId : '', $e->getMessage());
            }
        }
        return $result;
    }

    private function findExisting(string $supplierCode, string $externalId, string $sku): int {
        if ($sku !== '' && function_exists('wc_get_product_id_by_sku')) {
            $id = (int) wc_get_product_id_by_sku($sku);
            if ($id) return $id;
        }
        if ($externalId === '') return 0;
        $ids = get_posts(['post_type'=>'product','post_status'=>'any','numberposts'=>1,'fields'=>'ids','meta_query'=>[
            ['key'=>'_trendza_external_id','value'=>$externalId],
            ['key'=>'_trendza_supplier_code','value'=>$supplierCode],
        ]]);
        return (int) ($ids[0] ?? 0);
    }
}
