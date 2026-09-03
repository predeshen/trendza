<?php
/**
 * Plugin Name: Trendza Core
 * Description: Product intelligence, trend scoring and discovery APIs for Trendza.
 * Version: 0.2.0
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 */

defined('ABSPATH') || exit;

if (file_exists(__DIR__ . '/vendor/autoload.php')) require_once __DIR__ . '/vendor/autoload.php';
else spl_autoload_register(function ($class) { $prefix='Trendza\\'; if (strpos($class,$prefix)!==0) return; $file=__DIR__.'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(file_exists($file)) require_once $file; });

add_action('plugins_loaded', static function () {
    if (class_exists('WooCommerce')) \Trendza\Support\Plugin::boot();
});

register_deactivation_hook(__FILE__, static function () {
    $timestamp = wp_next_scheduled('trendza_recalculate_trends');
    if ($timestamp) wp_unschedule_event($timestamp, 'trendza_recalculate_trends');
});

function trendza_get_discovery_products(int $limit = 8, string $mode = 'trending'): array {
    return class_exists('Trendza\\Support\\Plugin') ? \Trendza\Support\Plugin::discovery($limit, $mode) : [];
}
