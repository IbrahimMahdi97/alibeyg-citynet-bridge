<?php
/**
 * Plugin Name: Alibeyg Citynet Bridge
 * Description: Server-side proxy to Citynet API (Flights/Hotels/Visa) + modern travel widget with autocomplete & i18n.
 * Version: 0.5.1
 * Author: Alibeyg
 * Text Domain: alibeyg-citynet
 *
 * @package Alibeyg_Citynet_Bridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define plugin constants
 */
define('ABG_CITYNET_VERSION', '0.5.1');
define('ABG_CITYNET_PLUGIN_FILE', __FILE__);
define('ABG_CITYNET_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABG_CITYNET_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Configuration Constants
 * These can be overridden in wp-config.php
 */
if (!defined('CN_API_BASE')) {
    define('CN_API_BASE', 'https://171.22.24.69/api/v1.0/');
}
if (!defined('CN_ORG_ID')) {
    define('CN_ORG_ID', 12345);
}
if (!defined('CN_API_KEY')) {
    define('CN_API_KEY', '');
}
if (!defined('CN_USERNAME')) {
    define('CN_USERNAME', '');
}
if (!defined('CN_PASSWORD')) {
    define('CN_PASSWORD', '');
}

/**
 * Load the main plugin class
 */
require_once ABG_CITYNET_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize the plugin
 *
 * @return ABG_Citynet_Bridge_Plugin
 */
function abg_citynet_bridge() {
    return ABG_Citynet_Bridge_Plugin::get_instance(__FILE__);
}

// Initialize plugin
abg_citynet_bridge();
