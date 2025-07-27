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
        
        init: function() {
            this.initializeItems();
            this.bindEvents();
            this.initializeColorPickers();
            this.setupSortable();
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
            
            // Export/Import settings
            $(document).on('click', '#wpbnp-export-settings', this.exportSettings.bind(this));
            $(document).on('click', '#wpbnp-import-settings', this.importSettings.bind(this));
        },
        
        // Handle form submission
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'wpbnp_save_settings');
            formData.append('nonce', this.nonce);
            
            // Disable submit button
            const submitBtn = $('.wpbnp-save-settings');
            submitBtn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Settings saved successfully!', 'success');
                        this.settings = response.data.settings;
                    } else {
                        this.showNotification(response.data || 'Error saving settings', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Ajax error occurred', 'error');
                },
                complete: () => {
                    submitBtn.prop('disabled', false).text('Save Settings');
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
            const dataStr = JSON.stringify(this.settings, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = 'wpbnp-settings.json';
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        },
        
        // Import settings
        importSettings: function(e) {
            e.preventDefault();
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            
            input.onchange = (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        try {
                            const settings = JSON.parse(e.target.result);
                            this.applyImportedSettings(settings);
                            this.showNotification('Settings imported successfully!', 'success');
                        } catch (error) {
                            this.showNotification('Error parsing JSON file', 'error');
                        }
                    };
                    reader.readAsText(file);
                }
            };
            
            input.click();
        },
        
        // Apply imported settings
        applyImportedSettings: function(settings) {
            // Update form fields
            Object.keys(settings).forEach(section => {
                if (typeof settings[section] === 'object') {
                    Object.keys(settings[section]).forEach(key => {
                        const input = $(`input[name="settings[${section}][${key}]"], select[name="settings[${section}][${key}]"]`);
                        if (input.length) {
                            if (input.attr('type') === 'checkbox') {
                                input.prop('checked', settings[section][key]);
                            } else {
                                input.val(settings[section][key]);
                                if (input.hasClass('wpbnp-color-picker')) {
                                    input.wpColorPicker('color', settings[section][key]);
                                }
                            }
                        }
                    });
                }
            });
            
            // Update items
            if (settings.items) {
                this.settings.items = settings.items;
                this.initializeItems();
            }
        },
        
        // Show notification
        showNotification: function(message, type = 'success') {
            const notification = $(`
                <div class="wpbnp-notification ${type}">
                    ${message}
                </div>
            `);
            
            $('#wpbnp-notifications').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => {
                    notification.remove();
                });
            }, 4000);
        }
    };
    
    // Initialize admin
    WPBottomNavAdmin.init();
    
    // Make it globally available
    window.WPBottomNavAdmin = WPBottomNavAdmin;
});