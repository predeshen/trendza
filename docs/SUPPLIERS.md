# Trendza Supplier Architecture

Trendza uses a supplier adapter boundary so CSV, XML, API and future supplier integrations can share one normalised catalogue model.

## Flow

Supplier feed -> SupplierProduct -> CatalogueSynchronizer -> deduplication -> pricing -> WooCommerce importer.

Supplier credentials and endpoints must never be committed to Git. Production integrations should use environment variables or WordPress secrets.

## Pricing

\`PricingEngine\` currently supports a configurable target margin. It does not assume a supplier-specific markup until a commercial rule is configured.

## Deduplication

SKU is preferred. If SKU is unavailable, supplier external ID is used. A future phase should add GTIN and content/image fingerprinting for cross-supplier duplicates.

## Feed safety

Every normal supplier sync performs a preflight before changing WooCommerce data.

- Empty feeds are rejected.
- Parent product identifiers must be unique within the feed.
- Variation SKUs must be unique within the feed.
- Missing product names or identifiers reject the feed.
- A successful feed establishes a supplier-specific snapshot.
- Later feeds retaining less than 50% of the previous successful product count are rejected as a safety measure against truncated or broken supplier feeds.
- The snapshot is only advanced when the complete import finishes without row-level errors.

The 50% threshold is intentionally conservative. A supplier that legitimately removes more than half its catalogue should be reviewed before running that feed against production.

## Variants

Supplier products may optionally include a \`variants\` array. Each variant should contain a stable \`external_id\` or \`sku\`, \`in_stock\`, pricing fields, and an \`attributes\` object such as \`{"Colour":"White","Size":"Large"}\`.

CSV feeds may place the variants array in a \`variants\` column as a JSON array. XML feeds may use:

\`\`\`xml
<variants>
  <variant>
    <id>variant-1</id>
    <sku>SKU-WHITE-L</sku>
    <cost>100</cost>
    <rrp>150</rrp>
    <in_stock>1</in_stock>
    <image>https://supplier.example/white-l.jpg</image>
    <attributes>
      <attribute name="Colour" value="White"/>
      <attribute name="Size" value="Large"/>
    </attributes>
  </variant>
</variants>
\`\`\`

When variants are present, Trendza imports the parent as a WooCommerce variable product and manages only the supplier-owned variations. Stale supplier-managed variations are removed on subsequent syncs; manually created variations are not identified as supplier-managed and are therefore preserved. Variant image URLs are downloaded into WooCommerce variation thumbnails and replaced when the supplier source image changes.
