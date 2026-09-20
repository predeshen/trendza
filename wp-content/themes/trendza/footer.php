<?php defined('ABSPATH') || exit; ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <p class="footer-title">Trendza</p>
            <p>We don't sell everything. We find what everyone is talking about.</p>
        </div>
        <div>
            <p class="footer-title">Discover</p>
            <?php wp_nav_menu(['theme_location'=>'footer','container'=>false,'menu_class'=>'footer-nav','fallback_cb'=>false]); ?>
            <?php if (has_nav_menu('footer') === false) : ?>
                <ul class="footer-nav">
                    <li><a href="<?php echo esc_url(home_url('/trending/')); ?>">Trending</a></li>
                    <li><a href="<?php echo esc_url(home_url('/rising/')); ?>">Rising</a></li>
                    <li><a href="<?php echo esc_url(home_url('/best-value/')); ?>">Best value</a></li>
                    <li><a href="<?php echo esc_url(home_url('/quality-picks/')); ?>">Quality picks</a></li>
                </ul>
            <?php endif; ?>
        </div>
        <div>
            <p class="footer-title">Shop confidently</p>
            <p>Curated products, clear information and South African delivery details.</p>
            <?php if (class_exists('WooCommerce')) : ?>
                <p><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop all products →</a></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="container footer-bottom">
        <small>© <?php echo esc_html(wp_date('Y')); ?> Trendza. All rights reserved.</small>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
