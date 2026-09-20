<?php
get_header();
?>
<main id="main-content" class="container section">
    <div class="woocommerce">
        <?php do_action('woocommerce_before_main_content'); ?>
        <header class="section-head">
            <div>
                <span class="eyebrow">Discover</span>
                <?php do_action('woocommerce_archive_description'); ?>
            </div>
        </header>

        <?php if (woocommerce_product_loop()) : ?>
            <?php do_action('woocommerce_before_shop_loop'); ?>
            <div class="product-grid">
                <?php while (have_posts()) : the_post(); trendza_render_product_card((int) get_the_ID()); endwhile; ?>
            </div>
            <?php do_action('woocommerce_after_shop_loop'); ?>
        <?php else : ?>
            <div class="discovery-empty">
                <h2>No products found</h2>
                <p>Try another category or explore what is currently trending.</p>
                <a class="button button-primary" href="<?php echo esc_url(home_url('/trending/')); ?>">Explore trending</a>
            </div>
        <?php endif; ?>

        <?php do_action('woocommerce_after_main_content'); ?>
    </div>
</main>
<?php get_footer(); ?>
