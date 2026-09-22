# Trendza Supplier Architecture

Trendza uses a supplier adapter boundary so CSV, XML, API and future supplier integrations can share one normalised catalogue model.

## Flow

Supplier feed -> SupplierProduct -> CatalogueSynchronizer -> deduplication -> pricing -> WooCommerce importer.

Supplier credentials and endpoints must never be committed to Git. Production integrations should use environment variables or WordPress secrets.

## Pricing

`PricingEngine` currently supports a configurable target margin. It does not assume a supplier-specific markup until a commercial rule is configured.

## Deduplication

SKU is preferred. If SKU is unavailable, supplier external ID is used. A future phase should add GTIN and content/image fingerprinting for cross-supplier duplicates.


## Variants

Supplier products may optionally include a `variants` array. Each variant should contain a stable `external_id` or `sku`, `in_stock`, pricing fields, and an `attributes` object such as `{"Colour":"White","Size":"Large"}`.

CSV feeds may place the variants array in a `variants` column as a JSON array. XML feeds may use:

```xml
<variants>
  <variant>
    <id>variant-1</id>
    <sku>SKU-WHITE-L</sku>
    <cost>100</cost>
    <rrp>150</rrp>
    <in_stock>1</in_stock>
    <attributes>
      <attribute name="Colour" value="White"/>
      <attribute name="Size" value="Large"/>
    </attributes>
  </variant>
</variants>
```

When variants are present, Trendza imports the parent as a WooCommerce variable product and manages only the supplier-owned variations. Stale supplier-managed variations are removed on subsequent syncs; manually created variations are not identified as supplier-managed and are therefore preserved.
