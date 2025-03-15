<?php
/**
 * HTML rendering functionality
 *
 * @package HandlingFees
 */

namespace HandlingFees;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Handles HTML rendering for admin UI
 */
class HandlingFeesRenderer {
  /**
   * Cache manager instance
   */
  private $cache;

  /**
   * Constructor
   */
  public function __construct() {
    $this->cache = new HandlingFeesCache();
  }

  /**
   * Render the Shipping Classes field (checkboxes)
   *
   * @param array $field Field data
   * @param array $selected_classes Selected shipping classes
   * @param array $shipping_classes All shipping classes
   * @return string HTML output
   */
  public function renderShippingClassesField(array $field, array $selected_classes, array $shipping_classes): string {
    ob_start();
    ?>
    <!-- Main section header -->
    <tr valign="top">
      <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($field['id']); ?>"><?php echo esc_html($field['title']); ?></label>
      </th>
      <td class="forminp forminp-<?php echo esc_attr($field['type']); ?>">
        <?php if (!empty($shipping_classes) && !is_wp_error($shipping_classes)): ?>
          <p class="description"><?php _e('Select the shipping classes that require handling fees.', 'wc-handling-fees'); ?></p>
          <div id="shipping-class-list">
            <?php foreach ($shipping_classes as $shipping_class): 
              $checked = in_array($shipping_class->slug, $selected_classes) ? 'checked="checked"' : '';
            ?>
              <label>
                <input type="checkbox" 
                       name="<?php echo esc_attr(HANDLING_FEES_OPTION_NAME); ?>[shipping_classes][]" 
                       value="<?php echo esc_attr($shipping_class->slug); ?>" 
                       <?php echo $checked; ?> 
                       class="shipping-class-checkbox" />
                <?php echo esc_html($shipping_class->name); ?>
              </label><br>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>
            <?php _e('No shipping classes found.', 'wc-handling-fees'); ?> 
            <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_shipping_class&post_type=product'); ?>">
              <?php _e('Add shipping classes here', 'wc-handling-fees'); ?>
            </a>.
          </p>
        <?php endif; ?>
      </td>
    </tr>
  
    <!-- Container for dynamically added class settings rows -->
    <tbody id="handling-fee-settings">
      <?php foreach ($selected_classes as $class_slug): ?>
        <?php echo $this->renderClassSettingsField($class_slug); ?>
      <?php endforeach; ?>
    </tbody>
    <?php
    return ob_get_clean();
  }

  /**
   * Render the settings fields for a specific shipping class
   *
   * @param string $class_slug The shipping class slug
   * @param string $instance_id Optional unique identifier to prevent ID collisions
   * @return string HTML output
   */
  public function renderClassSettingsField(string $class_slug, string $instance_id = ''): string {
    // Check cache first
    $cached_html = $this->cache->getCachedClassSettingsField($class_slug, $instance_id);
    if ($cached_html !== false) {
      return $cached_html;
    }
    
    $options = $this->cache->getOptions();
    $class_settings = $options['class_settings'][$class_slug] ?? [];
    $apply_with_others = $class_settings['apply_with_others'] ?? false;
    $tier_count = $class_settings['tier_count'] ?? $default_tier_count;
    $rates = $class_settings['rates'] ?? [];
  
    // Generate a unique ID suffix if not provided
    if (empty($instance_id)) {
      $instance_id = uniqid();
    }
    
    $unique_suffix = esc_attr($class_slug . '-' . $instance_id);
    
    // Get shipping class name - use cached data if possible
    $shipping_class_name = '';
    $shipping_classes = $this->cache->getAllShippingClasses();
    foreach ($shipping_classes as $class) {
      if ($class->slug === $class_slug) {
        $shipping_class_name = $class->name;
        break;
      }
    }
    
    $class_name = !empty($shipping_class_name) ? esc_html($shipping_class_name) : esc_html($class_slug);
    
    // Create a lowercase version for natural language
    $class_name_lower = strtolower($class_name);
  
    // Create unique anchor names for this instance
    $apply_anchor = "--tooltip-apply-$unique_suffix";
    $tiers_anchor = "--tooltip-tiers-$unique_suffix";
  
    ob_start();
    ?>
    <!-- Class settings header row -->
    <tr valign="top" id="class-settings-<?php echo $unique_suffix; ?>" class="handling-fee-class-row">
      <th scope="row" class="titledesc">
        <label><?php echo $class_name; ?></label>
      </th>
      <td class="forminp">
        <div class="handling-fee-class-settings">
          <p class="form-field">
            <label class='apply-with-others-label'>
              <input type='checkbox' 
                    name='<?php echo esc_attr(HANDLING_FEES_OPTION_NAME); ?>[class_settings][<?php echo esc_attr($class_slug); ?>][apply_with_others]' 
                    <?php echo $apply_with_others ? 'checked="checked"' : ''; ?> /> 
              <?php _e('Apply handling fee even with other classes present', 'wc-handling-fees'); ?>
              <button type="button" 
                      id="tooltip-btn-apply-<?php echo $unique_suffix; ?>"
                      class="tooltip-button" 
                      aria-label="Help" 
                      style="anchor-name: <?php echo $apply_anchor; ?>;"
                      popovertarget="popover-apply-<?php echo $unique_suffix; ?>">
                <span class="dashicons dashicons-info"></span>
              </button>
            </label>
            
            <div id="popover-apply-<?php echo $unique_suffix; ?>" 
              popover="auto" 
              class="tooltip-popover"
              style="inset-block-start: anchor(<?php echo $apply_anchor; ?> bottom); inset-inline-start: anchor(<?php echo $apply_anchor; ?> right);">
              <?php printf(__('If checked, this handling fee will apply even when products from other shipping classes are in the cart. If unchecked, this fee will only apply when the cart contains only %s products.', 'wc-handling-fees'), $class_name_lower); ?>
            </div>
          </p>
  
          <p class="form-field">
            <label for='tier-count-<?php echo $unique_suffix; ?>'>
              <?php _e('Number of Quantity Tiers:', 'wc-handling-fees'); ?>
              <button type="button"
                      id="tooltip-btn-tiers-<?php echo $unique_suffix; ?>"
                      class="tooltip-button" 
                      aria-label="Help" 
                      style="anchor-name: <?php echo $tiers_anchor; ?>;"
                      popovertarget="popover-tiers-<?php echo $unique_suffix; ?>">
                <span class="dashicons dashicons-info"></span>
              </button>
            </label>
            
            <div id="popover-tiers-<?php echo $unique_suffix; ?>" 
              popover="auto" 
              class="tooltip-popover"
              style="inset-block-start: anchor(<?php echo $tiers_anchor; ?> bottom); inset-inline-start: anchor(<?php echo $tiers_anchor; ?> right);">
              <?php printf(__('Set how many quantity-based handling fee tiers to configure. For example, if you want different handling fees for 1, 2, or 3+ %s, select 3 tiers.', 'wc-handling-fees'), $class_name_lower); ?>
            </div>
            
            <select id='tier-count-<?php echo $unique_suffix; ?>' 
                    name='<?php echo esc_attr(HANDLING_FEES_OPTION_NAME); ?>[class_settings][<?php echo esc_attr($class_slug); ?>][tier_count]' 
                    class='tier-count-select wc-enhanced-select' 
                    data-class-slug='<?php echo esc_attr($class_slug); ?>'>
              <?php for ($i = 1; $i <= 10; $i++) { ?>
                <option value='<?php echo esc_attr($i); ?>' <?php echo $tier_count == $i
                  ? 'selected="selected"'
                  : ''; ?>><?php echo esc_html($i); ?></option>
              <?php } ?>
            </select>
          </p>
  
          <div class='tier-settings'>
            <?php if ($tier_count > 1): // Only show explanation if there's more than one tier ?>
              <div class="tier-explanation">
                <span class="dashicons dashicons-lightbulb"></span>
                <strong><?php _e('How handling fee tiers work:', 'wc-handling-fees'); ?></strong> 
                <?php printf(__('The first handling fee tier applies when there is 1 %1$s, The second handling fee tier applies when there are 2 %1$s, and so on. For quantities higher than your highest tier, the highest tier fee will be used.', 'wc-handling-fees'), $class_name_lower); ?>
              </div>
            <?php endif; ?>
            <h3>Handling fees for <?php echo $class_name_lower; ?></h3>
            <?php for ($i = 1; $i <= $tier_count; $i++) {
              $rate = isset($rates[$i]) ? esc_attr($rates[$i]) : ''; 
              $tier_id = esc_attr(HANDLING_FEES_OPTION_NAME) . '-' . $unique_suffix . '-rate-' . esc_attr($i);
              ?>
              <p class='form-field fee-tier-field fee-tier-field-<?php echo esc_attr($class_slug); ?>'>
                <label for='<?php echo $tier_id; ?>'>
                  <?php printf(__('%1$d %2$s:', 'wc-handling-fees'), $i, $class_name_lower); ?>
                </label>
                
                <input 
                  type='text' 
                  id='<?php echo $tier_id; ?>'
                  name='<?php echo esc_attr(HANDLING_FEES_OPTION_NAME); ?>[class_settings][<?php echo esc_attr($class_slug); ?>][rates][<?php echo esc_attr($i); ?>]' 
                  value='<?php echo $rate; ?>' 
                  class='wc_input_price' 
                  placeholder="0.00"
                  aria-label="<?php printf(__('Handling fee for %1$d %2$s', 'wc-handling-fees'), $i, $class_name_lower); ?>"
                />
              </p>
            <?php } ?>
          </div>
        </div>
      </td>
    </tr>
    <?php
    $html = ob_get_clean();
    
    // Cache the generated HTML
    $this->cache->cacheClassSettingsField($class_slug, $html, $instance_id);
    
    return $html;
  }
}
