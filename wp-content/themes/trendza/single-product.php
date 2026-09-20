<?php
get_header();
?>
<main id="main-content" class="container section">
    <div class="woocommerce trendza-single-product" data-trendza-product="<?php echo esc_attr(get_the_ID()); ?>">
        <?php while (have_posts()) : the_post(); ?>
            <?php wc_get_template_part('content', 'single-product'); ?>
        <?php endwhile; ?>
    </div>
</main>
<?php get_footer(); ?>
