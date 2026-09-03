<?php
/**
 * Plugin Name: Trendza Core
 * Plugin URI: https://trendza.co.za/
 * Description: Core domain logic and integrations for the Trendza WooCommerce marketplace.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Author: Trendza
 * License: GPL-2.0-or-later
 * Text Domain: trendza-core
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('TRENDZA_CORE_VERSION', '0.1.0');
define('TRENDZA_CORE_FILE', __FILE__);
define('TRENDZA_CORE_DIR', plugin_dir_path(__FILE__));

/**
 * Bootstrap the plugin.
 */
function trendza_core_bootstrap(): void
{
    // Domain modules will be loaded here as they are introduced.
}

add_action('plugins_loaded', 'trendza_core_bootstrap');
