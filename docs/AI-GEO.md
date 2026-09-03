# Trendza AI / GEO architecture

Trendza is designed so AI systems can discover, understand and recommend products from the public site without requiring a private integration.

## Public machine-readable layers

- Product pages expose stable product URLs, clear names, descriptions, pricing, availability and structured Product/Offer data.
- `/wp-json/trendza/v1/products` exposes a compact product catalogue representation.
- `/wp-json/trendza/v1/products/{id}` exposes a single product representation.
- `/wp-json/trendza/v1/discover/trending` and related discovery routes expose ranked collections.
- `/wp-json/trendza/v1/ai/products` supports constrained natural-language-style catalogue retrieval using filters such as query, category, price, stock, trend status and quality.

## Recommendation data

The AI endpoint returns product data plus `why_recommended`. This is an explanation derived from stored Trendza signals, not a fabricated AI claim.

Trend signals currently use real first-party events where available: product views, add-to-cart activity, purchases, reviews and availability. Search, social and market-price signals remain unpopulated until real data sources are connected.

## GEO principles

1. Use descriptive, factual product content.
2. Keep prices and stock current.
3. Give products stable canonical URLs.
4. Use internal links between categories, products and useful editorial content.
5. Publish useful buying guides and comparison content rather than large volumes of thin pages.
6. Add FAQs and specifications where they genuinely help shoppers.
7. Avoid keyword stuffing and pages generated solely to manipulate search or AI systems.
8. Keep structured data consistent with visible page content.

## Example query

```text
GET /wp-json/trendza/v1/ai/products?q=wireless+car+charger&max_price=500&in_stock=true&limit=10
```

The response is intentionally deterministic. An external AI assistant can call the endpoint, interpret the catalogue data and make its own recommendation without Trendza pretending that an LLM generated the ranking.
