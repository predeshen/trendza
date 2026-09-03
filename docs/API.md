# Trendza API

Public discovery endpoints:

- `GET /wp-json/trendza/v1/products`
- `GET /wp-json/trendza/v1/products/{id}`
- `GET /wp-json/trendza/v1/discover/trending`
- `GET /wp-json/trendza/v1/discover/rising`
- `GET /wp-json/trendza/v1/discover/best-value`
- `GET /wp-json/trendza/v1/discover/quality`
- `GET /wp-json/trendza/v1/discover/recent`

All discovery endpoints accept a `limit` query parameter capped at 50. Responses expose public commerce and discovery metadata only.
