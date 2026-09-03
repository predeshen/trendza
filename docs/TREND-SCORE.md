# Trend Score

Trendza uses a normalized 0–100 score. Initial signal weights are designed for a production-ready model but should only be populated from observed data.

| Signal | Weight |
|---|---:|
| Sales velocity | 30% |
| View velocity | 10% |
| Add-to-cart velocity | 15% |
| Search growth | 15% |
| Social velocity | 10% |
| Review quality | 5% |
| Availability | 5% |
| Price competitiveness | 10% |

Statuses: **Trending** >=75; **Rising** 55–74.99 with positive momentum; **Stable** 35–54.99; **Declining** below 35 or materially negative momentum.

No score should be presented as evidence of popularity until its underlying signal data is populated from measurable events or trusted external feeds.
