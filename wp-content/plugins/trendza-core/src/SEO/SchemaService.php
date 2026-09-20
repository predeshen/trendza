<?php
namespace Trendza\SEO;

final class SchemaService {
    public static function register(): void {
        // Trendza owns the core JSON-LD graph. Disable WooCommerce's duplicate graph,
        // while leaving SEO plugins free to add their own non-overlapping entities.
        add_filter('woocommerce_structured_data_enabled', '__return_false');
        add_action('wp_head', [self::class, 'output'], 20);
    }

    public static function output(): void {
        if (is_admin()) {
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
            'offers' => [
                '@type' => 'Offer',
                'url' => $product->get_permalink(),
                'priceCurrency' => get_woocommerce_currency(),
                'price' => $product->get_price() !== '' ? (string) $product->get_price() : null,
                'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@id' => home_url('/#organization')],
            ],
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

    private static function brand($product): ?array {
        $brand = get_post_meta($product->get_id(), '_trendza_brand', true);
        if (!$brand) {
            $brand = $product->get_attribute('pa_brand') ?: $product->get_attribute('brand');
        }

        return $brand ? ['@type' => 'Brand', 'name' => wp_strip_all_tags((string) $brand)] : null;
    }

    private static function siteLogo(): ?string {
        $logo_id = (int) get_theme_mod('custom_logo');
        if (!$logo_id) {
            return null;
        }

        $logo = wp_get_attachment_image_url($logo_id, 'full');
        return $logo ?: null;
    }

    private static function breadcrumbs($product): array {
        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => get_bloginfo('name'),
                'item' => home_url('/'),
            ],
        ];

        $terms = get_the_terms($product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $term = self::deepestCategory($terms);
            if ($term) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $term->name,
                    'item' => get_term_link($term),
                ];
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
        usort($terms, static function (\WP_Term $a, \WP_Term $b): int {
            return ((int) $b->parent) <=> ((int) $a->parent);
        });

        return $terms[0] ?? null;
    }

    private static function clean(array $data): array {
        foreach ($data as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
                continue;
            }

            if (is_array($value)) {
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
