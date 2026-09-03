# Trendza Fresh WordPress Deployment

Trendza is intended for a clean WordPress + WooCommerce installation. WordPress core and WooCommerce are installed normally; this repository supplies the Trendza Core plugin and complete Trendza storefront theme.

## Fresh installation

1. Provision a clean WordPress installation with HTTPS enabled.
2. Install and activate WooCommerce.
3. Create/configure the WooCommerce Shop, Cart, Checkout and My Account pages.
4. Copy `wp-content/plugins/trendza-core/` into the site's `wp-content/plugins/` directory.
5. Copy `wp-content/themes/trendza/` into the site's `wp-content/themes/` directory.
6. Activate Trendza Core.
7. Activate the Trendza theme.
8. Configure menus, logo/site identity and WooCommerce payment/shipping settings.
9. Save Settings > Permalinks once to refresh rewrite rules.
10. Add the initial curated product catalogue.
11. Verify product discovery, checkout, analytics and structured data before opening the store publicly.

## Server layout

```text
/var/www/html/
├── wp-admin/
├── wp-includes/
├── wp-content/
│   ├── plugins/
│   │   └── trendza-core/
│   ├── themes/
│   │   └── trendza/
│   └── uploads/
├── wp-config.php
└── ...
```

## WP-CLI

From the WordPress root:

```bash
wp plugin install woocommerce --activate
wp plugin activate trendza-core
wp theme activate trendza
wp rewrite flush
```

## Supplier rollout

Do not connect supplier feeds until the storefront and checkout have been tested. Configure credentials through protected server configuration/secrets, never Git. Start with one supplier and a small test catalogue, validate prices/stock/categories/images, then enable scheduled synchronization.

## Cron

For reliable production scheduling, use a real server cron to run due WP-Cron events:

```bash
*/5 * * * * cd /var/www/html && wp cron event run --due-now --quiet
```

Adjust the path and execution user to match the server.

## Production acceptance checks

- Homepage renders correctly on mobile and desktop.
- Navigation and search work.
- Category and product pages render correctly.
- Cart and checkout complete successfully.
- Payment gateway is in live mode only after test transactions pass.
- Product view/add-to-cart/purchase analytics are recorded without collecting unnecessary personal data.
- Trend scores recalculate from real signals; no artificial trend data is created.
- Supplier synchronization does not create duplicate products.
- Product schema and canonical metadata are valid.
- HTTPS, backups, firewall and WordPress security controls are enabled.
- Caching/CDN is configured only after dynamic WooCommerce pages are excluded appropriately.

## Rollback

Keep the previous Trendza plugin/theme release available. If an application release fails, restore the previous Trendza directories and flush rewrites. Take a database backup before major catalogue synchronization or schema changes.
