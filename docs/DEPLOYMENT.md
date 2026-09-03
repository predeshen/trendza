# Trendza deployment

Trendza is designed to be added to an existing WordPress + WooCommerce installation. The repository does not replace WordPress core or the site's uploads/database.

## What gets installed

Copy these repository paths into the matching WordPress paths:

- `wp-content/plugins/trendza-core/` -> `<wordpress>/wp-content/plugins/trendza-core/`
- `wp-content/themes/trendza/` -> `<wordpress>/wp-content/themes/trendza/`

Do not copy the repository `.git` directory into the public web root unless you intentionally want the working tree there.

## Recommended first deployment

1. Take a database and `wp-content/uploads` backup.
2. Verify WordPress, WooCommerce and PHP meet the versions in the plugin/theme requirements.
3. Upload or clone this repository outside the public web root.
4. Copy/sync the plugin and theme directories into `wp-content`.
5. Activate **Trendza Core** after WooCommerce is active.
6. Activate the **Trendza** theme from Appearance > Themes.
7. Visit Settings > Permalinks and save once to flush rewrite rules.
8. Confirm WooCommerce Shop, Cart, Checkout and My Account pages are configured.
9. Confirm the Trendza REST API responds at `/wp-json/trendza/v1/products`.
10. Confirm a product page outputs Product/Offer structured data and that product view events are being recorded.

## WP-CLI example

From the WordPress root:

```bash
wp plugin activate woocommerce
wp plugin activate trendza-core
wp theme activate trendza
wp rewrite flush
```

## Updating an existing installation

Deploy the new repository revision over the two Trendza directories. Do not overwrite the entire WordPress installation. Keep database, uploads, `wp-config.php`, and other site-specific plugins/themes intact.

After deployment:

```bash
wp plugin status trendza-core
wp theme status trendza
wp rewrite flush
```

## Supplier credentials

Supplier API keys, feed URLs, usernames and passwords must be supplied through server environment/secrets or a protected configuration layer. Never commit them to Git.

Supplier synchronization should initially run in dry-run/staging mode. Enable price and stock updates only after validating the supplier mapping and pricing rules.

## Production cron

Trendza uses WordPress scheduling for trend recalculation and event retention. For reliable production execution, configure a real server cron to call WP-Cron rather than relying on visitor traffic:

```bash
*/5 * * * * cd /var/www/html && wp cron event run --due-now --quiet
```

Adjust the WordPress root and user for the server.

## Rollback

Keep the previous Trendza revision available. If a deployment fails, restore the previous plugin/theme directories and flush rewrites. Database backups should be taken before migrations or major catalogue synchronization changes.
