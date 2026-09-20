<?php
namespace Trendza\SEO;

final class SchemaService {
    public static function register(): void {
        // Let established SEO plugins own their graph. Trendza only supplies
        // product-specific schema when no compatible SEO graph is active.
        add_filter('woocommerce_structured_data_enabled', [self::class, 'woocommerceStructuredDataEnabled']);
        add_action('wp_head', [self::class, 'output'], 20);
    }

    public static function woocommerceStructuredDataEnabled(bool $enabled): bool {
        return self::seoPluginActive() ? $enabled : false;
    }

    public static function output(): void {
        if (is_admin() || self::seoPluginActive()) {
            return;
        }

        $graphs = [
            [
                '@type' => 'Organization',
                '@id' => home_url('/#organization'),
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'logo' => self::siteLogo(),
            ],
            [
                '@type' => 'WebSite',
                '@id' => home_url('/#website'),
                'url' => home_url('/'),
                'name' => get_bloginfo('name'),
                'publisher' => ['@id' => home_url('/#organization')],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => home_url('/?s={search_term_string}&post_type=product'),
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];

        if (function_exists('is_product') && is_product()) {
            $product = wc_get_product(get_the_ID());
            if ($product) {
                $graphs[] = self::product($product);
                $graphs[] = self::breadcrumbs($product);
            }
        }

        $graphs = array_values(array_filter(array_map([self::class, 'clean'], $graphs)));
        if (!$graphs) {
            return;
        }

        echo '<script type="application/ld+json">' . wp_json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graphs],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . '</script>' . PHP_EOL;
    }

    private static function seoPluginActive(): bool {
        return defined('WPSEO_VERSION')
            || class_exists('RankMath')
            || defined('RANK_MATH_VERSION');
    }

    private static function product($product): array {
        $description = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());
        $image = $product->get_image_id() ? wp_get_attachment_image_url($product->get_image_id(), 'full') : null;
        $schema = [
            '@type' => 'Product',
            '@id' => $product->get_permalink() . '#product',
            'name' => $product->get_name(),
            'url' => $product->get_permalink(),
            'description' => $description,
            'sku' => $product->get_sku() ?: null,
            'image' => $image ? [$image] : null,
            'brand' => self::brand($product),
            'offers' => self::offers($product),
        ];

        if ($product->get_rating_count() > 0 && $product->get_average_rating() !== '') {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $product->get_average_rating(),
                'reviewCount' => (int) $product->get_review_count(),
                'ratingCount' => (int) $product->get_rating_count(),
            ];
        }

        return self::clean($schema);
    }

    private static function offers($product): array {
        $currency = get_woocommerce_currency();
        $base = [
            'url' => $product->get_permalink(),
            'priceCurrency' => $currency,
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@id' => home_url('/#organization')],
        ];

        if ($product->is_type('variable')) {
            $prices = $product->get_variation_prices(true);
            $values = isset($prices['price']) ? array_filter(array_map('floatval', $prices['price']), static fn(float $price): bool => $price > 0) : [];
            if ($values) {
                return self::clean([
                    '@type' => 'AggregateOffer',
                    ...$base,
                    'lowPrice' => (string) min($values),
                    'highPrice' => (string) max($values),
                    'offerCount' => count($values),
                ]);
            }
        }

        return self::clean([
            '@type' => 'Offer',
            ...$base,
            'price' => $product->get_price() !== '' ? (string) $product->get_price() : null,
        ]);
    }

    private static function brand($product): ?array {
        $brand = get_post_meta($product->get_id(), '_trendza_brand', true);
        if (!$brand) $brand = $product->get_attribute('pa_brand') ?: $product->get_attribute('brand');
        return $brand ? ['@type' => 'Brand', 'name' => wp_strip_all_tags((string) $brand)] : null;
    }

    private static function siteLogo(): ?string {
        $logo_id = (int) get_theme_mod('custom_logo');
        if (!$logo_id) return null;
        $logo = wp_get_attachment_image_url($logo_id, 'full');
        return $logo ?: null;
    }

    private static function breadcrumbs($product): array {
        $items = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => get_bloginfo('name'),
            'item' => home_url('/'),
        ]];

        $terms = get_the_terms($product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $term = self::deepestCategory($terms);
            if ($term) {
                $link = get_term_link($term);
                if (!is_wp_error($link)) {
                    $items[] = [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $term->name,
                        'item' => $link,
                    ];
                }
            }
        }

        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $product->get_name(),
            'item' => $product->get_permalink(),
        ];

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private static function deepestCategory(array $terms): ?\WP_Term {
        $best = null;
        $bestDepth = -1;

        foreach ($terms as $term) {
            $depth = 0;
            $parent = (int) $term->parent;
            $guard = 0;

            while ($parent > 0 && $guard++ < 50) {
                $ancestor = get_term($parent, 'product_cat');
                if (!$ancestor || is_wp_error($ancestor)) {
                    break;
                }
                $depth++;
                $parent = (int) $ancestor->parent;
            }

            if ($depth > $bestDepth) {
                $best = $term;
                $bestDepth = $depth;
            }
        }

        return $best;
    }

    private static function clean(array $data): array {
        foreach ($data as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = self::cleanNested($value);
            }
        }
        return $data;
    }

    private static function cleanNested(array $data): array {
        foreach ($data as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = self::cleanNested($value);
            }
        }
        return $data;
    }
}
