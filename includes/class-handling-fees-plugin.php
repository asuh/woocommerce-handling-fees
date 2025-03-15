<?php
/**
 * Core plugin class
 *
 * @package HandlingFees
 */

namespace HandlingFees;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Main plugin class
 */
class HandlingFeesPlugin {
  /**
   * Singleton instance
   */
  private static $instance = null;

  /**
   * Admin functionality instance
   */
  private $admin;

  /**
   * Calculator instance
   */
  private $calculator;

  /**
   * Cache manager instance
   */
  private $cache;

  /**
   * Get the singleton instance
   *
   * @return HandlingFeesPlugin
   */
  public static function getInstance(): self {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Private constructor to prevent direct instantiation
   */
  private function __construct() {
    $this->initComponents();
    $this->registerHooks();
  }

  /**
   * Initialize plugin components
   */
  private function initComponents(): void {
    $this->cache = new HandlingFeesCache();
    $this->calculator = new HandlingFeesCalculator($this->cache);
    $this->admin = new HandlingFeesAdmin($this->cache);
  }

  /**
   * Register all hooks for the plugin
   */
  private function registerHooks(): void {
    // WooCommerce hooks for fee calculation
    add_filter('woocommerce_shipping_rate_cost', [$this->calculator, 'applyHandlingFees'], 10, 2);
    
    // WooCommerce Settings API integration
    add_filter('woocommerce_get_settings_pages', [$this, 'addHandlingSettingsPage']);
    
    // Clear caches when cart is updated
    add_action('woocommerce_cart_updated', [$this->cache, 'clearHandlingFeesCache']);
    add_action('woocommerce_checkout_update_order_review', [$this->cache, 'clearHandlingFeesCache']);
  }

  /**
   * Add the Handling settings page to WooCommerce
   *
   * @param array $settings Array of WC_Settings_Page objects
   * @return array Modified array of WC_Settings_Page objects
   */
  public function addHandlingSettingsPage($settings) {
    $settings[] = include HANDLING_FEES_PLUGIN_DIR . 'includes/class-wc-settings-handling.php';
    return $settings;
  }

  /**
   * Plugin activation hook
   */
  public static function activate(): void {
    // Set default options
    $default_options = [
      'shipping_classes' => [],
      'class_settings' => []
    ];
    
    add_option(HANDLING_FEES_OPTION_NAME, $default_options);
  }

  /**
   * Plugin deactivation hook
   */
  public static function deactivate(): void {
    // Clear any cached data
    $cache = new HandlingFeesCache();
    $cache->clearHandlingFeesCache();
  }
}
