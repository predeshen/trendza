<?php
namespace Trendza\SEO;

final class SchemaService {
    public static function register(): void {
        // Trendza owns the JSON-LD graph to avoid duplicate Product entities from WooCommerce.
        add_filter('woocommerce_structured_data_enabled', '__return_false');
        add_action('wp_head', [self::class, 'output'], 20);
    }

    public static function output(): void {
        $graphs = [
            [
                '@type' => 'Organization',
                '@id' => home_url('/#organization'),
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => home_url('/#website'),
                'url' => home_url('/'),
                'name' => get_bloginfo('name'),
                'publisher' => ['@id' => home_url('/#organization')],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => home_url('/?s={search_term_string}')],
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

        echo '<script type="application/ld+json">' . wp_json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graphs],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . '</script>\n';
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
            'offers' => [
                '@type' => 'Offer',
                'url' => $product->get_permalink(),
                'priceCurrency' => get_woocommerce_currency(),
                'price' => (string) $product->get_price(),
                'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];
        if ($product->get_rating_count() > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $product->get_average_rating(),
                'reviewCount' => (int) $product->get_rating_count(),
            ];
        }
        return self::clean($schema);
    }

    private static function breadcrumbs($product): array {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => get_bloginfo('name'), 'item' => home_url('/')],
        ];
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $term = reset($terms);
            $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $term->name, 'item' => get_term_link($term)];
            $position = 3;
        } else {
            $position = 2;
        }
        $items[] = ['@type' => 'ListItem', 'position' => $position, 'name' => $product->get_name(), 'item' => $product->get_permalink()];
        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    private static function clean(array $data): array {
        foreach ($data as $key => $value) {
            if ($value === null || $value === '' || $value === []) unset($data[$key]);
        }
        return $data;
    }
}
