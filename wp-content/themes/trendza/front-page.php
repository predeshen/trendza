<?php get_header(); ?>
<main id="main-content">
    <section class="hero shop-hero">
        <div class="container hero-grid">
            <div class="shop-hero-copy">
                <span class="eyebrow">South Africa's trending product store</span>
                <h1>Find your next <em>favourite.</em></h1>
                <p class="hero-copy">Viral finds, clever gadgets and everyday upgrades worth adding to your cart. Discover what's trending, compare your options and shop in one place.</p>
                <form class="hero-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <label class="screen-reader-text" for="trendza-search">Search products</label>
                    <input id="trendza-search" type="search" name="s" placeholder="Search products, gadgets, beauty..." value="<?php echo esc_attr(get_search_query()); ?>">
                    <input type="hidden" name="post_type" value="product">
                    <button class="button button-primary" type="submit">Search</button>
                </form>
                <div class="hero-actions">
                    <?php if (class_exists('WooCommerce')) : ?>
                        <a class="button button-primary" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop all products</a>
                    <?php endif; ?>
                    <a class="button button-secondary" href="<?php echo esc_url(home_url('/trending/')); ?>">Explore trending</a>
                </div>
                <div class="shop-benefits" aria-label="Shopping benefits">
                    <span><b aria-hidden="true">✓</b> Curated finds</span>
                    <span><b aria-hidden="true">⌕</b> Easy discovery</span>
                    <span><b aria-hidden="true">♡</b> Products worth a look</span>
                </div>
            </div>
            <div class="shop-hero-feature">
                <div class="feature-topline"><span class="live-dot"></span> THE TREND EDIT <span>01 / DISCOVER</span></div>
                <div class="feature-orbit orbit-one"></div><div class="feature-orbit orbit-two"></div>
                <div class="feature-glyph" aria-hidden="true">✦</div>
                <div class="feature-copy">
                    <span class="eyebrow">The good finds, all in one place</span>
                    <h2>Scroll less.<br>Discover more.</h2>
                    <p>Shop smart picks across tech, home, beauty and everyday essentials.</p>
                    <?php if (class_exists('WooCommerce')) : ?><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Explore the store <span aria-hidden="true">↗</span></a><?php endif; ?>
                </div>
                <div class="feature-sticker">TREND<br>SPOTTED <span>✳</span></div>
            </div>
        </div>
    </section>

    <section class="shop-assurance">
        <div class="container assurance-grid">
            <div><span class="assurance-icon">✦</span><span><strong>Curated, not cluttered</strong><small>Products selected for discovery</small></span></div>
            <div><span class="assurance-icon">↗</span><span><strong>What's trending</strong><small>Explore the products gaining attention</small></span></div>
            <div><span class="assurance-icon">◎</span><span><strong>Shop with context</strong><small>Useful details to compare options</small></span></div>
        </div>
    </section>

    <section class="section shop-section">
        <div class="container">
            <div class="section-head"><div><span class="eyebrow">Find your thing</span><h2>Shop by category</h2><p class="section-intro">A little inspiration for whatever you're looking for.</p></div><?php if (class_exists('WooCommerce')) : ?><a class="text-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">All categories <span aria-hidden="true">→</span></a><?php endif; ?></div>
            <div class="category-grid shop-category-grid">
                <?php
                $cats = class_exists('WooCommerce') ? get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 8, 'orderby' => 'count', 'order' => 'DESC']) : [];
                $categoryIcons = ['✦', '⌘', '♡', '⌁', '✳', '◈', '↗', '◎'];
                if (!is_wp_error($cats) && $cats) :
                    foreach ($cats as $index => $cat) :
                ?>
                    <a class="category-card shop-category-card" href="<?php echo esc_url(get_term_link($cat)); ?>">
                        <span class="category-symbol" aria-hidden="true"><?php echo esc_html($categoryIcons[$index % count($categoryIcons)]); ?></span>
                        <strong><?php echo esc_html($cat->name); ?></strong>
                        <small><?php echo esc_html(number_format_i18n($cat->count)); ?> products <span aria-hidden="true">↗</span></small>
                    </a>
                <?php endforeach; else :
                    $suggested = [
                        ['Viral Finds', 'Products worth talking about', '✦'],
                        ['Tech & Gadgets', 'Clever everyday upgrades', '⌘'],
                        ['Home & Living', 'Make your space work smarter', '⌁'],
                        ['Beauty & Care', 'Your next self-care favourite', '♡'],
                        ['Baby Essentials', 'Little things that help a lot', '✳'],
                        ['Car Accessories', 'Useful upgrades for the road', '◈'],
                    ];
                    foreach ($suggested as $category) :
                ?>
                    <div class="category-card shop-category-card category-card-placeholder"><span class="category-symbol" aria-hidden="true"><?php echo esc_html($category[2]); ?></span><strong><?php echo esc_html($category[0]); ?></strong><small><?php echo esc_html($category[1]); ?></small></div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>

    <section id="trending" class="section shop-section trending-section">
        <div class="container">
            <div class="section-head"><div><span class="eyebrow">The ones to watch</span><h2>Trending right now</h2><p class="section-intro">Discover the products moving up our trend rankings.</p></div><a class="text-link" href="<?php echo esc_url(home_url('/trending/')); ?>">Explore trends <span aria-hidden="true">→</span></a></div>
            <?php if (class_exists('WooCommerce')) : $products = trendza_get_discovery_products(8, 'trending'); if ($products) : ?>
                <div class="product-grid"><?php foreach ($products as $item) trendza_render_product_card((int) $item['id']); ?></div>
            <?php else : ?>
                <div class="catalogue-empty"><span class="empty-sparkle" aria-hidden="true">✦</span><h3>Your next favourite goes here.</h3><p>Add and publish your first products to bring the Trendza storefront to life. Trend rankings will populate as product data and shopper signals become available.</p><a class="button button-primary" href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>">Add your first product</a></div>
            <?php endif; else : ?>
                <div class="catalogue-empty"><h3>Your store is almost ready.</h3><p>Activate WooCommerce to enable product listings, shopping cart and checkout.</p></div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (class_exists('WooCommerce')) :
        $saleIds = wc_get_product_ids_on_sale();
        $saleProducts = $saleIds ? wc_get_products(['include' => array_slice($saleIds, 0, 4), 'status' => 'publish', 'limit' => 4, 'return' => 'objects']) : [];
        if ($saleProducts) :
    ?>
    <section class="section shop-section sale-section">
        <div class="container"><div class="section-head"><div><span class="eyebrow">Worth a closer look</span><h2>Special finds</h2><p class="section-intro">Products currently marked on sale.</p></div><a class="text-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop all <span aria-hidden="true">→</span></a></div>
            <div class="product-grid"><?php foreach ($saleProducts as $saleProduct) trendza_render_product_card((int) $saleProduct->get_id()); ?></div>
        </div>
    </section>
    <?php endif; endif; ?>

    <section class="section shop-section final-discovery">
        <div class="container"><div class="final-discovery-panel"><div><span class="eyebrow">Your next great find is out there</span><h2>Ready to have a look?</h2><p>Browse the catalogue and discover something useful, clever or simply too good to scroll past.</p></div><?php if (class_exists('WooCommerce')) : ?><a class="button button-primary" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Start exploring <span aria-hidden="true">→</span></a><?php endif; ?></div></div>
    </section>
</main>
<?php get_footer(); ?>
