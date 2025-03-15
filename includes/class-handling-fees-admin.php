<?php
/**
 * Admin functionality
 *
 * @package HandlingFees
 */

namespace HandlingFees;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Handles all admin-related functionality
 */
class HandlingFeesAdmin {
  /**
   * Cache manager instance
   */
  private $cache;

  /**
   * Renderer instance
   */
  private $renderer;

  /**
   * Constructor
   *
   * @param HandlingFeesCache $cache Cache manager
   */
  public function __construct(HandlingFeesCache $cache) {
    $this->cache = $cache;
    $this->renderer = new HandlingFeesRenderer();
    $this->registerHooks();
  }

  /**
   * Register admin hooks
   */
  private function registerHooks(): void {
    // Admin hooks
    add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    add_action('admin_init', [$this, 'registerSettings']);
    
    // AJAX hooks
    add_action('wp_ajax_get_class_settings_field', [$this, 'getClassSettingsFieldCallback']);
    
    // Register custom field type
    add_action('woocommerce_admin_field_handling_shipping_classes', [$this, 'renderShippingClassesField']);
  }

  /**
   * Enqueue admin scripts and styles
   *
   * @param string $hook Current admin page hook
   */
  public function enqueueAdminScripts(string $hook): void {
    // Only load on WooCommerce settings pages
    if (strpos($hook, 'woocommerce_page_wc-settings') === false) {
      return;
    }

    // Check if we're on the handling tab
    if (isset($_GET['tab']) && $_GET['tab'] === 'handling') {
      wp_enqueue_style(
        'handling-fees-admin-styles',
        HANDLING_FEES_PLUGIN_URL . '/admin-styles.css',
        [],
        HANDLING_FEES_VERSION
      );

      wp_enqueue_script(
        'handling-fees-admin-script',
        HANDLING_FEES_PLUGIN_URL . '/admin-script.js',
        ['jquery'],
        HANDLING_FEES_VERSION,
        true
      );

      wp_localize_script(
        'handling-fees-admin-script',
        'ajax_object',
        [
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('handling_fees_nonce')
        ]
      );
    }
  }

  /**
   * Register and define the settings
   */
  public function registerSettings(): void {
    // Register the setting
    register_setting(
      'handling_fees_settings',
      HANDLING_FEES_OPTION_NAME,
      [$this, 'sanitizeHandlingFeesOptions']
    );
  }

  /**
   * Sanitize the input values
   *
   * @param array $input Input values
   * @return array Sanitized values
   */
  public function sanitizeHandlingFeesOptions(array $input): array {
    $sanitized = [];

    // Get valid shipping classes for validation
    $all_shipping_classes = $this->cache->getAllShippingClasses();
    $valid_class_slugs = array_map(function($class) {
      return $class->slug;
    }, $all_shipping_classes);

    // Sanitize selected shipping classes
    $sanitized['shipping_classes'] = [];
    if (isset($input['shipping_classes']) && is_array($input['shipping_classes'])) {
      foreach ($input['shipping_classes'] as $class_slug) {
        // Verify the shipping class exists
        if (in_array($class_slug, $valid_class_slugs, true)) {
          $sanitized['shipping_classes'][] = sanitize_text_field($class_slug);
        }
      }
    }

    // Sanitize per-class settings
    $sanitized['class_settings'] = [];
    if (isset($input['class_settings']) && is_array($input['class_settings'])) {
      foreach ($input['class_settings'] as $class_slug => $settings) {
        // Verify the class slug is valid
        if (!in_array($class_slug, $sanitized['shipping_classes'])) {
          continue;
        }
        
        $sanitized['class_settings'][$class_slug] = [
          'apply_with_others' => isset($settings['apply_with_others']),
          'tier_count' => isset($settings['tier_count']) 
            ? absint($settings['tier_count']) 
            : 0,
        ];

        // Sanitize the fee rates
        $sanitized['class_settings'][$class_slug]['rates'] = [];
        if (
          isset($settings['rates']) &&
          is_array($settings['rates']) &&
          isset($sanitized['class_settings'][$class_slug]['tier_count'])
        ) {
          $tier_count = $sanitized['class_settings'][$class_slug]['tier_count'];
          for ($i = 1; $i <= $tier_count; $i++) {
            $sanitized['class_settings'][$class_slug]['rates'][$i] = isset($settings['rates'][$i])
              ? floatval($settings['rates'][$i])
              : 0;
          }
        }
      }
    }

    // Clear caches when settings are updated
    $this->cache->clearPluginOptionsCache();
    
    return $sanitized;
  }

  /**
   * Render the Shipping Classes field (checkboxes)
   *
   * @param array $field Field data
   */
  public function renderShippingClassesField($field): void {
    $options = $this->cache->getOptions();
    $selected_classes = $options['shipping_classes'] ?? [];
    $shipping_classes = $this->cache->getAllShippingClasses();
    
    echo $this->renderer->renderShippingClassesField($field, $selected_classes, $shipping_classes);
  }
    
  /**
   * AJAX handler to generate the class settings field
   */
  public function getClassSettingsFieldCallback(): void {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'handling_fees_nonce')) {
      wp_send_json_error('Security check failed');
      return;
    }
    
    if (!isset($_POST['class_slug'])) {
      wp_send_json_error('Missing class slug');
      return;
    }
    
    try {
      $class_slug = sanitize_text_field($_POST['class_slug']);
      $default_tier_count = isset($_POST['default_tier_count']) ? intval($_POST['default_tier_count']) : 1;      
      $html = $this->renderer->renderClassSettingsField($class_slug, '', $default_tier_count);
      $this->cache->cacheClassSettingsField($class_slug, $html);
      wp_send_json_success($html);
    } catch (\Exception $e) {
      error_log('Handling Fees Config error: ' . $e->getMessage());
      wp_send_json_error('An error occurred: ' . $e->getMessage());
    }
  }
}
