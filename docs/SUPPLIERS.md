# Trendza Supplier Architecture

Trendza uses a supplier adapter boundary so CSV, XML, API and future supplier integrations can share one normalised catalogue model.

## Flow

Supplier feed -> SupplierProduct -> CatalogueSynchronizer -> deduplication -> pricing -> WooCommerce importer.

Supplier credentials and endpoints must never be committed to Git. Production integrations should use environment variables or WordPress secrets.

## Pricing

`PricingEngine` currently supports a configurable target margin. It does not assume a supplier-specific markup until a commercial rule is configured.

## Deduplication

SKU is preferred. If SKU is unavailable, supplier external ID is used. A future phase should add GTIN and content/image fingerprinting for cross-supplier duplicates.
