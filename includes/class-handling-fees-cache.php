<?php
/**
 * Caching functionality
 *
 * @package HandlingFees
 */

namespace HandlingFees;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Handles caching operations
 */
class HandlingFeesCache {
  /**
   * Get plugin options with caching
   * 
   * @return array Plugin options
   */
  public function getOptions(): array {
    static $options = null;
    
    if ($options === null) {
      $cache_key = 'plugin_options';
      $cached_options = $this->getCachedValue($cache_key);
      
      if ($cached_options === false) {
        $options = get_option(HANDLING_FEES_OPTION_NAME, []);
        $this->setCachedValue($cache_key, $options, HOUR_IN_SECONDS);
      } else {
        $options = $cached_options;
      }
    }
    
    return $options;
  }

  /**
   * Clear plugin options cache
   */
  public function clearPluginOptionsCache(): void {
    wp_cache_delete('plugin_options', HANDLING_FEES_CACHE_GROUP);
  }

  /**
   * Get all shipping classes with caching
   * 
   * @return array Array of shipping class terms
   */
  public function getAllShippingClasses(): array {
    static $shipping_classes = null;
    
    if ($shipping_classes === null) {
      $cache_key = 'all_shipping_classes';
      $cached_classes = $this->getCachedValue($cache_key);
      
      if ($cached_classes === false) {
        $shipping_classes = get_terms([
          'taxonomy' => 'product_shipping_class',
          'hide_empty' => false,
        ]);
        
        if (!is_wp_error($shipping_classes)) {
          $this->setCachedValue($cache_key, $shipping_classes, HOUR_IN_SECONDS);
        } else {
          $shipping_classes = [];
        }
      } else {
        $shipping_classes = $cached_classes;
      }
    }
    
    return $shipping_classes;
  }

  /**
   * Cache class settings field HTML
   *
   * @param string $class_slug Shipping class slug
   * @param string $html HTML content
   * @param string $instance_id Optional instance ID
   */
  public function cacheClassSettingsField(string $class_slug, string $html, string $instance_id = ''): void {
    $cache_key = 'field_html_' . $class_slug . ($instance_id ? '_' . $instance_id : '');
    $this->setCachedValue($cache_key, $html, HOUR_IN_SECONDS);
  }

  /**
   * Get cached class settings field HTML
   *
   * @param string $class_slug Shipping class slug
   * @param string $instance_id Optional instance ID
   * @return string|false HTML content or false if not cached
   */
  public function getCachedClassSettingsField(string $class_slug, string $instance_id = '') {
    $cache_key = 'field_html_' . $class_slug . ($instance_id ? '_' . $instance_id : '');
    return $this->getCachedValue($cache_key);
  }

  /**
   * Clear all handling fees caches
   */
  public function clearHandlingFeesCache(): void {
    wp_cache_flush_group(HANDLING_FEES_CACHE_GROUP);
  }

  /**
   * Get a cached value
   *
   * @param string $key Cache key
   * @return mixed Cached value or false if not found
   */
  public function getCachedValue(string $key) {
    return wp_cache_get($key, HANDLING_FEES_CACHE_GROUP);
  }

  /**
   * Set a cached value
   *
   * @param string $key Cache key
   * @param mixed $value Value to cache
   * @param int $expiration Expiration time in seconds
   * @return bool Success or failure
   */
  public function setCachedValue(string $key, $value, int $expiration = 0): bool {
    return wp_cache_set($key, $value, HANDLING_FEES_CACHE_GROUP, $expiration);
  }
}
