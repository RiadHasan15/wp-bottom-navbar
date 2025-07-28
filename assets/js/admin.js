jQuery(document).ready(function($) {
    'use strict';
    
    // CRITICAL: Immediate checkbox state preservation - runs before everything else
    (function() {
        const savedState = localStorage.getItem('wpbnp_form_state');
        if (savedState) {
            try {
                const formData = JSON.parse(savedState);
                if (formData['settings[enabled]'] !== undefined) {
                    // Use MutationObserver to watch for the checkbox and restore it immediately
                    const observer = new MutationObserver(function(mutations) {
                        const checkbox = document.querySelector('input[name="settings[enabled]"][type="checkbox"]');
                        const hiddenField = document.querySelector('input[name="settings[enabled]"][type="hidden"]');
                        
                        if (checkbox) {
                            checkbox.checked = Boolean(formData['settings[enabled]']);
                            console.log('MutationObserver: Restored enabled checkbox (visible) to:', checkbox.checked);
                            observer.disconnect();
                        } else if (hiddenField) {
                            hiddenField.value = formData['settings[enabled]'] ? '1' : '0';
                            console.log('MutationObserver: Restored enabled checkbox (hidden) to:', formData['settings[enabled]']);
                            observer.disconnect();
                        }
                    });
                    
                    // Start observing
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                    
                    // Fallback: try to set it immediately if it already exists
                    setTimeout(() => {
                        const checkbox = document.querySelector('input[name="settings[enabled]"][type="checkbox"]');
                        const hiddenField = document.querySelector('input[name="settings[enabled]"][type="hidden"]');
                        
                        if (checkbox) {
                            checkbox.checked = Boolean(formData['settings[enabled]']);
                            console.log('Immediate fallback: Restored enabled checkbox (visible) to:', checkbox.checked);
                        } else if (hiddenField) {
                            hiddenField.value = formData['settings[enabled]'] ? '1' : '0';
                            console.log('Immediate fallback: Restored enabled checkbox (hidden) to:', formData['settings[enabled]']);
                        }
                        observer.disconnect(); // Clean up observer
                    }, 50);
                }
            } catch (e) {
                console.warn('Error in immediate checkbox restoration:', e);
            }
        }
    })();
    
    // Check if we have admin data available
    if (typeof wpbnp_admin === 'undefined') {
        console.warn('WP Bottom Navigation Pro: Admin data not available');
        return;
    }
    
    // Main admin object
    const WPBottomNavAdmin = {
        settings: wpbnp_admin.settings || {},
        presets: wpbnp_admin.presets || {},
        dashicons: wpbnp_admin.dashicons || {},
        nonce: wpbnp_admin.nonce || '',
        currentTab: 'items',
        
        init: function() {
            this.currentTab = this.getCurrentTab();
            
            // CRITICAL: Immediately save current form state on load to capture any existing values
            setTimeout(() => {
                this.saveFormState();
                console.log('Initial form state saved on page load');
            }, 100);
            
            this.bindEvents();
            this.initializeColorPickers();
            this.setupSortable();
            
            // Initialize form data after DOM is ready
            setTimeout(() => {
                this.loadFormData();
                this.initializeItems();
                
                // Restore form state if switching tabs (delay to ensure elements are ready)
                if (localStorage.getItem('wpbnp_form_state')) {
                    setTimeout(() => {
                        this.restoreFormState();
                    }, 100);
                }
            }, 200);
        },
        
        // Get current tab from URL or default
        getCurrentTab: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tab') || 'items';
        },
        
        // Load form data from settings
        loadFormData: function() {
            this.populateFormFields();
        },
        
        // Populate form fields with current settings
        populateFormFields: function() {
            const settings = this.settings;
            
            // First, populate the main enabled checkbox - this is critical
            const enabledCheckbox = $('input[name="settings[enabled]"]');
            if (enabledCheckbox.length) {
                enabledCheckbox.prop('checked', Boolean(settings.enabled));
                console.log('Set enabled checkbox to:', Boolean(settings.enabled));
            }
            
            // Populate style fields
            if (settings.style) {
                Object.keys(settings.style).forEach(key => {
                    const input = $(`input[name="settings[style][${key}]"], select[name="settings[style][${key}]"], textarea[name="settings[style][${key}]"]`);
                    if (input.length) {
                        input.val(settings.style[key]);
                        if (input.hasClass('wpbnp-color-picker')) {
                            input.wpColorPicker('color', settings.style[key]);
                        }
                    }
                });
            }
            
            // Populate animation fields
            if (settings.animations) {
                Object.keys(settings.animations).forEach(key => {
                    const input = $(`input[name="settings[animations][${key}]"], select[name="settings[animations][${key}]"]`);
                    if (input.length) {
                        if (input.attr('type') === 'checkbox') {
                            input.prop('checked', Boolean(settings.animations[key]));
                        } else {
                            input.val(settings.animations[key]);
                        }
                    }
                });
            }
            
            // Populate device fields
            if (settings.devices) {
                Object.keys(settings.devices).forEach(device => {
                    Object.keys(settings.devices[device]).forEach(key => {
                        const input = $(`input[name="settings[devices][${device}][${key}]"]`);
                        if (input.length) {
                            if (input.attr('type') === 'checkbox') {
                                input.prop('checked', Boolean(settings.devices[device][key]));
                            } else {
                                input.val(settings.devices[device][key]);
                            }
                        }
                    });
                });
            }
            
            // Populate display rules
            if (settings.display_rules) {
                Object.keys(settings.display_rules).forEach(key => {
                    if (key === 'user_roles' && Array.isArray(settings.display_rules[key])) {
                        // Clear all first
                        $(`input[name="settings[display_rules][user_roles][]"]`).prop('checked', false);
                        // Then set the selected ones
                        settings.display_rules[key].forEach(role => {
                            $(`input[name="settings[display_rules][user_roles][]"][value="${role}"]`).prop('checked', true);
                        });
                    } else {
                        const input = $(`input[name="settings[display_rules][${key}]"]`);
                        if (input.length) {
                            if (input.attr('type') === 'checkbox') {
                                input.prop('checked', Boolean(settings.display_rules[key]));
                            } else {
                                input.val(settings.display_rules[key]);
                            }
                        }
                    }
                });
            }
            
            // Populate badge fields
            if (settings.badges) {
                Object.keys(settings.badges).forEach(key => {
                    const input = $(`input[name="settings[badges][${key}]"], select[name="settings[badges][${key}]"]`);
                    if (input.length) {
                        if (input.attr('type') === 'checkbox') {
                            input.prop('checked', Boolean(settings.badges[key]));
                        } else {
                            input.val(settings.badges[key]);
                            if (input.hasClass('wpbnp-color-picker')) {
                                input.wpColorPicker('color', settings.badges[key]);
                            }
                        }
                    }
                });
            }
            
            // Populate advanced fields
            if (settings.advanced) {
                Object.keys(settings.advanced).forEach(key => {
                    const input = $(`input[name="settings[advanced][${key}]"], select[name="settings[advanced][${key}]"], textarea[name="settings[advanced][${key}]"]`);
                    if (input.length) {
                        input.val(settings.advanced[key]);
                    }
                });
            }
            
            console.log('Form fields populated with settings:', settings);
        },
        
        // Initialize navigation items
        initializeItems: function() {
            const itemsList = $('#wpbnp-items-list');
            if (itemsList.length && this.settings.items) {
                itemsList.empty();
                this.settings.items.forEach((item, index) => {
                    this.addItemRow(item, index);
                });
            }
        },
        
        // Add new item row
        addItemRow: function(item = null, index = null) {
            if (index === null) {
                index = this.settings.items ? this.settings.items.length : 0;
            }
            
            const defaultItem = {
                id: `item_${index}`,
                label: 'New Item',
                icon: 'dashicons-admin-home',
                url: '#',
                enabled: true
            };
            
            const itemData = item || defaultItem;
            
            const rowHtml = `
                <div class="wpbnp-nav-item-row" data-index="${index}">
                    <span class="wpbnp-drag-handle dashicons dashicons-sort"></span>
                    <div class="wpbnp-item-fields">
                        <div class="wpbnp-field">
                            <label>Item ID</label>
                            <input type="text" name="settings[items][${index}][id]" 
                                   value="${itemData.id}" class="wpbnp-item-id" required>
                        </div>
                        <div class="wpbnp-field">
                            <label>Label</label>
                            <input type="text" name="settings[items][${index}][label]" 
                                   value="${itemData.label}" class="wpbnp-item-label">
                        </div>
                        <div class="wpbnp-field">
                            <label>Icon</label>
                            <div class="wpbnp-icon-picker">
                                <input type="text" name="settings[items][${index}][icon]" 
                                       value="${itemData.icon}" class="wpbnp-icon-input">
                                <button type="button" class="button wpbnp-pick-icon">Choose</button>
                            </div>
                        </div>
                        <div class="wpbnp-field">
                            <label>URL</label>
                            <input type="url" name="settings[items][${index}][url]" 
                                   value="${itemData.url}" class="wpbnp-item-url">
                        </div>
                    </div>
                    <div class="wpbnp-item-controls">
                        <button type="button" class="wpbnp-toggle-item ${itemData.enabled ? 'enabled' : 'disabled'}">
                            ${itemData.enabled ? 'Enabled' : 'Disabled'}
                        </button>
                        <button type="button" class="wpbnp-remove-item">Remove</button>
                        <input type="hidden" name="settings[items][${index}][enabled]" 
                               value="${itemData.enabled ? '1' : '0'}" class="wpbnp-item-enabled">
                    </div>
                </div>
            `;
            
            $('#wpbnp-items-list').append(rowHtml);
            this.updateItemsData();
        },
        
        // Bind all events
        bindEvents: function() {
            // Form submission
            $(document).on('submit', '#wpbnp-settings-form', this.handleFormSubmit.bind(this));
            
            // Add item button
            $(document).on('click', '#wpbnp-add-item', this.handleAddItem.bind(this));
            
            // Toggle item
            $(document).on('click', '.wpbnp-toggle-item', this.handleToggleItem.bind(this));
            
            // Remove item
            $(document).on('click', '.wpbnp-remove-item', this.handleRemoveItem.bind(this));
            
            // Icon picker
            $(document).on('click', '.wpbnp-pick-icon', this.openIconPicker.bind(this));
            
            // Preset application
            $(document).on('click', '.wpbnp-preset-card, .wpbnp-apply-preset', this.applyPreset.bind(this));
            
            // Update items data when inputs change
            $(document).on('input change', '.wpbnp-nav-item-row input', this.updateItemsData.bind(this));
            
            // Export/Import/Reset settings
            $(document).on('click', '.wpbnp-export-settings', this.exportSettings.bind(this));
            $(document).on('click', '.wpbnp-import-settings', this.importSettings.bind(this));
            $(document).on('click', '.wpbnp-reset-settings', this.resetSettings.bind(this));
            
            // Handle tab switching with state preservation
            $(document).on('click', '.wpbnp-tab', this.handleTabSwitch.bind(this));
            
            // Auto-save form state when any field changes (CRITICAL for Enable Bottom Navigation)
            $(document).on('change input', '#wpbnp-settings-form input, #wpbnp-settings-form select, #wpbnp-settings-form textarea', this.debounce(() => {
                this.saveFormState();
                console.log('Auto-saved form state due to field change');
            }, 500));
            
            // Specific handler for the Enable Bottom Navigation checkbox
            $(document).on('change', 'input[name="settings[enabled]"]', (e) => {
                const isChecked = $(e.target).is(':checked') || $(e.target).val() === '1';
                
                // Update local settings immediately
                this.settings.enabled = isChecked;
                
                // Save form state immediately
                this.saveFormState();
                
                console.log('Enable Bottom Navigation changed to:', isChecked);
                console.log('Saved state immediately');
            });
            
            // Handle hidden field updates for non-Items tabs
            $(document).on('wpbnp_update_enabled_state', (e, newState) => {
                const hiddenField = $('#wpbnp-enabled-hidden');
                if (hiddenField.length) {
                    hiddenField.val(newState ? '1' : '0');
                    console.log('Updated hidden enabled field to:', newState);
                }
            });
        },
        
        // Debounce function to prevent excessive saves
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Handle tab switching while preserving form state
        handleTabSwitch: function(e) {
            // Save current form state before switching tabs
            this.saveFormState();
            
            // Small delay to ensure state is saved before navigation
            setTimeout(() => {
                // Let the navigation proceed
                window.location.href = e.target.href;
            }, 50);
            
            // Prevent immediate navigation to allow state saving
            e.preventDefault();
        },
        
        // Save current form state to localStorage
        saveFormState: function() {
            const formData = this.getFormData();
            
            // CRITICAL: Always ensure the enabled checkbox state is captured
            // Check for both visible checkbox and hidden field
            const enabledCheckbox = $('input[name="settings[enabled]"][type="checkbox"]');
            const enabledHidden = $('input[name="settings[enabled]"][type="hidden"]');
            
            if (enabledCheckbox.length) {
                // We're on Items tab with visible checkbox
                formData['settings[enabled]'] = enabledCheckbox.is(':checked');
                console.log('Saved enabled checkbox state (visible):', formData['settings[enabled]']);
            } else if (enabledHidden.length) {
                // We're on another tab with hidden field
                formData['settings[enabled]'] = enabledHidden.val() === '1';
                console.log('Saved enabled checkbox state (hidden):', formData['settings[enabled]']);
            }
            
            localStorage.setItem('wpbnp_form_state', JSON.stringify(formData));
            console.log('Form state saved to localStorage:', formData);
        },
        
        // Get form data
        getFormData: function() {
            const formData = {};
            const form = $('#wpbnp-settings-form');
            
            // Get all form inputs
            form.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                if (name) {
                    if ($input.attr('type') === 'checkbox') {
                        // For checkboxes, always store the state (checked or not)
                        formData[name] = $input.is(':checked');
                    } else if ($input.attr('type') === 'radio') {
                        if ($input.is(':checked')) {
                            formData[name] = $input.val();
                        }
                    } else {
                        formData[name] = $input.val();
                    }
                }
            });
            
            // Also store the items data specifically
            formData['wpbnp_items_data'] = JSON.stringify(this.settings.items || []);
            
            return formData;
        },
        
        // Restore form state from localStorage
        restoreFormState: function() {
            const savedState = localStorage.getItem('wpbnp_form_state');
            if (savedState) {
                try {
                    const formData = JSON.parse(savedState);
                    
                    // CRITICAL: Handle the enabled checkbox FIRST and more aggressively
                    if (formData['settings[enabled]'] !== undefined) {
                        const shouldBeChecked = Boolean(formData['settings[enabled]']);
                        
                        // Handle visible checkbox (Items tab)
                        const enabledCheckbox = $('input[name="settings[enabled]"][type="checkbox"]');
                        if (enabledCheckbox.length) {
                            enabledCheckbox.prop('checked', shouldBeChecked);
                            console.log('Restored enabled checkbox (visible) to:', shouldBeChecked);
                        }
                        
                        // Handle hidden field (other tabs)
                        const enabledHidden = $('input[name="settings[enabled]"][type="hidden"]');
                        if (enabledHidden.length) {
                            enabledHidden.val(shouldBeChecked ? '1' : '0');
                            console.log('Restored enabled checkbox (hidden) to:', shouldBeChecked);
                        }
                        
                        // Also update the local settings object
                        this.settings.enabled = shouldBeChecked;
                    }
                    
                    // Restore regular form fields
                    Object.keys(formData).forEach(name => {
                        if (name === 'wpbnp_items_data') {
                            // Handle items data separately
                            try {
                                const itemsData = JSON.parse(formData[name]);
                                if (Array.isArray(itemsData) && itemsData.length > 0) {
                                    this.settings.items = itemsData;
                                    this.initializeItems();
                                }
                            } catch (e) {
                                console.warn('Error parsing items data:', e);
                            }
                            return;
                        }
                        
                        // Skip enabled checkbox as we handled it above
                        if (name === 'settings[enabled]') {
                            return;
                        }
                        
                        const $input = $(`[name="${name}"]`);
                        if ($input.length) {
                            if ($input.attr('type') === 'checkbox') {
                                // Properly handle checkbox state
                                $input.prop('checked', Boolean(formData[name]));
                            } else {
                                $input.val(formData[name]);
                                if ($input.hasClass('wpbnp-color-picker')) {
                                    $input.wpColorPicker('color', formData[name]);
                                }
                            }
                        }
                    });
                    
                    console.log('Form state restored successfully');
                } catch (e) {
                    console.error('Error restoring form state:', e);
                    // Clear corrupted state
                    localStorage.removeItem('wpbnp_form_state');
                }
            }
        },
        
        // Handle form submission
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            // Critical fix: Ensure unchecked checkboxes are handled properly
            // FormData doesn't include unchecked checkboxes, so we need to explicitly add them
            $('#wpbnp-settings-form input[type="checkbox"]').each(function() {
                const checkbox = $(this);
                const name = checkbox.attr('name');
                if (name && !formData.has(name)) {
                    // If checkbox is not in FormData (meaning it's unchecked), add it as '0'
                    formData.append(name, '0');
                }
            });
            
            formData.append('action', 'wpbnp_save_settings');
            formData.append('nonce', this.nonce);
            
            // Disable submit button
            const submitBtn = $('.wpbnp-save-settings');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text(wpbnp_admin.strings.saving || 'Saving...');
            
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(wpbnp_admin.strings.saved || 'Settings saved successfully!', 'success');
                        // Update local settings from response
                        if (response.data && response.data.settings) {
                            this.settings = response.data.settings;
                            console.log('Updated local settings:', this.settings);
                        } else {
                            // If no settings in response, merge current form data with existing settings
                            const currentFormData = this.getFormData();
                            // Update enabled state specifically
                            if (currentFormData['settings[enabled]'] !== undefined) {
                                this.settings.enabled = currentFormData['settings[enabled]'];
                            }
                        }
                        // Clear saved form state after successful save
                        localStorage.removeItem('wpbnp_form_state');
                        console.log('Settings saved and form state cleared');
                    } else {
                        this.showNotification(response.data ? response.data.message : wpbnp_admin.strings.error || 'Error saving settings', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Ajax error occurred', 'error');
                },
                complete: () => {
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Reset settings
        resetSettings: function(e) {
            e.preventDefault();
            
            if (!confirm(wpbnp_admin.strings.confirm_reset || 'Are you sure you want to reset all settings to defaults?')) {
                return;
            }
            
            const button = $(e.target);
            const originalText = button.text();
            button.prop('disabled', true).text('Resetting...');
            
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_reset_settings',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Settings reset to defaults!', 'success');
                        // Update settings and reload page to reflect changes
                        if (response.data && response.data.settings) {
                            this.settings = response.data.settings;
                        }
                        // Clear saved form state and reload page
                        localStorage.removeItem('wpbnp_form_state');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotification(response.data ? response.data.message : 'Error resetting settings', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Ajax error occurred', 'error');
                },
                complete: () => {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Handle add item
        handleAddItem: function(e) {
            e.preventDefault();
            this.addItemRow();
        },
        
        // Handle toggle item
        handleToggleItem: function(e) {
            e.preventDefault();
            const button = $(e.target);
            const hiddenInput = button.siblings('.wpbnp-item-enabled');
            const isEnabled = hiddenInput.val() === '1';
            
            hiddenInput.val(isEnabled ? '0' : '1');
            button.toggleClass('enabled disabled');
            button.text(isEnabled ? 'Disabled' : 'Enabled');
            
            this.updateItemsData();
        },
        
        // Handle remove item
        handleRemoveItem: function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this item?')) {
                $(e.target).closest('.wpbnp-nav-item-row').remove();
                this.reindexItems();
                this.updateItemsData();
            }
        },
        
        // Open icon picker modal
        openIconPicker: function(e) {
            e.preventDefault();
            const button = $(e.target);
            const input = button.siblings('.wpbnp-icon-input');
            
            // Create modal if it doesn't exist
            if (!$('#wpbnp-icon-modal').length) {
                this.createIconModal();
            }
            
            // Get current preset and determine recommended icon library
            const currentPreset = $('input[name="settings[preset]"]').val() || 'minimal';
            const presetIconMapping = {
                'minimal': 'dashicons',
                'dark': 'fontawesome', 
                'material': 'material',
                'ios': 'apple',
                'glassmorphism': 'bootstrap',
                'neumorphism': 'apple',
                'cyberpunk': 'fontawesome',
                'vintage': 'dashicons',
                'gradient': 'bootstrap',
                'floating': 'fontawesome'
            };
            
            const recommendedLibrary = presetIconMapping[currentPreset] || 'dashicons';
            
            // Show modal and store reference to input
            $('#wpbnp-icon-modal').show().data('target-input', input);
            
            // Automatically switch to recommended icon library tab
            $('.wpbnp-icon-tab').removeClass('active');
            $('.wpbnp-icon-library-content').removeClass('active');
            $(`.wpbnp-icon-tab[data-library="${recommendedLibrary}"]`).addClass('active');
            $(`.wpbnp-icon-library-content[data-library="${recommendedLibrary}"]`).addClass('active');
            
            // Update library indicator
            const libraryName = $(`.wpbnp-icon-tab[data-library="${recommendedLibrary}"]`).text();
            $('#wpbnp-current-library').text(`${libraryName} (Recommended for ${currentPreset})`);
            this.updateIconCount();
            
            // Highlight current selection
            $('.wpbnp-icon-option').removeClass('selected');
            $(`.wpbnp-icon-option[data-icon="${input.val()}"]`).addClass('selected');
            
            // Focus search
            $('#wpbnp-icon-search').focus();
            
            // Show helpful message about recommended library
            if (recommendedLibrary !== 'dashicons') {
                this.showNotification(`💡 ${libraryName} icons work best with ${currentPreset} preset!`, 'info');
            }
        },
        
        // Create enhanced icon picker modal with multiple libraries
        createIconModal: function() {
            const iconLibraries = {
                'dashicons': {
                    name: 'WordPress Icons',
                    description: 'WordPress native Dashicons',
                    icons: wpbnp_admin.icon_libraries.dashicons,
                    class: 'dashicons',
                    badge: 'WP'
                },
                'fontawesome': {
                    name: 'FontAwesome',
                    description: 'Most popular icon library',
                    icons: wpbnp_admin.icon_libraries.fontawesome,
                    class: 'fas fa-',
                    badge: 'FA'
                },
                'bootstrap': {
                    name: 'Bootstrap Icons',
                    description: 'Clean and modern',
                    icons: wpbnp_admin.icon_libraries.bootstrap,
                    class: 'bi bi-',
                    badge: 'BI'
                },
                'material': {
                    name: 'Material Design',
                    description: 'Google Material Design',
                    icons: wpbnp_admin.icon_libraries.material,
                    class: 'material-icons',
                    badge: 'MD'
                },
                'apple': {
                    name: 'iOS Icons',
                    description: 'Apple SF Symbols style',
                    icons: wpbnp_admin.icon_libraries.apple,
                    class: '',
                    badge: 'iOS'
                },
                'feather': {
                    name: 'Feather Icons',
                    description: 'Minimalist line icons',
                    icons: wpbnp_admin.icon_libraries.feather,
                    class: 'feather-',
                    badge: 'FE'
                }
            };
            
            // Generate organized tabs HTML with badges and descriptions
            let tabsHtml = '<div class="wpbnp-icon-tabs-container">';
            tabsHtml += '<div class="wpbnp-icon-tabs">';
            Object.keys(iconLibraries).forEach(libKey => {
                const lib = iconLibraries[libKey];
                const iconCount = Object.keys(lib.icons).length;
                tabsHtml += `
                    <button type="button" class="wpbnp-icon-tab" data-library="${libKey}" title="${lib.description}">
                        <span class="wpbnp-tab-badge">${lib.badge}</span>
                        <span class="wpbnp-tab-name">${lib.name}</span>
                        <span class="wpbnp-tab-count">${iconCount}</span>
                    </button>
                `;
            });
            tabsHtml += '</div>';
            tabsHtml += '<div class="wpbnp-library-info"><span id="wpbnp-current-library-desc">Select an icon library</span></div>';
            tabsHtml += '</div>';
            
            // Generate organized content HTML for each library
            let contentHtml = '<div class="wpbnp-icon-content-wrapper">';
            Object.keys(iconLibraries).forEach(libKey => {
                const lib = iconLibraries[libKey];
                contentHtml += `<div class="wpbnp-icon-library-content" data-library="${libKey}">`;
                contentHtml += `<div class="wpbnp-icons-grid">`;
                
                Object.keys(lib.icons).forEach(iconKey => {
                    const iconName = lib.icons[iconKey];
                    let iconElement = '';
                    let saveValue = '';
                    
                    // Generate proper icon markup and save value based on library type
                    if (libKey === 'dashicons') {
                        iconElement = `<span class="dashicons ${iconKey}"></span>`;
                        saveValue = iconKey;
                    } else if (libKey === 'fontawesome') {
                        iconElement = `<i class="fas fa-${iconKey}"></i>`;
                        saveValue = `fas fa-${iconKey}`;
                    } else if (libKey === 'bootstrap') {
                        iconElement = `<i class="bi bi-${iconKey}"></i>`;
                        saveValue = `bi bi-${iconKey}`;
                    } else if (libKey === 'material') {
                        iconElement = `<span class="material-icons">${iconKey}</span>`;
                        saveValue = iconKey;
                    } else if (libKey === 'apple') {
                        iconElement = `<span class="${iconKey}"></span>`;
                        saveValue = iconKey;
                    } else if (libKey === 'feather') {
                        iconElement = `<i class="feather-${iconKey}"></i>`;
                        saveValue = `feather-${iconKey}`;
                    }
                    
                    contentHtml += `
                        <div class="wpbnp-icon-option" data-icon="${saveValue}" data-library="${libKey}" title="${iconName}">
                            <div class="wpbnp-icon-preview">${iconElement}</div>
                            <div class="wpbnp-icon-label">${iconName}</div>
                        </div>
                    `;
                });
                
                contentHtml += '</div></div>';
            });
            contentHtml += '</div>';
            
            // Complete organized modal HTML
            const modalHtml = `
                <div id="wpbnp-icon-modal" class="wpbnp-modal" style="display: none;">
                    <div class="wpbnp-modal-backdrop"></div>
                    <div class="wpbnp-modal-content wpbnp-icon-modal-content">
                        <div class="wpbnp-modal-header">
                            <h3><i class="dashicons dashicons-admin-appearance"></i> Choose Icon</h3>
                            <button type="button" class="wpbnp-modal-close">&times;</button>
                        </div>
                        <div class="wpbnp-modal-body">
                            <div class="wpbnp-search-container">
                                <div class="wpbnp-search-wrapper">
                                    <i class="dashicons dashicons-search"></i>
                                    <input type="text" id="wpbnp-icon-search" placeholder="Search icons..." />
                                </div>
                                <div class="wpbnp-search-stats">
                                    <span id="wpbnp-icon-count">0 icons</span>
                                    <span class="wpbnp-search-separator">•</span>
                                    <span id="wpbnp-visible-count">0 visible</span>
                                </div>
                            </div>
                            ${tabsHtml}
                            ${contentHtml}
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal and add new one
            $('#wpbnp-icon-modal').remove();
            $('body').append(modalHtml);
            
            // Initialize with first tab active
            $('.wpbnp-icon-tab').first().addClass('active');
            $('.wpbnp-icon-library-content').first().addClass('active');
            this.updateIconCount();
            this.updateLibraryInfo();
            
            // Bind events
            this.bindIconModalEvents();
        },
        
        // Apply preset with debouncing and performance optimization
        applyPreset: function(e) {
            e.preventDefault();
            const $target = $(e.currentTarget);
            
            // Prevent double-clicks and rapid clicking
            if ($target.hasClass('wpbnp-applying')) {
                return;
            }
            
            $target.addClass('wpbnp-applying');
            
            const presetKey = $target.data('preset');
            const preset = this.presets[presetKey];
            
            if (!preset) {
                $target.removeClass('wpbnp-applying');
                return;
            }
            
            // Show immediate feedback
            $target.find('.wpbnp-preset-name').text('Applying...');
            
            // Use setTimeout to allow UI to update and prevent blocking
            setTimeout(() => {
                this.doApplyPreset(preset, presetKey, $target, e);
            }, 50);
        },
        
        // Actual preset application logic (separated for performance)
        doApplyPreset: function(preset, presetKey, $target, originalEvent) {
            // Define icon library mapping for each preset
            const presetIconMapping = {
                'minimal': 'dashicons',
                'dark': 'dashicons', 
                'material': 'material',
                'ios': 'apple',
                'glassmorphism': 'bootstrap',
                'neumorphism': 'apple',
                'cyberpunk': 'bootstrap',
                'vintage': 'dashicons',
                'gradient': 'bootstrap',
                'floating': 'apple'
            };
            
            // Get recommended icon library for this preset
            const recommendedIconLibrary = presetIconMapping[presetKey] || 'dashicons';
            
            // Icon conversion mapping between libraries
            const iconConversion = {
                // Common icon mappings
                'home': {
                    'dashicons': 'dashicons-admin-home',
                    'fontawesome': 'fas fa-home',
                    'bootstrap': 'bi bi-house-door-fill',
                    'material': 'home',
                    'apple': 'house-fill',
                    'feather': 'home'
                },
                'cart': {
                    'dashicons': 'dashicons-cart',
                    'fontawesome': 'fas fa-shopping-cart',
                    'bootstrap': 'bi bi-cart-fill',
                    'material': 'shopping_cart',
                    'apple': 'cart-fill',
                    'feather': 'shopping-cart'
                },
                'user': {
                    'dashicons': 'dashicons-admin-users',
                    'fontawesome': 'fas fa-user',
                    'bootstrap': 'bi bi-person-fill',
                    'material': 'person',
                    'apple': 'person-fill',
                    'feather': 'user'
                },
                'heart': {
                    'dashicons': 'dashicons-heart',
                    'fontawesome': 'fas fa-heart',
                    'bootstrap': 'bi bi-heart-fill',
                    'material': 'favorite',
                    'apple': 'heart-fill',
                    'feather': 'heart'
                },
                'search': {
                    'dashicons': 'dashicons-search',
                    'fontawesome': 'fas fa-search',
                    'bootstrap': 'bi bi-search',
                    'material': 'search',
                    'apple': 'magnifyingglass',
                    'feather': 'search'
                },
                'settings': {
                    'dashicons': 'dashicons-admin-settings',
                    'fontawesome': 'fas fa-cog',
                    'bootstrap': 'bi bi-gear-fill',
                    'material': 'settings',
                    'apple': 'gearshape-fill',
                    'feather': 'settings'
                },
                'star': {
                    'dashicons': 'dashicons-star-filled',
                    'fontawesome': 'fas fa-star',
                    'bootstrap': 'bi bi-star-fill',
                    'material': 'star',
                    'apple': 'star-fill',
                    'feather': 'star'
                },
                'message': {
                    'dashicons': 'dashicons-email',
                    'fontawesome': 'fas fa-envelope',
                    'bootstrap': 'bi bi-envelope-fill',
                    'material': 'mail',
                    'apple': 'envelope-fill',
                    'feather': 'mail'
                },
                'camera': {
                    'dashicons': 'dashicons-camera',
                    'fontawesome': 'fas fa-camera',
                    'bootstrap': 'bi bi-camera-fill',
                    'material': 'camera_alt',
                    'apple': 'camera-fill',
                    'feather': 'camera'
                },
                'menu': {
                    'dashicons': 'dashicons-menu',
                    'fontawesome': 'fas fa-bars',
                    'bootstrap': 'bi bi-list',
                    'material': 'menu',
                    'apple': 'list-bullet',
                    'feather': 'menu'
                },
                'phone': {
                    'dashicons': 'dashicons-phone',
                    'fontawesome': 'fas fa-phone',
                    'bootstrap': 'bi bi-telephone-fill',
                    'material': 'phone',
                    'apple': 'phone-fill',
                    'feather': 'phone'
                },
                'info': {
                    'dashicons': 'dashicons-info',
                    'fontawesome': 'fas fa-info-circle',
                    'bootstrap': 'bi bi-info-circle-fill',
                    'material': 'info',
                    'apple': 'info-circle-fill',
                    'feather': 'info'
                }
            };
            
            // Apply style settings
            if (preset.style) {
                Object.keys(preset.style).forEach(key => {
                    const input = $(`input[name="settings[style][${key}]"], select[name="settings[style][${key}]"]`);
                    if (input.length) {
                        input.val(preset.style[key]);
                        if (input.hasClass('wpbnp-color-picker')) {
                            input.wpColorPicker('color', preset.style[key]);
                        }
                    }
                });
            }
            
            // Apply animation settings
            if (preset.animations) {
                Object.keys(preset.animations).forEach(key => {
                    const input = $(`input[name="settings[animations][${key}]"], select[name="settings[animations][${key}]"]`);
                    if (input.length) {
                        if (input.attr('type') === 'checkbox') {
                            input.prop('checked', preset.animations[key]);
                        } else {
                            input.val(preset.animations[key]);
                        }
                    }
                });
            }
            
            // OPTIONAL: Auto-convert existing item icons to match preset's icon library (simplified)
            if (this.settings.items && this.settings.items.length > 0) {
                // Only convert icons if explicitly requested (to avoid performance issues)
                // Users can enable this by holding Shift while clicking preset
                const shouldConvertIcons = originalEvent && originalEvent.shiftKey; // Enable with Shift+Click
                
                if (shouldConvertIcons) {
                    let iconsChanged = 0;
                    
                    // Simplified conversion - only convert obvious mismatches
                    this.settings.items.forEach((item, index) => {
                        if (item.icon && this.needsIconConversion(item.icon, recommendedIconLibrary)) {
                            const convertedIcon = this.getSimpleIconConversion(item.icon, recommendedIconLibrary, index);
                            if (convertedIcon && convertedIcon !== item.icon) {
                                item.icon = convertedIcon;
                                iconsChanged++;
                                
                                // Update the UI in batch (more efficient)
                                const iconInput = $(`.wpbnp-nav-item-row:eq(${index}) .wpbnp-icon-input`);
                                if (iconInput.length) {
                                    iconInput.val(convertedIcon);
                                    const iconPreview = iconInput.siblings('.wpbnp-icon-preview');
                                    if (iconPreview.length) {
                                        iconPreview.html(this.generateIconHTML(convertedIcon));
                                    }
                                }
                            }
                        }
                    });
                    
                                                              // Show notification about icon conversions
                     if (iconsChanged > 0) {
                         this.showNotification(`🎨 Updated ${iconsChanged} icon(s) to ${recommendedIconLibrary.toUpperCase()} library!`, 'success');
                     }
                }
            }
            
            // If no items exist, create default preset-appropriate items
            if (!this.settings.items || this.settings.items.length === 0) {
                const defaultPresetItems = {
                    'minimal': [
                        {id: 'home', label: 'Home', icon: 'dashicons-admin-home', url: wpbnp_admin.home_url, enabled: true},
                        {id: 'shop', label: 'Shop', icon: 'dashicons-cart', url: '#', enabled: true},
                        {id: 'account', label: 'Account', icon: 'dashicons-admin-users', url: '#', enabled: true}
                    ],
                    'dark': [
                        {id: 'home', label: 'Home', icon: 'dashicons-admin-home', url: wpbnp_admin.home_url, enabled: true},
                        {id: 'search', label: 'Search', icon: 'dashicons-search', url: '#', enabled: true},
                        {id: 'profile', label: 'Profile', icon: 'dashicons-admin-users', url: '#', enabled: true}
                    ],
                    'material': [
                        {id: 'home', label: 'Home', icon: 'home', url: wpbnp_admin.home_url, enabled: true},
                        {id: 'explore', label: 'Explore', icon: 'explore', url: '#', enabled: true},
                        {id: 'favorite', label: 'Favorites', icon: 'favorite', url: '#', enabled: true},
                        {id: 'person', label: 'Profile', icon: 'person', url: '#', enabled: true}
                    ],
                    'ios': [
                        {id: 'home', label: 'Home', icon: 'house-fill', url: wpbnp_admin.home_url, enabled: true},
                        {id: 'search', label: 'Search', icon: 'magnifyingglass', url: '#', enabled: true},
                        {id: 'heart', label: 'Favorites', icon: 'heart-fill', url: '#', enabled: true},
                        {id: 'person', label: 'Profile', icon: 'person-fill', url: '#', enabled: true}
                    ],
                                         'glassmorphism': [
                         {id: 'home', label: 'Home', icon: 'bi bi-house-door-fill', url: wpbnp_admin.home_url, enabled: true},
                         {id: 'bookmark', label: 'Saved', icon: 'bi bi-bookmark-fill', url: '#', enabled: true},
                         {id: 'person', label: 'Profile', icon: 'bi bi-person-fill', url: '#', enabled: true}
                     ],
                    'neumorphism': [
                        {id: 'home', label: 'Home', icon: 'house-fill', url: wpbnp_admin.home_url, enabled: true},
                        {id: 'message', label: 'Messages', icon: 'envelope-fill', url: '#', enabled: true},
                        {id: 'settings', label: 'Settings', icon: 'gearshape-fill', url: '#', enabled: true}
                    ],
                                         'cyberpunk': [
                         {id: 'home', label: 'Home', icon: 'bi bi-house-door-fill', url: wpbnp_admin.home_url, enabled: true},
                         {id: 'explore', label: 'Explore', icon: 'bi bi-search', url: '#', enabled: true},
                         {id: 'notifications', label: 'Alerts', icon: 'bi bi-bell-fill', url: '#', enabled: true},
                         {id: 'account', label: 'Account', icon: 'bi bi-person-circle', url: '#', enabled: true}
                     ],
                    'vintage': [
                        {id: 'home', label: 'Home', icon: 'dashicons-admin-home', url: wpbnp_admin.home_url, enabled: true},
                        {id: 'gallery', label: 'Gallery', icon: 'dashicons-format-gallery', url: '#', enabled: true},
                        {id: 'contact', label: 'Contact', icon: 'dashicons-email', url: '#', enabled: true}
                    ],
                                         'gradient': [
                         {id: 'home', label: 'Home', icon: 'bi bi-house-door-fill', url: wpbnp_admin.home_url, enabled: true},
                         {id: 'star', label: 'Featured', icon: 'bi bi-star-fill', url: '#', enabled: true},
                         {id: 'person', label: 'Profile', icon: 'bi bi-person-fill', url: '#', enabled: true}
                     ],
                    'floating': [
                        {id: 'home', label: 'Home', icon: 'house-fill', url: wpbnp_admin.home_url, enabled: true},
                        {id: 'heart', label: 'Likes', icon: 'heart-fill', url: '#', enabled: true},
                        {id: 'bookmark', label: 'Saved', icon: 'bookmark-fill', url: '#', enabled: true},
                        {id: 'person', label: 'Profile', icon: 'person-fill', url: '#', enabled: true}
                    ]
                };
                
                const defaultItems = defaultPresetItems[presetKey] || defaultPresetItems['minimal'];
                this.settings.items = [...defaultItems];
                
                // Re-render items list (batched)
                setTimeout(() => {
                    this.renderItemsList();
                }, 10);
                this.showNotification(`✨ Added ${defaultItems.length} default items for the ${preset.name} preset!`, 'info');
            }

            // Update preset selector
            $('input[name="settings[preset]"]').val(presetKey);
            $('.wpbnp-preset-card').removeClass('active');
            $(`.wpbnp-preset-card[data-preset="${presetKey}"]`).addClass('active');
            
            // Show success notification
            this.showNotification(`✨ ${preset.name} preset applied successfully!`, 'success');
            
            // Auto-save the form after preset application (debounced)
            setTimeout(() => {
                this.saveFormState();
            }, 100);
            
            // Reset button state and restore text
            $target.removeClass('wpbnp-applying');
            $target.find('.wpbnp-preset-name').text(preset.name);
        },
        
        // Check if icon needs conversion (simplified)
        needsIconConversion: function(iconClass, targetLibrary) {
            if (!iconClass || !targetLibrary) return false;
            
            const iconType = this.getIconType(iconClass);
            return iconType !== targetLibrary;
        },
        
        // Get icon type (simplified detection)
        getIconType: function(iconClass) {
            if (iconClass.startsWith('dashicons-')) return 'dashicons';
            if (iconClass.startsWith('fas fa-') || iconClass.startsWith('far fa-') || iconClass.startsWith('fab fa-')) return 'fontawesome';
            if (iconClass.startsWith('bi bi-')) return 'bootstrap';
            if (iconClass.startsWith('feather-')) return 'feather';
            if (this.isAppleIcon(iconClass)) return 'apple';
            if (!iconClass.includes('-') && !iconClass.includes(' ') && !iconClass.includes('<')) return 'material';
            return 'dashicons'; // default
        },
        
        // Simple icon conversion (basic mapping only)
        getSimpleIconConversion: function(iconClass, targetLibrary, index) {
            // Basic conversion map for common icons only
            const basicConversions = {
                'home': {
                    'dashicons': 'dashicons-admin-home',
                    'fontawesome': 'fas fa-home',
                    'bootstrap': 'bi bi-house-door-fill',
                    'material': 'home',
                    'apple': 'house-fill',
                    'feather': 'feather-home'
                },
                'user': {
                    'dashicons': 'dashicons-admin-users',
                    'fontawesome': 'fas fa-user',
                    'bootstrap': 'bi bi-person-fill',
                    'material': 'person',
                    'apple': 'person-fill',
                    'feather': 'feather-user'
                },
                'search': {
                    'dashicons': 'dashicons-search',
                    'fontawesome': 'fas fa-search',
                    'bootstrap': 'bi bi-search',
                    'material': 'search',
                    'apple': 'magnifyingglass',
                    'feather': 'feather-search'
                }
            };
            
            // Try to find conversion, otherwise return original
            for (const [key, mapping] of Object.entries(basicConversions)) {
                if (Object.values(mapping).includes(iconClass)) {
                    return mapping[targetLibrary] || iconClass;
                }
            }
            
            return iconClass; // Return original if no conversion found
        },
        
        // Generate HTML for icon preview
        generateIconHTML: function(iconClass) {
            if (!iconClass) return '';
            
            // Handle different icon types
            if (iconClass.startsWith('dashicons-')) {
                return `<span class="dashicons ${iconClass}"></span>`;
            } else if (iconClass.startsWith('fas fa-') || iconClass.startsWith('far fa-') || iconClass.startsWith('fab fa-')) {
                return `<i class="${iconClass}"></i>`;
            } else if (iconClass.startsWith('bi bi-')) {
                return `<i class="${iconClass}"></i>`;
            } else if (iconClass.startsWith('feather-')) {
                return `<i class="${iconClass}"></i>`;
            } else if (this.isAppleIcon(iconClass)) {
                // Apple SF Symbols (using direct class names like 'house-fill', 'person-circle')
                return `<span class="${iconClass}"></span>`;
            } else if (!iconClass.includes('-') && !iconClass.includes(' ') && !iconClass.includes('<')) {
                // Material icons (single words like 'home', 'person')
                return `<span class="material-icons">${iconClass}</span>`;
            } else {
                // Default fallback
                return `<span class="wpbnp-custom-icon">${iconClass}</span>`;
            }
        },
        
        // Helper function to identify Apple SF Symbols (only ones with CSS definitions)
        isAppleIcon: function(iconClass) {
            // List of Apple icon patterns that have proper CSS definitions
            const validAppleIcons = [
                'house', 'house-fill', 'house-circle', 'house-circle-fill',
                'building', 'building-fill', 'building-2', 'building-2-fill',
                'map', 'map-fill', 'mappin', 'mappin-circle', 'mappin-circle-fill',
                'location', 'location-fill', 'location-north', 'location-north-fill', 'location-slash', 'location-slash-fill',
                'scope', 'airplane', 'airplane-circle', 'airplane-circle-fill',
                'car', 'car-fill', 'car-circle', 'car-circle-fill',
                'tram', 'tram-fill', 'train-side-front-car',
                'bicycle', 'bicycle-circle', 'bicycle-circle-fill',
                'figure-walk', 'figure-walk-circle', 'figure-walk-circle-fill',
                'person', 'person-fill', 'person-circle', 'person-circle-fill',
                'person-crop-circle', 'person-crop-circle-fill', 'person-crop-square', 'person-crop-square-fill',
                'person-crop-artframe', 'person-badge-plus', 'person-badge-plus-fill', 'person-badge-minus', 'person-badge-minus-fill',
                'person-and-background-dotted', 'person-2', 'person-2-fill', 'person-3', 'person-3-fill',
                'person-2-circle', 'person-2-circle-fill', 'person-2-square-stack', 'person-2-square-stack-fill',
                'facetime', 'faceid', 'touchid',
                'cart', 'cart-fill', 'cart-circle', 'cart-circle-fill', 'cart-badge-plus', 'cart-badge-minus',
                'bag', 'bag-fill', 'bag-badge-plus', 'bag-badge-minus',
                'handbag', 'handbag-fill', 'briefcase', 'briefcase-fill', 'briefcase-circle', 'briefcase-circle-fill',
                'case', 'case-fill', 'suitcase', 'suitcase-fill', 'suitcase-cart', 'suitcase-cart-fill',
                'creditcard', 'creditcard-fill', 'creditcard-circle', 'creditcard-circle-fill',
                'banknote', 'banknote-fill', 'dollarsign', 'dollarsign-circle', 'dollarsign-circle-fill', 'dollarsign-square', 'dollarsign-square-fill',
                'eurosign', 'eurosign-circle', 'eurosign-circle-fill', 'sterlingsign', 'sterlingsign-circle', 'sterlingsign-circle-fill',
                'yensign', 'yensign-circle', 'yensign-circle-fill', 'bitcoinsign', 'bitcoinsign-circle', 'bitcoinsign-circle-fill',
                'tag', 'tag-fill', 'tag-circle', 'tag-circle-fill', 'tags', 'tags-fill', 'percent',
                'gift', 'gift-fill', 'gift-circle', 'gift-circle-fill', 'giftcard', 'giftcard-fill',
                'purchased', 'purchased-circle', 'purchased-circle-fill',
                'message', 'message-fill', 'message-circle', 'message-circle-fill', 'message-badge', 'message-badge-filled-fill',
                'envelope', 'envelope-fill', 'envelope-circle', 'envelope-circle-fill', 'envelope-badge', 'envelope-badge-fill',
                'envelope-open', 'envelope-open-fill', 'mail-stack', 'mail-stack-fill', 'mail-and-text-magnifyingglass',
                'paperplane', 'paperplane-fill', 'paperplane-circle', 'paperplane-circle-fill',
                'phone', 'phone-fill', 'phone-circle', 'phone-circle-fill', 'phone-badge-plus', 'phone-connection',
                'phone-arrow-up-right', 'phone-arrow-down-left', 'phone-down', 'phone-down-fill', 'phone-down-circle',
                'text-bubble', 'text-bubble-fill', 'megaphone', 'megaphone-fill', 'speaker', 'speaker-fill',
                'wifi', 'airpods', 'homepod', 'homepod-fill',
                'camera', 'camera-fill', 'camera-circle', 'camera-circle-fill',
                'video', 'video-fill', 'photo', 'photo-fill', 'music-note', 'headphones',
                'play', 'play-fill', 'play-circle', 'play-circle-fill',
                'pause', 'pause-fill', 'pause-circle', 'pause-circle-fill',
                'stop', 'stop-fill', 'backward', 'backward-fill', 'forward', 'forward-fill',
                'shuffle', 'repeat', 'tv', 'tv-fill', 'appletv', 'appletv-fill',
                'list-bullet', 'list-bullet-circle', 'list-bullet-circle-fill',
                'square-grid-2x2', 'square-grid-2x2-fill', 'square-grid-3x2', 'square-grid-3x2-fill', 'square-grid-3x3', 'square-grid-3x3-fill',
                'rectangle', 'rectangle-fill',
                'arrow-up', 'arrow-up-circle', 'arrow-up-circle-fill', 'arrow-down', 'arrow-down-circle', 'arrow-down-circle-fill',
                'arrow-left', 'arrow-left-circle', 'arrow-left-circle-fill', 'arrow-right', 'arrow-right-circle', 'arrow-right-circle-fill',
                'chevron-up', 'chevron-down', 'chevron-left', 'chevron-right',
                'plus', 'plus-circle', 'plus-circle-fill', 'minus', 'minus-circle', 'minus-circle-fill',
                'multiply', 'xmark', 'xmark-circle', 'xmark-circle-fill', 'checkmark', 'checkmark-circle', 'checkmark-circle-fill',
                'trash', 'trash-fill', 'archivebox', 'archivebox-fill',
                'heart', 'heart-fill', 'heart-circle', 'heart-circle-fill',
                'star', 'star-fill', 'star-circle', 'star-circle-fill',
                'bookmark', 'bookmark-fill', 'bookmark-circle', 'bookmark-circle-fill',
                'trophy', 'trophy-fill', 'flag', 'flag-fill', 'bell', 'bell-fill', 'bell-circle', 'bell-circle-fill',
                'magnifyingglass', 'magnifyingglass-circle', 'magnifyingglass-circle-fill',
                'binoculars', 'binoculars-fill', 'eye', 'eye-fill', 'eye-slash', 'eye-slash-fill',
                'questionmark', 'questionmark-circle', 'questionmark-circle-fill',
                'doc', 'doc-fill', 'doc-text', 'doc-text-fill',
                'folder', 'folder-fill', 'folder-circle', 'folder-circle-fill',
                'icloud', 'icloud-fill', 'book', 'book-fill', 'newspaper', 'newspaper-fill',
                'gearshape', 'gearshape-fill', 'gearshape-2', 'gearshape-2-fill',
                'wrench', 'wrench-fill', 'hammer', 'hammer-fill', 'scissors',
                'app', 'app-fill', 'bolt', 'bolt-fill', 'power',
                'moon', 'moon-fill', 'sun-max', 'sun-max-fill', 'lightbulb', 'lightbulb-fill',
                'lock', 'lock-fill', 'lock-circle', 'lock-circle-fill', 'key', 'key-fill',
                'shield', 'shield-fill', 'exclamationmark', 'exclamationmark-circle', 'exclamationmark-circle-fill',
                'info', 'info-circle', 'info-circle-fill',
                'clock', 'clock-fill', 'clock-circle', 'clock-circle-fill',
                'alarm', 'alarm-fill', 'stopwatch', 'stopwatch-fill', 'timer', 'calendar', 'hourglass'
            ];
            
            return validAppleIcons.includes(iconClass);
        },
        
        // Initialize color pickers
        initializeColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.wpbnp-color-picker').wpColorPicker();
            }
        },
        
        // Setup sortable
        setupSortable: function() {
            if ($.fn.sortable) {
                $('#wpbnp-items-list').sortable({
                    handle: '.wpbnp-drag-handle',
                    placeholder: 'wpbnp-sort-placeholder',
                    update: () => {
                        this.reindexItems();
                        this.updateItemsData();
                    }
                });
            }
        },
        
        // Reindex items after sorting/removal
        reindexItems: function() {
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('input').each(function() {
                    const name = $(this).attr('name');
                    if (name && name.includes('[items][')) {
                        const newName = name.replace(/\[items\]\[\d+\]/, `[items][${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
        },
        
        // Update items data in memory
        updateItemsData: function() {
            const items = [];
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function() {
                const row = $(this);
                items.push({
                    id: row.find('.wpbnp-item-id').val(),
                    label: row.find('.wpbnp-item-label').val(),
                    icon: row.find('.wpbnp-icon-input').val(),
                    url: row.find('.wpbnp-item-url').val(),
                    enabled: row.find('.wpbnp-item-enabled').val() === '1'
                });
            });
            this.settings.items = items;
        },
        
        // Export settings
        exportSettings: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const originalText = button.text();
            button.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_export_settings',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Create and trigger download
                        const dataStr = response.data.data;
                        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
                        const linkElement = document.createElement('a');
                        linkElement.setAttribute('href', dataUri);
                        linkElement.setAttribute('download', response.data.filename);
                        linkElement.click();
                        
                        this.showNotification('Settings exported successfully!', 'success');
                    } else {
                        this.showNotification(response.data ? response.data.message : 'Error exporting settings', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Ajax error occurred', 'error');
                },
                complete: () => {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Import settings
        importSettings: function(e) {
            e.preventDefault();
            $('#wpbnp-import-file').click();
        },
        
        // Show notification
        showNotification: function(message, type = 'success') {
            const notification = $(`
                <div class="wpbnp-notification ${type}">
                    <span class="wpbnp-notification-message">${message}</span>
                    <button type="button" class="wpbnp-notification-close">&times;</button>
                </div>
            `);
            
            $('#wpbnp-notifications').append(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => {
                    notification.remove();
                });
            }, 5000);
            
            // Manual close
            notification.find('.wpbnp-notification-close').on('click', function() {
                notification.fadeOut(() => {
                    notification.remove();
                });
            });
        },

        // Update icon count in the modal
        updateIconCount: function() {
            const totalIcons = $('.wpbnp-icon-option').length;
            $('#wpbnp-icon-count').text(`${totalIcons} icons`);
        },

        // Update library info in the modal
        updateLibraryInfo: function() {
            const currentLibrary = $('.wpbnp-icon-tab.active').data('library');
            const currentLibraryName = $('.wpbnp-icon-tab.active .wpbnp-tab-name').text();
            const currentLibraryDescription = $('.wpbnp-icon-tab.active').attr('title');
            const currentLibraryBadge = $('.wpbnp-icon-tab.active .wpbnp-tab-badge').text();
            const currentLibraryCount = $('.wpbnp-icon-tab.active .wpbnp-tab-count').text();

            $('#wpbnp-current-library-desc').text(`${currentLibraryName} - ${currentLibraryDescription}`);
            
            // Update icon counts
            this.updateIconCount();
        },

        // Update icon count display
        updateIconCount: function() {
            const totalIcons = $('.wpbnp-icon-library-content.active .wpbnp-icon-option').length;
            const visibleIcons = $('.wpbnp-icon-library-content.active .wpbnp-icon-option:visible').length;
            $('#wpbnp-icon-count').text(`${totalIcons} icons`);
            $('#wpbnp-visible-count').text(`${visibleIcons} visible`);
        },

        // Bind events for the icon picker modal
        bindIconModalEvents: function() {
            $(document).on('click', '.wpbnp-modal-close, .wpbnp-modal', function(e) {
                if (e.target === this) {
                    $('#wpbnp-icon-modal').hide();
                }
            });

            $(document).on('click', '.wpbnp-icon-option', function() {
                const icon = $(this).data('icon');
                const targetInput = $('#wpbnp-icon-modal').data('target-input');
                targetInput.val(icon);
                targetInput.trigger('change'); // Trigger change event for auto-save
                $('.wpbnp-icon-option').removeClass('selected');
                $(this).addClass('selected');
                $('#wpbnp-icon-modal').hide();
                
                // Show success feedback
                WPBottomNavAdmin.showNotification(`Icon "${icon}" selected!`, 'success');
            });

            $(document).on('click', '.wpbnp-icon-tab', function() {
                const library = $(this).data('library');
                $('.wpbnp-icon-tab').removeClass('active');
                $('.wpbnp-icon-library-content').removeClass('active');
                $(this).addClass('active');
                $(`.wpbnp-icon-library-content[data-library="${library}"]`).addClass('active');
                WPBottomNavAdmin.updateLibraryInfo();
                $('#wpbnp-icon-search').val('').trigger('input');
            });

            $(document).on('input', '#wpbnp-icon-search', function() {
                const searchTerm = $(this).val().toLowerCase();
                let visibleCount = 0;
                
                $('.wpbnp-icon-library-content.active .wpbnp-icon-option').each(function() {
                    const iconName = $(this).find('.wpbnp-icon-label').text().toLowerCase();
                    const iconClass = $(this).data('icon').toLowerCase();
                    if (iconName.includes(searchTerm) || iconClass.includes(searchTerm)) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });
                
                // Update visible count
                const totalIcons = $('.wpbnp-icon-library-content.active .wpbnp-icon-option').length;
                $('#wpbnp-icon-count').text(`${totalIcons} icons`);
                $('#wpbnp-visible-count').text(`${visibleCount} visible`);
            });
        }
    };
    
    // Initialize admin
    WPBottomNavAdmin.init();
    
    // Handle file import when file is selected
    $('#wpbnp-import-file').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const importData = e.target.result;
                    
                    // Send import data via AJAX
                    $.ajax({
                        url: wpbnp_admin.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wpbnp_import_settings',
                            nonce: WPBottomNavAdmin.nonce,
                            import_data: importData
                        },
                        success: function(response) {
                            if (response.success) {
                                WPBottomNavAdmin.showNotification('Settings imported successfully!', 'success');
                                // Update settings and reload page
                                if (response.data && response.data.settings) {
                                    WPBottomNavAdmin.settings = response.data.settings;
                                }
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                WPBottomNavAdmin.showNotification(response.data ? response.data.message : 'Error importing settings', 'error');
                            }
                        },
                        error: function() {
                            WPBottomNavAdmin.showNotification('Ajax error occurred', 'error');
                        }
                    });
                } catch (error) {
                    WPBottomNavAdmin.showNotification('Error reading file: ' + error.message, 'error');
                }
            };
            reader.readAsText(file);
        }
        // Reset file input
        $(this).val('');
    });
    
    // Restore form state on page load if switching tabs
    if (localStorage.getItem('wpbnp_form_state')) {
        setTimeout(() => {
            WPBottomNavAdmin.restoreFormState();
            console.log('Restored form state on page load');
        }, 800); // Longer delay to ensure all elements are ready
    }
    
    // Make it globally available
    window.WPBottomNavAdmin = WPBottomNavAdmin;
});