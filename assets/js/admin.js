jQuery(document).ready(function($) {
    'use strict';
    
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
            this.initializeItems();
            this.bindEvents();
            this.initializeColorPickers();
            this.setupSortable();
            this.loadFormData();
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
            
            // Populate enabled checkbox
            $('input[name="settings[enabled]"]').prop('checked', settings.enabled);
            
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
                            input.prop('checked', settings.animations[key]);
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
                                input.prop('checked', settings.devices[device][key]);
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
                        settings.display_rules[key].forEach(role => {
                            $(`input[name="settings[display_rules][user_roles][]"][value="${role}"]`).prop('checked', true);
                        });
                    } else {
                        const input = $(`input[name="settings[display_rules][${key}]"]`);
                        if (input.length) {
                            if (input.attr('type') === 'checkbox') {
                                input.prop('checked', settings.display_rules[key]);
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
                            input.prop('checked', settings.badges[key]);
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
        },
        
        // Handle tab switching while preserving form state
        handleTabSwitch: function(e) {
            // Let the default navigation happen, but preserve current form state
            this.saveFormState();
        },
        
        // Save current form state to localStorage
        saveFormState: function() {
            const formData = this.getFormData();
            localStorage.setItem('wpbnp_form_state', JSON.stringify(formData));
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
            
            return formData;
        },
        
        // Restore form state from localStorage
        restoreFormState: function() {
            const savedState = localStorage.getItem('wpbnp_form_state');
            if (savedState) {
                try {
                    const formData = JSON.parse(savedState);
                    Object.keys(formData).forEach(name => {
                        const $input = $(`[name="${name}"]`);
                        if ($input.length) {
                            if ($input.attr('type') === 'checkbox') {
                                $input.prop('checked', formData[name]);
                            } else {
                                $input.val(formData[name]);
                                if ($input.hasClass('wpbnp-color-picker')) {
                                    $input.wpColorPicker('color', formData[name]);
                                }
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error restoring form state:', e);
                }
            }
        },
        
        // Handle form submission
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
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
                        // Update local settings
                        if (response.data && response.data.settings) {
                            this.settings = response.data.settings;
                        }
                        // Clear saved form state
                        localStorage.removeItem('wpbnp_form_state');
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
            
            // Show modal and store reference to input
            $('#wpbnp-icon-modal').show().data('target-input', input);
            
            // Highlight current selection
            $('.wpbnp-icon-option').removeClass('selected');
            $(`.wpbnp-icon-option[data-icon="${input.val()}"]`).addClass('selected');
        },
        
        // Create icon picker modal
        createIconModal: function() {
            let iconsHtml = '';
            Object.keys(this.dashicons).forEach(icon => {
                iconsHtml += `
                    <div class="wpbnp-icon-option" data-icon="${icon}">
                        <span class="dashicons ${icon}"></span>
                        <span class="icon-name">${this.dashicons[icon]}</span>
                    </div>
                `;
            });
            
            const modalHtml = `
                <div id="wpbnp-icon-modal" class="wpbnp-modal" style="display: none;">
                    <div class="wpbnp-modal-content">
                        <div class="wpbnp-modal-header">
                            <h3>Choose Icon</h3>
                            <span class="wpbnp-modal-close">&times;</span>
                        </div>
                        <div class="wpbnp-modal-body">
                            <div class="wpbnp-icon-search">
                                <input type="text" placeholder="Search icons..." id="wpbnp-icon-search">
                            </div>
                            <div class="wpbnp-icon-grid">
                                ${iconsHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Bind modal events
            $(document).on('click', '.wpbnp-modal-close, .wpbnp-modal', function(e) {
                if (e.target === this) {
                    $('#wpbnp-icon-modal').hide();
                }
            });
            
            $(document).on('click', '.wpbnp-icon-option', function() {
                const icon = $(this).data('icon');
                const targetInput = $('#wpbnp-icon-modal').data('target-input');
                targetInput.val(icon);
                $('#wpbnp-icon-modal').hide();
            });
            
            // Icon search functionality
            $(document).on('input', '#wpbnp-icon-search', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.wpbnp-icon-option').each(function() {
                    const iconName = $(this).find('.icon-name').text().toLowerCase();
                    const iconClass = $(this).data('icon').toLowerCase();
                    if (iconName.includes(searchTerm) || iconClass.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        },
        
        // Apply preset
        applyPreset: function(e) {
            e.preventDefault();
            const presetKey = $(e.currentTarget).data('preset');
            const preset = this.presets[presetKey];
            
            if (!preset) return;
            
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
            
            // Update preset selector
            $('input[name="settings[preset]"]').val(presetKey);
            $('.wpbnp-preset-card').removeClass('active');
            $(`.wpbnp-preset-card[data-preset="${presetKey}"]`).addClass('active');
            
            this.showNotification(`${preset.name} preset applied!`, 'success');
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
        }, 500); // Delay to ensure color pickers are initialized
    }
    
    // Make it globally available
    window.WPBottomNavAdmin = WPBottomNavAdmin;
});