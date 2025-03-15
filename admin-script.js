document.addEventListener('DOMContentLoaded', () => {
  // Find elements within the WooCommerce settings context
  const shippingClassList = document.getElementById('shipping-class-list');
  const handlingFeeSettings = document.getElementById('handling-fee-settings');
  
  // If elements don't exist, we're not on the handling settings page
  if (!shippingClassList || !handlingFeeSettings) {
    return;
  }
  
  // Debug function to help troubleshoot
  const debug = (message, data) => {
    if (window.debugHandlingFees) {
      console.log(`[Handling Fees] ${message}`, data);
    }
  };
  
  // Enable debug mode
  window.debugHandlingFees = true;
  
  // Function to load class settings via AJAX using Fetch API
  const loadClassSettings = async (classSlug) => {
    try {
      debug(`Loading settings for class: ${classSlug}`);
      
      const formData = new FormData();
      formData.append('action', 'get_class_settings_field');
      formData.append('class_slug', classSlug);
      formData.append('nonce', ajax_object.nonce);

      const response = await fetch(ajax_object.ajax_url, {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();
      
      if (data.success) {
        // Insert the HTML returned from the server
        handlingFeeSettings.insertAdjacentHTML('beforeend', data.data);

        // Find the newly added panel and ensure tier fields are displayed
        const newPanel = findSettingsPanelByClassSlug(classSlug);
        if (newPanel) {
          const tierSelect = newPanel.querySelector('.tier-count-select');
          if (tierSelect) {
            // Force a tier count change event to generate the tier fields
            const tierCount = Number.parseInt(tierSelect.value, 10);
            
            // Only force update if tier count is > 0 but tier fields are empty
            const tierSettingsContainer = newPanel.querySelector('.tier-settings');
            const tierFields = tierSettingsContainer.querySelectorAll('.fee-tier-field');
            
            if (tierCount > 0 && tierFields.length === 0) {
              debug(`Forcing tier field generation for ${classSlug} with count ${tierCount}`);
              handleTierCountChange({ target: tierSelect });
            }
          }
        }
                
        // Initialize any JS functionality for the newly added content
        initializeTierCountHandlers();
        
        // Initialize Select2 for the new elements if needed
        if (jQuery?.fn.select2) {
          jQuery('.wc-enhanced-select').select2();
        }
      } else {
        console.error('Error loading settings:', data.data);
      }
    } catch (error) {
      console.error('Error fetching class settings:', error);
    }
  };

  // Function to find a settings panel by class slug
  const findSettingsPanelByClassSlug = (classSlug) => {
    // Search for any panel that contains the class slug in its ID
    const panels = document.querySelectorAll(`.handling-fee-class-row[id*="${classSlug}"]`);
    return panels.length > 0 ? panels[0] : null;
  };
  
  // Handler for tier count changes
  const handleTierCountChange = (event) => {
    const select = event.target;
    const classSlug = select.dataset.classSlug;
    const tierCount = Number.parseInt(select.value, 10);
    
    debug(`Tier count changed for ${classSlug} to ${tierCount}`);
    
    // Find the settings panel
    const settingsPanel = findSettingsPanelByClassSlug(classSlug);
    if (!settingsPanel) {
      debug(`No settings panel found for ${classSlug}`);
      return;
    }
    
    // Get the tier settings container
    const tierSettingsContainer = settingsPanel.querySelector('.tier-settings');
    if (!tierSettingsContainer) {
      debug(`No tier settings container found for ${classSlug}`);
      return;
    }
    
    // Collect existing tier values
    const existingTiers = {};
    for (const input of tierSettingsContainer.querySelectorAll('.fee-tier-field input')) {
      const matches = input.name.match(/\[rates\]\[(\d+)\]/);
      if (matches?.[1]) {
        const tierNumber = Number.parseInt(matches[1], 10);
        existingTiers[tierNumber] = input.value;
        debug(`Found existing tier ${tierNumber} with value ${input.value}`);
      }
    }
    
    // Get shipping class name
    let className = classSlug;
    const classNameElement = settingsPanel.querySelector('th label');
    if (classNameElement) {
      className = classNameElement.textContent.trim();
    }
    const classNameLower = className.toLowerCase();
    
    // Generate a unique suffix for field IDs
    const uniqueSuffix = settingsPanel.id.replace('class-settings-', '');
    
    // Build new tier fields HTML
    let tierFieldsHtml = `<h3>Handling fees for ${classNameLower}</h3>`;
    
    // Add explanation if there are multiple tiers
    if (tierCount > 1) {
      tierFieldsHtml += `
        <div class="tier-explanation">
          <span class="dashicons dashicons-lightbulb"></span>
          <strong>How handling fee tiers work:</strong> 
          The first handling fee tier applies when there is 1 ${classNameLower}, 
          The second handling fee tier applies when there are 2 ${classNameLower}, and so on. 
          For quantities higher than your highest tier, the highest tier fee will be used.
        </div>
      `;
    }
    
    // Add tier fields
    for (let i = 1; i <= tierCount; i++) {
      const tierId = `handling_fees_options-${uniqueSuffix}-rate-${i}`;
      const tierValue = existingTiers[i] || '';
      
      tierFieldsHtml += `
        <p class="form-field fee-tier-field fee-tier-field-${classSlug}">
          <label for="${tierId}">
            ${i} ${classNameLower}:
          </label>
          
          <input 
            type="text" 
            id="${tierId}"
            name="handling_fees_options[class_settings][${classSlug}][rates][${i}]" 
            value="${tierValue}"
            class="wc_input_price" 
            placeholder="0.00"
            aria-label="Handling fee for ${i} ${classNameLower}"
          />
        </p>
      `;
    }
    
    // Update the tier settings container
    tierSettingsContainer.innerHTML = tierFieldsHtml;
    
    debug(`Updated tier fields for ${classSlug}`);
  };
  
  // Initialize tier count change handlers - using event delegation
  const initializeTierCountHandlers = () => {
    // Remove any existing event listener
    document.removeEventListener('change', delegatedChangeHandler);
    
    // Add the event listener using event delegation
    document.addEventListener('change', delegatedChangeHandler);
    
    debug('Initialized tier count handlers using event delegation');
  };
  
  // Event delegation handler for change events
  const delegatedChangeHandler = (event) => {
    const target = event.target;
    
    // Check if the changed element is a tier count select
    if (target.classList.contains('tier-count-select')) {
      debug(`Delegated handler caught change on select for ${target.dataset.classSlug}`);
      handleTierCountChange(event);
    }
  };

  // Initial load of settings for checked classes
  if (shippingClassList) {
    for (const checkbox of shippingClassList.querySelectorAll('.shipping-class-checkbox:checked')) {
      const classSlug = checkbox.value;
      if (!findSettingsPanelByClassSlug(classSlug)) {
        loadClassSettings(classSlug);
      }
    }

    // Change event listener for checkboxes
    shippingClassList.addEventListener('change', (event) => {
      if (event.target?.classList.contains('shipping-class-checkbox')) {
        const classSlug = event.target.value;
        const isChecked = event.target.checked;
        const settingsPanel = findSettingsPanelByClassSlug(classSlug);

        if (isChecked) {
          if (!settingsPanel) {
            loadClassSettings(classSlug);
          }
        } else if (settingsPanel) {
          settingsPanel.remove();
        }
      }
    });
  }

  // Initialize handlers for existing content
  initializeTierCountHandlers();
  
  /*
   * Add compatibility with WooCommerce settings page and Select2
   * Note: jQuery must be used here because it's required by Select2 
   */
  jQuery(($) => {
    // Handle Select2 changes specifically
    $(document).on('change', '.tier-count-select', function() {
      const event = { target: this };
      handleTierCountChange(event);
    });
    
    // Also listen for Select2's specific events
    $(document).on('select2:select', '.tier-count-select', function(e) {
      const event = { target: this };
      handleTierCountChange(event);
    });
    
    // If WooCommerce has a form submission handler, make sure our dynamic content is included
    $('form.woocommerce-settings-form').on('submit', () => {
      // Any pre-submission processing can go here if needed
    });
  });
});
