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

- [ ] Complete release audit of all public routes and WooCommerce templates.
- [ ] Complete supplier adapter configuration for the actual suppliers selected for launch.
- [ ] Prepare production feed credentials outside GitHub.
- [ ] Finalize payment gateway, shipping, tax and transactional-email decisions.
- [ ] Verify legal/store policy pages and contact details for the live business.
- [ ] Perform production packaging and clean-install verification.
- [ ] Perform browser QA against the clean installation.
- [ ] Run a controlled supplier dry-run before importing the launch catalogue.
- [ ] Validate real product data, images, prices, stock and delivery information.
- [ ] Run checkout/payment test transactions before enabling live payments.
- [ ] Verify analytics and structured data on the installed site.
- [ ] Configure backups, caching, HTTPS and server cron.
- [ ] Complete launch smoke test.

## Definition of complete

The first installation is considered release-ready only when the application can be installed on a clean WordPress + WooCommerce environment without manual code changes, the storefront renders correctly, WooCommerce flows work, supplier synchronization is safely configured, and production-specific credentials/settings are supplied through the deployment environment.

The first install is therefore a release-candidate validation, not a development preview.
