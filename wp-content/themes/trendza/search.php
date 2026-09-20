<?php
defined('ABSPATH') || exit;
get_header();
?>
<main id="main-content" class="container section">
    <header class="section-head">
        <div>
            <span class="eyebrow">Product search</span>
            <h1><?php printf(esc_html__('Results for “%s”','trendza'),esc_html(get_search_query())); ?></h1>
            <p class="muted">Showing products from the Trendza catalogue.</p>
        </div>
    </header>
    <?php if(have_posts()): ?>
        <div class="product-grid" data-trendza-search="<?php echo esc_attr(get_search_query()); ?>">
            <?php while(have_posts()): the_post(); trendza_render_product_card((int)get_the_ID()); endwhile; ?>
        </div>
        <?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'← Previous','next_text'=>'Next →']); ?>
    <?php else: ?>
        <div class="discovery-empty">
            <h2>No products found</h2>
            <p>Try a broader search or explore the products currently trending on Trendza.</p>
            <a class="button button-primary" href="<?php echo esc_url(home_url('/trending/')); ?>">Explore trending</a>
        </div>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
