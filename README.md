# Trendza

South Africa's curated trending-product marketplace.

## Vision

Trendza is not a generic dropshipping catalogue. It discovers, scores, curates and presents products that are gaining attention, with a strong focus on South African shoppers and AI-readable product data.

## Repository

This repository contains the WordPress/WooCommerce application code and supporting development infrastructure.

### Planned architecture

- `wp-content/themes/trendza` — custom lightweight WooCommerce theme
- `wp-content/plugins/trendza-core` — Trendza domain logic, trend scoring, APIs and integrations
- `docs` — architecture and product decisions
- `tests` — automated tests
- `infrastructure` — deployment and environment configuration

## Principles

1. Mobile-first and conversion-focused.
2. Machine-readable product and merchant data.
3. Accurate price, stock and delivery information.
4. Curated catalogue over catalogue bloat.
5. SEO and generative-engine discoverability built into the product architecture.
6. Security, performance and maintainability are first-class requirements.

## Development

The application targets modern WordPress and WooCommerce installations. Production secrets and environment-specific configuration must never be committed to this repository.
