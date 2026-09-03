<?php
defined('ABSPATH') || exit;

function trendza_setup(): void {
    load_theme_textdomain('trendza', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', ['height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('responsive-embeds');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus(['primary' => __('Primary Menu', 'trendza'), 'footer' => __('Footer Menu', 'trendza')]);
}
add_action('after_setup_theme', 'trendza_setup');

function trendza_assets(): void {
    $v = wp_get_theme()->get('Version');
    wp_enqueue_style('trendza-style', get_stylesheet_uri(), [], $v);
    wp_enqueue_script('trendza-theme', get_template_directory_uri() . '/assets/js/theme.js', [], $v, true);
    wp_localize_script('trendza-theme', 'trendzaAnalytics', ['endpoint' => esc_url_raw(rest_url('trendza/v1/events'))]);
}
add_action('wp_enqueue_scripts', 'trendza_assets');

function trendza_body_classes(array $classes): array {
    $classes[] = 'trendza-theme';
    if (class_exists('WooCommerce')) $classes[] = 'trendza-woocommerce';
    return $classes;
}
add_filter('body_class', 'trendza_body_classes');

function trendza_get_product_trend(int $product_id): ?array {
    $score = get_post_meta($product_id, '_trendza_trend_score', true);
    $status = get_post_meta($product_id, '_trendza_trend_status', true);
    if ($score === '' || $status === '') return null;
    return ['score' => (float) $score, 'status' => sanitize_key((string) $status)];
}

function trendza_render_product_card(int $product_id): void {
    if (!function_exists('wc_get_product')) return;
    $product = wc_get_product($product_id);
    if (!$product) return;
    $trend = trendza_get_product_trend($product_id);
    ?>
    <article class="product-card" data-trendza-product="<?php echo esc_attr($product_id); ?>">
        <?php if ($trend) : ?><span class="trend-badge"><?php echo esc_html(ucfirst($trend['status'])); ?> · <?php echo esc_html(number_format_i18n($trend['score'], 0)); ?></span><?php endif; ?>
        <?php if ($product->is_on_sale()) : ?><span class="sale-badge">Sale</span><?php endif; ?>
        <a class="product-image" href="<?php echo esc_url($product->get_permalink()); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
            <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
        </a>
        <div class="product-body">
            <?php if ($product->get_rating_count()) : ?><div class="product-rating" aria-label="<?php echo esc_attr(sprintf(__('Rated %s out of 5', 'trendza'), $product->get_average_rating())); ?>"><?php echo wp_kses_post(wc_get_rating_html($product->get_average_rating(), $product->get_rating_count())); ?></div><?php endif; ?>
            <a class="product-title" href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product->get_name()); ?></a>
            <div class="product-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
        </div>
    </article>
    <?php
}

function trendza_single_product_trend_badge(): void {
    global $product;
    if (!$product) return;
    $trend = trendza_get_product_trend((int) $product->get_id());
    if (!$trend) return;
    echo '<div class="trendza-product-signal"><span class="trend-badge">' . esc_html(ucfirst($trend['status'])) . ' · ' . esc_html(number_format_i18n($trend['score'], 0)) . '/100</span><span class="muted">Trend score based on product activity and quality signals.</span></div>';
}
add_action('woocommerce_single_product_summary', 'trendza_single_product_trend_badge', 4);

function trendza_render_intelligence_panel(): void {
    global $product;
    if (!$product) return;
    $id = (int) $product->get_id();
    $meta = static function (string $key, $default = '') use ($id) { return get_post_meta($id, $key, true) ?: $default; };
    $trend = trendza_get_product_trend($id);
    $quality = (float) $meta('_trendza_quality_score', 0);
    $value = (float) $meta('_trendza_value_score', 0);
    $summary = trim((string) $meta('_trendza_summary'));
    $ai = trim((string) $meta('_trendza_ai_summary'));
    $use_cases = $meta('_trendza_use_cases', []);
    $pros = $meta('_trendza_pros', []);
    $cons = $meta('_trendza_cons', []);
    $specs = $meta('_trendza_specs', []);
    $shipping = trim((string) $meta('_trendza_shipping_info'));
    foreach (['use_cases','pros','cons'] as $field) {
        ${$field} = is_string(${$field}) ? preg_split('/\r\n|\r|\n|,/', ${$field}, -1, PREG_SPLIT_NO_EMPTY) : (array) ${$field};
    }
    if (is_string($specs)) {
        $decoded = json_decode($specs, true);
        $specs = is_array($decoded) ? $decoded : [];
    }
    $has_content = $summary || $ai || $quality || $value || $use_cases || $pros || $cons || $specs || $shipping;
    if (!$has_content) return;
    ?>
    <section class="trendza-intelligence" aria-labelledby="trendza-intelligence-title">
        <div class="intelligence-heading">
            <div><span class="eyebrow">Trendza Intelligence</span><h2 id="trendza-intelligence-title">Why this product stands out</h2></div>
            <?php if ($trend) : ?><div class="intelligence-score"><strong><?php echo esc_html(number_format_i18n($trend['score'], 0)); ?></strong><span>Trend score</span></div><?php endif; ?>
        </div>
        <?php if ($summary) : ?><p class="intelligence-summary"><?php echo esc_html($summary); ?></p><?php elseif ($ai) : ?><p class="intelligence-summary"><?php echo esc_html($ai); ?></p><?php endif; ?>
        <?php if ($quality || $value) : ?>
            <div class="score-grid">
                <?php if ($quality) : ?><div class="score-card"><span>Product quality</span><strong><?php echo esc_html(number_format_i18n($quality, 0)); ?><small>/100</small></strong><div class="score-track"><i style="width:<?php echo esc_attr(min(100, $quality)); ?>%"></i></div></div><?php endif; ?>
                <?php if ($value) : ?><div class="score-card"><span>Value score</span><strong><?php echo esc_html(number_format_i18n($value, 0)); ?><small>/100</small></strong><div class="score-track"><i style="width:<?php echo esc_attr(min(100, $value)); ?>%"></i></div></div><?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="intelligence-grid">
            <?php if ($use_cases) : ?><div class="intel-card"><h3>Best for</h3><ul><?php foreach ($use_cases as $item) : ?><li><?php echo esc_html(trim((string) $item)); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if ($pros) : ?><div class="intel-card"><h3>Pros</h3><ul class="check-list"><?php foreach ($pros as $item) : ?><li><?php echo esc_html(trim((string) $item)); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if ($cons) : ?><div class="intel-card"><h3>Things to consider</h3><ul><?php foreach ($cons as $item) : ?><li><?php echo esc_html(trim((string) $item)); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if ($specs) : ?><div class="intel-card"><h3>Key specs</h3><dl><?php foreach ($specs as $key => $value) : if (is_array($value)) $value = implode(', ', $value); ?><div><dt><?php echo esc_html(ucwords(str_replace(['_','-'], ' ', (string) $key))); ?></dt><dd><?php echo esc_html((string) $value); ?></dd></div><?php endforeach; ?></dl></div><?php endif; ?>
        </div>
        <?php if ($shipping) : ?><div class="shipping-note"><strong>Delivery</strong><span><?php echo esc_html($shipping); ?></span></div><?php endif; ?>
    </section>
    <?php
}
add_action('woocommerce_after_single_product_summary', 'trendza_render_intelligence_panel', 8);

function trendza_discovery_routes(): void {
    add_rewrite_rule('^(trending|rising|best-value|quality-picks)/?$', 'index.php?trendza_discovery=$matches[1]', 'top');
    add_rewrite_rule('^(trending|rising|best-value|quality-picks)/page/([0-9]+)/?$', 'index.php?trendza_discovery=$matches[1]&paged=$matches[2]', 'top');
}
add_action('init', 'trendza_discovery_routes');

function trendza_discovery_query_var(array $vars): array {
    $vars[] = 'trendza_discovery';
    return $vars;
}
add_filter('query_vars', 'trendza_discovery_query_var');

function trendza_discovery_template(string $template): string {
    if (get_query_var('trendza_discovery')) {
        $candidate = get_template_directory() . '/discovery.php';
        if (is_readable($candidate)) return $candidate;
    }
    return $template;
}
add_filter('template_include', 'trendza_discovery_template');

function trendza_discovery_title(): string {
    $key = sanitize_key((string) get_query_var('trendza_discovery'));
    return match ($key) {
        'trending' => 'Trending Products in South Africa',
        'rising' => 'Rising Products to Watch',
        'best-value' => 'Best Value Products',
        'quality-picks' => 'Quality Picks',
        default => 'Discover on Trendza',
    };
}

function trendza_discovery_description(): string {
    $key = sanitize_key((string) get_query_var('trendza_discovery'));
    return match ($key) {
        'trending' => 'Explore products showing the strongest current Trendza signals across activity, demand and product quality.',
        'rising' => 'Find products gaining momentum before they become the next big trend.',
        'best-value' => 'Shop products selected for a strong balance of price, quality and usefulness.',
        'quality-picks' => 'Browse products with strong catalogue quality signals, useful information and dependable availability.',
        default => 'Discover curated products on Trendza.',
    };
}

function trendza_discovery_query(): WP_Query {
    $key = sanitize_key((string) get_query_var('trendza_discovery'));
    $args = ['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 24, 'paged' => max(1, (int) get_query_var('paged')), 'no_found_rows' => false];
    if ($key === 'trending' || $key === 'rising') {
        $args['meta_key'] = '_trendza_trend_score';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        $args['meta_query'] = [['key' => '_trendza_trend_status', 'value' => $key === 'trending' ? 'trending' : 'rising']];
    } elseif ($key === 'best-value') {
        $args['meta_key'] = '_trendza_value_score';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        $args['meta_query'] = [['key' => '_trendza_value_score', 'compare' => 'EXISTS']];
    } elseif ($key === 'quality-picks') {
        $args['meta_key'] = '_trendza_quality_score';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        $args['meta_query'] = [['key' => '_trendza_quality_score', 'compare' => 'EXISTS']];
    }
    return new WP_Query($args);
}

function trendza_fallback_menu(): void {
    echo '<ul class="main-menu"><li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
    if (class_exists('WooCommerce')) {
        echo '<li><a href="' . esc_url(wc_get_page_permalink('shop')) . '">Shop</a></li><li><a href="' . esc_url(wc_get_cart_url()) . '">Cart</a></li>';
    }
    echo '</ul>';
}
