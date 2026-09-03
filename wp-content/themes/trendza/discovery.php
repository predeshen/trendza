<?php
/**
 * Trendza discovery landing template for /trending/, /rising/, /best-value/ and /quality-picks/.
 */
defined('ABSPATH') || exit;

$key = sanitize_key((string) get_query_var('trendza_discovery'));
$title = trendza_discovery_title();
$description = trendza_discovery_description();
$query = trendza_discovery_query();

get_header();
?>
<main id="main-content" class="container section discovery-page">
    <header class="discovery-hero">
        <div>
            <span class="eyebrow">Curated discovery</span>
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html($description); ?></p>
        </div>
        <nav class="discovery-nav" aria-label="Trendza discovery sections">
            <a class="<?php echo $key === 'trending' ? 'is-active' : ''; ?>" href="<?php echo esc_url(home_url('/trending/')); ?>">Trending</a>
            <a class="<?php echo $key === 'rising' ? 'is-active' : ''; ?>" href="<?php echo esc_url(home_url('/rising/')); ?>">Rising</a>
            <a class="<?php echo $key === 'best-value' ? 'is-active' : ''; ?>" href="<?php echo esc_url(home_url('/best-value/')); ?>">Best value</a>
            <a class="<?php echo $key === 'quality-picks' ? 'is-active' : ''; ?>" href="<?php echo esc_url(home_url('/quality-picks/')); ?>">Quality picks</a>
        </nav>
    </header>

    <?php if ($query->have_posts()) : ?>
        <div class="discovery-meta">
            <span><?php echo esc_html(number_format_i18n((int) $query->found_posts)); ?> products</span>
            <span class="muted">Updated as Trendza intelligence changes.</span>
        </div>
        <div class="product-grid" data-trendza-discovery="<?php echo esc_attr($key); ?>">
            <?php while ($query->have_posts()) : $query->the_post(); trendza_render_product_card((int) get_the_ID()); endwhile; ?>
        </div>
        <nav class="discovery-pagination" aria-label="Discovery pagination">
            <?php echo wp_kses_post(paginate_links(['total' => $query->max_num_pages, 'current' => max(1, (int) get_query_var('paged')), 'type' => 'list', 'base' => trailingslashit(home_url('/' . $key . '/page/%#%/')), 'format' => ''])); ?>
        </nav>
    <?php else : ?>
        <div class="discovery-empty"><h2>We’re still finding the signal.</h2><p>No products have enough data for this collection yet. Check back as the catalogue and trend signals grow.</p><a class="button button-primary" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>">Browse the shop</a></div>
    <?php endif; wp_reset_postdata(); ?>

    <section class="discovery-explainer">
        <span class="eyebrow">How Trendza works</span>
        <h2>Not every product makes the cut.</h2>
        <p>Trendza combines product activity, demand signals and catalogue quality to help surface products worth paying attention to. Scores are a discovery aid, not a guarantee of future popularity or product performance.</p>
    </section>
</main>
<?php get_footer(); ?>
