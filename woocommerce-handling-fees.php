<?php
/**
 * Plugin Name: WooCommerce Handling Fees
 * Description: Add configurable handling fees based on shipping classes and product quantities in the cart.
 * Version: 1.0.0
 * Author: Asuh
 * Author URI: https://asuh.com
 * Text Domain: wc-handling-fees
 * WC requires at least: 9.7.1
 * WC tested up to: 9.7.1
 */

namespace HandlingFees;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

// Define plugin constants
define('HANDLING_FEES_VERSION', '1.0.0');
define('HANDLING_FEES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HANDLING_FEES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HANDLING_FEES_OPTION_NAME', 'handling_fees_options');
define('HANDLING_FEES_CACHE_GROUP', 'handling_fees');

// Include required files directly instead of using autoloader
require_once HANDLING_FEES_PLUGIN_DIR . 'includes/class-handling-fees-cache.php';
require_once HANDLING_FEES_PLUGIN_DIR . 'includes/class-handling-fees-renderer.php';
require_once HANDLING_FEES_PLUGIN_DIR . 'includes/class-handling-fees-calculator.php';
require_once HANDLING_FEES_PLUGIN_DIR . 'includes/class-handling-fees-admin.php';
require_once HANDLING_FEES_PLUGIN_DIR . 'includes/class-handling-fees-plugin.php';

// Initialize the plugin
function init_plugin() {
    // Load text domain for translations
    load_plugin_textdomain('wc-handling-fees', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize main plugin class
    HandlingFeesPlugin::getInstance();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\init_plugin');

// Register activation/deactivation hooks
register_activation_hook(__FILE__, [__NAMESPACE__ . '\\HandlingFeesPlugin', 'activate']);
register_deactivation_hook(__FILE__, [__NAMESPACE__ . '\\HandlingFeesPlugin', 'deactivate']);
