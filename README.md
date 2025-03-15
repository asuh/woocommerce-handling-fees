# WooCommerce Handling Fees

**WooCommerce Handling Fees** is a WordPress plugin that lets you add configurable handling fees to your WooCommerce store based on shipping classes and product quantities in the cart.

## Features

- **Flexible Fee Configuration:**  
  Assign handling fees per shipping class with a dynamic tier-based system.

- **Tiered Fee Structure:**  
  Set different fee rates based on the quantity of products in the cart. For example, you can configure a fee for 1 item, another for 2 items, and so on; if the quantity exceeds your highest defined tier, the highest fee applies.

- **Selective Fee Application:**  
  Choose whether the handling fee should apply even when products from multiple shipping classes are in the cart.

- **WooCommerce Integration:**  
  Seamlessly integrates with WooCommerce by using its settings API and shipping filter hooks. The fee is automatically added to shipping rates based on cart contents.

## Requirements

- **WordPress:** Version 6 or higher.
- **WooCommerce:** Verion 9.7.1 or higher installed and activated.
- **PHP:** Compatible with PHP 8.3 or higher.

## Installation

1. **Upload the Plugin:**  
   Copy the plugin folder to your site's `/wp-content/plugins/` directory.

2. **Activate the Plugin:**  
   Go to the **Plugins** menu in your WordPress admin area and activate **WooCommerce Handling Fees**.

3. **Configure Handling Fees:**  
   Navigate to **WooCommerce > Settings > Handling**. From there, select the shipping classes that should have handling fees and configure the fee tiers accordingly.

## Usage

1. **Select Shipping Classes:**  
   On the Handling Fees settings page, you can select one or more shipping classes. If no shipping classes are shown, make sure you have added some via **WooCommerce > Settings > Shipping > Classes**.

2. **Configure Fee Tiers for Each Class:**  
   - For each selected shipping class, determine the number of handling fee tiers you wish to use.
   - Enter a handling fee for each tier. For example, if you set three tiers, you can configure fees for 1 item, 2 items, and 3+ items.
   - Use the checkbox option to enable fees when products from other shipping classes are present in the cart.

3. **Save Settings:**  
   Once you’ve configured your fee schedules, save your settings. The handling fee will be automatically applied to your shipping rate at checkout based on the cart’s contents.

## How It Works

- The plugin hooks into WooCommerce by filtering the shipping cost via the `woocommerce_shipping_rate_cost` hook. It calculates the total number of products in the cart per shipping class and applies the appropriate fee tier.
- The admin interface provides dynamic feedback and settings using AJAX, making it easier to manage fee tiers for each shipping class.
- The settings are stored in the WordPress options table under the option name `handling_fees_options`.

## Developer Notes

- **Singleton Pattern:**  
  The plugin is implemented as a singleton through the `HandlingFeesPlugin` class to ensure only one instance is active.

- **Settings Integration:**  
  A custom settings page is included via `includes/class-wc-settings-handling.php` to integrate with the WooCommerce settings API.

- **AJAX Endpoints:**  
  The AJAX endpoint (`wp_ajax_get_class_settings_field`) dynamically renders the fee fields for each shipping class, which allows for a smoother admin experience.

## Version History

- **1.0.0**
  - Initial public release of the plugin.
  - Added dynamic, tiered handling fees per shipping class with configurable options.
  - Integrated AJAX for dynamic field management in the admin interface.
  - Enhanced WooCommerce settings API integration for seamless configuration.

## License

This plugin is released under the [GPLv2 or later](LICENSE) license.

## Contributing

Contributions are welcome! If you would like to contribute improvements or fixes:
- Fork the repository.
- Create a feature branch.
- Submit a pull request with detailed explanations.
