# Trendza analytics

Trendza stores first-party behavioural events in a dedicated database table rather than mixing raw events into post meta.

## Events

- `view` — product discovery/view
- `search` — product appeared in an internal search result
- `add_to_cart` — WooCommerce add-to-cart
- `begin_checkout` — reserved for checkout instrumentation
- `purchase` — order line item purchase

No raw customer identity is stored. The event store keeps a one-way session/client hash and optional non-sensitive metadata.

## Trend scoring

Trend scores are calculated from signals that have real data. Missing external signals are not filled with invented values. Current first-party signals include sales velocity, view velocity, add-to-cart velocity, review quality and stock availability.

Future integrations can add search-growth, social velocity and market-price competitiveness once trustworthy sources are connected.

## Retention

Raw events are retained for 90 days and pruned by the daily scheduled task.

## Production

Use a real server cron to run due WP-Cron events so trend recalculation and retention do not depend on site traffic.
