/**
 * COMPREHENSIVE PRESET DROPDOWN FIX
 * 
 * This script fixes the persistent issue where custom presets 
 * are not showing in Page Targeting dropdowns.
 * 
 * Apply this fix to your pro branch by copying the relevant parts
 * into your assets/js/admin.js file.
 */

// 🎯 ROOT CAUSE ANALYSIS:
// The issue is likely one of these:
// 1. Timing issue - selectors populated before presets are available
// 2. Event binding issue - functions not called at right time
// 3. Data source issue - presets not properly retrieved
// 4. DOM targeting issue - selectors not found

// 🔧 COMPREHENSIVE FIX:

// Replace the existing getAvailableCustomPresets function with this enhanced version:
getAvailableCustomPresets: function() {
    const presets = [];
    const debugMode = true; // Set to false in production
    
    if (debugMode) console.log('🔍 Getting available custom presets...');
    
    // Method 1: Try to get from wpbnp_admin.settings (database source)
    if (typeof wpbnp_admin !== 'undefined' && wpbnp_admin.settings && wpbnp_admin.settings.custom_presets) {
        const settingsPresets = wpbnp_admin.settings.custom_presets.presets || [];
        if (debugMode) console.log(`📊 Found ${settingsPresets.length} presets in wpbnp_admin.settings`);
        
        settingsPresets.forEach(preset => {
            if (preset.id && preset.name) {
                presets.push({
                    id: preset.id,
                    name: preset.name,
                    items: preset.items || [],
                    source: 'settings'
                });
                if (debugMode) console.log(`✅ Added settings preset: ${preset.name}`);
            }
        });
    } else {
        if (debugMode) console.log('⚠️ wpbnp_admin.settings.custom_presets not available');
    }
    
    // Method 2: Scan DOM for additional presets (unsaved ones)
    const settingsPresetIds = presets.map(p => p.id);
    $('.wpbnp-preset-item').each(function() {
        const $item = $(this);
        const presetId = $item.data('preset-id');
        
        // Skip if already found in settings
        if (settingsPresetIds.includes(presetId)) {
            if (debugMode) console.log(`⏭️ Skipping DOM preset ${presetId} - already in settings`);
            return;
        }
        
        const preset = {
            id: presetId,
            name: $item.find('.wpbnp-preset-name').text().trim(),
            items: [],
            source: 'dom'
        };
        
        // Try to get items from hidden input
        const itemsJson = $item.find('input[name*="[items]"]').val();
        if (itemsJson) {
            try {
                preset.items = JSON.parse(itemsJson);
            } catch (e) {
                if (debugMode) console.warn('❌ Failed to parse preset items:', e);
            }
        }
        
        if (preset.id && preset.name) {
            presets.push(preset);
            if (debugMode) console.log(`✅ Added DOM preset: ${preset.name}`);
        }
    });
    
    if (debugMode) {
        console.log(`🎯 Total presets found: ${presets.length}`);
        presets.forEach(p => console.log(`  - ${p.name} (${p.items.length} items, source: ${p.source})`));
    }
    
    return presets;
},

// Replace the existing populatePresetSelector function with this enhanced version:
populatePresetSelector: function($selector) {
    if (!$selector || !$selector.length) {
        console.warn('❌ populatePresetSelector: Invalid selector provided');
        return;
    }
    
    const debugMode = true; // Set to false in production
    if (debugMode) console.log('🔄 Populating preset selector:', $selector[0]);
    
    // Get available presets
    const presets = this.getAvailableCustomPresets();
    
    // Remove existing custom preset options (keep default)
    $selector.find('optgroup').remove();
    $selector.find('option:not([value="default"])').remove();
    
    if (presets.length > 0) {
        // Create optgroup for custom presets
        let optgroupHtml = '<optgroup label="Custom Presets">';
        
        presets.forEach(preset => {
            const itemCount = preset.items ? preset.items.length : 0;
            const selected = $selector.data('selected-preset') === preset.id ? 'selected' : '';
            optgroupHtml += `<option value="${preset.id}" ${selected}>${preset.name} (${itemCount} items)</option>`;
        });
        
        optgroupHtml += '</optgroup>';
        $selector.append(optgroupHtml);
        
        if (debugMode) console.log(`✅ Added ${presets.length} presets to selector`);
    } else {
        // Add "no presets" option
        const noPresetsOption = '<option value="" disabled>No custom presets available - Create some in the Items tab</option>';
        $selector.append(noPresetsOption);
        
        if (debugMode) console.log('ℹ️ No presets available - added placeholder option');
    }
},

// Replace the existing updateAllPresetSelectors function with this enhanced version:
updateAllPresetSelectors: function() {
    const debugMode = true; // Set to false in production
    if (debugMode) console.log('🔄 Updating all preset selectors...');
    
    // Update settings data first
    this.updateSettingsPresetData();
    
    // Find all preset selectors
    const $selectors = $('.wpbnp-preset-selector');
    if (debugMode) console.log(`📊 Found ${$selectors.length} preset selectors`);
    
    if ($selectors.length === 0) {
        if (debugMode) console.log('⚠️ No preset selectors found - might not be on Page Targeting tab');
        return;
    }
    
    // Update each selector
    const self = this;
    $selectors.each(function(index) {
        const $selector = $(this);
        if (debugMode) console.log(`🎯 Updating selector ${index + 1}:`, $selector[0]);
        
        // Store current selection
        const currentValue = $selector.val();
        $selector.data('selected-preset', currentValue);
        
        // Populate with latest presets
        self.populatePresetSelector($selector);
        
        // Restore selection if still valid
        if (currentValue && $selector.find(`option[value="${currentValue}"]`).length > 0) {
            $selector.val(currentValue);
            if (debugMode) console.log(`✅ Restored selection: ${currentValue}`);
        }
    });
    
    if (debugMode) console.log('✅ All preset selectors updated');
},

// Add this new function to force refresh preset selectors:
forceRefreshPresetSelectors: function() {
    console.log('🚀 Force refreshing preset selectors...');
    
    // Wait for DOM to be ready
    setTimeout(() => {
        this.updateAllPresetSelectors();
        
        // If still no presets, try emergency fix
        setTimeout(() => {
            const $selectors = $('.wpbnp-preset-selector');
            const hasCustomOptions = $selectors.find('optgroup').length > 0;
            
            if (!hasCustomOptions && $('.wpbnp-preset-item').length > 0) {
                console.log('🚨 Applying emergency fix...');
                this.emergencyFixPresetSelectors();
            }
        }, 500);
    }, 100);
},

// Add this emergency fix function:
emergencyFixPresetSelectors: function() {
    console.log('🚨 Emergency fix: Manually populating preset selectors...');
    
    const foundPresets = [];
    
    // Scan DOM for any presets
    $('.wpbnp-preset-item').each(function() {
        const $item = $(this);
        const id = $item.data('preset-id');
        const name = $item.find('.wpbnp-preset-name').text().trim();
        
        if (id && name) {
            foundPresets.push({ id, name });
        }
    });
    
    console.log(`🔍 Emergency scan found ${foundPresets.length} presets:`, foundPresets);
    
    // Force add to all selectors
    $('.wpbnp-preset-selector').each(function() {
        const $selector = $(this);
        
        // Remove existing custom options
        $selector.find('optgroup').remove();
        $selector.find('option:not([value="default"])').remove();
        
        if (foundPresets.length > 0) {
            let html = '<optgroup label="Custom Presets (Emergency Fix)">';
            foundPresets.forEach(preset => {
                html += `<option value="${preset.id}">${preset.name}</option>`;
            });
            html += '</optgroup>';
            $selector.append(html);
        }
    });
    
    console.log('🚨 Emergency fix applied!');
},

// Enhanced initialization - add this to your main init function:
initPresetSelectors: function() {
    console.log('🚀 Initializing preset selectors...');
    
    // Initial population
    this.forceRefreshPresetSelectors();
    
    // Refresh when switching to Page Targeting tab
    $(document).on('click', '.wpbnp-tab[href*="page_targeting"]', () => {
        console.log('📍 Switched to Page Targeting tab - refreshing selectors...');
        setTimeout(() => {
            this.forceRefreshPresetSelectors();
        }, 200);
    });
    
    // Refresh after creating/editing presets
    $(document).on('click', '.wpbnp-preset-edit, .wpbnp-preset-duplicate', () => {
        setTimeout(() => {
            this.updateAllPresetSelectors();
        }, 100);
    });
    
    // Refresh after form saves
    $(document).on('wpbnp-settings-saved', () => {
        setTimeout(() => {
            this.updateAllPresetSelectors();
        }, 500);
    });
    
    console.log('✅ Preset selectors initialized');
}

// 📋 INTEGRATION INSTRUCTIONS:
// 
// 1. Copy the functions above into your assets/js/admin.js file
// 2. Replace the existing functions with the same names
// 3. Add this line to your main initialization (usually in $(document).ready):
//    this.initPresetSelectors();
// 4. Test by creating a preset and switching to Page Targeting tab
// 
// 🚨 EMERGENCY CONSOLE COMMANDS:
// 
// If it still doesn't work, run these in browser console:
// 
// // Force refresh
// WPBottomNavAdmin.forceRefreshPresetSelectors();
// 
// // Emergency fix
// WPBottomNavAdmin.emergencyFixPresetSelectors();
// 
// // Debug info
// WPBottomNavAdmin.debugPresets();