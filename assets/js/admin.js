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
                    name: 'Dashicons',
                    icons: wpbnp_admin.icon_libraries.dashicons,
                    class: 'dashicons'
                },
                'fontawesome': {
                    name: 'FontAwesome',
                    icons: wpbnp_admin.icon_libraries.fontawesome,
                    class: 'fas fa-'
                },
                'bootstrap': {
                    name: 'Bootstrap Icons',
                    icons: wpbnp_admin.icon_libraries.bootstrap,
                    class: 'bi bi-'
                },
                'material': {
                    name: 'Material Icons',
                    icons: wpbnp_admin.icon_libraries.material,
                    class: 'material-icons'
                },
                'feather': {
                    name: 'Feather Icons',
                    icons: wpbnp_admin.icon_libraries.feather,
                    class: 'feather-'
                },
                'apple': {
                    name: 'Apple SF',
                    icons: wpbnp_admin.icon_libraries.apple,
                    class: 'apple-'
                }
            };
            
            // Generate tabs HTML
            let tabsHtml = '<div class="wpbnp-icon-tabs">';
            Object.keys(iconLibraries).forEach(libKey => {
                const lib = iconLibraries[libKey];
                const iconCount = Object.keys(lib.icons).length;
                tabsHtml += `
                    <button type="button" class="wpbnp-icon-tab" data-library="${libKey}">
                        ${lib.name} (${iconCount})
                    </button>
                `;
            });
            tabsHtml += '</div>';
            
            // Generate content HTML for each library
            let contentHtml = '<div class="wpbnp-icon-content">';
            Object.keys(iconLibraries).forEach(libKey => {
                const lib = iconLibraries[libKey];
                contentHtml += `<div class="wpbnp-icon-library-content" data-library="${libKey}">`;
                
                Object.keys(lib.icons).forEach(iconKey => {
                    const iconName = lib.icons[iconKey];
                    let iconClass = '';
                    let iconElement = '';
                    
                    // Generate proper icon markup based on library type
                    if (libKey === 'dashicons') {
                        iconClass = `dashicons ${iconKey}`;
                        iconElement = `<span class="${iconClass}"></span>`;
                    } else if (libKey === 'fontawesome') {
                        iconClass = `fas fa-${iconKey}`;
                        iconElement = `<i class="${iconClass}"></i>`;
                    } else if (libKey === 'bootstrap') {
                        iconClass = `bi bi-${iconKey}`;
                        iconElement = `<i class="${iconClass}"></i>`;
                    } else if (libKey === 'material') {
                        iconClass = 'material-icons';
                        iconElement = `<span class="${iconClass}">${iconKey}</span>`;
                    } else if (libKey === 'feather') {
                        iconClass = `feather-${iconKey}`;
                        iconElement = `<i class="${iconClass}"></i>`;
                    } else if (libKey === 'apple') {
                        iconClass = `apple-${iconKey}`;
                        iconElement = `<span class="${iconClass}"></span>`;
                    }
                    
                    // Store the actual icon identifier for saving
                    let saveValue = '';
                    if (libKey === 'dashicons') {
                        saveValue = iconKey;
                    } else if (libKey === 'fontawesome') {
                        saveValue = `fas fa-${iconKey}`;
                    } else if (libKey === 'bootstrap') {
                        saveValue = `bi bi-${iconKey}`;
                    } else if (libKey === 'material') {
                        saveValue = iconKey; // Material icons use the text content
                    } else if (libKey === 'feather') {
                        saveValue = `feather-${iconKey}`;
                    } else if (libKey === 'apple') {
                        saveValue = `apple-${iconKey}`;
                    }
                    
                    contentHtml += `
                        <div class="wpbnp-icon-option" data-icon="${saveValue}" data-library="${libKey}" title="${iconName}">
                            ${iconElement}
                            <span class="wpbnp-icon-name">${iconName}</span>
                        </div>
                    `;
                });
                
                contentHtml += '</div>';
            });
            contentHtml += '</div>';
            
            // Complete modal HTML
            const modalHtml = `
                <div id="wpbnp-icon-modal" class="wpbnp-modal" style="display: none;">
                    <div class="wpbnp-modal-content">
                        <div class="wpbnp-modal-header">
                            <h3>Choose Icon</h3>
                            <button type="button" class="wpbnp-modal-close">&times;</button>
                        </div>
                        <div class="wpbnp-modal-body">
                            <div class="wpbnp-icon-search-wrapper">
                                <input type="text" id="wpbnp-icon-search" placeholder="Search icons..." />
                                <div class="wpbnp-icon-stats">
                                    <span id="wpbnp-current-library">All Icons</span> | 
                                    <span id="wpbnp-icon-count">0 icons</span>
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
            
            // Bind events
            this.bindIconModalEvents();
        },
        
        // Apply preset
        applyPreset: function(e) {
            e.preventDefault();
            const presetKey = $(e.currentTarget).data('preset');
            const preset = this.presets[presetKey];
            
            if (!preset) return;
            
            // Define icon library mapping for each preset
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
            
            // Get recommended icon library for this preset
            const recommendedIconLibrary = presetIconMapping[presetKey] || 'dashicons';
            
            // Icon conversion mapping between libraries
            const iconConversion = {
                // Common icon mappings
                'home': {
                    'dashicons': 'dashicons-admin-home',
                    'fontawesome': 'fas fa-home',
                    'bootstrap': 'bi bi-house',
                    'material': 'home',
                    'apple': 'apple-house-fill'
                },
                'cart': {
                    'dashicons': 'dashicons-cart',
                    'fontawesome': 'fas fa-shopping-cart',
                    'bootstrap': 'bi bi-cart',
                    'material': 'shopping_cart',
                    'apple': 'apple-cart-fill'
                },
                'user': {
                    'dashicons': 'dashicons-admin-users',
                    'fontawesome': 'fas fa-user',
                    'bootstrap': 'bi bi-person',
                    'material': 'person',
                    'apple': 'apple-person-fill'
                },
                'heart': {
                    'dashicons': 'dashicons-heart',
                    'fontawesome': 'fas fa-heart',
                    'bootstrap': 'bi bi-heart-fill',
                    'material': 'favorite',
                    'apple': 'apple-heart-fill'
                },
                'search': {
                    'dashicons': 'dashicons-search',
                    'fontawesome': 'fas fa-search',
                    'bootstrap': 'bi bi-search',
                    'material': 'search',
                    'apple': 'apple-magnifyingglass'
                },
                'settings': {
                    'dashicons': 'dashicons-admin-settings',
                    'fontawesome': 'fas fa-cog',
                    'bootstrap': 'bi bi-gear',
                    'material': 'settings',
                    'apple': 'apple-gearshape'
                },
                'star': {
                    'dashicons': 'dashicons-star-filled',
                    'fontawesome': 'fas fa-star',
                    'bootstrap': 'bi bi-star-fill',
                    'material': 'star',
                    'apple': 'apple-star-fill'
                },
                'message': {
                    'dashicons': 'dashicons-email',
                    'fontawesome': 'fas fa-message',
                    'bootstrap': 'bi bi-chat',
                    'material': 'message',
                    'apple': 'apple-message'
                },
                'camera': {
                    'dashicons': 'dashicons-camera',
                    'fontawesome': 'fas fa-camera',
                    'bootstrap': 'bi bi-camera',
                    'material': 'camera_alt',
                    'apple': 'apple-camera'
                },
                'menu': {
                    'dashicons': 'dashicons-menu',
                    'fontawesome': 'fas fa-bars',
                    'bootstrap': 'bi bi-list',
                    'material': 'menu',
                    'apple': 'apple-list-bullet'
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
            
            // CRITICAL: Auto-convert existing item icons to match preset's icon library
            if (this.settings.items && this.settings.items.length > 0) {
                let iconsChanged = 0;
                
                this.settings.items.forEach((item, index) => {
                    if (item.icon) {
                        // Detect current icon type
                        let currentIconType = 'dashicons';
                        if (item.icon.startsWith('apple-')) {
                            currentIconType = 'apple';
                        } else if (item.icon.startsWith('material-')) {
                            currentIconType = 'material';
                        }
                        
                        // Only convert if different from recommended library
                        if (currentIconType !== recommendedIconLibrary) {
                            // Try to find a matching icon in the conversion table
                            let convertedIcon = null;
                            
                            // Search through conversion mappings
                            Object.keys(iconConversion).forEach(iconType => {
                                const mapping = iconConversion[iconType];
                                if (mapping[currentIconType] === item.icon) {
                                    convertedIcon = mapping[recommendedIconLibrary];
                                }
                            });
                            
                            // If we found a conversion, apply it
                            if (convertedIcon) {
                                item.icon = convertedIcon;
                                iconsChanged++;
                                
                                // Update the UI immediately
                                const iconInput = $(`.wpbnp-nav-item-row:eq(${index}) .wpbnp-icon-input`);
                                if (iconInput.length) {
                                    iconInput.val(convertedIcon);
                                }
                            }
                        }
                    }
                });
                
                // Show notification about icon conversions
                if (iconsChanged > 0) {
                    this.showNotification(`Converted ${iconsChanged} icon(s) to ${recommendedIconLibrary.toUpperCase()} library for ${preset.name} preset!`, 'info');
                    
                    // Update the items data
                    this.updateItemsData();
                }
            }
            
            // Update preset selector
            $('input[name="settings[preset]"]').val(presetKey);
            $('.wpbnp-preset-card').removeClass('active');
            $(`.wpbnp-preset-card[data-preset="${presetKey}"]`).addClass('active');
            
            // Show success notification with icon library info
            this.showNotification(`🎨 ${preset.name} preset applied! Using ${recommendedIconLibrary.toUpperCase()} icons for optimal design.`, 'success');
            
            // Auto-save the form after preset application
            this.saveFormState();
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
                $('#wpbnp-current-library').text($(this).text());
                WPBottomNavAdmin.updateIconCount();
                $('#wpbnp-icon-search').val('').trigger('input');
            });

            $(document).on('input', '#wpbnp-icon-search', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.wpbnp-icon-option').each(function() {
                    const iconName = $(this).find('.wpbnp-icon-name').text().toLowerCase();
                    const iconClass = $(this).data('icon').toLowerCase();
                    if (iconName.includes(searchTerm) || iconClass.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
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