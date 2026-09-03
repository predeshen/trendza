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
        $events = EventStore::countAll(24);
        $synced = self::syncCount();
        ?>
        <div class="wrap">
            <h1>Trendza Intelligence</h1>
            <p>Catalogue health, demand signals and supplier sync at a glance.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;max-width:1100px;margin:20px 0">
                <?php foreach ([['Published products',$counts['published']],['Trending',$counts['trending']],['Rising',$counts['rising']],['Events · 24h',$events],['Synced products',$synced]] as $card) : ?>
                    <div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px"><div style="color:#646970;font-size:13px"><?php echo esc_html($card[0]); ?></div><strong style="display:block;font-size:28px;margin-top:6px"><?php echo esc_html(number_format_i18n((int)$card[1])); ?></strong></div>
                <?php endforeach; ?>
            </div>
            <h2>Catalogue health</h2>
            <table class="widefat striped" style="max-width:1100px"><thead><tr><th>Metric</th><th>Products</th></tr></thead><tbody>
                <tr><td>Missing SKU</td><td><?php echo esc_html(number_format_i18n($counts['missing_sku'])); ?></td></tr>
                <tr><td>Missing images</td><td><?php echo esc_html(number_format_i18n($counts['missing_image'])); ?></td></tr>
                <tr><td>Missing prices</td><td><?php echo esc_html(number_format_i18n($counts['missing_price'])); ?></td></tr>
                <tr><td>Out of stock</td><td><?php echo esc_html(number_format_i18n($counts['outofstock'])); ?></td></tr>
            </tbody></table>
            <p style="margin-top:18px;color:#646970">Trend scores are recalculated by the scheduled Trendza intelligence job. Supplier sync status is stored per product.</p>
        </div>
        <?php
    }

    private static function productCounts(): array {
        $ids = get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids']);
        $out = ['published'=>count($ids),'trending'=>0,'rising'=>0,'missing_sku'=>0,'missing_image'=>0,'missing_price'=>0,'outofstock'=>0];
        foreach ($ids as $id) {
            $id = (int)$id;
            $status = ProductMeta::get($id, ProductMeta::TREND_STATUS, 'stable');
            if ($status === 'trending') $out['trending']++;
            if ($status === 'rising') $out['rising']++;
            if (function_exists('wc_get_product')) {
                $product = wc_get_product($id);
                if ($product) {
                    if ($product->get_sku() === '') $out['missing_sku']++;
                    if (!$product->get_image_id()) $out['missing_image']++;
                    if ($product->get_price() === '') $out['missing_price']++;
                    if (!$product->is_in_stock()) $out['outofstock']++;
                }
            }
        }
        return $out;
    }

    private static function syncCount(): int {
        global $wpdb;
        $key = ProductMeta::SYNC_STATUS;
        return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = 'synced'", $key));
    }
}
