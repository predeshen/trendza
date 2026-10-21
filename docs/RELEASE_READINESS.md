# Trendza Release Readiness

Trendza should not be installed on the production store until the release-candidate checklist below is complete.

## Application

- [x] Trend scoring and status resolution
- [x] Product quality/value intelligence
- [x] Product discovery routes
- [x] AI-readable product discovery API
- [x] Product comparison and recommendations
- [x] Storefront theme
- [x] WooCommerce cart/checkout integration
- [x] Privacy-safe storefront analytics
- [x] Product and merchant structured data
- [x] Supplier CSV/XML parsing
- [x] Variable products and supplier variations
- [x] Supplier media synchronization
- [x] Supplier feed preflight and shrink protection
- [x] Supplier-specific commercial/safety policies
- [x] Supplier sync audit trail
- [x] Trendza admin intelligence dashboard

## Remaining pre-install work

- [x] Complete source audit of public REST routes, product visibility checks, recommendation rendering, and WooCommerce template entry points. Runtime/browser verification remains part of clean-install QA.
- [ ] Complete supplier adapter configuration for the actual suppliers selected for launch.
- [ ] Prepare production feed credentials outside GitHub.
- [ ] Finalize payment gateway, shipping, tax and transactional-email decisions.
- [ ] Verify legal/store policy pages and contact details for the live business.
- [x] Add automated release-candidate packaging with production Composer autoloading, installable ZIP folder structure, test exclusion, archive integrity checks, and SHA-256 checksums.
- [ ] Run the release-candidate packaging workflow and verify its artifact on GitHub.
- [ ] Perform clean WordPress + WooCommerce installation verification.
- [ ] Perform browser QA against the clean installation.
- [ ] Run a controlled supplier dry-run before importing the launch catalogue.
- [ ] Validate real product data, images, prices, stock and delivery information.
- [ ] Run checkout/payment test transactions before enabling live payments.
- [ ] Verify analytics and structured data on the installed site.
- [ ] Configure backups, caching, HTTPS and server cron.
- [ ] Complete launch smoke test.

## External inputs required before launch

These items cannot be truthfully completed in source code alone and must be supplied or verified against the actual launch environment:

- Selected supplier names, feed endpoints, credentials, licences/terms and product-data mappings.
- Payment provider account, shipping zones/rates, tax configuration, transactional email provider and sender identity.
- Legal entity/store contact details, returns/refunds, privacy, terms and delivery policy content.
- Production host/domain, HTTPS, backup destination, cache exclusions and server cron configuration.
- A clean WordPress + WooCommerce environment and test payment credentials for end-to-end browser acceptance.

No supplier secrets, payment credentials, legal business details, or production infrastructure have been invented or committed.

## Definition of complete

The first installation is considered release-ready only when the application can be installed on a clean WordPress + WooCommerce environment without manual code changes, the storefront renders correctly, WooCommerce flows work, supplier synchronization is safely configured, and production-specific credentials/settings are supplied through the deployment environment.

The first install is therefore a release-candidate validation, not a development preview.
