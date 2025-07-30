jQuery(document).ready(function ($) {
    'use strict';

    // CRITICAL: Immediate checkbox state preservation - runs before everything else
    (function () {
        const savedState = localStorage.getItem('wpbnp_form_state');
        if (savedState) {
            try {
                const formData = JSON.parse(savedState);
                if (formData['settings[enabled]'] !== undefined) {
                    // Use MutationObserver to watch for the checkbox and restore it immediately
                    const observer = new MutationObserver(function (mutations) {
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

        init: function () {
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
                
                // Restore loaded preset items if any
                this.restoreLoadedPresetItems();
            }, 200);
        },

        // Get current tab from URL or default
        getCurrentTab: function () {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tab') || 'items';
        },

        // Load form data from settings
        loadFormData: function () {
            this.populateFormFields();
        },

        // Populate form fields with current settings
        populateFormFields: function () {
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
        initializeItems: function () {
            const itemsList = $('#wpbnp-items-list');
            if (itemsList.length && this.settings.items) {
                itemsList.empty();
                this.settings.items.forEach((item, index) => {
                    this.addItemRow(item, index);
                });
            }
        },

        // Add new item row
        addItemRow: function (item = null, index = null) {
            if (index === null) {
                index = this.settings.items ? this.settings.items.length : 0;
            }

            const defaultItem = {
                id: `item_${index}`,
                label: 'New Item',
                icon: 'bi bi-house-door',
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
        bindEvents: function () {
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

            // Handle preset update and cancel buttons
            $(document).on('click', '.wpbnp-update-preset-btn', (e) => {
                const presetId = $(e.target).data('preset-id');
                this.updatePresetItems(presetId);
            });

            $(document).on('click', '.wpbnp-cancel-preset-edit', (e) => {
                const presetId = $(e.target).data('preset-id');
                this.cancelPresetEdit(presetId);
            });
        },

        // Debounce function to prevent excessive saves
        debounce: function (func, wait) {
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
        handleTabSwitch: function (e) {
            console.log('Tab switch triggered:', e.target, e.currentTarget);

            // Get the actual tab link (might be e.target or its parent)
            const tabLink = $(e.target).closest('.wpbnp-tab')[0];
            const targetHref = tabLink ? tabLink.href : null;

            console.log('Tab link found:', tabLink, 'href:', targetHref);

            // Only proceed if we have a valid href
            if (!targetHref) {
                console.warn('Tab click without valid href, ignoring');
                return;
            }

            // Save current form state before switching tabs
            this.saveFormState();

            // Small delay to ensure state is saved before navigation
            setTimeout(() => {
                console.log('Navigating to:', targetHref);
                // Let the navigation proceed
                window.location.href = targetHref;
            }, 50);

            // Prevent immediate navigation to allow state saving
            e.preventDefault();
        },

        // Save current form state to localStorage
        saveFormState: function () {
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

            // CRITICAL: Save custom presets data separately
            const customPresets = this.getCustomPresetsData();
            if (customPresets.length > 0) {
                formData['wpbnp_custom_presets_data'] = JSON.stringify(customPresets);
                console.log('Saved custom presets data:', customPresets);
            }

            localStorage.setItem('wpbnp_form_state', JSON.stringify(formData));
            console.log('Form state saved to localStorage:', formData);
        },

        // Get form data
        getFormData: function () {
            const formData = {};
            const form = $('#wpbnp-settings-form');

            // Get all form inputs
            form.find('input, select, textarea').each(function () {
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

        // Get page targeting configurations from DOM
        getPageTargetingConfigurations: function () {
            const configurations = [];
            console.log('getPageTargetingConfigurations: Starting to collect configurations...');
            console.log('Total .wpbnp-config-item elements found:', $('.wpbnp-config-item').length);
            
            // First, try to collect from visible DOM elements
            $('.wpbnp-config-item').each(function (index) {
                const $config = $(this);
                const configId = $config.data('config-id');
                
                console.log('Processing visible config item', index, 'with config-id:', configId);
                
                if (!configId) {
                    console.warn('Configuration item missing config-id');
                    return;
                }
                
                const config = {
                    id: configId,
                    name: $config.find('input[name*="[name]"]').val() || 'Untitled Configuration',
                    priority: parseInt($config.find('input[name*="[priority]"]').val()) || 1,
                    preset_id: $config.find('select[name*="[preset_id]"]').val() || 'default',
                    conditions: {
                        pages: [],
                        post_types: [],
                        categories: [],
                        user_roles: []
                    }
                };
                
                console.log('Collected config data for', configId, ':', {
                    name: config.name,
                    priority: config.priority,
                    preset_id: config.preset_id
                });
                
                // Collect selected pages
                $config.find('select[name*="[conditions][pages][]"] option:selected').each(function () {
                    const value = $(this).val();
                    if (value && value !== '') {
                        config.conditions.pages.push(value);
                    }
                });
                
                // Collect selected post types
                $config.find('select[name*="[conditions][post_types][]"] option:selected').each(function () {
                    const value = $(this).val();
                    if (value && value !== '') {
                        config.conditions.post_types.push(value);
                    }
                });
                
                // Collect selected categories
                $config.find('select[name*="[conditions][categories][]"] option:selected').each(function () {
                    const value = $(this).val();
                    if (value && value !== '') {
                        config.conditions.categories.push(value);
                    }
                });
                
                // Collect selected user roles
                $config.find('select[name*="[conditions][user_roles][]"] option:selected').each(function () {
                    const value = $(this).val();
                    if (value && value !== '') {
                        config.conditions.user_roles.push(value);
                    }
                });
                
                configurations.push(config);
                console.log('Collected configuration:', config.name, 'with ID:', config.id);
            });
            
            console.log('Total configurations collected from visible DOM:', configurations.length);
            
            // If no configurations found in visible DOM, try to collect from hidden fields
            if (configurations.length === 0) {
                console.log('No visible configurations found, checking hidden fields...');
                
                // Find all hidden fields for page targeting configurations
                const hiddenConfigIds = new Set();
                $('input[name*="settings[page_targeting][configurations]"][name*="[id]"]').each(function() {
                    const name = $(this).attr('name');
                    const match = name.match(/settings\[page_targeting\]\[configurations\]\[([^\]]+)\]\[id\]/);
                    if (match) {
                        hiddenConfigIds.add(match[1]);
                    }
                });
                
                console.log('Found hidden config IDs:', Array.from(hiddenConfigIds));
                
                // Collect configurations from hidden fields
                hiddenConfigIds.forEach(configId => {
                    const config = {
                        id: configId,
                        name: $(`input[name="settings[page_targeting][configurations][${configId}][name]"]`).val() || 'Untitled Configuration',
                        priority: parseInt($(`input[name="settings[page_targeting][configurations][${configId}][priority]"]`).val()) || 1,
                        preset_id: $(`input[name="settings[page_targeting][configurations][${configId}][preset_id]"]`).val() || 'default',
                        conditions: {
                            pages: [],
                            post_types: [],
                            categories: [],
                            user_roles: []
                        }
                    };
                    
                    // Collect conditions from hidden fields
                    $(`input[name="settings[page_targeting][configurations][${configId}][conditions][pages][]"]`).each(function() {
                        const value = $(this).val();
                        if (value && value !== '') {
                            config.conditions.pages.push(value);
                        }
                    });
                    
                    $(`input[name="settings[page_targeting][configurations][${configId}][conditions][post_types][]"]`).each(function() {
                        const value = $(this).val();
                        if (value && value !== '') {
                            config.conditions.post_types.push(value);
                        }
                    });
                    
                    $(`input[name="settings[page_targeting][configurations][${configId}][conditions][categories][]"]`).each(function() {
                        const value = $(this).val();
                        if (value && value !== '') {
                            config.conditions.categories.push(value);
                        }
                    });
                    
                    $(`input[name="settings[page_targeting][configurations][${configId}][conditions][user_roles][]"]`).each(function() {
                        const value = $(this).val();
                        if (value && value !== '') {
                            config.conditions.user_roles.push(value);
                        }
                    });
                    
                    configurations.push(config);
                    console.log('Collected hidden config:', config.name, 'with ID:', config.id);
                });
            }
            
            console.log('Total configurations collected (visible + hidden):', configurations.length);
            return configurations;
        },

        // Get custom presets data from DOM with proper ID handling
        getCustomPresetsData: function () {
            const presets = [];
            console.log('getCustomPresetsData: Starting to collect presets...');
            console.log('Total .wpbnp-preset-item elements found:', $('.wpbnp-preset-item').length);
            
            // First, try to collect from visible DOM elements
            $('.wpbnp-preset-item').each(function () {
                const $item = $(this);
                const presetId = $item.data('preset-id');
                
                console.log('Processing visible preset item with preset-id:', presetId);
                
                const preset = {
                    id: presetId,
                    name: $item.find('.wpbnp-preset-name').text().trim(),
                    description: $item.find('.wpbnp-preset-description').text().trim() || '',
                    created_at: parseInt($item.find('input[name*="[created_at]"]').val()) || Math.floor(Date.now() / 1000),
                    items: []
                };

                // Get items from hidden input using the preset ID
                const itemsJson = $item.find(`input[name="settings[custom_presets][presets][${presetId}][items]"]`).val();
                if (itemsJson) {
                    try {
                        preset.items = JSON.parse(itemsJson.replace(/&quot;/g, '"'));
                    } catch (e) {
                        console.warn('Failed to parse preset items for preset', presetId, ':', e);
                        preset.items = [];
                    }
                }

                if (preset.id && preset.name) {
                    presets.push(preset);
                    console.log('Collected preset data:', preset.name, 'with ID:', preset.id, 'items:', preset.items.length);
                }
            });

            console.log('Total presets collected from visible DOM:', presets.length);
            
            // If no presets found in visible DOM, try to collect from hidden fields
            if (presets.length === 0) {
                console.log('No visible presets found, checking hidden fields...');
                
                // Find all hidden fields for custom presets
                const hiddenPresetIds = new Set();
                $('input[name*="settings[custom_presets][presets]"][name*="[id]"]').each(function() {
                    const name = $(this).attr('name');
                    const match = name.match(/settings\[custom_presets\]\[presets\]\[([^\]]+)\]\[id\]/);
                    if (match) {
                        hiddenPresetIds.add(match[1]);
                    }
                });
                
                console.log('Found hidden preset IDs:', Array.from(hiddenPresetIds));
                
                // Collect presets from hidden fields
                hiddenPresetIds.forEach(presetId => {
                    const preset = {
                        id: presetId,
                        name: $(`input[name="settings[custom_presets][presets][${presetId}][name]"]`).val() || 'Untitled Preset',
                        description: $(`input[name="settings[custom_presets][presets][${presetId}][description]"]`).val() || '',
                        created_at: parseInt($(`input[name="settings[custom_presets][presets][${presetId}][created_at]"]`).val()) || Math.floor(Date.now() / 1000),
                        items: []
                    };
                    
                    // Get items from hidden field
                    const itemsJson = $(`input[name="settings[custom_presets][presets][${presetId}][items]"]`).val();
                    if (itemsJson) {
                        try {
                            preset.items = JSON.parse(itemsJson.replace(/&quot;/g, '"'));
                        } catch (e) {
                            console.warn('Failed to parse hidden preset items for preset', presetId, ':', e);
                            preset.items = [];
                        }
                    }
                    
                    presets.push(preset);
                    console.log('Collected hidden preset:', preset.name, 'with ID:', preset.id, 'items:', preset.items.length);
                });
            }
            
            console.log('Total presets collected (visible + hidden):', presets.length);
            return presets;
        },

        // Restore form state from localStorage
        restoreFormState: function () {
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

                        if (name === 'wpbnp_custom_presets_data') {
                            // Handle custom presets data separately
                            try {
                                const presetsData = JSON.parse(formData[name]);
                                if (Array.isArray(presetsData) && presetsData.length > 0) {
                                    this.restoreCustomPresets(presetsData);
                                }
                            } catch (e) {
                                console.warn('Error parsing custom presets data:', e);
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
        handleFormSubmit: function (e) {
            e.preventDefault();

            const formData = new FormData(e.target);

            // Critical fix: Ensure unchecked checkboxes are handled properly
            // FormData doesn't include unchecked checkboxes, so we need to explicitly add them
            $('#wpbnp-settings-form input[type="checkbox"]').each(function () {
                const checkbox = $(this);
                const name = checkbox.attr('name');
                if (name && !formData.has(name)) {
                    // If checkbox is not in FormData (meaning it's unchecked), add it as '0'
                    formData.append(name, '0');
                }
            });

            // CRITICAL: Custom presets are already in the form as hidden fields
            // No need to send JSON data separately - the form fields will handle it
            console.log('Looking for custom presets in DOM...');
            console.log('Found .wpbnp-preset-item elements:', $('.wpbnp-preset-item').length);
            console.log('Found hidden custom preset fields:', $('input[name*="settings[custom_presets][presets]"][name*="[id]"]').length);
            
            const customPresets = this.getCustomPresetsData();
            console.log('Custom presets in form:', customPresets.length, 'presets');
            console.log('Custom presets found:', customPresets);
            
            // CRITICAL: Collect page targeting configurations from DOM and add to form data
            console.log('Looking for page targeting configurations in DOM...');
            console.log('Found .wpbnp-config-item elements:', $('.wpbnp-config-item').length);
            console.log('Current tab:', window.location.hash);
            console.log('All form elements:', $('#wpbnp-settings-form').find('input, select').length);
            
            $('.wpbnp-config-item').each(function(index) {
                console.log('Config item', index, ':', $(this).data('config-id'), $(this).find('.wpbnp-config-name').text());
                console.log('Config item HTML:', $(this).html().substring(0, 200) + '...');
            });
            
            const pageTargetingConfigs = this.getPageTargetingConfigurations();
            console.log('Page targeting configurations in form:', pageTargetingConfigs.length, 'configs');
            console.log('Configurations found:', pageTargetingConfigs);
            
            // Add page targeting configurations to form data
            pageTargetingConfigs.forEach((config, index) => {
                console.log('Adding config to form data:', config.id, config.name);
                formData.append(`settings[page_targeting][configurations][${config.id}][id]`, config.id);
                formData.append(`settings[page_targeting][configurations][${config.id}][name]`, config.name);
                formData.append(`settings[page_targeting][configurations][${config.id}][priority]`, config.priority);
                formData.append(`settings[page_targeting][configurations][${config.id}][preset_id]`, config.preset_id);
                
                // Add conditions
                if (config.conditions.pages && config.conditions.pages.length > 0) {
                    config.conditions.pages.forEach(pageId => {
                        formData.append(`settings[page_targeting][configurations][${config.id}][conditions][pages][]`, pageId);
                    });
                }
                if (config.conditions.post_types && config.conditions.post_types.length > 0) {
                    config.conditions.post_types.forEach(postType => {
                        formData.append(`settings[page_targeting][configurations][${config.id}][conditions][post_types][]`, postType);
                    });
                }
                if (config.conditions.categories && config.conditions.categories.length > 0) {
                    config.conditions.categories.forEach(categoryId => {
                        formData.append(`settings[page_targeting][configurations][${config.id}][conditions][categories][]`, categoryId);
                    });
                }
                if (config.conditions.user_roles && config.conditions.user_roles.length > 0) {
                    config.conditions.user_roles.forEach(role => {
                        formData.append(`settings[page_targeting][configurations][${config.id}][conditions][user_roles][]`, role);
                    });
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
                    console.log('Server response:', response);
                    
                    if (response.success) {
                        this.showNotification(wpbnp_admin.strings.saved || 'Settings saved successfully!', 'success');
                        
                        // Update local settings from response
                        if (response.data && response.data.settings) {
                            this.settings = response.data.settings;
                            console.log('Updated local settings:', this.settings);
                            console.log('Custom presets in response:', this.settings.custom_presets);
                            
                            // CRITICAL: Update global wpbnp_admin.settings object as well
                            if (typeof wpbnp_admin !== 'undefined') {
                                wpbnp_admin.settings = response.data.settings;
                                console.log('Updated global wpbnp_admin.settings:', wpbnp_admin.settings);
                            }
                            
                            // CRITICAL: Restore custom presets from server response
                            if (this.settings.custom_presets && this.settings.custom_presets.presets) {
                                console.log('Restoring custom presets from server response:', this.settings.custom_presets.presets);
                                // Add a small delay to ensure DOM is ready
                                setTimeout(() => {
                                    this.restoreCustomPresets(this.settings.custom_presets.presets);
                                }, 100);
                            } else {
                                console.log('No custom presets in server response');
                            }
                            
                            // CRITICAL: Restore page targeting configurations from server response
                            if (this.settings.page_targeting && this.settings.page_targeting.configurations) {
                                console.log('Restoring page targeting configurations from server response:', this.settings.page_targeting.configurations);
                                // Add a small delay to ensure DOM is ready
                                setTimeout(() => {
                                    this.restorePageTargetingConfigurations(this.settings.page_targeting.configurations);
                                }, 100);
                            } else {
                                console.log('No page targeting configurations in server response');
                            }
                        } else {
                            console.warn('No settings data in server response');
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
                        const errorMessage = response.data ? response.data.message : wpbnp_admin.strings.error || 'Error saving settings';
                        console.error('Server returned error:', errorMessage);
                        this.showNotification(errorMessage, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', {xhr, status, error});
                    this.showNotification('Ajax error occurred: ' + error, 'error');
                },
                complete: () => {
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        // Restore page targeting configurations from server data
        restorePageTargetingConfigurations: function (serverConfigurations) {
            console.log('Restoring page targeting configurations from server data:', serverConfigurations);
            
            // Clear existing configurations in DOM
            $('.wpbnp-config-item').remove();
            
            // Add no configurations message if no configurations
            if (!serverConfigurations || (Array.isArray(serverConfigurations) && serverConfigurations.length === 0) || (typeof serverConfigurations === 'object' && Object.keys(serverConfigurations).length === 0)) {
                $('#wpbnp-configurations-list').html('<div class="wpbnp-no-configs">No configurations created yet. Click "Add Configuration" to get started.</div>');
                return;
            }
            
            // Handle both array and object formats
            const configsArray = Array.isArray(serverConfigurations) ? serverConfigurations : Object.values(serverConfigurations);
            
            // Add each configuration to DOM
            configsArray.forEach(config => {
                if (config.id && config.name) {
                    console.log('Restoring configuration:', config.name, 'with ID:', config.id);
                    this.addConfigurationToDOM(config);
                }
            });
            
            // Update all preset selectors
            this.updateAllPresetSelectors();
            
            console.log('Page targeting configurations restored successfully');
        },

        // Add configuration to DOM
        addConfigurationToDOM: function (config) {
            console.log('addConfigurationToDOM called for config:', config.name);
            
            const configsContainer = $('#wpbnp-configurations-list');
            const noConfigsMessage = configsContainer.find('.wpbnp-no-configs');

            if (noConfigsMessage.length) {
                noConfigsMessage.remove();
            }

            const configId = config.id;
            const priority = config.priority || 1;

            const configHtml = `
                <div class="wpbnp-config-item" data-config-id="${configId}">
                    <div class="wpbnp-config-header">
                        <div class="wpbnp-config-title">
                            <span class="wpbnp-config-name">${config.name}</span>
                            <span class="wpbnp-config-priority">Priority: ${priority}</span>
                        </div>
                        <div class="wpbnp-config-actions">
                            <button type="button" class="wpbnp-config-toggle" title="Expand/Collapse">
                                <span class="dashicons dashicons-arrow-down"></span>
                            </button>
                            <button type="button" class="wpbnp-config-delete" title="Delete Configuration">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="wpbnp-config-content" style="display: none;">
                        <div class="wpbnp-config-settings">
                            <div class="wpbnp-field">
                                <label>Configuration Name</label>
                                <input type="text" name="settings[page_targeting][configurations][${configId}][name]" 
                                       value="${config.name}" placeholder="Enter configuration name...">
                            </div>
                            
                            <div class="wpbnp-field">
                                <label>Priority</label>
                                <input type="number" name="settings[page_targeting][configurations][${configId}][priority]" 
                                       value="${priority}" min="1" max="100">
                                <p class="description">Higher priority configurations will override lower ones when conditions match.</p>
                            </div>
                            
                            <div class="wpbnp-targeting-conditions">
                                <h4>Display Conditions</h4>
                                
                                <div class="wpbnp-condition-group">
                                    <label>Specific Pages</label>
                                    <select name="settings[page_targeting][configurations][${configId}][conditions][pages][]" multiple class="wpbnp-multiselect">
                                        <option value="">Select pages...</option>
                                    </select>
                                </div>
                                
                                <div class="wpbnp-condition-group">
                                    <label>Post Types</label>
                                    <select name="settings[page_targeting][configurations][${configId}][conditions][post_types][]" multiple class="wpbnp-multiselect">
                                        <option value="">Select post types...</option>
                                    </select>
                                </div>
                                
                                <div class="wpbnp-condition-group">
                                    <label>Categories</label>
                                    <select name="settings[page_targeting][configurations][${configId}][conditions][categories][]" multiple class="wpbnp-multiselect">
                                        <option value="">Select categories...</option>
                                    </select>
                                </div>
                                
                                <div class="wpbnp-condition-group">
                                    <label>User Roles</label>
                                    <select name="settings[page_targeting][configurations][${configId}][conditions][user_roles][]" multiple class="wpbnp-multiselect">
                                        <option value="">Select user roles...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="wpbnp-navigation-config">
                                <h4>Navigation Configuration</h4>
                                
                                <div class="wpbnp-field">
                                    <label>Preset to Display</label>
                                    <select name="settings[page_targeting][configurations][${configId}][preset_id]" class="wpbnp-preset-selector">
                                        <option value="default">Default Navigation (Items Tab)</option>
                                    </select>
                                    <p class="description">Choose which navigation preset to display when the conditions above are met.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden fields -->
                    <input type="hidden" name="settings[page_targeting][configurations][${configId}][id]" value="${configId}">
                </div>
            `;

            configsContainer.append(configHtml);
            console.log('Configuration added to DOM successfully:', config.name, 'with ID:', configId);
            
            // Populate the selectors with data
            const $newConfig = configsContainer.find(`[data-config-id="${configId}"]`);
            this.populateConfigurationSelectors($newConfig, config);
        },

        // Populate configuration selectors with data
        populateConfigurationSelectors: function ($config, configData) {
            // Populate pages selector
            const $pagesSelector = $config.find('select[name*="[conditions][pages][]"]');
            if ($pagesSelector.length && configData.conditions.pages) {
                configData.conditions.pages.forEach(pageId => {
                    $pagesSelector.append(`<option value="${pageId}" selected>Page ${pageId}</option>`);
                });
            }
            
            // Populate post types selector
            const $postTypesSelector = $config.find('select[name*="[conditions][post_types][]"]');
            if ($postTypesSelector.length && configData.conditions.post_types) {
                configData.conditions.post_types.forEach(postType => {
                    $postTypesSelector.append(`<option value="${postType}" selected>${postType}</option>`);
                });
            }
            
            // Populate categories selector
            const $categoriesSelector = $config.find('select[name*="[conditions][categories][]"]');
            if ($categoriesSelector.length && configData.conditions.categories) {
                configData.conditions.categories.forEach(categoryId => {
                    $categoriesSelector.append(`<option value="${categoryId}" selected>Category ${categoryId}</option>`);
                });
            }
            
            // Populate user roles selector
            const $userRolesSelector = $config.find('select[name*="[conditions][user_roles][]"]');
            if ($userRolesSelector.length && configData.conditions.user_roles) {
                configData.conditions.user_roles.forEach(role => {
                    $userRolesSelector.append(`<option value="${role}" selected>${role}</option>`);
                });
            }
            
            // Populate preset selector
            const $presetSelector = $config.find('.wpbnp-preset-selector');
            if ($presetSelector.length && configData.preset_id) {
                $presetSelector.val(configData.preset_id);
            }
        },

        // Restore custom presets from server data
        restoreCustomPresets: function (serverPresets) {
            console.log('Restoring custom presets from server data:', serverPresets);
            
            // Clear existing presets in DOM
            $('.wpbnp-preset-item').remove();
            
            // Add no presets message if no presets
            if (!serverPresets || (Array.isArray(serverPresets) && serverPresets.length === 0) || (typeof serverPresets === 'object' && Object.keys(serverPresets).length === 0)) {
                $('#wpbnp-custom-presets-list').html('<div class="wpbnp-no-presets">No custom presets created yet.</div>');
                return;
            }
            
            // Handle both array and object formats
            const presetsArray = Array.isArray(serverPresets) ? serverPresets : Object.values(serverPresets);
            
            // Add each preset to DOM
            presetsArray.forEach(preset => {
                if (preset.id && preset.name) {
                    console.log('Restoring preset:', preset.name, 'with ID:', preset.id);
                    this.addPresetToDOM(preset);
                }
            });
            
            // Update all preset selectors
            this.updateAllPresetSelectors();
            
            console.log('Custom presets restored successfully');
        },

        // Reset settings
        resetSettings: function (e) {
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
        handleAddItem: function (e) {
            e.preventDefault();
            this.addItemRow();
        },

        // Handle toggle item
        handleToggleItem: function (e) {
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
        handleRemoveItem: function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this item?')) {
                $(e.target).closest('.wpbnp-nav-item-row').remove();
                this.reindexItems();
                this.updateItemsData();
            }
        },

        // Open icon picker modal
        openIconPicker: function (e) {
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

            // Update library indicator (Bootstrap for all presets)
            const libraryName = $(`.wpbnp-icon-tab[data-library="${recommendedLibrary}"]`).text();
            $('#wpbnp-current-library').text(`${libraryName}`);
            this.updateIconCount();

            // Highlight current selection
            $('.wpbnp-icon-option').removeClass('selected');
            $(`.wpbnp-icon-option[data-icon="${input.val()}"]`).addClass('selected');

            // Focus search
            $('#wpbnp-icon-search').focus();

            // Icon library notifications disabled (using Bootstrap for all presets)
        },

        // Create enhanced icon picker modal with multiple libraries
        createIconModal: function () {
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
        applyPreset: function (e) {
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
        doApplyPreset: function (preset, presetKey, $target, originalEvent) {
            // Define icon library mapping for each preset (Bootstrap Icons for all)
            const presetIconMapping = {
                'minimal': 'bootstrap',      // Bootstrap Icons
                'dark': 'bootstrap',         // Bootstrap Icons
                'material': 'bootstrap',     // Bootstrap Icons (simplified)
                'ios': 'bootstrap',          // Bootstrap Icons (simplified)
                'glassmorphism': 'bootstrap', // Bootstrap Icons
                'neumorphism': 'bootstrap',  // Bootstrap Icons (simplified)
                'cyberpunk': 'bootstrap',    // Bootstrap Icons (simplified)
                'vintage': 'bootstrap',      // Bootstrap Icons (simplified)
                'gradient': 'bootstrap',     // Bootstrap Icons
                'floating': 'bootstrap'      // Bootstrap Icons (simplified)
            };

            // Get recommended icon library for this preset
            const recommendedIconLibrary = presetIconMapping[presetKey] || 'bootstrap';

            // Icon conversion mapping between libraries
            const iconConversion = {
                // Common icon mappings (Bootstrap outline icons as preferred default)
                'home': {
                    'bootstrap': 'bi bi-house-door',
                    'dashicons': 'dashicons-admin-home',
                    'fontawesome': 'fas fa-home',
                    'material': 'home',
                    'apple': 'house-fill',
                    'feather': 'home'
                },
                'cart': {
                    'bootstrap': 'bi bi-cart',
                    'dashicons': 'dashicons-cart',
                    'fontawesome': 'fas fa-shopping-cart',
                    'material': 'shopping_cart',
                    'apple': 'cart-fill',
                    'feather': 'shopping-cart'
                },
                'user': {
                    'bootstrap': 'bi bi-person',
                    'dashicons': 'dashicons-admin-users',
                    'fontawesome': 'fas fa-user',
                    'material': 'person',
                    'apple': 'person-fill',
                    'feather': 'user'
                },
                'heart': {
                    'bootstrap': 'bi bi-heart',
                    'dashicons': 'dashicons-heart',
                    'fontawesome': 'fas fa-heart',
                    'material': 'favorite',
                    'apple': 'heart-fill',
                    'feather': 'heart'
                },
                'search': {
                    'bootstrap': 'bi bi-search',
                    'dashicons': 'dashicons-search',
                    'fontawesome': 'fas fa-search',
                    'material': 'search',
                    'apple': 'magnifyingglass',
                    'feather': 'search'
                },
                'settings': {
                    'bootstrap': 'bi bi-gear',
                    'dashicons': 'dashicons-admin-settings',
                    'fontawesome': 'fas fa-cog',
                    'material': 'settings',
                    'apple': 'gearshape-fill',
                    'feather': 'settings'
                },
                'star': {
                    'bootstrap': 'bi bi-star',
                    'dashicons': 'dashicons-star-filled',
                    'fontawesome': 'fas fa-star',
                    'material': 'star',
                    'apple': 'star-fill',
                    'feather': 'star'
                },
                'message': {
                    'bootstrap': 'bi bi-envelope',
                    'dashicons': 'dashicons-email',
                    'fontawesome': 'fas fa-envelope',
                    'material': 'mail',
                    'apple': 'envelope-fill',
                    'feather': 'mail'
                },
                'camera': {
                    'bootstrap': 'bi bi-camera',
                    'dashicons': 'dashicons-camera',
                    'fontawesome': 'fas fa-camera',
                    'material': 'camera_alt',
                    'apple': 'camera-fill',
                    'feather': 'camera'
                },
                'menu': {
                    'bootstrap': 'bi bi-list',
                    'dashicons': 'dashicons-menu',
                    'fontawesome': 'fas fa-bars',
                    'material': 'menu',
                    'apple': 'list-bullet',
                    'feather': 'menu'
                },
                'phone': {
                    'bootstrap': 'bi bi-telephone',
                    'dashicons': 'dashicons-phone',
                    'fontawesome': 'fas fa-phone',
                    'material': 'phone',
                    'apple': 'phone-fill',
                    'feather': 'phone'
                },
                'info': {
                    'bootstrap': 'bi bi-info-circle',
                    'dashicons': 'dashicons-info',
                    'fontawesome': 'fas fa-info-circle',
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

            // DISABLED: Auto-conversion (keeping Bootstrap Icons for all presets for simplicity)
            if (false && this.settings.items && this.settings.items.length > 0) {
                // Conversion disabled - using Bootstrap Icons universally
                const shouldConvertIcons = false; // Disabled

                if (shouldConvertIcons) {
                    let iconsChanged = 0;

                    // Simplified conversion - only convert obvious mismatches
                    this.settings.items.forEach((item, index) => {
                        if (item.icon && this.needsIconConversion(item.icon, recommendedIconLibrary)) {
                            const convertedIcon = this.getSimpleIconConversion(item.icon, recommendedIconLibrary, index);
                            if (convertedIcon && convertedIcon !== item.icon) {
                                item.icon = convertedIcon;
                                iconsChanged++;

                                // Icon updated in memory, UI will be refreshed after loop
                            }
                        }
                    });

                    // Icon conversion disabled - using Bootstrap Icons for all presets
                }
            }

            // If no items exist, create default preset-appropriate items
            if (!this.settings.items || this.settings.items.length === 0) {
                // Same 3 default items (Home, Shop, Account) with Bootstrap outline icons for all presets
                const defaultPresetItems = {
                    'minimal': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'dark': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'material': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'ios': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'glassmorphism': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'neumorphism': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'cyberpunk': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'vintage': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'gradient': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ],
                    'floating': [
                        { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                        { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                        { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                    ]
                };

                // Use preset default items, fallback to Bootstrap outline icons
                const defaultItems = defaultPresetItems[presetKey] || [
                    { id: 'home', label: 'Home', icon: 'bi bi-house-door', url: wpbnp_admin.home_url, enabled: true },
                    { id: 'shop', label: 'Shop', icon: 'bi bi-cart', url: '#', enabled: true },
                    { id: 'account', label: 'Account', icon: 'bi bi-person', url: '#', enabled: true }
                ];
                this.settings.items = [...defaultItems];

                // Re-render items list (batched)
                setTimeout(() => {
                    this.refreshItemsList();
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
        needsIconConversion: function (iconClass, targetLibrary) {
            if (!iconClass || !targetLibrary) return false;

            const iconType = this.getIconType(iconClass);
            return iconType !== targetLibrary;
        },

        // Get icon type (simplified detection)
        getIconType: function (iconClass) {
            if (iconClass.startsWith('bi bi-')) return 'bootstrap';
            if (iconClass.startsWith('dashicons-')) return 'dashicons';
            if (iconClass.startsWith('fas fa-') || iconClass.startsWith('far fa-') || iconClass.startsWith('fab fa-')) return 'fontawesome';
            if (iconClass.startsWith('feather-')) return 'feather';
            if (this.isAppleIcon(iconClass)) return 'apple';
            if (!iconClass.includes('-') && !iconClass.includes(' ') && !iconClass.includes('<')) return 'material';
            return 'bootstrap'; // default changed to bootstrap
        },

        // Simple icon conversion (basic mapping only)
        getSimpleIconConversion: function (iconClass, targetLibrary, index) {
            // Basic conversion map for common icons only (Bootstrap outline icons as default)
            const basicConversions = {
                'home': {
                    'bootstrap': 'bi bi-house-door',
                    'dashicons': 'dashicons-admin-home',
                    'fontawesome': 'fas fa-home',
                    'material': 'home',
                    'apple': 'house-fill',
                    'feather': 'feather-home'
                },
                'user': {
                    'bootstrap': 'bi bi-person',
                    'dashicons': 'dashicons-admin-users',
                    'fontawesome': 'fas fa-user',
                    'material': 'person',
                    'apple': 'person-fill',
                    'feather': 'feather-user'
                },
                'account': {  // Added account mapping
                    'bootstrap': 'bi bi-person',
                    'dashicons': 'dashicons-admin-users',
                    'fontawesome': 'fas fa-user',
                    'material': 'person',
                    'apple': 'person-fill',
                    'feather': 'feather-user'
                },
                'search': {
                    'bootstrap': 'bi bi-search',
                    'dashicons': 'dashicons-search',
                    'fontawesome': 'fas fa-search',
                    'material': 'search',
                    'apple': 'magnifyingglass',
                    'feather': 'feather-search'
                },
                'cart': {
                    'bootstrap': 'bi bi-cart',
                    'dashicons': 'dashicons-cart',
                    'fontawesome': 'fas fa-shopping-cart',
                    'material': 'shopping_cart',
                    'apple': 'cart-fill',
                    'feather': 'feather-shopping-cart'
                },
                'shop': {  // Added shop mapping
                    'bootstrap': 'bi bi-cart',
                    'dashicons': 'dashicons-cart',
                    'fontawesome': 'fas fa-shopping-cart',
                    'material': 'shopping_cart',
                    'apple': 'cart-fill',
                    'feather': 'feather-shopping-cart'
                },
                'heart': {
                    'bootstrap': 'bi bi-heart',
                    'dashicons': 'dashicons-heart',
                    'fontawesome': 'fas fa-heart',
                    'material': 'favorite',
                    'apple': 'heart-fill',
                    'feather': 'feather-heart'
                },
                'settings': {
                    'bootstrap': 'bi bi-gear',
                    'dashicons': 'dashicons-admin-settings',
                    'fontawesome': 'fas fa-cog',
                    'material': 'settings',
                    'apple': 'gearshape-fill',
                    'feather': 'feather-settings'
                }
            };

            // Try to find conversion by checking if current icon exists in any mapping
            for (const [key, mapping] of Object.entries(basicConversions)) {
                if (Object.values(mapping).includes(iconClass)) {
                    return mapping[targetLibrary] || iconClass;
                }
            }

            // Special handling for common Bootstrap to Apple conversions
            const bootstrapToAppleMap = {
                'bi bi-house-door': 'house-fill',
                'bi bi-cart': 'cart-fill',
                'bi bi-person': 'person-fill',
                'bi bi-search': 'magnifyingglass',
                'bi bi-heart': 'heart-fill',
                'bi bi-gear': 'gearshape-fill'
            };

            // Special handling for common Dashicons to Apple conversions  
            const dashiconsToAppleMap = {
                'dashicons-admin-home': 'house-fill',
                'dashicons-cart': 'cart-fill',
                'dashicons-admin-users': 'person-fill',
                'dashicons-search': 'magnifyingglass',
                'dashicons-heart': 'heart-fill',
                'dashicons-admin-settings': 'gearshape-fill'
            };

            // Apply direct mappings based on target library
            if (targetLibrary === 'apple') {
                if (bootstrapToAppleMap[iconClass]) {
                    return bootstrapToAppleMap[iconClass];
                }
                if (dashiconsToAppleMap[iconClass]) {
                    return dashiconsToAppleMap[iconClass];
                }
            } else if (targetLibrary === 'bootstrap') {
                // Reverse mappings for converting TO Bootstrap
                const appleToBootstrapMap = {
                    'house-fill': 'bi bi-house-door',
                    'cart-fill': 'bi bi-cart',
                    'person-fill': 'bi bi-person',
                    'magnifyingglass': 'bi bi-search',
                    'heart-fill': 'bi bi-heart',
                    'gearshape-fill': 'bi bi-gear'
                };
                if (appleToBootstrapMap[iconClass]) {
                    return appleToBootstrapMap[iconClass];
                }
            } else if (targetLibrary === 'material') {
                // Common conversions to Material
                const toMaterialMap = {
                    'bi bi-house-door': 'home',
                    'bi bi-cart': 'shopping_cart',
                    'bi bi-person': 'person',
                    'house-fill': 'home',
                    'cart-fill': 'shopping_cart',
                    'person-fill': 'person',
                    'dashicons-admin-home': 'home',
                    'dashicons-cart': 'shopping_cart',
                    'dashicons-admin-users': 'person'
                };
                if (toMaterialMap[iconClass]) {
                    return toMaterialMap[iconClass];
                }
            }

            return iconClass; // Return original if no conversion found
        },

        // Generate HTML for icon preview
        generateIconHTML: function (iconClass) {
            if (!iconClass) return '';

            // Handle different icon types (Bootstrap first as default)
            if (iconClass.startsWith('bi bi-')) {
                return `<i class="${iconClass}"></i>`;
            } else if (iconClass.startsWith('dashicons-')) {
                return `<span class="dashicons ${iconClass}"></span>`;
            } else if (iconClass.startsWith('fas fa-') || iconClass.startsWith('far fa-') || iconClass.startsWith('fab fa-')) {
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
        isAppleIcon: function (iconClass) {
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
        initializeColorPickers: function () {
            if ($.fn.wpColorPicker) {
                $('.wpbnp-color-picker').wpColorPicker();
            }
        },

        // Setup sortable
        setupSortable: function () {
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
        reindexItems: function () {
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function (index) {
                $(this).attr('data-index', index);
                $(this).find('input').each(function () {
                    const name = $(this).attr('name');
                    if (name && name.includes('[items][')) {
                        const newName = name.replace(/\[items\]\[\d+\]/, `[items][${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
        },

        // Update items data in memory
        updateItemsData: function () {
            const items = [];
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function () {
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

        // Refresh the items list display
        refreshItemsList: function () {
            // Clear existing items
            $('#wpbnp-items-list').empty();

            // Re-render all items from current settings
            if (this.settings.items && this.settings.items.length > 0) {
                this.settings.items.forEach((item, index) => {
                    this.addItemRow(item, index);
                });
            }

            // Re-setup sortable after refresh
            this.setupSortable();
        },

        // Save settings programmatically (for auto-save after conversions)
        saveSettings: function () {
            // Get current form data
            const form = document.getElementById('wpbnp-settings-form');
            if (!form) {
                console.error('Settings form not found');
                return;
            }

            const formData = new FormData(form);

            // Handle unchecked checkboxes
            $('#wpbnp-settings-form input[type="checkbox"]').each(function () {
                const checkbox = $(this);
                const name = checkbox.attr('name');
                if (name && !formData.has(name)) {
                    formData.append(name, '0');
                }
            });

            formData.append('action', 'wpbnp_save_settings');
            formData.append('nonce', this.nonce);

            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        // Update local settings from response
                        if (response.data && response.data.settings) {
                            this.settings = response.data.settings;
                        }
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error during auto-save:', error);
                }
            });
        },

        // Export settings
        exportSettings: function (e) {
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
                        const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
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
        importSettings: function (e) {
            e.preventDefault();
            $('#wpbnp-import-file').click();
        },

        // Show notification
        showNotification: function (message, type = 'success') {
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
            notification.find('.wpbnp-notification-close').on('click', function () {
                notification.fadeOut(() => {
                    notification.remove();
                });
            });
        },

        // Update icon count in the modal
        updateIconCount: function () {
            const totalIcons = $('.wpbnp-icon-option').length;
            $('#wpbnp-icon-count').text(`${totalIcons} icons`);
        },

        // Update library info in the modal
        updateLibraryInfo: function () {
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
        updateIconCount: function () {
            const totalIcons = $('.wpbnp-icon-library-content.active .wpbnp-icon-option').length;
            const visibleIcons = $('.wpbnp-icon-library-content.active .wpbnp-icon-option:visible').length;
            $('#wpbnp-icon-count').text(`${totalIcons} icons`);
            $('#wpbnp-visible-count').text(`${visibleIcons} visible`);
        },

        // Bind events for the icon picker modal
        bindIconModalEvents: function () {
            $(document).on('click', '.wpbnp-modal-close, .wpbnp-modal', function (e) {
                if (e.target === this) {
                    $('#wpbnp-icon-modal').hide();
                }
            });

            $(document).on('click', '.wpbnp-icon-option', function () {
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

            $(document).on('click', '.wpbnp-icon-tab', function () {
                const library = $(this).data('library');
                $('.wpbnp-icon-tab').removeClass('active');
                $('.wpbnp-icon-library-content').removeClass('active');
                $(this).addClass('active');
                $(`.wpbnp-icon-library-content[data-library="${library}"]`).addClass('active');
                WPBottomNavAdmin.updateLibraryInfo();
                $('#wpbnp-icon-search').val('').trigger('input');
            });

            $(document).on('input', '#wpbnp-icon-search', function () {
                const searchTerm = $(this).val().toLowerCase();
                let visibleCount = 0;

                $('.wpbnp-icon-library-content.active .wpbnp-icon-option').each(function () {
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
        },

        // Initialize pro features
        initProFeatures: function () {
            // CRITICAL: Load custom presets from server data if available
            if (this.settings && this.settings.custom_presets && this.settings.custom_presets.presets) {
                console.log('Loading custom presets from settings:', this.settings.custom_presets.presets);
                this.restoreCustomPresets(this.settings.custom_presets.presets);
            } else {
                this.updateAllPresetSelectors();
            }
            
            // CRITICAL: Load page targeting configurations from server data if available
            if (this.settings && this.settings.page_targeting && this.settings.page_targeting.configurations) {
                console.log('Loading page targeting configurations from settings:', this.settings.page_targeting.configurations);
                this.restorePageTargetingConfigurations(this.settings.page_targeting.configurations);
            }

            // License activation modal
            $(document).on('click', '#wpbnp-activate-license', function (e) {
                e.preventDefault();
                $('#wpbnp-license-modal').show();
            });

            // License activation from custom presets section
            $(document).on('click', '#wpbnp-activate-license-custom-presets', function (e) {
                e.preventDefault();
                $('#wpbnp-license-modal').show();
            });

            $(document).on('click', '#wpbnp-license-modal .wpbnp-modal-close', function () {
                $('#wpbnp-license-modal').hide();
            });

            $(document).on('click', '#wpbnp-license-modal', function (e) {
                if (e.target === this) {
                    $('#wpbnp-license-modal').hide();
                }
            });

            // License activation button click
            $(document).on('click', '#wpbnp-activate-license-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('License activation button clicked');

                const licenseKey = $('#wpbnp-license-key').val().trim();
                console.log('License key:', licenseKey);
                if (!licenseKey) {
                    WPBottomNavAdmin.showNotification('Please enter a license key', 'error');
                    return;
                }

                const submitBtn = $(this);
                const originalText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Activating...');

                console.log('Making AJAX call to wpbnp_activate_license');
                console.log('AJAX URL:', wpbnp_admin.ajax_url);
                console.log('Nonce:', WPBottomNavAdmin.nonce);

                $.ajax({
                    url: wpbnp_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpbnp_activate_license',
                        license_key: licenseKey,
                        nonce: WPBottomNavAdmin.nonce
                    },
                    success: function (response) {
                        console.log('License activation response:', response);
                        if (response.success) {
                            WPBottomNavAdmin.showNotification('License activated successfully!', 'success');
                            $('#wpbnp-license-modal').hide();
                            console.log('Reloading page in 1 second...');
                            setTimeout(() => {
                                console.log('Reloading page now');
                                window.location.reload();
                            }, 1000);
                        } else {
                            console.log('License activation failed:', response);
                            WPBottomNavAdmin.showNotification(response.data ? response.data.message : 'Error activating license', 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log('AJAX error:', xhr, status, error);
                        WPBottomNavAdmin.showNotification('Ajax error occurred: ' + error, 'error');
                    },
                    complete: function () {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Page targeting configuration management
            $(document).on('click', '#wpbnp-add-config', function (e) {
                e.preventDefault();
                WPBottomNavAdmin.addPageTargetingConfig();
            });

            $(document).on('click', '.wpbnp-config-toggle', function (e) {
                e.preventDefault();
                const configItem = $(this).closest('.wpbnp-config-item');
                const content = configItem.find('.wpbnp-config-content');

                if (content.is(':visible')) {
                    content.slideUp();
                    configItem.removeClass('expanded');
                } else {
                    content.slideDown();
                    configItem.addClass('expanded');
                }
            });

            $(document).on('click', '.wpbnp-config-delete', function (e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this configuration?')) {
                    $(this).closest('.wpbnp-config-item').fadeOut(() => {
                        $(this).closest('.wpbnp-config-item').remove();
                        WPBottomNavAdmin.reindexConfigurations();
                    });
                }
            });
        },

        // Add new page targeting configuration
        addPageTargetingConfig: function () {
            try {
                const existingConfigs = $('.wpbnp-config-item').length;
                const configIndex = existingConfigs;
                const configId = 'config_' + Date.now();

                // Make AJAX call to get complete configuration HTML from server
                $.ajax({
                    url: wpbnp_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpbnp_get_configuration_template',
                        nonce: wpbnp_admin.nonce,
                        config_index: configIndex,
                        config_id: configId
                    },
                    success: (response) => {
                        if (response.success && response.data.html) {
                            if ($('.wpbnp-no-configs').length) {
                                $('.wpbnp-no-configs').remove();
                            }

                            $('#wpbnp-configurations-list').append(response.data.html);

                            // Populate custom presets
                            const $newConfig = $('.wpbnp-config-item').last();
                            const newPresetSelector = $newConfig.find('.wpbnp-preset-selector');
                            if (newPresetSelector.length > 0) {
                                this.populatePresetSelector(newPresetSelector);
                            }

                            // Save form state to preserve the new configuration
                            this.saveFormState();

                            this.showNotification('New configuration added!', 'success');
                        } else {
                            this.showNotification('Error: Could not generate configuration template', 'error');
                        }
                    },
                    error: () => {
                        this.showNotification('Error: Could not load configuration template', 'error');
                    }
                });
            } catch (error) {
                console.error('Error adding configuration:', error);
                this.showNotification('Error adding configuration: ' + error.message, 'error');
            }
                },

        // Custom Preset Management
        initCustomPresets: function () {
            // Add new custom preset
            $(document).on('click', '#wpbnp-add-custom-preset', function (e) {
                e.preventDefault();
                WPBottomNavAdmin.createCustomPreset();
            });

            // Edit custom preset items
            $(document).on('click', '.wpbnp-preset-edit-items', function (e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                WPBottomNavAdmin.editCustomPresetItems(presetId);
            });

            // Edit custom preset name/description
            $(document).on('click', '.wpbnp-preset-edit', function (e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                WPBottomNavAdmin.editCustomPreset(presetId);
            });

            // Duplicate custom preset
            $(document).on('click', '.wpbnp-preset-duplicate', function (e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                WPBottomNavAdmin.duplicateCustomPreset(presetId);
            });

            // Delete custom preset
            $(document).on('click', '.wpbnp-preset-delete', function (e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                const presetName = presetItem.find('.wpbnp-preset-name').text();

                if (confirm(`Are you sure you want to delete the preset "${presetName}"? This action cannot be undone.`)) {
                    WPBottomNavAdmin.deleteCustomPreset(presetId);
                }
            });

            // Update preset items
            $(document).on('click', '.wpbnp-update-preset-btn', function (e) {
                e.preventDefault();
                const presetId = $(this).data('preset-id');
                WPBottomNavAdmin.updatePresetItems(presetId);
            });

            // Cancel preset editing
            $(document).on('click', '.wpbnp-cancel-preset-edit', function (e) {
                e.preventDefault();
                const presetId = $(this).data('preset-id');
                WPBottomNavAdmin.cancelPresetEdit(presetId);
            });
        },

        // Create new custom preset
        createCustomPreset: function () {
            this.showCreatePresetModal();
        },

        // Show create preset modal
        showCreatePresetModal: function () {
            // Remove existing modal if any
            $('#wpbnp-create-preset-modal').remove();

            // Create modal HTML
            const modalHtml = `
                <div id="wpbnp-create-preset-modal" class="wpbnp-modal-overlay">
                    <div class="wpbnp-modal">
                        <div class="wpbnp-modal-header">
                            <div class="wpbnp-modal-header-content">
                                <div class="wpbnp-modal-icon">✨</div>
                                <div class="wpbnp-modal-title">
                                    <h3>Create Custom Preset</h3>
                                    <p>Save your current navigation configuration as a reusable preset</p>
                                </div>
                            </div>
                            <button type="button" class="wpbnp-modal-close" title="Close">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <div class="wpbnp-modal-body">
                            <form id="wpbnp-create-preset-form">
                                <div class="wpbnp-form-section">
                                    <div class="wpbnp-section-header">
                                        <div class="wpbnp-section-icon">📝</div>
                                        <div class="wpbnp-section-title">
                                            <h4>Preset Details</h4>
                                            <p>Give your preset a name and description</p>
                                        </div>
                                    </div>
                                    
                                    <div class="wpbnp-form-group">
                                        <label for="preset-name">
                                            <span class="wpbnp-label-text">Preset Name</span>
                                            <span class="wpbnp-required">*</span>
                                        </label>
                                        <div class="wpbnp-input-wrapper">
                                            <input type="text" id="preset-name" name="preset-name" 
                                                   placeholder="e.g., Homepage Navigation, Mobile Menu, etc." 
                                                   required maxlength="50">
                                            <div class="wpbnp-input-icon">📋</div>
                                        </div>
                                        <small class="wpbnp-help-text">This name will be displayed in the preset selector</small>
                                    </div>
                                    
                                    <div class="wpbnp-form-group">
                                        <label for="preset-description">
                                            <span class="wpbnp-label-text">Description</span>
                                            <span class="wpbnp-optional">(Optional)</span>
                                        </label>
                                        <div class="wpbnp-input-wrapper">
                                            <textarea id="preset-description" name="preset-description" 
                                                      placeholder="Describe what this preset is for, when to use it, etc." 
                                                      rows="3" maxlength="200"></textarea>
                                            <div class="wpbnp-input-icon">💭</div>
                                        </div>
                                        <small class="wpbnp-help-text">Help others understand when to use this preset</small>
                                    </div>
                                </div>
                                
                                <div class="wpbnp-form-section">
                                    <div class="wpbnp-section-header">
                                        <div class="wpbnp-section-icon">🎯</div>
                                        <div class="wpbnp-section-title">
                                            <h4>Navigation Items</h4>
                                            <p>Preview of items that will be included in this preset</p>
                                        </div>
                                    </div>
                                    
                                    <div class="wpbnp-current-items-preview">
                                        <div class="wpbnp-items-summary">
                                            <div class="wpbnp-items-count">
                                                <span class="wpbnp-count-badge">0</span>
                                                <span class="wpbnp-count-text">items selected</span>
                                            </div>
                                            <div class="wpbnp-items-status">
                                                <span class="wpbnp-status-indicator wpbnp-status-ready">Ready to create</span>
                                            </div>
                                        </div>
                                        <div class="wpbnp-items-list"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="wpbnp-modal-footer">
                            <div class="wpbnp-modal-actions">
                                <button type="button" class="wpbnp-btn wpbnp-btn-secondary wpbnp-modal-cancel">
                                    <span class="wpbnp-btn-icon">✕</span>
                                    <span class="wpbnp-btn-text">Cancel</span>
                                </button>
                                <button type="button" class="wpbnp-btn wpbnp-btn-primary wpbnp-create-preset-btn" disabled>
                                    <span class="wpbnp-btn-icon">✨</span>
                                    <span class="wpbnp-btn-text">Create Preset</span>
                                    <span class="wpbnp-btn-loading" style="display: none;">
                                        <svg class="wpbnp-spinner" width="16" height="16" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                                <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                            </circle>
                                        </svg>
                                        Creating...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to body
            $('body').append(modalHtml);

            // Initialize modal functionality
            this.initCreatePresetModal();

            // Show modal with animation
            setTimeout(() => {
                $('#wpbnp-create-preset-modal').addClass('wpbnp-modal-show');
                // Add a subtle entrance animation for the form
                setTimeout(() => {
                    $('#wpbnp-create-preset-form').css('opacity', '0').animate({ opacity: 1 }, 300);
                }, 100);
            }, 10);
        },

        // Initialize create preset modal
        initCreatePresetModal: function () {
            const $modal = $('#wpbnp-create-preset-modal');
            const $form = $('#wpbnp-create-preset-form');
            const $nameInput = $('#preset-name');
            const $descriptionInput = $('#preset-description');
            const $createBtn = $('.wpbnp-create-preset-btn');
            const $cancelBtn = $('.wpbnp-modal-cancel, .wpbnp-modal-close');

            // Update items preview
            this.updateCreatePresetItemsPreview();

            // Handle name input validation
            $nameInput.on('input', function() {
                const isValid = $(this).val().trim().length > 0;
                $createBtn.prop('disabled', !isValid);
            });



            // Handle form submission
            $createBtn.on('click', (e) => {
                e.preventDefault();
                this.handleCreatePresetSubmit();
            });

            // Handle cancel/close
            $cancelBtn.on('click', (e) => {
                e.preventDefault();
                this.closeCreatePresetModal();
            });

            // Handle escape key
            $(document).on('keydown.wpbnp-create-preset', (e) => {
                if (e.key === 'Escape') {
                    this.closeCreatePresetModal();
                }
            });

            // Handle click outside modal
            $modal.on('click', (e) => {
                if (e.target === $modal[0]) {
                    this.closeCreatePresetModal();
                }
            });

            // Focus on name input
            $nameInput.focus();
        },

        // Update items preview in create preset modal
        updateCreatePresetItemsPreview: function () {
            const currentItems = this.getCurrentNavigationItems();
            const $itemsList = $('.wpbnp-items-list');
            const $countBadge = $('.wpbnp-count-badge');

            // Show all current items (no filtering)
            const itemsToShow = currentItems;

            // Update count
            $countBadge.text(itemsToShow.length);

            // Update items list
            $itemsList.empty();
            itemsToShow.forEach(item => {
                const itemHtml = `
                    <div class="wpbnp-preview-item ${!item.enabled ? 'wpbnp-disabled' : ''}">
                        <span class="wpbnp-item-icon">${item.icon || '📱'}</span>
                        <span class="wpbnp-item-label">${item.label || 'Unnamed Item'}</span>
                        ${!item.enabled ? '<span class="wpbnp-disabled-badge">Disabled</span>' : ''}
                    </div>
                `;
                $itemsList.append(itemHtml);
            });

            // Update count badge color based on item count
            if (itemsToShow.length === 0) {
                $countBadge.addClass('wpbnp-count-empty');
            } else if (itemsToShow.length < 3) {
                $countBadge.addClass('wpbnp-count-warning');
            } else {
                $countBadge.removeClass('wpbnp-count-empty wpbnp-count-warning');
            }
        },

        // Update items preview in edit preset modal
        updateEditPresetItemsPreview: function (savedItems) {
            const $itemsList = $('#wpbnp-edit-preset-modal .wpbnp-items-list');
            const $countBadge = $('#wpbnp-edit-preset-modal .wpbnp-count-badge');

            // Update count
            $countBadge.text(savedItems.length);

            // Update items list
            $itemsList.empty();
            savedItems.forEach(item => {
                const itemHtml = `
                    <div class="wpbnp-preview-item ${!item.enabled ? 'wpbnp-disabled' : ''}">
                        <span class="wpbnp-item-icon">${item.icon || '📱'}</span>
                        <span class="wpbnp-item-label">${item.label || 'Unnamed Item'}</span>
                        ${!item.enabled ? '<span class="wpbnp-disabled-badge">Disabled</span>' : ''}
                    </div>
                `;
                $itemsList.append(itemHtml);
            });

            // Update count badge color based on item count
            if (savedItems.length === 0) {
                $countBadge.addClass('wpbnp-count-empty');
            } else if (savedItems.length < 3) {
                $countBadge.addClass('wpbnp-count-warning');
            } else {
                $countBadge.removeClass('wpbnp-count-empty wpbnp-count-warning');
            }
        },

        // Handle create preset form submission
        handleCreatePresetSubmit: function () {
            const $modal = $('#wpbnp-create-preset-modal');
            const $form = $('#wpbnp-create-preset-form');
            const $createBtn = $('.wpbnp-create-preset-btn');
            const $btnText = $createBtn.find('.wpbnp-btn-text');
            const $btnLoading = $createBtn.find('.wpbnp-btn-loading');

            const presetName = $('#preset-name').val().trim();
            const presetDescription = $('#preset-description').val().trim();
            const includeDisabled = $('#preset-include-disabled').is(':checked');

            if (!presetName) {
                this.showNotification('Please enter a preset name', 'error');
                $('#preset-name').focus();
                return;
            }

            // Show loading state
            $createBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();

            // Get current navigation items
            const currentItems = this.getCurrentNavigationItems();
            
            const itemsToInclude = currentItems; // Include all items

            if (itemsToInclude.length === 0) {
                this.showNotification('No items to include in the preset. Please add some navigation items first.', 'error');
                $createBtn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();
                return;
            }

            // Create the preset
            const presetId = 'preset_' + Date.now();
            const newPreset = {
                id: presetId,
                name: presetName,
                description: presetDescription,
                created_at: Math.floor(Date.now() / 1000),
                items: itemsToInclude
            };

            // Add to DOM
            this.addPresetToDOM(newPreset);
            this.updateAllPresetSelectors();

            // Save form state
            this.saveFormState();

            // Close modal
            this.closeCreatePresetModal();

            // Show success notification
            this.showNotification(`✅ Custom preset "${presetName}" created successfully!`, 'success');

            // Important reminder
            setTimeout(() => {
                this.showNotification(`⚠️ Remember to click "Save Changes" to permanently save your custom preset!`, 'warning', 5000);
            }, 2000);
        },

        // Close create preset modal
        closeCreatePresetModal: function () {
            const $modal = $('#wpbnp-create-preset-modal');
            
            // Remove event listeners
            $(document).off('keydown.wpbnp-create-preset');
            
            // Hide modal with animation
            $modal.removeClass('wpbnp-modal-show');
            
            // Remove modal after animation
            setTimeout(() => {
                $modal.remove();
            }, 300);
        },

        // Get current navigation items
        getCurrentNavigationItems: function () {
            const items = [];
            
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function (index) {
                const $row = $(this);
                const enabledInput = $row.find('input[name*="[enabled]"]');
                // For hidden inputs, check the value instead of :checked
                const enabledValue = enabledInput.attr('type') === 'hidden' ? 
                    enabledInput.val() === '1' : 
                    enabledInput.is(':checked');
                
                const item = {
                    id: $row.find('input[name*="[id]"]').val() || 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                    label: $row.find('input[name*="[label]"]').val() || '',
                    icon: $row.find('input[name*="[icon]"]').val() || '',
                    url: $row.find('input[name*="[url]"]').val() || '',
                    enabled: enabledValue,
                    target: $row.find('select[name*="[target]"]').val() || '_self',
                    show_badge: $row.find('input[name*="[show_badge]"]').is(':checked'),
                    badge_type: $row.find('select[name*="[badge_type]"]').val() || 'count',
                    custom_badge_text: $row.find('input[name*="[custom_badge_text]"]').val() || '',
                    user_roles: []
                };

                // Get user roles
                $row.find('input[name*="[user_roles][]"]:checked').each(function () {
                    item.user_roles.push($(this).val());
                });

                items.push(item);
            });

            return items;
        },

        // Add preset to DOM with unique ID management
        addPresetToDOM: function (preset) {
            console.log('addPresetToDOM called for preset:', preset.name);
            
            const presetsContainer = $('#wpbnp-custom-presets-list');
            const noPresetsMessage = presetsContainer.find('.wpbnp-no-presets');

            if (noPresetsMessage.length) {
                noPresetsMessage.remove();
            }

            // Use preset ID as the unique identifier instead of index
            const presetId = preset.id;
            const itemsCount = preset.items ? preset.items.length : 0;
            const createdDate = new Date(preset.created_at * 1000).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            const presetHtml = `
                <div class="wpbnp-preset-item" data-preset-id="${presetId}">
                    <div class="wpbnp-preset-header">
                        <div class="wpbnp-preset-info">
                            <h4 class="wpbnp-preset-name">${preset.name}</h4>
                            <p class="wpbnp-preset-meta">${itemsCount} items • Created ${createdDate}</p>
                            ${preset.description ? `<p class="wpbnp-preset-description">${preset.description}</p>` : ''}
                        </div>
                        <div class="wpbnp-preset-actions">
                            <button type="button" class="wpbnp-preset-edit-items" title="Edit Items">
                                <span class="wpbnp-edit-items-icon">⚙️</span>
                            </button>
                            <button type="button" class="wpbnp-preset-edit" title="Edit Name & Description">
                                <span class="wpbnp-edit-icon">✏️</span>
                            </button>
                            <button type="button" class="wpbnp-preset-duplicate" title="Duplicate Preset">
                                <span class="wpbnp-duplicate-icon">📋</span>
                            </button>
                            <button type="button" class="wpbnp-preset-delete" title="Delete Preset">
                                <span class="wpbnp-delete-icon">×</span>
                            </button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="settings[custom_presets][presets][${presetId}][id]" value="${presetId}">
                    <input type="hidden" name="settings[custom_presets][presets][${presetId}][name]" value="${preset.name}">
                    <input type="hidden" name="settings[custom_presets][presets][${presetId}][description]" value="${preset.description || ''}">
                    <input type="hidden" name="settings[custom_presets][presets][${presetId}][created_at]" value="${preset.created_at}">
                    <input type="hidden" name="settings[custom_presets][presets][${presetId}][items]" value="${JSON.stringify(preset.items || []).replace(/"/g, '&quot;')}">
                </div>
            `;

            presetsContainer.append(presetHtml);
            console.log('Preset added to DOM successfully:', preset.name, 'with ID:', presetId);
        },

        // Edit custom preset items
        editCustomPresetItems: function (presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const currentItems = JSON.parse(presetItem.find('input[name*="[items]"]').val() || '[]');
            const presetName = presetItem.find('.wpbnp-preset-name').text();

            console.log('EditCustomPresetItems called for preset:', presetId, 'with items:', currentItems);

            // Show modal or interface to edit items
            if (confirm(`Edit navigation items for "${presetName}"?\n\nThis will temporarily load the current preset items into the main Items tab for editing. After making changes, come back and click "Update Preset Items" to save them.`)) {
                // Load items into the main navigation items interface
                this.loadItemsIntoMainInterface(currentItems);

                // Show notification with instructions
                this.showNotification(`✅ Preset items loaded into Items tab. Please click the "Items" tab to see and edit them.`, 'info', 8000);

                // Add update button to preset
                this.addUpdatePresetButton(presetId, presetName);
            }
        },

        // Load items into main interface for editing
        loadItemsIntoMainInterface: function (items) {
            console.log('Loading items into main interface:', items);
            
            // Store the loaded items in localStorage so they persist across page reloads
            localStorage.setItem('wpbnp_loaded_preset_items', JSON.stringify(items));
            console.log('Stored loaded preset items in localStorage:', items);
            
            // Clear existing items
            $('#wpbnp-items-list').empty();

            // Add each item to the main interface
            items.forEach((item, index) => {
                console.log('Adding item to main interface:', item);
                this.addItemRow(item, index);
            });

            // Update the items counter if it exists
            this.updateItemsDisplay();
            
            console.log('Items loaded successfully. Total items in list:', $('#wpbnp-items-list .wpbnp-nav-item-row').length);
        },

        // Restore loaded preset items from localStorage
        restoreLoadedPresetItems: function () {
            const loadedItems = localStorage.getItem('wpbnp_loaded_preset_items');
            if (loadedItems) {
                try {
                    const items = JSON.parse(loadedItems);
                    console.log('Restoring loaded preset items from localStorage:', items);
                    
                    // Clear existing items
                    $('#wpbnp-items-list').empty();

                    // Add each item to the main interface
                    items.forEach((item, index) => {
                        console.log('Restoring item to main interface:', item);
                        this.addItemRow(item, index);
                    });

                    // Update the items counter if it exists
                    this.updateItemsDisplay();
                    
                    console.log('Loaded preset items restored successfully. Total items in list:', $('#wpbnp-items-list .wpbnp-nav-item-row').length);
                    
                    // Show notification that items were restored
                    this.showNotification('✅ Preset items restored from previous session. You can now edit them.', 'info', 5000);
                    
                } catch (e) {
                    console.error('Failed to parse loaded preset items:', e);
                    localStorage.removeItem('wpbnp_loaded_preset_items');
                }
            }
        },

        // Add update button to preset
        addUpdatePresetButton: function (presetId, presetName) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);

            // Remove existing update button if any
            presetItem.find('.wpbnp-update-preset-items').remove();

            // Add update button
            const updateButton = `
                <div class="wpbnp-update-preset-items" style="margin-top: 10px; padding: 10px; background: #e8f4fd; border: 1px solid #0073aa; border-radius: 4px;">
                    <p style="margin: 0 0 8px 0; font-weight: 600; color: #0073aa;">📝 Items loaded for editing</p>
                    <button type="button" class="wpbnp-update-preset-btn" data-preset-id="${presetId}" 
                            style="background: #0073aa; color: white; border: none; padding: 6px 12px; border-radius: 3px; cursor: pointer;">
                        Update "${presetName}" Items
                    </button>
                    <button type="button" class="wpbnp-cancel-preset-edit" data-preset-id="${presetId}" 
                            style="background: #666; color: white; border: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; margin-left: 8px;">
                        Cancel
                    </button>
                </div>
            `;

            presetItem.append(updateButton);
        },

        // Update preset items with current items from main interface
        updatePresetItems: function (presetId) {
            console.log('Updating preset items for preset:', presetId);
            
            // Get current items from main interface
            const currentItems = this.getCurrentNavigationItems();
            console.log('Current items from main interface:', currentItems);
            
            // Find the preset item
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            if (presetItem.length === 0) {
                console.error('Preset item not found:', presetId);
                this.showNotification('Error: Preset not found.', 'error');
                return;
            }
            
            // Update the hidden input with new items
            const itemsInput = presetItem.find('input[name*="[items]"]');
            itemsInput.val(JSON.stringify(currentItems));
            
            // Update the preset name display
            const presetName = presetItem.find('.wpbnp-preset-name').text();
            const itemsCount = currentItems.length;
            presetItem.find('.wpbnp-preset-meta').text(`${itemsCount} items • Created ${new Date().toLocaleDateString()}`);
            
            // Remove the update button
            presetItem.find('.wpbnp-update-preset-items').remove();
            
            // Clear loaded preset items from localStorage
            localStorage.removeItem('wpbnp_loaded_preset_items');
            
            // Show success notification
            this.showNotification(`✅ Preset "${presetName}" updated successfully with ${itemsCount} items!`, 'success');
            
            console.log('Preset items updated successfully');
        },

        // Cancel preset editing
        cancelPresetEdit: function (presetId) {
            console.log('Canceling preset edit for preset:', presetId);
            
            // Remove the update button
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            presetItem.find('.wpbnp-update-preset-items').remove();
            
            // Clear loaded preset items from localStorage
            localStorage.removeItem('wpbnp_loaded_preset_items');
            
            // Show notification
            this.showNotification('Preset editing canceled.', 'info');
            
            console.log('Preset editing canceled');
        },

        // Edit custom preset name/description
        editCustomPreset: function (presetId) {
            this.showEditPresetModal(presetId);
        },

        // Show edit preset modal
        showEditPresetModal: function (presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const currentName = presetItem.find('.wpbnp-preset-name').text();
            const currentDescription = presetItem.find('.wpbnp-preset-description').text();
            const savedItems = JSON.parse(presetItem.find('input[name*="[items]"]').val() || '[]');

            // Remove existing modal if any
            $('#wpbnp-edit-preset-modal').remove();

            // Create modal HTML
            const modalHtml = `
                <div id="wpbnp-edit-preset-modal" class="wpbnp-modal-overlay">
                    <div class="wpbnp-modal">
                        <div class="wpbnp-modal-header">
                            <h3>✏️ Edit Custom Preset</h3>
                            <button type="button" class="wpbnp-modal-close" title="Close">×</button>
                        </div>
                        <div class="wpbnp-modal-body">
                            <form id="wpbnp-edit-preset-form">
                                <div class="wpbnp-form-group">
                                    <label for="edit-preset-name">Preset Name *</label>
                                    <input type="text" id="edit-preset-name" name="edit-preset-name" 
                                           value="${currentName}" placeholder="Enter a descriptive name for your preset" 
                                           required maxlength="50">
                                    <small>This will be the name displayed in the preset selector</small>
                                </div>
                                
                                <div class="wpbnp-form-group">
                                    <label for="edit-preset-description">Description (Optional)</label>
                                    <textarea id="edit-preset-description" name="edit-preset-description" 
                                              placeholder="Describe what this preset is for..." 
                                              rows="3" maxlength="200">${currentDescription}</textarea>
                                    <small>Help others understand what this preset is for</small>
                                </div>
                                
                                <div class="wpbnp-form-group">
                                    <label>Saved Navigation Items</label>
                                    <div class="wpbnp-current-items-preview">
                                        <div class="wpbnp-items-count">
                                            <span class="wpbnp-count-badge">${savedItems.length}</span> items in this preset
                                        </div>
                                        <div class="wpbnp-items-list"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="wpbnp-modal-footer">
                            <button type="button" class="wpbnp-btn wpbnp-btn-secondary wpbnp-modal-cancel">Cancel</button>
                            <button type="button" class="wpbnp-btn wpbnp-btn-primary wpbnp-edit-preset-btn">
                                <span class="wpbnp-btn-text">Update Preset</span>
                                <span class="wpbnp-btn-loading" style="display: none;">Updating...</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to body
            $('body').append(modalHtml);

            // Initialize modal functionality
            this.initEditPresetModal(presetId);

            // Show modal with animation
            setTimeout(() => {
                $('#wpbnp-edit-preset-modal').addClass('wpbnp-modal-show');
                // Add a subtle entrance animation for the form
                setTimeout(() => {
                    $('#wpbnp-edit-preset-form').css('opacity', '0').animate({ opacity: 1 }, 300);
                }, 100);
            }, 10);
        },

        // Initialize edit preset modal
        initEditPresetModal: function (presetId) {
            const $modal = $('#wpbnp-edit-preset-modal');
            const $nameInput = $('#edit-preset-name');
            const $descriptionInput = $('#edit-preset-description');
            const $editBtn = $('.wpbnp-edit-preset-btn');
            const $cancelBtn = $('.wpbnp-modal-cancel, .wpbnp-modal-close');

            // Get saved items from the preset
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const savedItems = JSON.parse(presetItem.find('input[name*="[items]"]').val() || '[]');

            // Populate the items list with saved items
            this.updateEditPresetItemsPreview(savedItems);

            // Handle name input validation
            $nameInput.on('input', function() {
                const isValid = $(this).val().trim().length > 0;
                $editBtn.prop('disabled', !isValid);
            });

            // Handle form submission
            $editBtn.on('click', (e) => {
                e.preventDefault();
                this.handleEditPresetSubmit(presetId);
            });

            // Handle cancel/close
            $cancelBtn.on('click', (e) => {
                e.preventDefault();
                this.closeEditPresetModal();
            });

            // Handle escape key
            $(document).on('keydown.wpbnp-edit-preset', (e) => {
                if (e.key === 'Escape') {
                    this.closeEditPresetModal();
                }
            });

            // Handle click outside modal
            $modal.on('click', (e) => {
                if (e.target === $modal[0]) {
                    this.closeEditPresetModal();
                }
            });

            // Focus on name input
            $nameInput.focus();
            $nameInput.select();
        },

        // Handle edit preset form submission
        handleEditPresetSubmit: function (presetId) {
            const $modal = $('#wpbnp-edit-preset-modal');
            const $editBtn = $('.wpbnp-edit-preset-btn');
            const $btnText = $editBtn.find('.wpbnp-btn-text');
            const $btnLoading = $editBtn.find('.wpbnp-btn-loading');

            const presetName = $('#edit-preset-name').val().trim();
            const presetDescription = $('#edit-preset-description').val().trim();

            if (!presetName) {
                this.showNotification('Please enter a preset name', 'error');
                $('#edit-preset-name').focus();
                return;
            }

            // Show loading state
            $editBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();

            // Update DOM
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            presetItem.find('.wpbnp-preset-name').text(presetName);
            presetItem.find('input[name*="[name]"]').val(presetName);

            if (presetDescription) {
                if (presetItem.find('.wpbnp-preset-description').length) {
                    presetItem.find('.wpbnp-preset-description').text(presetDescription);
                } else {
                    presetItem.find('.wpbnp-preset-meta').after(`<p class="wpbnp-preset-description">${presetDescription}</p>`);
                }
                presetItem.find('input[name*="[description]"]').val(presetDescription);
            } else {
                presetItem.find('.wpbnp-preset-description').remove();
                presetItem.find('input[name*="[description]"]').val('');
            }

            this.updateAllPresetSelectors();

            // Save form state
            this.saveFormState();

            // Close modal
            this.closeEditPresetModal();

            // Show success notification
            this.showNotification(`✅ Preset "${presetName}" updated successfully!`, 'success');
        },

        // Close edit preset modal
        closeEditPresetModal: function () {
            const $modal = $('#wpbnp-edit-preset-modal');
            
            // Remove event listeners
            $(document).off('keydown.wpbnp-edit-preset');
            
            // Hide modal with animation
            $modal.removeClass('wpbnp-modal-show');
            
            // Remove modal after animation
            setTimeout(() => {
                $modal.remove();
            }, 300);
        },

        // Duplicate custom preset
        duplicateCustomPreset: function (presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const originalName = presetItem.find('.wpbnp-preset-name').text();
            const originalDescription = presetItem.find('.wpbnp-preset-description').text();
            const originalItems = JSON.parse(presetItem.find('input[name*="[items]"]').val() || '[]');

            const newPreset = {
                id: 'preset_' + Date.now(),
                name: originalName + ' (Copy)',
                description: originalDescription,
                created_at: Math.floor(Date.now() / 1000),
                items: originalItems
            };

            this.addPresetToDOM(newPreset);
            this.updateAllPresetSelectors();

            // Save form state to preserve the new preset
            this.saveFormState();

            this.showNotification(`Preset "${newPreset.name}" created successfully!`, 'success');
        },

        // Update preset items with current items from main interface
        updatePresetItems: function (presetId) {
            const currentItems = this.getCurrentNavigationItems();
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const presetName = presetItem.find('.wpbnp-preset-name').text();

            // Update the hidden input with new items
            presetItem.find('input[name*="[items]"]').val(JSON.stringify(currentItems));

            // Update the items count in the display
            const itemsCount = currentItems.length;
            const metaText = presetItem.find('.wpbnp-preset-meta');
            const currentMeta = metaText.text();
            const newMeta = currentMeta.replace(/\d+ items/, `${itemsCount} items`);
            metaText.text(newMeta);

            // Remove the update button
            presetItem.find('.wpbnp-update-preset-items').remove();

            this.updateAllPresetSelectors();
            this.showNotification(`✅ Preset "${presetName}" updated with ${itemsCount} items!`, 'success');
        },

        // Cancel preset editing
        cancelPresetEdit: function (presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);

            // Remove the update button
            presetItem.find('.wpbnp-update-preset-items').remove();

            this.showNotification('Preset editing cancelled.', 'info');
        },

        // Delete custom preset
        deleteCustomPreset: function (presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const presetName = presetItem.find('.wpbnp-preset-name').text();

            presetItem.fadeOut(300, function () {
                $(this).remove();

                // Reindex remaining presets
                $('.wpbnp-preset-item').each(function (index) {
                    $(this).find('input[name*="[presets]["]').each(function () {
                        const name = $(this).attr('name');
                        const newName = name.replace(/\[presets\]\[\d+\]/, `[presets][${index}]`);
                        $(this).attr('name', newName);
                    });
                });

                // Show no presets message if empty
                if ($('.wpbnp-preset-item').length === 0) {
                    $('#wpbnp-custom-presets-list').html('<p class="wpbnp-no-presets">No custom presets created yet. Click "Create New Preset" to get started.</p>');
                }

                // Update all preset selectors
                WPBottomNavAdmin.updateAllPresetSelectors();

                // Save form state to preserve the deletion
                WPBottomNavAdmin.saveFormState();
            });

            this.showNotification(`Preset "${presetName}" deleted successfully!`, 'success');
        },

        // Populate preset selector with available custom presets
        populatePresetSelector: function ($selector) {
            if (!$selector || $selector.length === 0) {
                return;
            }

            // Get custom presets from the page (if any)
            const customPresets = this.getAvailableCustomPresets();

            // Clear existing options except default
            $selector.find('option:not([value="default"])').remove();

            if (customPresets.length > 0) {
                // Add optgroup for custom presets
                let optgroupHtml = '<optgroup label="Custom Presets">';
                customPresets.forEach(preset => {
                    const itemCount = preset.items ? preset.items.length : 0;
                    optgroupHtml += `<option value="${preset.id}">${preset.name} (${itemCount} items)</option>`;
                });
                optgroupHtml += '</optgroup>';

                $selector.append(optgroupHtml);
            } else {
                // Add disabled option when no presets available
                $selector.append('<option value="" disabled>No custom presets available - Create some in the Items tab</option>');
            }
        },

        // Get available custom presets from settings data or DOM
        getAvailableCustomPresets: function () {
            const presets = [];

            if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings && wpbnp_admin.settings.custom_presets && wpbnp_admin.settings.custom_presets.presets) {
                const settingsPresets = wpbnp_admin.settings.custom_presets.presets;

                settingsPresets.forEach(preset => {
                    if (preset.id && preset.name) {
                        presets.push({
                            id: preset.id,
                            name: preset.name,
                            items: preset.items || []
                        });
                    }
                });
            }

            // Always also check DOM for additional presets (might be newly created)
            const settingsPresetIds = presets.map(p => p.id);
            $('.wpbnp-preset-item').each(function () {
                const $item = $(this);
                const presetId = $item.data('preset-id');

                // Skip if we already have this preset from settings
                if (settingsPresetIds.includes(presetId)) {
                    return;
                }

                const preset = {
                    id: presetId,
                    name: $item.find('.wpbnp-preset-name').text(),
                    items: []
                };

                // Try to get items from hidden input
                const itemsJson = $item.find('input[name*="[items]"]').val();
                if (itemsJson) {
                    try {
                        preset.items = JSON.parse(itemsJson);
                    } catch (e) {
                        // Ignore parsing errors
                    }
                }

                if (preset.id && preset.name) {
                    presets.push(preset);
                }
            });

            return presets;
        },

        // Update settings data with current presets from DOM
        updateSettingsPresetData: function () {
            if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings) {
                const domPresets = [];

                $('.wpbnp-preset-item').each(function () {
                    const $item = $(this);
                    const preset = {
                        id: $item.data('preset-id'),
                        name: $item.find('.wpbnp-preset-name').text(),
                        description: $item.find('.wpbnp-preset-description').text() || '',
                        created_at: parseInt($item.find('input[name*="[created_at]"]').val()) || Math.floor(Date.now() / 1000),
                        items: []
                    };

                    // Get items from hidden input
                    const itemsJson = $item.find('input[name*="[items]"]').val();
                    if (itemsJson) {
                        try {
                            preset.items = JSON.parse(itemsJson);
                        } catch (e) {
                            console.warn('Failed to parse preset items for settings update:', e);
                        }
                    }

                    if (preset.id && preset.name) {
                        domPresets.push(preset);
                    }
                });

                // Update the settings data
                if (!wpbnp_admin.settings.custom_presets) {
                    wpbnp_admin.settings.custom_presets = {};
                }
                wpbnp_admin.settings.custom_presets.presets = domPresets;

                console.log(`Updated settings data with ${domPresets.length} presets`);
            }
        },

        // Update all preset selectors when presets change
        updateAllPresetSelectors: function () {
            console.log('Updating all preset selectors...');

            // First update the settings data with current DOM state
            this.updateSettingsPresetData();

            const selectorCount = $('.wpbnp-preset-selector').length;
            console.log(`Found ${selectorCount} preset selectors`);

            if (selectorCount === 0) {
                console.log('No preset selectors found on current page');
                return;
            }

            $('.wpbnp-preset-selector').each((index, element) => {
                console.log(`Updating selector ${index + 1}/${selectorCount}`);
                this.populatePresetSelector($(element));
            });
        },

        // Manual debug function - can be called from console
        debugPresets: function () {
            console.log('=== PRESET DEBUG INFORMATION ===');
            console.log('1. wpbnp_admin object:', wpbnp_admin);
            console.log('2. Settings presets:', wpbnp_admin?.settings?.custom_presets);
            console.log('3. DOM preset items:', $('.wpbnp-preset-item').length);
            console.log('4. Available presets:', this.getAvailableCustomPresets());
            console.log('5. Preset selectors on page:', $('.wpbnp-preset-selector').length);

            $('.wpbnp-preset-selector').each((index, element) => {
                console.log(`Selector ${index + 1}:`, element, 'Options:', $(element).find('option').length);
            });

            console.log('=== END DEBUG ===');
        },

        // Check custom preset save state
        checkCustomPresetState: function () {
            console.log('=== CUSTOM PRESET STATE CHECK ===');
            
            // Check DOM state
            const domPresets = $('.wpbnp-preset-item');
            console.log('DOM presets count:', domPresets.length);
            
            domPresets.each(function(index) {
                const $item = $(this);
                const presetId = $item.data('preset-id');
                const presetName = $item.find('.wpbnp-preset-name').text();
                const hasItemsInput = $item.find('input[name*="[items]"]').length > 0;
                const itemsValue = $item.find('input[name*="[items]"]').val();
                
                console.log(`DOM Preset ${index + 1}:`, {
                    id: presetId,
                    name: presetName,
                    hasItemsInput: hasItemsInput,
                    itemsValueLength: itemsValue ? itemsValue.length : 0
                });
            });
            
            // Check settings state
            if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings) {
                console.log('Settings custom_presets:', wpbnp_admin.settings.custom_presets);
            } else {
                console.log('No wpbnp_admin.settings available');
            }
            
            // Check localStorage state
            const savedState = localStorage.getItem('wpbnp_form_state');
            if (savedState) {
                try {
                    const parsedState = JSON.parse(savedState);
                    console.log('localStorage form state:', parsedState);
                    if (parsedState.wpbnp_custom_presets_data) {
                        console.log('localStorage custom presets data:', parsedState.wpbnp_custom_presets_data);
                    }
                } catch (e) {
                    console.log('Error parsing localStorage state:', e);
                }
            } else {
                console.log('No saved form state in localStorage');
            }
            
            console.log('=== END STATE CHECK ===');
        },

        // Test function to save form and check if presets persist
        testPresetSave: function () {
            console.log('=== TESTING PRESET SAVE ===');
            console.log('1. DOM presets before save:', $('.wpbnp-preset-item').length);

            // Trigger form save
            $('#wpbnp-settings-form').trigger('submit');

            setTimeout(() => {
                console.log('2. Form should be saved now, checking...');
                // This won't work because page doesn't reload, but it's a test
                console.log('3. You should now go to another tab and back to see if presets persist');
            }, 2000);
        },

        // Manual test function for custom preset save
        testCustomPresetSave: function () {
            console.log('=== MANUAL CUSTOM PRESET SAVE TEST ===');
            
            // Get current custom presets from DOM
            const currentPresets = this.getCustomPresetsData();
            console.log('Current presets in DOM:', currentPresets);
            
            // Create a test preset if none exist
            if (currentPresets.length === 0) {
                console.log('No presets found, creating a test preset...');
                this.createCustomPreset();
                
                setTimeout(() => {
                    const newPresets = this.getCustomPresetsData();
                    console.log('After creating test preset:', newPresets);
                    
                    // Now test the save
                    console.log('Testing save with new preset...');
                    $('#wpbnp-settings-form').trigger('submit');
                }, 1000);
            } else {
                console.log('Found existing presets, testing save...');
                $('#wpbnp-settings-form').trigger('submit');
            }
        },


        // Reindex configurations after deletion
        reindexConfigurations: function () {
            $('.wpbnp-config-item').each(function (index) {
                $(this).find('input, select').each(function () {
                    const name = $(this).attr('name');
                    if (name && name.includes('[configurations][')) {
                        const newName = name.replace(/\[configurations\]\[\d+\]/, `[configurations][${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });

            if ($('.wpbnp-config-item').length === 0) {
                $('#wpbnp-configurations-list').html('<p class="wpbnp-no-configs">No configurations created yet. Click "Add Configuration" to get started.</p>');
            }
        },

        // Populate pages selector for new configurations
        populatePagesSelector: function ($selector, configIndex) {
            if (!$selector || !$selector.length) {
                return;
            }

            // Make AJAX call to get pages
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_get_pages',
                    nonce: wpbnp_admin.nonce
                },
                success: function (response) {
                    if (response.success && response.data.pages) {
                        $selector.empty();
                        $selector.append('<option value="">Select pages...</option>');

                        response.data.pages.forEach(function (page) {
                            $selector.append(`<option value="${page.ID}">${page.post_title}</option>`);
                        });
                    } else {
                        $selector.html('<option value="" disabled>No pages found - Create some pages first</option>');
                    }
                },
                error: function () {
                    $selector.html('<option value="" disabled>Error loading pages</option>');
                }
            });
        },

        // Populate categories selector for new configurations
        populateCategoriesSelector: function ($selector, configIndex) {
            if (!$selector || !$selector.length) {
                return;
            }

            // Make AJAX call to get categories
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_get_categories',
                    nonce: wpbnp_admin.nonce
                },
                success: function (response) {
                    if (response.success && response.data.categories) {
                        $selector.empty();
                        $selector.append('<option value="">Select categories...</option>');

                        response.data.categories.forEach(function (category) {
                            $selector.append(`<option value="${category.term_id}">${category.name}</option>`);
                        });
                    } else {
                        $selector.html('<option value="" disabled>No categories found</option>');
                    }
                },
                error: function () {
                    $selector.html('<option value="" disabled>Error loading categories</option>');
                }
            });
        }
    };

    // Initialize admin
    WPBottomNavAdmin.init();

    // Initialize pro features
    WPBottomNavAdmin.initProFeatures();

    // Initialize custom presets
    WPBottomNavAdmin.initCustomPresets();

    // Debug: Check what's available in settings vs DOM
    console.log('=== PRESET DETECTION DEBUG ===');
    console.log('1. WPBNP Admin Settings:', wpbnp_admin);
    if (wpbnp_admin && wpbnp_admin.settings && wpbnp_admin.settings.custom_presets) {
        console.log('2. Custom Presets in Settings (from database):', wpbnp_admin.settings.custom_presets);
        console.log('   - Enabled:', wpbnp_admin.settings.custom_presets.enabled);
        console.log('   - Presets count:', wpbnp_admin.settings.custom_presets.presets ? wpbnp_admin.settings.custom_presets.presets.length : 0);
    } else {
        console.log('2. No custom presets in settings data!');
    }

    console.log('3. DOM Preset Elements:', $('.wpbnp-preset-item').length);
    $('.wpbnp-preset-item').each(function (index) {
        const $item = $(this);
        console.log(`   DOM Preset ${index + 1}:`, {
            id: $item.data('preset-id'),
            name: $item.find('.wpbnp-preset-name').text(),
            hasItemsInput: $item.find('input[name*="[items]"]').length > 0,
            itemsValue: $item.find('input[name*="[items]"]').val()
        });
    });
    console.log('=== END DEBUG ===');

    // Populate existing preset selectors (with delay to ensure DOM is ready)
    setTimeout(() => {
        console.log('Initial preset selector population...');
        WPBottomNavAdmin.updateAllPresetSelectors();
    }, 100);

    // Also populate when switching to page targeting tab
    $(document).on('click', '.wpbnp-tab[href*="tab=page_targeting"]', function () {
        setTimeout(() => {
            console.log('Page targeting tab clicked, updating selectors...');
            WPBottomNavAdmin.updateAllPresetSelectors();
        }, 200);
    });

    // Icons are now using Unicode, no need to check dashicons

    // Handle file import when file is selected
    $('#wpbnp-import-file').on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
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
                        success: function (response) {
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
                        error: function () {
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