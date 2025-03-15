<?php
/**
 * Fee calculation functionality
 *
 * @package HandlingFees
 */

namespace HandlingFees;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Handles fee calculation logic
 */
class HandlingFeesCalculator {
  /**
   * Cache manager instance
   */
  private $cache;

  /**
   * Constructor
   *
   * @param HandlingFeesCache $cache Cache manager
   */
  public function __construct(HandlingFeesCache $cache) {
    $this->cache = $cache;
  }

  /**
   * Apply handling fees based on cart contents
   *
   * @param float $cost The shipping cost
   * @param mixed $method The shipping method
   * @return float Modified shipping cost with handling fees
   */
  public function applyHandlingFees(float $cost, $method): float {
    try {
      // Generate a cache key based on cart contents and method
      $cart_hash = WC()->cart ? WC()->cart->get_cart_hash() : '';
      $method_id = is_object($method) && method_exists($method, 'get_id') ? $method->get_id() : 'default';
      $cache_key = "handling_fee_{$cart_hash}_{$method_id}";
      
      // Check if we have a cached result
      $cached_cost = $this->cache->getCachedValue($cache_key);
      if ($cached_cost !== false) {
        return (float)$cached_cost;
      }
      
      // Get options
      $options = $this->cache->getOptions();
      
      // Get selected shipping classes and class settings
      $shipping_classes = $options['shipping_classes'] ?? [];
      $class_settings = $options['class_settings'] ?? [];
      
      if (empty($shipping_classes) || empty($class_settings)) {
        return $cost;
      }
      
      // Get cart items once
      $cart = WC()->cart;
      if (!$cart) {
        return $cost;
      }
      
      $cart_items = $cart->get_cart();
      if (empty($cart_items)) {
        return $cost;
      }
      
      // Pre-calculate item counts per shipping class
      $class_item_counts = $this->calculateClassItemCounts($cart_items, $shipping_classes);
      
      if (empty($class_item_counts)) {
        return $cost;
      }
      
      // Calculate handling fees
      $original_cost = $cost;
      $cost = $this->calculateTotalHandlingFees($cost, $shipping_classes, $class_settings, $class_item_counts);
      
      // Only cache if we actually modified the cost
      if ($cost !== $original_cost) {
        $this->cache->setCachedValue($cache_key, $cost, MINUTE_IN_SECONDS * 5);
      }
      
      return $cost;
    } catch (\Exception $e) {
      // Log the error but don't disrupt checkout
      error_log('WooCommerce Handling Fees error: ' . $e->getMessage());
      return $cost; // Return original cost on error
    }
  }

  /**
   * Calculate item counts for each shipping class
   *
   * @param array $cart_items Cart items
   * @param array $shipping_classes Shipping classes to check
   * @return array Counts indexed by shipping class
   */
  private function calculateClassItemCounts(array $cart_items, array $shipping_classes): array {
    $class_item_counts = [];
    
    foreach ($cart_items as $item) {
      $product = wc_get_product($item['product_id']);
      if (!$product) {
        continue;
      }
      
      $class = $product->get_shipping_class();
      if (in_array($class, $shipping_classes)) {
        $class_item_counts[$class] = ($class_item_counts[$class] ?? 0) + $item['quantity'];
      }
    }
    
    return $class_item_counts;
  }

  /**
   * Calculate total handling fees
   *
   * @param float $cost Original cost
   * @param array $shipping_classes Shipping classes
   * @param array $class_settings Class settings
   * @param array $class_item_counts Item counts per class
   * @return float Modified cost with handling fees
   */
  private function calculateTotalHandlingFees(
    float $cost, 
    array $shipping_classes, 
    array $class_settings, 
    array $class_item_counts
  ): float {
    foreach ($shipping_classes as $shipping_class) {
      if (!isset($class_settings[$shipping_class]) || !isset($class_item_counts[$shipping_class])) {
        continue;
      }
      
      $settings = $class_settings[$shipping_class];
      
      // Skip if no tiers or not applying with others
      if (empty($settings['tier_count']) || (!$settings['apply_with_others'] && count($class_item_counts) > 1)) {
        continue;
      }
      
      $tier_count = intval($settings['tier_count']);
      $item_count = $class_item_counts[$shipping_class];
      
      // Get appropriate tier rate
      if (isset($settings['rates'])) {
        $tier = min($item_count, $tier_count);
        if ($tier > 0 && isset($settings['rates'][$tier])) {
          $cost += floatval($settings['rates'][$tier]);
        }
      }
    }
    
    return $cost;
  }
}
