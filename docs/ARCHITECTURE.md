# Trendza Architecture

## 1. Application layers

### WordPress / WooCommerce

WooCommerce remains the commerce engine for products, customers, carts, checkout, orders and payments.

### Trendza Core plugin

Business capabilities belong in a custom plugin rather than the theme:

- Trend Score calculation
- Product quality scoring
- Trending/Rising/Declining states
- Product metadata and identifiers
- Supplier feed ingestion
- Price and stock synchronization
- Trendza REST endpoints
- AI shopping assistant integration points
- Scheduled jobs
- Analytics events

### Trendza theme

The theme owns presentation only:

- Homepage
- Category and collection pages
- Product pages
- Search and discovery UI
- Cart and checkout styling
- Account pages
- Responsive/mobile navigation
- Accessibility and performance optimizations

## 2. Trend Score

The first implementation should support a normalized score composed from independently measurable signals:

- sales velocity
- product view velocity
- add-to-cart velocity
- search interest growth
- social interest/velocity where data is available
- supplier availability
- review quality and volume
- margin/price competitiveness

The scoring engine must keep raw signals separate from the calculated score so weights can evolve without losing source data.

## 3. AI / GEO

Every product should expose structured information that is useful to both search engines and AI systems:

- stable product identifiers
- brand/manufacturer
- price and currency
- availability
- shipping/delivery information
- concise product summary
- use cases
- specifications
- reviews/ratings where available
- canonical URL
- image URLs and alt text
- product/category relationships

Public pages should also provide strong internal linking and useful editorial content rather than thin AI-generated pages.

## 4. Data integrity

Price, stock and supplier data should have timestamps and source information. Cached data must never silently appear current when it is stale.

## 5. Security

Secrets belong in environment configuration. External API credentials must never be stored in PHP source or committed to Git.

## 6. Performance

The storefront should minimize plugin overhead, avoid unnecessary database queries, use object/page caching where appropriate, optimize images, and progressively enhance interactive features.
