# Trendza Core

Core domain layer for Trendza's WooCommerce intelligence platform.

## Current capabilities
- Product intelligence metadata and WooCommerce admin fields
- Product quality scoring
- Trend score/status persistence foundation
- Discovery ranking for trending, rising, quality, best value and recent products
- Public REST API under `/wp-json/trendza/v1/`
- Twice-daily score/quality refresh job

Trend signals are intentionally separated from aggregate scores so future supplier, analytics and social integrations can add real evidence without fabricating demand.
