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
   * Cached plugin options for this request.
   */
  private $options = null;

  /**
   * Cached shipping classes for this request.
   */
  private $shipping_classes = null;

  /**
   * Get plugin options with caching
   * 
   * @return array Plugin options
   */
  public function getOptions(): array {
    if ($this->options === null) {
      $cache_key = 'plugin_options';
      $cached_options = $this->getCachedValue($cache_key);
      
      if ($cached_options === false) {
        $this->options = get_option(HANDLING_FEES_OPTION_NAME, []);
        $this->setCachedValue($cache_key, $this->options, HOUR_IN_SECONDS);
      } else {
        $this->options = $cached_options;
      }
    }
    
    return $this->options;
  }

  /**
   * Clear plugin options cache
   */
  public function clearPluginOptionsCache(): void {
    $this->options = null;
    wp_cache_delete('plugin_options', HANDLING_FEES_CACHE_GROUP);
  }

  /**
   * Get all shipping classes with caching
   * 
   * @return array Array of shipping class terms
   */
  public function getAllShippingClasses(): array {
    if ($this->shipping_classes === null) {
      $cache_key = 'all_shipping_classes';
      $cached_classes = $this->getCachedValue($cache_key);
      
      if ($cached_classes === false) {
        $this->shipping_classes = get_terms([
          'taxonomy' => 'product_shipping_class',
          'hide_empty' => false,
        ]);
        
        if (!is_wp_error($this->shipping_classes)) {
          $this->setCachedValue($cache_key, $this->shipping_classes, HOUR_IN_SECONDS);
        } else {
          $this->shipping_classes = [];
        }
      } else {
        $this->shipping_classes = $cached_classes;
      }
    }
    
    return $this->shipping_classes;
  }

  /**
   * Clear all handling fees caches
   */
  public function clearHandlingFeesCache(): void {
    $this->options = null;
    $this->shipping_classes = null;

    if (function_exists('wp_cache_flush_group')) {
      wp_cache_flush_group(HANDLING_FEES_CACHE_GROUP);
      return;
    }

    wp_cache_delete('plugin_options', HANDLING_FEES_CACHE_GROUP);
    wp_cache_delete('all_shipping_classes', HANDLING_FEES_CACHE_GROUP);
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
