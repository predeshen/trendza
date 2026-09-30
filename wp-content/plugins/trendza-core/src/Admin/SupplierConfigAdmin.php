<?php
namespace Trendza\Admin;

use Trendza\Suppliers\SupplierConfig;
use Trendza\Suppliers\SupplierConfigStore;

final class SupplierConfigAdmin {
    public static function register(): void {
        add_submenu_page('trendza','Supplier Policies','Supplier Policies','manage_woocommerce','trendza-suppliers',[self::class,'render']);
        add_action('admin_post_trendza_save_supplier_config',[self::class,'save']);
    }

    public static function render(): void {
        if (!current_user_can('manage_woocommerce')) return;
        $code = isset($_GET['supplier']) ? sanitize_key((string) $_GET['supplier']) : '';
        $saved = $code !== '' ? SupplierConfigStore::get($code) : null;
        ?>
        <div class="wrap">
            <h1>Trendza Supplier Policies</h1>
            <p>Configure commercial and feed-safety defaults per supplier. Feed URLs and credentials remain outside GitHub.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('trendza_save_supplier_config'); ?>
                <input type="hidden" name="action" value="trendza_save_supplier_config">
                <table class="form-table" role="presentation">
                    <tr><th><label for="supplier_code">Supplier code</label></th><td><input name="supplier_code" id="supplier_code" class="regular-text" value="<?php echo esc_attr($code); ?>" required></td></tr>
                    <tr><th><label for="margin_percent">Target margin (%)</label></th><td><input type="number" step="0.01" min="0" max="89.99" name="margin_percent" id="margin_percent" value="<?php echo esc_attr($saved?->marginPercent ?? 25); ?>" required></td></tr>
                    <tr><th><label for="minimum_feed_retention">Minimum feed retention (%)</label></th><td><input type="number" step="1" min="0" max="100" name="minimum_feed_retention" id="minimum_feed_retention" value="<?php echo esc_attr(($saved?->minimumFeedRetention ?? 0.50) * 100); ?>" required></td></tr>
                    <tr><th><label for="max_products">Maximum products</label></th><td><input type="number" min="1" name="max_products" id="max_products" value="<?php echo esc_attr($saved?->maxProducts ?? 300); ?>" required></td></tr>
                    <tr><th>Sync controls</th><td>
                        <label><input type="checkbox" name="update_price" value="1" <?php checked($saved?->updatePrice ?? true); ?>> Update supplier prices</label><br>
                        <label><input type="checkbox" name="update_stock" value="1" <?php checked($saved?->updateStock ?? true); ?>> Update supplier stock</label><br>
                        <label><input type="checkbox" name="allow_empty_feed" value="1" <?php checked($saved?->allowEmptyFeed ?? false); ?>> Allow an empty feed</label>
                    </td></tr>
                </table>
                <?php submit_button('Save supplier policy'); ?>
            </form>
        </div>
        <?php
    }

    public static function save(): void {
        if (!current_user_can('manage_woocommerce')) wp_die('You do not have permission to manage supplier policies.');
        check_admin_referer('trendza_save_supplier_config');
        $code = sanitize_key((string) ($_POST['supplier_code'] ?? ''));
        if ($code === '') wp_die('Supplier code is required.');

        try {
            $config = new SupplierConfig(
                $code,
                (float) ($_POST['margin_percent'] ?? 25),
                ((float) ($_POST['minimum_feed_retention'] ?? 50)) / 100,
                (int) ($_POST['max_products'] ?? 300),
                isset($_POST['update_price']),
                isset($_POST['update_stock']),
                isset($_POST['allow_empty_feed'])
            );
        } catch (\Throwable $e) {
            wp_die(esc_html($e->getMessage()));
        }

        SupplierConfigStore::save($config);
        wp_safe_redirect(add_query_arg(['page'=>'trendza-suppliers','supplier'=>$code,'updated'=>'1'],admin_url('admin.php')));
        exit;
    }
}
