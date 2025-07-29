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
            console.log('Initializing WP Bottom Navigation Pro Admin...');
            
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
                
                // CRITICAL: Initialize custom presets from database
                this.initCustomPresetsFromDatabase();
                
                // Restore form state if switching tabs (delay to ensure elements are ready)
                if (localStorage.getItem('wpbnp_form_state')) {
                    setTimeout(() => {
                        this.restoreFormState();
                    }, 100);
                }
            }, 200);
            
            console.log('Admin initialization complete');
        },
        
        // Get current tab from URL
        getCurrentTab: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tab') || 'items';
        },
        
        // Bind all events
        bindEvents: function() {
            // Form submission
            $('#wpbnp-settings-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Tab switching
            $(document).on('click', '.wpbnp-tab', function(e) {
                WPBottomNavAdmin.saveFormState();
                console.log('Form state saved before tab switch');
                setTimeout(() => {
                    console.log('Navigating to:', this.href);
                    window.location.href = this.href;
                }, 50);
                e.preventDefault();
            });
            
            // Save button backup handler
            $(document).on('click', '.wpbnp-save-settings', function(e) {
                console.log('Save button clicked directly');
                console.log('Button element:', this);
                console.log('Form element:', $('#wpbnp-settings-form').length);
                e.preventDefault();
                console.log('Triggering form submission...');
                $('#wpbnp-settings-form').trigger('submit');
            });
            
            // Reset, export, import buttons
            $(document).on('click', '#wpbnp-reset-settings', this.resetSettings.bind(this));
            $(document).on('click', '#wpbnp-export-settings', this.exportSettings.bind(this));
            $(document).on('click', '#wpbnp-import-settings', this.importSettings.bind(this));
            
            // Custom preset management
            $(document).on('click', '#wpbnp-add-custom-preset', function(e) {
                e.preventDefault();
                WPBottomNavAdmin.createCustomPreset();
            });
            
            $(document).on('click', '.wpbnp-preset-edit-items', function(e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                WPBottomNavAdmin.editCustomPresetItems(presetId);
            });
            
            $(document).on('click', '.wpbnp-preset-edit', function(e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                WPBottomNavAdmin.editCustomPreset(presetId);
            });
            
            $(document).on('click', '.wpbnp-preset-duplicate', function(e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                WPBottomNavAdmin.duplicateCustomPreset(presetId);
            });
            
            $(document).on('click', '.wpbnp-preset-delete', function(e) {
                e.preventDefault();
                const presetItem = $(this).closest('.wpbnp-preset-item');
                const presetId = presetItem.data('preset-id');
                const presetName = presetItem.find('.wpbnp-preset-name').text();
                
                if (confirm(`Are you sure you want to delete the preset "${presetName}"? This action cannot be undone.`)) {
                    WPBottomNavAdmin.deleteCustomPreset(presetId);
                }
            });
            
            $(document).on('click', '.wpbnp-update-preset-btn', function(e) {
                e.preventDefault();
                const presetId = $(this).data('preset-id');
                WPBottomNavAdmin.updatePresetItems(presetId);
            });
            
            $(document).on('click', '.wpbnp-cancel-preset-edit', function(e) {
                e.preventDefault();
                const presetId = $(this).data('preset-id');
                WPBottomNavAdmin.cancelPresetEdit(presetId);
            });
            
            // Page targeting functionality
            $(document).on('click', '#wpbnp-add-config', function(e) {
                e.preventDefault();
                WPBottomNavAdmin.addPageTargetingConfig();
            });
            
            $(document).on('click', '.wpbnp-config-toggle', function(e) {
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
            
            $(document).on('click', '.wpbnp-config-delete', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this configuration?')) {
                    $(this).closest('.wpbnp-config-item').fadeOut(() => {
                        $(this).closest('.wpbnp-config-item').remove();
                        WPBottomNavAdmin.reindexConfigurations();
                    });
                }
            });
            
            // Icon picker functionality
            $(document).on('click', '.wpbnp-icon-picker', function() {
                const targetInput = $(this).siblings('input[type="text"]');
                $('#wpbnp-icon-modal').data('target-input', targetInput).show();
                WPBottomNavAdmin.updateLibraryInfo();
            });
            
            $(document).on('click', '.wpbnp-modal-close, .wpbnp-modal', function(e) {
                if (e.target === this) {
                    $('#wpbnp-icon-modal').hide();
                }
            });
            
            $(document).on('click', '.wpbnp-icon-option', function() {
                const icon = $(this).data('icon');
                const targetInput = $('#wpbnp-icon-modal').data('target-input');
                targetInput.val(icon);
                targetInput.trigger('change');
                $('.wpbnp-icon-option').removeClass('selected');
                $(this).addClass('selected');
                $('#wpbnp-icon-modal').hide();
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
                
                const totalIcons = $('.wpbnp-icon-library-content.active .wpbnp-icon-option').length;
                $('#wpbnp-icon-count').text(`${totalIcons} icons`);
                $('#wpbnp-visible-count').text(`${visibleCount} visible`);
            });
            
            // Navigation items functionality
            $(document).on('click', '#wpbnp-add-item', function(e) {
                e.preventDefault();
                WPBottomNavAdmin.handleAddItem(e);
            });
            
            $(document).on('click', '.wpbnp-item-toggle', function(e) {
                e.preventDefault();
                WPBottomNavAdmin.handleToggleItem(e);
            });
            
            $(document).on('click', '.wpbnp-item-remove', function(e) {
                e.preventDefault();
                WPBottomNavAdmin.handleRemoveItem(e);
            });
            
            // Preset application
            $(document).on('click', '.wpbnp-apply-preset', function(e) {
                e.preventDefault();
                WPBottomNavAdmin.applyPreset(e);
            });
            
            // License activation
            $(document).on('click', '#wpbnp-activate-license', function(e) {
                e.preventDefault();
                $('#wpbnp-license-modal').show();
            });
            
            $(document).on('click', '#wpbnp-activate-license-presets', function(e) {
                e.preventDefault();
                $('#wpbnp-license-modal').show();
            });
            
            $(document).on('click', '#wpbnp-activate-license-btn', function(e) {
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
                    success: function(response) {
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
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', xhr, status, error);
                        WPBottomNavAdmin.showNotification('Ajax error occurred: ' + error, 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Auto-save on form changes
            $(document).on('change', '#wpbnp-settings-form input, #wpbnp-settings-form select, #wpbnp-settings-form textarea', 
                WPBottomNavAdmin.debounce(function() {
                    WPBottomNavAdmin.saveFormState();
                }, 1000)
            );
            
            // Debounce function
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
        },
        
        // Handle form submission
        handleFormSubmit: function(e) {
            try {
                console.log('=== FORM SUBMISSION DEBUG ===');
                console.log('handleFormSubmit called');
                console.log('Event target:', e.target);
                console.log('Event type:', e.type);
                console.log('WPBottomNavAdmin object:', typeof WPBottomNavAdmin);
                console.log('wpbnp_admin object:', typeof wpbnp_admin);
                console.log('Form element:', $('#wpbnp-settings-form').length);
                console.log('Save button:', $('.wpbnp-save-settings').length);
                
                e.preventDefault();
                
                const formData = new FormData(e.target);
                console.log('FormData created, entries:', formData.entries ? 'available' : 'not available');
                
                // Critical fix: Ensure unchecked checkboxes are handled properly
                $('#wpbnp-settings-form input[type="checkbox"]').each(function() {
                    const checkbox = $(this);
                    const name = checkbox.attr('name');
                    if (name && !checkbox.is(':checked')) {
                        formData.append(name, '0');
                        console.log('Added unchecked checkbox:', name);
                    }
                });
                
                // CRITICAL: Ensure custom presets data is included in form submission
                const customPresets = this.getCustomPresetsData();
                console.log('Custom presets data:', customPresets);
                
                if (customPresets.length > 0) {
                    formData.append('wpbnp_custom_presets_data', JSON.stringify(customPresets));
                    console.log('Including custom presets data in form submission:', customPresets);
                } else {
                    console.log('No custom presets to include');
                }
                
                // Ensure action and nonce are included
                formData.append('action', 'wpbnp_save_settings');
                formData.append('nonce', wpbnp_admin.nonce);
                
                // Debug: Log all form data being sent
                console.log('=== FORM DATA BEING SENT ===');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ':', value);
                }
                
                console.log('Form data prepared, submitting...');
                console.log('AJAX URL:', wpbnp_admin.ajax_url);
                console.log('Nonce:', wpbnp_admin.nonce);
                
                const submitBtn = $('.wpbnp-save-settings');
                const originalText = submitBtn.text();
                
                submitBtn.prop('disabled', true).text(wpbnp_admin.strings.saving || 'Saving...');
                
                $.ajax({
                    url: wpbnp_admin.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('AJAX success response:', response);
                        if (response.success) {
                            WPBottomNavAdmin.showNotification(wpbnp_admin.strings.saved || 'Settings saved successfully!', 'success');
                            
                            // Update local settings from response
                            if (response.data && response.data.settings) {
                                WPBottomNavAdmin.settings = response.data.settings;
                                
                                // CRITICAL: Update wpbnp_admin.settings to ensure consistency
                                if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings) {
                                    wpbnp_admin.settings = response.data.settings;
                                    console.log('Updated wpbnp_admin.settings from response');
                                }
                                
                                // CRITICAL: Restore custom presets from the database response
                                if (response.data.settings.custom_presets && response.data.settings.custom_presets.presets) {
                                    console.log('Restoring custom presets from database response:', response.data.settings.custom_presets.presets);
                                    WPBottomNavAdmin.restoreCustomPresets(response.data.settings.custom_presets.presets);
                                }
                            }
                            
                            // Only clear localStorage after successful restoration
                            localStorage.removeItem('wpbnp_form_state');
                            console.log('Form state cleared after successful save and restoration');
                        } else {
                            WPBottomNavAdmin.showNotification(response.data ? response.data.message : wpbnp_admin.strings.error || 'Error saving settings', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', xhr, status, error);
                        console.error('Response text:', xhr.responseText);
                        console.error('Status:', xhr.status);
                        console.error('Status text:', xhr.statusText);
                        WPBottomNavAdmin.showNotification('Ajax error occurred: ' + error, 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            } catch (error) {
                console.error('Error in handleFormSubmit:', error);
                console.error('Error stack:', error.stack);
                WPBottomNavAdmin.showNotification('Error processing form submission: ' + error.message, 'error');
            }
        },
        
        // Save form state to localStorage
        saveFormState: function() {
            try {
                const formData = {};
                const form = $('#wpbnp-settings-form');
                
                // Save all form inputs
                form.find('input, select, textarea').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    const type = $input.attr('type');
                    
                    if (name) {
                        if (type === 'checkbox') {
                            formData[name] = $input.is(':checked') ? '1' : '0';
                        } else if (type === 'radio') {
                            if ($input.is(':checked')) {
                                formData[name] = $input.val();
                            }
                        } else {
                            formData[name] = $input.val();
                        }
                    }
                });
                
                // CRITICAL: Save custom presets data separately with improved structure
                const customPresets = this.getCustomPresetsData();
                if (customPresets.length > 0) {
                    formData['wpbnp_custom_presets_data'] = JSON.stringify(customPresets);
                    console.log('Saving custom presets to localStorage:', customPresets);
                    
                    // Also update wpbnp_admin.settings to keep in-memory settings consistent
                    if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings) {
                        if (!wpbnp_admin.settings.custom_presets) {
                            wpbnp_admin.settings.custom_presets = {};
                        }
                        wpbnp_admin.settings.custom_presets.presets = customPresets;
                        wpbnp_admin.settings.custom_presets.enabled = true;
                        console.log('Updated wpbnp_admin.settings with custom presets');
                    }
                }
                
                localStorage.setItem('wpbnp_form_state', JSON.stringify(formData));
                console.log('Form state saved to localStorage');
            } catch (error) {
                console.error('Error saving form state:', error);
            }
        },
        
        // Restore form state from localStorage
        restoreFormState: function() {
            try {
                const savedState = localStorage.getItem('wpbnp_form_state');
                if (!savedState) {
                    console.log('No saved form state found');
                    return;
                }
                
                const formData = JSON.parse(savedState);
                console.log('Restoring form state from localStorage:', formData);
                
                const form = $('#wpbnp-settings-form');
                
                // Restore all form inputs
                Object.keys(formData).forEach(name => {
                    if (name === 'wpbnp_custom_presets_data') {
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
                    
                    const $input = form.find(`[name="${name}"]`);
                    if ($input.length) {
                        const type = $input.attr('type');
                        const value = formData[name];
                        
                        if (type === 'checkbox') {
                            $input.prop('checked', value === '1');
                        } else if (type === 'radio') {
                            $input.filter(`[value="${value}"]`).prop('checked', true);
                        } else {
                            $input.val(value);
                        }
                    }
                });
                
                console.log('Form state restored from localStorage');
            } catch (error) {
                console.error('Error restoring form state:', error);
            }
        },
        
        // Initialize custom presets from database
        initCustomPresetsFromDatabase: function() {
            console.log('Initializing custom presets from database...');
            
            // Check if we have custom presets in the database settings
            if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings && wpbnp_admin.settings.custom_presets && wpbnp_admin.settings.custom_presets.presets) {
                const dbPresets = wpbnp_admin.settings.custom_presets.presets;
                console.log('Found custom presets in database:', dbPresets);
                
                // Only restore if no presets are currently in the DOM
                if ($('.wpbnp-preset-item').length === 0 && dbPresets.length > 0) {
                    console.log('No presets in DOM, restoring from database...');
                    this.restoreCustomPresets(dbPresets);
                } else {
                    console.log('Presets already in DOM or no database presets found');
                }
            } else {
                console.log('No custom presets found in database settings');
            }
        },
        
        // Restore custom presets to DOM
        restoreCustomPresets: function(presetsData) {
            console.log('Restoring custom presets to DOM:', presetsData);
            
            if (presetsData && presetsData.length > 0) {
                // Clear existing presets in DOM
                $('.wpbnp-preset-item').remove();
                
                presetsData.forEach(preset => {
                    console.log('Restoring preset:', preset.name, 'with', preset.items ? preset.items.length : 0, 'items');
                    this.addPresetToDOM(preset);
                });
                
                this.updateAllPresetSelectors();
                
                // Also update the settings object to ensure consistency
                if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings) {
                    if (!wpbnp_admin.settings.custom_presets) {
                        wpbnp_admin.settings.custom_presets = {};
                    }
                    wpbnp_admin.settings.custom_presets.presets = presetsData;
                    console.log('Updated wpbnp_admin.settings with restored presets');
                }
            }
        },
        
        // Get custom presets data from DOM
        getCustomPresetsData: function() {
            console.log('=== GETTING CUSTOM PRESETS DATA ===');
            const presets = [];
            const presetItems = $('.wpbnp-preset-item');
            console.log('Found preset items in DOM:', presetItems.length);
            
            presetItems.each(function(index) {
                const $item = $(this);
                console.log(`Processing preset item ${index + 1}:`, $item);
                
                const preset = {
                    id: $item.data('preset-id'),
                    name: $item.find('.wpbnp-preset-name').text().trim(),
                    description: $item.find('.wpbnp-preset-description').text().trim() || '',
                    created_at: parseInt($item.find('input[name*="[created_at]"]').val()) || Math.floor(Date.now() / 1000),
                    items: []
                };
                
                console.log('Preset data extracted:', preset);
                
                // Get items from hidden input
                const itemsJson = $item.find('input[name*="[items]"]').val();
                console.log('Items JSON found:', itemsJson);
                
                if (itemsJson) {
                    try {
                        preset.items = JSON.parse(itemsJson.replace(/&quot;/g, '"'));
                        console.log('Successfully parsed items:', preset.items);
                    } catch (e) {
                        console.warn('Failed to parse preset items:', e);
                        preset.items = [];
                    }
                } else {
                    console.log('No items JSON found for this preset');
                }
                
                if (preset.id && preset.name) {
                    presets.push(preset);
                    console.log('Added preset to collection:', preset.name);
                } else {
                    console.log('Skipping preset due to missing id or name');
                }
            });
            
            console.log('Final presets collection:', presets);
            return presets;
        },
        
        // Create new custom preset
        createCustomPreset: function() {
            const presetName = prompt('Enter preset name:', 'My Custom Preset');
            if (!presetName) return;
            
            const presetId = 'preset_' + Date.now();
            const preset = {
                id: presetId,
                name: presetName,
                description: '',
                created_at: Math.floor(Date.now() / 1000),
                items: []
            };
            
            // Add to DOM
            this.addPresetToDOM(preset);
            
            // Update all preset selectors immediately
            this.updateAllPresetSelectors();
            
            // CRITICAL: Save form state immediately to preserve the new preset
            this.saveFormState();
            
            // CRITICAL: Also save to database immediately to prevent loss
            this.saveCustomPresetsToDatabase();
            
            this.showNotification(`Custom preset "${presetName}" created successfully!`, 'success');
            this.showNotification(`⚠️ Remember to click "Save Changes" to permanently save your custom preset!`, 'warning', 5000);
        },
        
        // Save custom presets to database immediately
        saveCustomPresetsToDatabase: function() {
            const customPresets = this.getCustomPresetsData();
            if (customPresets.length === 0) {
                console.log('No custom presets to save to database');
                return;
            }
            
            console.log('Saving custom presets to database immediately:', customPresets);
            
            // Create a minimal form data with just the custom presets
            const formData = new FormData();
            formData.append('action', 'wpbnp_save_settings');
            formData.append('nonce', wpbnp_admin.nonce);
            formData.append('wpbnp_custom_presets_data', JSON.stringify(customPresets));
            
            // Add minimal settings to ensure the save works
            formData.append('settings[enabled]', this.settings.enabled ? '1' : '0');
            
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        console.log('Custom presets saved to database successfully');
                        // Update the settings object with the response
                        if (response.data && response.data.settings) {
                            WPBottomNavAdmin.settings = response.data.settings;
                            if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings) {
                                wpbnp_admin.settings = response.data.settings;
                            }
                        }
                    } else {
                        console.error('Failed to save custom presets to database:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving custom presets to database:', error);
                }
            });
        },
        
        // Add preset to DOM
        addPresetToDOM: function(preset) {
            const presetsContainer = $('#wpbnp-custom-presets-list');
            const noPresetsMessage = presetsContainer.find('.wpbnp-no-presets');
            
            if (noPresetsMessage.length) {
                noPresetsMessage.remove();
            }
            
            const index = $('.wpbnp-preset-item').length;
            const itemsCount = preset.items ? preset.items.length : 0;
            const createdDate = new Date(preset.created_at * 1000).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            
            const presetHtml = `
                <div class="wpbnp-preset-item" data-preset-id="${preset.id}">
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
                    
                    <input type="hidden" name="settings[custom_presets][presets][${index}][id]" value="${preset.id}">
                    <input type="hidden" name="settings[custom_presets][presets][${index}][name]" value="${preset.name}">
                    <input type="hidden" name="settings[custom_presets][presets][${index}][description]" value="${preset.description || ''}">
                    <input type="hidden" name="settings[custom_presets][presets][${index}][created_at]" value="${preset.created_at}">
                    <input type="hidden" name="settings[custom_presets][presets][${index}][items]" value="${JSON.stringify(preset.items || []).replace(/"/g, '&quot;')}">
                </div>
            `;
            
            presetsContainer.append(presetHtml);
        },
        
        // Update all preset selectors when presets change
        updateAllPresetSelectors: function() {
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
        
        // Update settings data with current presets from DOM
        updateSettingsPresetData: function() {
            if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings) {
                const domPresets = [];
                
                $('.wpbnp-preset-item').each(function() {
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
        
        // Populate preset selector with available custom presets
        populatePresetSelector: function($selector) {
            if (!$selector || $selector.length === 0) {
                console.warn('populatePresetSelector: No selector provided');
                return;
            }
            
            console.log('Populating preset selector:', $selector[0]);
            
            // Get custom presets from the page (if any)
            const customPresets = this.getAvailableCustomPresets();
            console.log('Available presets for selector:', customPresets);
            
            // Clear existing options except default
            $selector.find('option:not([value="default"])').remove();
            
            if (customPresets.length > 0) {
                // Add optgroup for custom presets
                let optgroupHtml = '<optgroup label="Custom Presets">';
                customPresets.forEach(preset => {
                    const itemCount = preset.items ? preset.items.length : 0;
                    optgroupHtml += `<option value="${preset.id}">${preset.name} (${itemCount} items)</option>`;
                    console.log(`Added preset option: ${preset.name} (${itemCount} items)`);
                });
                optgroupHtml += '</optgroup>';
                
                $selector.append(optgroupHtml);
                console.log('Successfully populated selector with', customPresets.length, 'presets');
            } else {
                // Add disabled option when no presets available
                $selector.append('<option value="" disabled>No custom presets available - Create some in the Items tab</option>');
                console.log('No presets available, added placeholder option');
            }
        },
        
        // Get available custom presets from settings data or DOM
        getAvailableCustomPresets: function() {
            const presets = [];
            
            console.log('Getting available custom presets...');
            
            // First try to get from settings data (more reliable)
            console.log('Checking wpbnp_admin object:', typeof wpbnp_admin !== 'undefined' ? 'exists' : 'undefined');
            if (typeof wpbnp_admin !== 'undefined') {
                console.log('wpbnp_admin.settings exists:', !!wpbnp_admin.settings);
                if (wpbnp_admin.settings) {
                    console.log('custom_presets exists:', !!wpbnp_admin.settings.custom_presets);
                    if (wpbnp_admin.settings.custom_presets) {
                        console.log('custom_presets enabled:', wpbnp_admin.settings.custom_presets.enabled);
                        console.log('presets array exists:', !!wpbnp_admin.settings.custom_presets.presets);
                        console.log('presets array length:', wpbnp_admin.settings.custom_presets.presets ? wpbnp_admin.settings.custom_presets.presets.length : 'N/A');
                        console.log('presets array content:', wpbnp_admin.settings.custom_presets.presets);
                    }
                }
            }
            
            if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings && wpbnp_admin.settings.custom_presets && wpbnp_admin.settings.custom_presets.presets) {
                const settingsPresets = wpbnp_admin.settings.custom_presets.presets;
                console.log(`Found ${settingsPresets.length} presets in settings data`);
                
                settingsPresets.forEach(preset => {
                    if (preset.id && preset.name) {
                        const itemCount = preset.items ? preset.items.length : 0;
                        console.log(`Settings preset "${preset.name}": ${itemCount} items`);
                        presets.push({
                            id: preset.id,
                            name: preset.name,
                            items: preset.items || []
                        });
                    }
                });
            } else {
                console.log('Settings presets not available, reason:');
                console.log('- wpbnp_admin undefined:', typeof wpbnp_admin === 'undefined');
                console.log('- settings missing:', !wpbnp_admin?.settings);
                console.log('- custom_presets missing:', !wpbnp_admin?.settings?.custom_presets);
                console.log('- presets array missing:', !wpbnp_admin?.settings?.custom_presets?.presets);
            }
            
            // Always also check DOM for additional presets (might be newly created)
            console.log('Checking DOM for additional presets...');
            const settingsPresetIds = presets.map(p => p.id);
            $('.wpbnp-preset-item').each(function() {
                const $item = $(this);
                const presetId = $item.data('preset-id');
                
                // Skip if we already have this preset from settings
                if (settingsPresetIds.includes(presetId)) {
                    console.log(`Skipping DOM preset ${presetId} - already in settings`);
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
                        console.log(`DOM preset "${preset.name}": ${preset.items.length} items`);
                    } catch (e) {
                        console.warn('Failed to parse preset items:', e);
                    }
                } else {
                    console.warn(`No items JSON found for preset "${preset.name}"`);
                }
                
                if (preset.id && preset.name) {
                    presets.push(preset);
                    console.log(`Added DOM preset "${preset.name}" to available presets`);
                }
            });
            
            console.log(`Total found ${presets.length} custom presets`);
            return presets;
        },
        
        // Edit custom preset items
        editCustomPresetItems: function(presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const currentItems = JSON.parse(presetItem.find('input[name*="[items]"]').val() || '[]');
            const presetName = presetItem.find('.wpbnp-preset-name').text();
            
            // Show modal or interface to edit items
            if (confirm(`Edit navigation items for "${presetName}"?\n\nThis will temporarily load the current preset items into the main Items tab for editing. After making changes, come back and click "Update Preset Items" to save them.`)) {
                // Load items into the main navigation items interface
                this.loadItemsIntoMainInterface(currentItems);
                
                // Switch to Items tab
                const itemsTab = $('.wpbnp-tab').filter(function() {
                    return $(this).attr('href') && $(this).attr('href').includes('tab=items');
                });
                
                if (itemsTab.length > 0) {
                    // Use direct navigation instead of click to avoid potential issues
                    const itemsHref = itemsTab.attr('href');
                    console.log('Switching to Items tab:', itemsHref);
                    window.location.href = itemsHref;
                } else {
                    // Fallback: try to find by text content and get its href
                    const fallbackTab = $('.wpbnp-tab:contains("Items")').first();
                    if (fallbackTab.length > 0 && fallbackTab.attr('href')) {
                        console.log('Using fallback Items tab:', fallbackTab.attr('href'));
                        window.location.href = fallbackTab.attr('href');
                    } else {
                        console.error('Could not find Items tab');
                        this.showNotification('Could not switch to Items tab. Please click the Items tab manually.', 'error');
                    }
                }
                
                // Show notification with instructions
                this.showNotification(`✅ Preset items loaded into Items tab. Edit them, then return here and click "Update Preset Items".`, 'info', 8000);
                
                // Add update button to preset
                this.addUpdatePresetButton(presetId, presetName);
            }
        },
        
        // Load items into main interface for editing
        loadItemsIntoMainInterface: function(items) {
            // Clear existing items
            $('#wpbnp-items-list').empty();
            
            // Add each item to the main interface
            items.forEach((item, index) => {
                this.addNavigationItem(item, index);
            });
            
            // Update the items counter if it exists
            this.updateItemsDisplay();
        },
        
        // Add update button to preset
        addUpdatePresetButton: function(presetId, presetName) {
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
        
        // Edit custom preset name/description
        editCustomPreset: function(presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const currentName = presetItem.find('.wpbnp-preset-name').text();
            const currentDescription = presetItem.find('.wpbnp-preset-description').text();
            
            const newName = prompt('Enter preset name:', currentName);
            if (!newName) return;
            
            const newDescription = prompt('Enter preset description (optional):', currentDescription);
            
            // Update DOM
            presetItem.find('.wpbnp-preset-name').text(newName);
            presetItem.find('input[name*="[name]"]').val(newName);
            
            if (newDescription) {
                if (presetItem.find('.wpbnp-preset-description').length) {
                    presetItem.find('.wpbnp-preset-description').text(newDescription);
                } else {
                    presetItem.find('.wpbnp-preset-meta').after(`<p class="wpbnp-preset-description">${newDescription}</p>`);
                }
                presetItem.find('input[name*="[description]"]').val(newDescription);
            } else {
                presetItem.find('.wpbnp-preset-description').remove();
                presetItem.find('input[name*="[description]"]').val('');
            }
            
            this.updateAllPresetSelectors();
            
            // Save form state to preserve the changes
            this.saveFormState();
            
            this.showNotification(`Preset "${newName}" updated successfully!`, 'success');
        },
        
        // Duplicate custom preset
        duplicateCustomPreset: function(presetId) {
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
        updatePresetItems: function(presetId) {
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
        cancelPresetEdit: function(presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            
            // Remove the update button
            presetItem.find('.wpbnp-update-preset-items').remove();
            
            this.showNotification('Preset editing cancelled.', 'info');
        },
        
        // Delete custom preset
        deleteCustomPreset: function(presetId) {
            const presetItem = $(`.wpbnp-preset-item[data-preset-id="${presetId}"]`);
            const presetName = presetItem.find('.wpbnp-preset-name').text();
            
            presetItem.fadeOut(300, function() {
                $(this).remove();
                
                // Reindex remaining presets
                $('.wpbnp-preset-item').each(function(index) {
                    $(this).find('input[name*="[presets]["]').each(function() {
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
        
        // Get current navigation items
        getCurrentNavigationItems: function() {
            const items = [];
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function() {
                const $row = $(this);
                const item = {
                    id: $row.find('input[name*="[id]"]').val() || 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                    label: $row.find('input[name*="[label]"]').val() || '',
                    icon: $row.find('input[name*="[icon]"]').val() || '',
                    url: $row.find('input[name*="[url]"]').val() || '',
                    enabled: $row.find('input[name*="[enabled]"]').is(':checked'),
                    target: $row.find('select[name*="[target]"]').val() || '_self',
                    show_badge: $row.find('input[name*="[show_badge]"]').is(':checked'),
                    badge_type: $row.find('select[name*="[badge_type]"]').val() || 'count',
                    custom_badge_text: $row.find('input[name*="[custom_badge_text]"]').val() || '',
                    user_roles: []
                };
                
                // Get user roles
                $row.find('input[name*="[user_roles][]"]:checked').each(function() {
                    item.user_roles.push($(this).val());
                });
                
                items.push(item);
            });
            
            return items;
        },
        
        // Add new page targeting configuration
        addPageTargetingConfig: function() {
            console.log('addPageTargetingConfig function called');
            
            try {
                const configIndex = $('.wpbnp-config-item').length;
                const configId = 'config_' + Date.now();
                console.log('Config index:', configIndex, 'Config ID:', configId);
                
                const configHtml = `
                <div class="wpbnp-config-item" data-config-id="${configId}">
                    <div class="wpbnp-config-header">
                        <div class="wpbnp-config-title">
                            <span class="wpbnp-config-name">New Configuration</span>
                            <span class="wpbnp-config-priority">Priority: 1</span>
                        </div>
                        <div class="wpbnp-config-actions">
                            <button type="button" class="wpbnp-config-toggle" title="Toggle Configuration">
                                <span class="wpbnp-arrow-icon">▼</span>
                            </button>
                            <button type="button" class="wpbnp-config-delete" title="Delete Configuration">
                                <span class="wpbnp-delete-icon">×</span>
                            </button>
                        </div>
                    </div>
                    <div class="wpbnp-config-content" style="display: block;">
                        <div class="wpbnp-config-settings">
                            <div class="wpbnp-field">
                                <label>Configuration Name</label>
                                <input type="text" name="settings[page_targeting][configurations][${configIndex}][name]" 
                                       value="New Configuration" placeholder="Enter configuration name...">
                            </div>
                            
                            <div class="wpbnp-field">
                                <label>Priority</label>
                                <input type="number" name="settings[page_targeting][configurations][${configIndex}][priority]" 
                                       value="1" min="1" max="100">
                                <p class="description">Higher priority configurations will override lower ones when conditions match.</p>
                            </div>
                            
                            <div class="wpbnp-targeting-conditions">
                                <h4>Display Conditions</h4>
                                <p class="description">Leave all conditions empty to use as default fallback.</p>
                                
                                <div class="wpbnp-condition-group">
                                    <label>Specific Pages</label>
                                    <select name="settings[page_targeting][configurations][${configIndex}][conditions][pages][]" multiple class="wpbnp-multiselect">
                                        <option value="">Loading pages...</option>
                                    </select>
                                </div>
                                
                                <div class="wpbnp-condition-group">
                                    <label>Post Types</label>
                                    <select name="settings[page_targeting][configurations][${configIndex}][conditions][post_types][]" multiple class="wpbnp-multiselect">
                                        <option value="">Select post types...</option>
                                        <option value="post">Posts</option>
                                        <option value="page">Pages</option>
                                        <option value="product">Products (WooCommerce)</option>
                                    </select>
                                </div>
                                
                                <div class="wpbnp-condition-group">
                                    <label>Categories</label>
                                    <select name="settings[page_targeting][configurations][${configIndex}][conditions][categories][]" multiple class="wpbnp-multiselect">
                                        <option value="">Loading categories...</option>
                                    </select>
                                </div>
                                
                                <div class="wpbnp-condition-group">
                                    <label>User Roles</label>
                                    <select name="settings[page_targeting][configurations][${configIndex}][conditions][user_roles][]" multiple class="wpbnp-multiselect">
                                        <option value="">Select user roles...</option>
                                        <option value="administrator">Administrator</option>
                                        <option value="editor">Editor</option>
                                        <option value="author">Author</option>
                                        <option value="contributor">Contributor</option>
                                        <option value="subscriber">Subscriber</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="wpbnp-navigation-config">
                                <h4>Navigation Configuration</h4>
                                <div class="wpbnp-field">
                                    <label>Preset to Display</label>
                                    <select name="settings[page_targeting][configurations][${configIndex}][preset_id]" class="wpbnp-preset-selector">
                                        <option value="default">Default Navigation (Items Tab)</option>
                                        <!-- Custom presets will be populated by JavaScript -->
                                    </select>
                                    <p class="description">Choose which navigation preset to display when the conditions above are met.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="settings[page_targeting][configurations][${configIndex}][id]" value="${configId}">
                </div>
                `;
                
                if ($('.wpbnp-no-configs').length) {
                    $('.wpbnp-no-configs').remove();
                }
            
                console.log('Appending config HTML to:', $('#wpbnp-configurations-list'));
                console.log('Config HTML:', configHtml);
                $('#wpbnp-configurations-list').append(configHtml);
                console.log('Configuration added successfully');
                console.log('Total configs now:', $('.wpbnp-config-item').length);
                
                // Populate all selectors in the new configuration
                const $newConfig = $('.wpbnp-config-item').last();
                
                // Populate custom presets immediately
                const newPresetSelector = $newConfig.find('.wpbnp-preset-selector');
                console.log('New preset selector found:', newPresetSelector.length);
                this.populatePresetSelector(newPresetSelector);
                
                // Populate pages selector immediately
                this.populatePagesSelector($newConfig.find('select[name*="[pages]"]'), configIndex);
                
                // Populate categories selector immediately
                this.populateCategoriesSelector($newConfig.find('select[name*="[categories]"]'), configIndex);
                console.log('Selector population completed');
                
                // Save form state to preserve the new configuration
                this.saveFormState();
                
                this.showNotification('New configuration added!', 'success');
            } catch (error) {
                console.error('Error adding configuration:', error);
                this.showNotification('Error adding configuration: ' + error.message, 'error');
            }
        },
        
        // Populate pages selector for new configurations
        populatePagesSelector: function($selector, configIndex) {
            if (!$selector || !$selector.length) return;
            
            console.log('Populating pages selector for config', configIndex);
            
            // Show loading state
            $selector.html('<option value="">Loading pages...</option>');
            
            // Make AJAX call to get pages
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_get_pages',
                    nonce: wpbnp_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.pages) {
                        $selector.empty();
                        $selector.append('<option value="">Select pages...</option>');
                        
                        response.data.pages.forEach(function(page) {
                            $selector.append(`<option value="${page.ID}">${page.post_title}</option>`);
                        });
                        
                        console.log('Pages populated:', response.data.pages.length);
                    } else {
                        $selector.html('<option value="" disabled>No pages found - Create some pages first</option>');
                        console.warn('No pages returned from AJAX');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading pages:', error);
                    $selector.html('<option value="" disabled>Error loading pages - Please try again</option>');
                }
            });
        },
        
        // Populate categories selector for new configurations
        populateCategoriesSelector: function($selector, configIndex) {
            if (!$selector || !$selector.length) return;
            
            console.log('Populating categories selector for config', configIndex);
            
            // Show loading state
            $selector.html('<option value="">Loading categories...</option>');
            
            // Make AJAX call to get categories
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_get_categories',
                    nonce: wpbnp_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.categories) {
                        $selector.empty();
                        $selector.append('<option value="">Select categories...</option>');
                        
                        response.data.categories.forEach(function(category) {
                            $selector.append(`<option value="${category.term_id}">${category.name}</option>`);
                        });
                        
                        console.log('Categories populated:', response.data.categories.length);
                    } else {
                        $selector.html('<option value="" disabled>No categories found</option>');
                        console.warn('No categories returned from AJAX');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading categories:', error);
                    $selector.html('<option value="" disabled>Error loading categories - Please try again</option>');
                }
            });
        },
        
        // Reindex configurations after deletion
        reindexConfigurations: function() {
            $('.wpbnp-config-item').each(function(index) {
                $(this).find('input, select').each(function() {
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
        
        // Utility functions
        initializeColorPickers: function() {
            // Initialize color pickers if they exist
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.wpbnp-color-picker').wpColorPicker();
            }
        },
        
        setupSortable: function() {
            // Setup sortable functionality if needed
            if ($.fn.sortable) {
                $('#wpbnp-items-list').sortable({
                    handle: '.wpbnp-sort-handle',
                    update: function() {
                        WPBottomNavAdmin.updateItemsDisplay();
                    }
                });
            }
        },
        
        // Load form data
        loadFormData: function() {
            console.log('Loading form data...');
            this.populateFormFields();
        },
        
        // Populate form fields with current settings
        populateFormFields: function() {
            console.log('Populating form fields with settings:', this.settings);
            
            // Populate items list
            if (this.settings.items && Array.isArray(this.settings.items)) {
                this.settings.items.forEach((item, index) => {
                    this.addItemRow(item, index);
                });
            }
            
            // Update items display
            this.updateItemsDisplay();
        },
        
        // Initialize navigation items
        initializeItems: function() {
            console.log('Initializing navigation items...');
            
            // If no items exist, add default items
            if ($('#wpbnp-items-list .wpbnp-nav-item-row').length === 0) {
                const defaultItems = [
                    {
                        id: 'home',
                        label: 'Home',
                        icon: 'bi bi-house-door',
                        url: home_url || '/',
                        enabled: true,
                        target: '_self',
                        show_badge: false,
                        badge_type: 'count',
                        custom_badge_text: '',
                        user_roles: []
                    },
                    {
                        id: 'shop',
                        label: 'Shop',
                        icon: 'bi bi-cart',
                        url: '#',
                        enabled: true,
                        target: '_self',
                        show_badge: false,
                        badge_type: 'count',
                        custom_badge_text: '',
                        user_roles: []
                    },
                    {
                        id: 'account',
                        label: 'Account',
                        icon: 'bi bi-person',
                        url: '#',
                        enabled: true,
                        target: '_self',
                        show_badge: false,
                        badge_type: 'count',
                        custom_badge_text: '',
                        user_roles: []
                    }
                ];
                
                defaultItems.forEach((item, index) => {
                    this.addItemRow(item, index);
                });
            }
        },
        
        // Add navigation item row
        addItemRow: function(item = null, index = null) {
            const itemIndex = index !== null ? index : $('#wpbnp-items-list .wpbnp-nav-item-row').length;
            const itemData = item || {
                id: 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                label: '',
                icon: '',
                url: '',
                enabled: true,
                target: '_self',
                show_badge: false,
                badge_type: 'count',
                custom_badge_text: '',
                user_roles: []
            };
            
            const itemHtml = `
                <div class="wpbnp-nav-item-row" data-item-index="${itemIndex}">
                    <div class="wpbnp-item-header">
                        <div class="wpbnp-sort-handle">⋮⋮</div>
                        <div class="wpbnp-item-toggle">
                            <input type="checkbox" name="settings[items][${itemIndex}][enabled]" 
                                   value="1" ${itemData.enabled ? 'checked' : ''}>
                        </div>
                        <div class="wpbnp-item-actions">
                            <button type="button" class="wpbnp-item-remove" title="Remove Item">×</button>
                        </div>
                    </div>
                    
                    <div class="wpbnp-item-content">
                        <div class="wpbnp-field-group">
                            <div class="wpbnp-field">
                                <label>Label</label>
                                <input type="text" name="settings[items][${itemIndex}][label]" 
                                       value="${itemData.label}" placeholder="Item label">
                            </div>
                            
                            <div class="wpbnp-field">
                                <label>Icon</label>
                                <div class="wpbnp-icon-input">
                                    <input type="text" name="settings[items][${itemIndex}][icon]" 
                                           value="${itemData.icon}" placeholder="Icon class" readonly>
                                    <button type="button" class="wpbnp-icon-picker">📱</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wpbnp-field-group">
                            <div class="wpbnp-field">
                                <label>URL</label>
                                <input type="url" name="settings[items][${itemIndex}][url]" 
                                       value="${itemData.url}" placeholder="https://example.com">
                            </div>
                            
                            <div class="wpbnp-field">
                                <label>Target</label>
                                <select name="settings[items][${itemIndex}][target]">
                                    <option value="_self" ${itemData.target === '_self' ? 'selected' : ''}>Same Window</option>
                                    <option value="_blank" ${itemData.target === '_blank' ? 'selected' : ''}>New Window</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="wpbnp-field-group">
                            <div class="wpbnp-field">
                                <label>
                                    <input type="checkbox" name="settings[items][${itemIndex}][show_badge]" 
                                           value="1" ${itemData.show_badge ? 'checked' : ''}>
                                    Show Badge
                                </label>
                            </div>
                            
                            <div class="wpbnp-field">
                                <label>Badge Type</label>
                                <select name="settings[items][${itemIndex}][badge_type]">
                                    <option value="count" ${itemData.badge_type === 'count' ? 'selected' : ''}>Count</option>
                                    <option value="dot" ${itemData.badge_type === 'dot' ? 'selected' : ''}>Dot</option>
                                    <option value="custom" ${itemData.badge_type === 'custom' ? 'selected' : ''}>Custom Text</option>
                                </select>
                            </div>
                            
                            <div class="wpbnp-field">
                                <label>Custom Badge Text</label>
                                <input type="text" name="settings[items][${itemIndex}][custom_badge_text]" 
                                       value="${itemData.custom_badge_text}" placeholder="Custom badge text">
                            </div>
                        </div>
                        
                        <div class="wpbnp-field-group">
                            <div class="wpbnp-field">
                                <label>User Roles</label>
                                <div class="wpbnp-user-roles">
                                    <label class="wpbnp-checkbox-label">
                                        <input type="checkbox" name="settings[items][${itemIndex}][user_roles][]" 
                                               value="administrator" ${itemData.user_roles.includes('administrator') ? 'checked' : ''}>
                                        Administrator
                                    </label>
                                    <label class="wpbnp-checkbox-label">
                                        <input type="checkbox" name="settings[items][${itemIndex}][user_roles][]" 
                                               value="editor" ${itemData.user_roles.includes('editor') ? 'checked' : ''}>
                                        Editor
                                    </label>
                                    <label class="wpbnp-checkbox-label">
                                        <input type="checkbox" name="settings[items][${itemIndex}][user_roles][]" 
                                               value="author" ${itemData.user_roles.includes('author') ? 'checked' : ''}>
                                        Author
                                    </label>
                                    <label class="wpbnp-checkbox-label">
                                        <input type="checkbox" name="settings[items][${itemIndex}][user_roles][]" 
                                               value="contributor" ${itemData.user_roles.includes('contributor') ? 'checked' : ''}>
                                        Contributor
                                    </label>
                                    <label class="wpbnp-checkbox-label">
                                        <input type="checkbox" name="settings[items][${itemIndex}][user_roles][]" 
                                               value="subscriber" ${itemData.user_roles.includes('subscriber') ? 'checked' : ''}>
                                        Subscriber
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="settings[items][${itemIndex}][id]" value="${itemData.id}">
                </div>
            `;
            
            $('#wpbnp-items-list').append(itemHtml);
            this.reindexItems();
        },
        
        // Add navigation item to the interface
        addNavigationItem: function(item, index) {
            this.addItemRow(item, index);
        },
        
        // Handle add item button
        handleAddItem: function(e) {
            e.preventDefault();
            this.addItemRow();
            this.updateItemsDisplay();
        },
        
        // Handle toggle item
        handleToggleItem: function(e) {
            const $row = $(e.target).closest('.wpbnp-nav-item-row');
            const $content = $row.find('.wpbnp-item-content');
            
            if ($content.is(':visible')) {
                $content.slideUp();
                $row.removeClass('expanded');
            } else {
                $content.slideDown();
                $row.addClass('expanded');
            }
        },
        
        // Handle remove item
        handleRemoveItem: function(e) {
            e.preventDefault();
            const $row = $(e.target).closest('.wpbnp-nav-item-row');
            
            if (confirm('Are you sure you want to remove this item?')) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                    WPBottomNavAdmin.reindexItems();
                    WPBottomNavAdmin.updateItemsDisplay();
                });
            }
        },
        
        // Open icon picker
        openIconPicker: function(e) {
            e.preventDefault();
            const $input = $(e.target).siblings('input[type="text"]');
            this.createIconModal();
            $('#wpbnp-icon-modal').data('target-input', $input).show();
            this.updateLibraryInfo();
        },
        
        // Create icon picker modal
        createIconModal: function() {
            if ($('#wpbnp-icon-modal').length > 0) {
                return; // Modal already exists
            }
            
            const modalHtml = `
                <div id="wpbnp-icon-modal" class="wpbnp-modal">
                    <div class="wpbnp-modal-content">
                        <div class="wpbnp-modal-header">
                            <h3>Select Icon</h3>
                            <span class="wpbnp-modal-close">&times;</span>
                        </div>
                        <div class="wpbnp-modal-body">
                            <div class="wpbnp-icon-search">
                                <input type="text" id="wpbnp-icon-search" placeholder="Search icons...">
                            </div>
                            
                            <div class="wpbnp-icon-tabs">
                                <button class="wpbnp-icon-tab active" data-library="bootstrap">
                                    <span class="wpbnp-tab-name">Bootstrap Icons</span>
                                    <span class="wpbnp-tab-count">(1,000+)</span>
                                </button>
                                <button class="wpbnp-icon-tab" data-library="fontawesome">
                                    <span class="wpbnp-tab-name">Font Awesome</span>
                                    <span class="wpbnp-tab-count">(1,600+)</span>
                                </button>
                                <button class="wpbnp-icon-tab" data-library="material">
                                    <span class="wpbnp-tab-name">Material Icons</span>
                                    <span class="wpbnp-tab-count">(1,000+)</span>
                                </button>
                                <button class="wpbnp-icon-tab" data-library="feather">
                                    <span class="wpbnp-tab-name">Feather Icons</span>
                                    <span class="wpbnp-tab-count">(287)</span>
                                </button>
                                <button class="wpbnp-icon-tab" data-library="dashicons">
                                    <span class="wpbnp-tab-name">Dashicons</span>
                                    <span class="wpbnp-tab-count">(400+)</span>
                                </button>
                            </div>
                            
                            <div class="wpbnp-icon-libraries">
                                <div class="wpbnp-icon-library-content active" data-library="bootstrap">
                                    <!-- Bootstrap icons will be populated -->
                                </div>
                                <div class="wpbnp-icon-library-content" data-library="fontawesome">
                                    <!-- Font Awesome icons will be populated -->
                                </div>
                                <div class="wpbnp-icon-library-content" data-library="material">
                                    <!-- Material icons will be populated -->
                                </div>
                                <div class="wpbnp-icon-library-content" data-library="feather">
                                    <!-- Feather icons will be populated -->
                                </div>
                                <div class="wpbnp-icon-library-content" data-library="dashicons">
                                    <!-- Dashicons will be populated -->
                                </div>
                            </div>
                            
                            <div class="wpbnp-icon-info">
                                <span id="wpbnp-icon-count">0 icons</span>
                                <span id="wpbnp-visible-count">0 visible</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            this.populateIconLibraries();
        },
        
        // Populate icon libraries
        populateIconLibraries: function() {
            // Bootstrap Icons
            const bootstrapIcons = [
                'bi bi-house-door', 'bi bi-cart', 'bi bi-person', 'bi bi-heart', 'bi bi-star',
                'bi bi-search', 'bi bi-gear', 'bi bi-bell', 'bi bi-bookmark', 'bi bi-share',
                'bi bi-phone', 'bi bi-envelope', 'bi bi-calendar', 'bi bi-clock', 'bi bi-map',
                'bi bi-camera', 'bi bi-music', 'bi bi-play', 'bi bi-pause', 'bi bi-stop',
                'bi bi-skip-forward', 'bi bi-skip-backward', 'bi bi-volume-up', 'bi bi-volume-mute',
                'bi bi-wifi', 'bi bi-battery-full', 'bi bi-battery-half', 'bi bi-battery-empty'
            ];
            
            const $bootstrapContent = $('.wpbnp-icon-library-content[data-library="bootstrap"]');
            bootstrapIcons.forEach(icon => {
                $bootstrapContent.append(`
                    <div class="wpbnp-icon-option" data-icon="${icon}">
                        <i class="${icon}"></i>
                        <span class="wpbnp-icon-label">${icon}</span>
                    </div>
                `);
            });
            
            // Font Awesome Icons
            const fontAwesomeIcons = [
                'fas fa-home', 'fas fa-shopping-cart', 'fas fa-user', 'fas fa-heart', 'fas fa-star',
                'fas fa-search', 'fas fa-cog', 'fas fa-bell', 'fas fa-bookmark', 'fas fa-share',
                'fas fa-phone', 'fas fa-envelope', 'fas fa-calendar', 'fas fa-clock', 'fas fa-map',
                'fas fa-camera', 'fas fa-music', 'fas fa-play', 'fas fa-pause', 'fas fa-stop',
                'fas fa-forward', 'fas fa-backward', 'fas fa-volume-up', 'fas fa-volume-mute',
                'fas fa-wifi', 'fas fa-battery-full', 'fas fa-battery-half', 'fas fa-battery-empty'
            ];
            
            const $fontAwesomeContent = $('.wpbnp-icon-library-content[data-library="fontawesome"]');
            fontAwesomeIcons.forEach(icon => {
                $fontAwesomeContent.append(`
                    <div class="wpbnp-icon-option" data-icon="${icon}">
                        <i class="${icon}"></i>
                        <span class="wpbnp-icon-label">${icon}</span>
                    </div>
                `);
            });
            
            // Material Icons
            const materialIcons = [
                'material-icons home', 'material-icons shopping_cart', 'material-icons person', 'material-icons favorite', 'material-icons star',
                'material-icons search', 'material-icons settings', 'material-icons notifications', 'material-icons bookmark', 'material-icons share',
                'material-icons phone', 'material-icons email', 'material-icons calendar_today', 'material-icons access_time', 'material-icons place',
                'material-icons camera_alt', 'material-icons music_note', 'material-icons play_arrow', 'material-icons pause', 'material-icons stop',
                'material-icons skip_next', 'material-icons skip_previous', 'material-icons volume_up', 'material-icons volume_off',
                'material-icons wifi', 'material-icons battery_full', 'material-icons battery_half', 'material-icons battery_empty'
            ];
            
            const $materialContent = $('.wpbnp-icon-library-content[data-library="material"]');
            materialIcons.forEach(icon => {
                $materialContent.append(`
                    <div class="wpbnp-icon-option" data-icon="${icon}">
                        <span class="${icon}"></span>
                        <span class="wpbnp-icon-label">${icon}</span>
                    </div>
                `);
            });
            
            // Feather Icons
            const featherIcons = [
                'feather-home', 'feather-shopping-cart', 'feather-user', 'feather-heart', 'feather-star',
                'feather-search', 'feather-settings', 'feather-bell', 'feather-bookmark', 'feather-share',
                'feather-phone', 'feather-mail', 'feather-calendar', 'feather-clock', 'feather-map-pin',
                'feather-camera', 'feather-music', 'feather-play', 'feather-pause', 'feather-square',
                'feather-skip-forward', 'feather-skip-back', 'feather-volume-2', 'feather-volume-x',
                'feather-wifi', 'feather-battery', 'feather-battery-charging', 'feather-battery-dead'
            ];
            
            const $featherContent = $('.wpbnp-icon-library-content[data-library="feather"]');
            featherIcons.forEach(icon => {
                $featherContent.append(`
                    <div class="wpbnp-icon-option" data-icon="${icon}">
                        <i data-feather="${icon.replace('feather-', '')}"></i>
                        <span class="wpbnp-icon-label">${icon}</span>
                    </div>
                `);
            });
            
            // Dashicons
            const dashicons = [
                'dashicons-admin-home', 'dashicons-cart', 'dashicons-admin-users', 'dashicons-heart', 'dashicons-star-filled',
                'dashicons-search', 'dashicons-admin-generic', 'dashicons-bell', 'dashicons-bookmark', 'dashicons-share',
                'dashicons-phone', 'dashicons-email', 'dashicons-calendar', 'dashicons-clock', 'dashicons-location',
                'dashicons-camera', 'dashicons-controls-volumeon', 'dashicons-controls-play', 'dashicons-controls-pause', 'dashicons-controls-stop',
                'dashicons-controls-forward', 'dashicons-controls-back', 'dashicons-controls-volumeon', 'dashicons-controls-volumeoff',
                'dashicons-wifi', 'dashicons-battery-full', 'dashicons-battery-half', 'dashicons-battery-empty'
            ];
            
            const $dashiconsContent = $('.wpbnp-icon-library-content[data-library="dashicons"]');
            dashicons.forEach(icon => {
                $dashiconsContent.append(`
                    <div class="wpbnp-icon-option" data-icon="${icon}">
                        <span class="${icon}"></span>
                        <span class="wpbnp-icon-label">${icon}</span>
                    </div>
                `);
            });
            
            this.updateIconCount();
        },
        
        // Apply preset
        applyPreset: function(e) {
            e.preventDefault();
            const $card = $(e.target).closest('.wpbnp-preset-card');
            const presetKey = $card.data('preset');
            const preset = this.presets[presetKey];
            
            if (!preset) {
                this.showNotification('Preset not found', 'error');
                return;
            }
            
            this.doApplyPreset(preset, presetKey, $card, e);
        },
        
        // Do apply preset
        doApplyPreset: function(preset, presetKey, $target, originalEvent) {
            console.log('Applying preset:', presetKey, preset);
            
            // Update preset field
            $('input[name="settings[preset]"]').val(presetKey);
            
            // Update style fields
            if (preset.style) {
                Object.keys(preset.style).forEach(key => {
                    const $field = $(`input[name="settings[style][${key}]"]`);
                    if ($field.length) {
                        $field.val(preset.style[key]);
                        // Trigger change event for color pickers
                        $field.trigger('change');
                    }
                });
            }
            
            // Update active state
            $('.wpbnp-preset-card').removeClass('active');
            $target.addClass('active');
            
            this.showNotification(`Preset "${preset.name}" applied successfully!`, 'success');
            
            // Auto-save after a short delay
            setTimeout(() => {
                this.saveFormState();
            }, 500);
        },
        
        // Reindex items
        reindexItems: function() {
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function(index) {
                $(this).find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name && name.includes('[items][')) {
                        const newName = name.replace(/\[items\]\[\d+\]/, `[items][${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
        },
        
        // Update items data
        updateItemsData: function() {
            const items = [];
            $('#wpbnp-items-list .wpbnp-nav-item-row').each(function() {
                const $row = $(this);
                const item = {
                    id: $row.find('input[name*="[id]"]').val(),
                    label: $row.find('input[name*="[label]"]').val(),
                    icon: $row.find('input[name*="[icon]"]').val(),
                    url: $row.find('input[name*="[url]"]').val(),
                    enabled: $row.find('input[name*="[enabled]"]').is(':checked'),
                    target: $row.find('select[name*="[target]"]').val(),
                    show_badge: $row.find('input[name*="[show_badge]"]').is(':checked'),
                    badge_type: $row.find('select[name*="[badge_type]"]').val(),
                    custom_badge_text: $row.find('input[name*="[custom_badge_text]"]').val(),
                    user_roles: []
                };
                
                $row.find('input[name*="[user_roles][]"]:checked').each(function() {
                    item.user_roles.push($(this).val());
                });
                
                items.push(item);
            });
            
            return items;
        },
        
        // Refresh items list
        refreshItemsList: function() {
            $('#wpbnp-items-list').empty();
            this.populateFormFields();
        },
        
        // Update items display
        updateItemsDisplay: function() {
            const itemCount = $('#wpbnp-items-list .wpbnp-nav-item-row').length;
            console.log(`Updated items display. Total items: ${itemCount}`);
        },
        
        // Save settings
        saveSettings: function() {
            console.log('Saving settings...');
            
            const formData = new FormData($('#wpbnp-settings-form')[0]);
            
            // Ensure unchecked checkboxes are included
            $('#wpbnp-settings-form input[type="checkbox"]').each(function() {
                const checkbox = $(this);
                const name = checkbox.attr('name');
                if (name && !checkbox.is(':checked')) {
                    formData.append(name, '0');
                }
            });
            
            formData.append('action', 'wpbnp_save_settings');
            formData.append('nonce', wpbnp_admin.nonce);
            
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        WPBottomNavAdmin.showNotification('Settings saved successfully!', 'success');
                    } else {
                        WPBottomNavAdmin.showNotification('Error saving settings', 'error');
                    }
                },
                error: function() {
                    WPBottomNavAdmin.showNotification('Ajax error occurred', 'error');
                }
            });
        },
        
        // Reset settings
        resetSettings: function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                $.ajax({
                    url: wpbnp_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpbnp_reset_settings',
                        nonce: wpbnp_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            WPBottomNavAdmin.showNotification('Settings reset successfully!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            WPBottomNavAdmin.showNotification('Error resetting settings', 'error');
                        }
                    },
                    error: function() {
                        WPBottomNavAdmin.showNotification('Ajax error occurred', 'error');
                    }
                });
            }
        },
        
        // Export settings
        exportSettings: function(e) {
            e.preventDefault();
            const button = $(this);
            const originalText = button.text();
            button.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: wpbnp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_export_settings',
                    nonce: wpbnp_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const dataStr = response.data.data;
                        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
                        const linkElement = document.createElement('a');
                        linkElement.setAttribute('href', dataUri);
                        linkElement.setAttribute('download', response.data.filename);
                        linkElement.click();
                        
                        WPBottomNavAdmin.showNotification('Settings exported successfully!', 'success');
                    } else {
                        WPBottomNavAdmin.showNotification(response.data ? response.data.message : 'Error exporting settings', 'error');
                    }
                },
                error: () => {
                    WPBottomNavAdmin.showNotification('Ajax error occurred', 'error');
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
        }
    };
    
    // Initialize admin
    WPBottomNavAdmin.init();
    
    // Make it globally available
    window.WPBottomNavAdmin = WPBottomNavAdmin;
    
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
                            nonce: wpbnp_admin.nonce,
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
    
});