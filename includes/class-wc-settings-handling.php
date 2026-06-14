<?php
/**
 * WooCommerce Handling Settings
 *
 * @package HandlingFees
 */

namespace HandlingFees;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * WC_Settings_Handling Class
 */
class WC_Settings_Handling extends \WC_Settings_Page {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->id    = 'handling';
    $this->label = __('Handling', 'wc-handling-fees');

    parent::__construct();
  }

  /**
   * Get settings array
   *
   * @return array
   */
  public function get_settings() {
    $settings = array(
      array(
        'title' => __('Handling Fee Settings', 'wc-handling-fees'),
        'type'  => 'title',
        'desc'  => __('Configure handling fees based on shipping classes and product quantities.', 'wc-handling-fees'),
        'id'    => 'handling_fees_options_section'
      ),
      
      array(
        'title'    => __('Shipping Classes with Handling Fees', 'wc-handling-fees'),
        'desc'     => '',
        'id'       => 'handling_fees_shipping_classes',
        'type'     => 'handling_shipping_classes',
      ),
      
      array(
        'type' => 'sectionend',
        'id'   => 'handling_fees_options_section'
      )
    );

    return apply_filters('woocommerce_handling_settings', $settings);
  }

  /**
   * Output the settings
   */
  public function output() {
    $settings = $this->get_settings();
    \WC_Admin_Settings::output_fields($settings);
  }

  /**
   * Save settings
   */
  public function save() {
    if (!current_user_can('manage_woocommerce')) {
      return;
    }

    // We're handling the saving in the admin class
    // through the registered setting
    $options = isset($_POST[HANDLING_FEES_OPTION_NAME]) ? wp_unslash($_POST[HANDLING_FEES_OPTION_NAME]) : [];
    if (is_array($options)) {
      $admin = new HandlingFeesAdmin(new HandlingFeesCache(), false);
      $sanitized = $admin->sanitizeHandlingFeesOptions($options);
      update_option(HANDLING_FEES_OPTION_NAME, $sanitized);
    }
  }
}

// Return the settings page instance
return new WC_Settings_Handling();
