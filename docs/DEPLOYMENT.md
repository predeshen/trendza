# Trendza Production Deployment

Trendza is designed to be added to an existing WordPress + WooCommerce installation. GitHub is the source of truth for application code; the production WordPress database, uploads and site configuration remain on the server.

## What gets installed

Copy these repository paths into the matching WordPress paths:

- `wp-content/plugins/trendza-core/` -> `<wordpress>/wp-content/plugins/trendza-core/`
- `wp-content/themes/trendza/` -> `<wordpress>/wp-content/themes/trendza/`

Do **not** replace WordPress core, `wp-config.php`, `wp-content/uploads/`, the production database, or unrelated plugins/themes.

## Before the first deployment

1. Take a full database backup.
2. Back up `wp-content/uploads/`.
3. Verify WordPress, WooCommerce and PHP meet the requirements.
4. If the store is live, deploy during a low-traffic period and have a rollback copy ready.
5. Keep supplier API keys, credentials and private feed URLs out of Git.

## Deployment

A server can clone the repository outside the public web root and sync only the required directories:

```bash
git clone https://github.com/predeshen/trendza.git /opt/trendza
rsync -a /opt/trendza/wp-content/plugins/trendza-core/ /var/www/html/wp-content/plugins/trendza-core/
rsync -a /opt/trendza/wp-content/themes/trendza/ /var/www/html/wp-content/themes/trendza/
```

Adjust paths for the actual server.

## Activate

From the WordPress root:

```bash
wp plugin activate woocommerce
wp plugin activate trendza-core
wp theme activate trendza
wp rewrite flush
```

The same activation can be performed from WordPress Admin > Plugins and Appearance > Themes.

## First-run smoke test

Confirm all of the following before importing a supplier catalogue:

- WordPress Admin loads normally.
- WooCommerce is active.
- Shop, product, cart, checkout and My Account work.
- Trendza REST API responds at `/wp-json/trendza/v1/products`.
- A published product is returned by discovery endpoints.
- Product metadata fields save correctly.
- Product pages expose the intended structured data without duplicate/conflicting markup.
- Product view events can be recorded.
- Add-to-cart and purchase events are recorded.
- Trend recalculation completes without PHP errors.
- Scheduled events are present.

## Supplier rollout

Start with **one supplier and a small feed**. Validate SKU/external-ID matching, categories, prices, stock, descriptions and images before enabling bulk synchronization.

Do not enable automatic price/stock updates until the mapping has been tested against real supplier data.

## Production cron

For reliable scheduled processing, use a real server cron to run due WP-Cron jobs rather than relying solely on site visitors:

```bash
*/5 * * * * cd /var/www/html && wp cron event run --due-now --quiet
```

Adjust the WordPress root, PHP/WP-CLI user and schedule to the actual server.

## Updating Trendza

1. Back up production.
2. Pull the reviewed GitHub revision.
3. Sync only the Trendza plugin/theme directories.
4. Run smoke tests.
5. Monitor PHP/web-server logs and checkout.
6. Enable supplier synchronization only after the release is verified.

## Rollback

Keep the previous Trendza revision available. If a release fails, deactivate Trendza Core and/or restore the previous Trendza plugin/theme directories, then flush rewrites. Restore the database only when a database change actually needs to be reversed.

## Security

Never commit passwords, API keys, supplier credentials, customer data, payment secrets or production database dumps. Use server environment variables or a protected secrets/configuration mechanism.
