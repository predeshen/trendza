<?php
namespace Trendza\Admin;

use Trendza\Analytics\EventStore;
use Trendza\Products\ProductMeta;

final class AdminDashboard {
    public static function register(): void { add_action('admin_menu', [self::class, 'menu']); }

    public static function menu(): void {
        add_menu_page('Trendza Intelligence', 'Trendza', 'manage_woocommerce', 'trendza', [self::class, 'render'], 'dashicons-chart-area', 56);
    }

    public static function render(): void {
        if (!current_user_can('manage_woocommerce')) return;
        $counts = self::productCounts();
        $events = EventStore::countsByType(24);
        $synced = self::syncCount();
        $syncErrors = self::syncErrorCount();
        $topProducts = self::topProducts(8);
        ?>
        <div class="wrap">
            <h1>Trendza Intelligence</h1>
            <p>Catalogue health, demand signals and supplier sync at a glance.</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;max-width:1200px;margin:20px 0">
                <?php foreach ([['Published products',$counts['published']],['Trending',$counts['trending']],['Rising',$counts['rising']],['Events · 24h',array_sum($events)],['Synced products',$synced],['Sync errors',$syncErrors]] as $card) : ?>
                    <div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px">
                        <div style="color:#646970;font-size:13px"><?php echo esc_html($card[0]); ?></div>
                        <strong style="display:block;font-size:28px;margin-top:6px"><?php echo esc_html(number_format_i18n((int) $card[1])); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(280px,1fr);gap:24px;max-width:1200px">
                <div>
                    <h2>Top trending products</h2>
                    <table class="widefat striped">
                        <thead><tr><th>Product</th><th>Trend</th><th>Status</th><th>Stock</th></tr></thead>
                        <tbody>
                        <?php if (!$topProducts) : ?>
                            <tr><td colspan="4">No published products have trend data yet.</td></tr>
                        <?php else : foreach ($topProducts as $item) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url(get_edit_post_link($item['id'])); ?>"><?php echo esc_html($item['name']); ?></a></td>
                                <td><?php echo esc_html(number_format_i18n($item['score'], 2)); ?></td>
                                <td><?php echo esc_html(ucfirst($item['status'])); ?></td>
                                <td><?php echo $item['in_stock'] ? 'In stock' : 'Out of stock'; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h2>Events · 24h</h2>
                    <table class="widefat striped">
                        <tbody>
                        <?php foreach (['view'=>'Product views','search'=>'Searches','add_to_cart'=>'Add to cart','begin_checkout'=>'Checkout starts','purchase'=>'Purchases'] as $type => $label) : ?>
                            <tr><td><?php echo esc_html($label); ?></td><td><?php echo esc_html(number_format_i18n((int) ($events[$type] ?? 0))); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h2>Catalogue health</h2>
            <table class="widefat striped" style="max-width:1200px">
                <thead><tr><th>Metric</th><th>Products</th></tr></thead>
                <tbody>
                    <tr><td>Missing SKU</td><td><?php echo esc_html(number_format_i18n($counts['missing_sku'])); ?></td></tr>
                    <tr><td>Missing images</td><td><?php echo esc_html(number_format_i18n($counts['missing_image'])); ?></td></tr>
                    <tr><td>Missing prices</td><td><?php echo esc_html(number_format_i18n($counts['missing_price'])); ?></td></tr>
                    <tr><td>Out of stock</td><td><?php echo esc_html(number_format_i18n($counts['outofstock'])); ?></td></tr>
                    <tr><td>Products with sync errors</td><td><?php echo esc_html(number_format_i18n($syncErrors)); ?></td></tr>
                </tbody>
            </table>

            <p style="margin-top:18px;color:#646970">Trend scores are recalculated by the scheduled Trendza intelligence job. Supplier sync status is stored per product.</p>
        </div>
        <?php
    }

    private static function productCounts(): array {
        global $wpdb;
        $published = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
        $trending = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_type = 'product' AND p.post_status = 'publish' AND m.meta_key = %s AND m.meta_value = 'trending'",
            ProductMeta::TREND_STATUS
        ));
        $rising = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_type = 'product' AND p.post_status = 'publish' AND m.meta_key = %s AND m.meta_value = 'rising'",
            ProductMeta::TREND_STATUS
        ));

        $missingSku = (int) $wpdb->get_var("SELECT COUNT(p.ID) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_sku' WHERE p.post_type = 'product' AND p.post_status = 'publish' AND (m.meta_id IS NULL OR m.meta_value = '')");
        $missingImage = (int) $wpdb->get_var("SELECT COUNT(p.ID) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id' WHERE p.post_type = 'product' AND p.post_status = 'publish' AND (m.meta_id IS NULL OR m.meta_value = '0' OR m.meta_value = '')");
        $missingPrice = (int) $wpdb->get_var("SELECT COUNT(p.ID) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_price' WHERE p.post_type = 'product' AND p.post_status = 'publish' AND (m.meta_id IS NULL OR m.meta_value = '')");
        $outofstock = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_type = 'product' AND p.post_status = 'publish' AND m.meta_key = '_stock_status' AND m.meta_value = %s",
            'outofstock'
        ));

        return [
            'published' => $published,
            'trending' => $trending,
            'rising' => $rising,
            'missing_sku' => $missingSku,
            'missing_image' => $missingImage,
            'missing_price' => $missingPrice,
            'outofstock' => $outofstock,
        ];
    }

    private static function topProducts(int $limit): array {
        $ids = get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>max(1,$limit),'fields'=>'ids','meta_key'=>ProductMeta::TREND_SCORE,'orderby'=>'meta_value_num','order'=>'DESC','no_found_rows'=>true]);
        $items = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            $product = function_exists('wc_get_product') ? wc_get_product($id) : null;
            $items[] = ['id'=>$id,'name'=>get_the_title($id),'score'=>(float) ProductMeta::get($id,ProductMeta::TREND_SCORE,0),'status'=>ProductMeta::get($id,ProductMeta::TREND_STATUS,'stable'),'in_stock'=>$product ? $product->is_in_stock() : false];
        }
        return $items;
    }

    private static function syncCount(): int {
        global $wpdb;
        $key = ProductMeta::SYNC_STATUS;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = 'synced'", $key));
    }

    private static function syncErrorCount(): int {
        global $wpdb;
        $key = ProductMeta::SYNC_STATUS;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s", $key, 'synced%error%'));
    }
}
