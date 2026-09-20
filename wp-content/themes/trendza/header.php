<?php defined('ABSPATH') || exit; ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0B0F19">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <?php if (has_custom_logo()) : ?>
                <?php
                $logoId = (int) get_theme_mod('custom_logo');
                echo wp_kses_post(wp_get_attachment_image($logoId, 'full', false, ['class' => 'custom-logo', 'alt' => get_bloginfo('name')]));
                ?>
            <?php else : ?>
                <span>Trend<span>za</span></span>
            <?php endif; ?>
        </a>

        <nav class="main-nav" aria-label="Primary navigation">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'trendza_fallback_menu']); ?>
        </nav>

        <div class="header-actions">
            <a class="icon-button header-search" href="<?php echo esc_url(home_url('/?post_type=product')); ?>" aria-label="Search products" title="Search products">
                <span aria-hidden="true">⌕</span>
            </a>
            <?php if (class_exists('WooCommerce')) : ?>
                <a class="icon-button cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="View cart" title="Cart">
                    <span aria-hidden="true">Bag</span>
                    <?php $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
                    <span class="cart-count" aria-label="<?php echo esc_attr($count . ' items in cart'); ?>"><?php echo esc_html($count); ?></span>
                </a>
            <?php endif; ?>
            <?php if (class_exists('WooCommerce') && wc_get_page_id('myaccount') > 0) : ?>
                <a class="icon-button account-link" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="My account" title="My account">
                    <span aria-hidden="true">Account</span>
                </a>
            <?php endif; ?>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu">
                <span aria-hidden="true">☰</span>
            </button>
        </div>
    </div>

    <div id="mobile-menu" class="container mobile-menu" hidden>
        <nav aria-label="Mobile navigation">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'trendza_fallback_menu']); ?>
        </nav>
        <?php if (class_exists('WooCommerce')) : ?>
            <div class="mobile-utility-links">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop</a>
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>">Cart (<?php echo esc_html($count); ?>)</a>
                <?php if (wc_get_page_id('myaccount') > 0) : ?><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Account</a><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</header>
