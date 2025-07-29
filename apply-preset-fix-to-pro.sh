#!/bin/bash

# Apply Preset Dropdown Fix to Pro Branch
# This script helps apply just the preset dropdown fix without merging everything

echo "🚀 WP Bottom Navigation Pro - Preset Dropdown Fix Application"
echo "============================================================="

# Check if we're in the right directory
if [ ! -f "wp-bottom-navigation-pro.php" ]; then
    echo "❌ Error: wp-bottom-navigation-pro.php not found. Are you in the plugin directory?"
    exit 1
fi

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 Current branch: $CURRENT_BRANCH"

# Backup current assets/js/admin.js
echo "💾 Creating backup of assets/js/admin.js..."
cp assets/js/admin.js assets/js/admin.js.backup
echo "✅ Backup created: assets/js/admin.js.backup"

echo ""
echo "🔧 MANUAL STEPS REQUIRED:"
echo "========================="
echo ""
echo "1. Open assets/js/admin.js in your editor"
echo ""
echo "2. Find and replace the following functions with the enhanced versions:"
echo "   - getAvailableCustomPresets"
echo "   - populatePresetSelector"
echo "   - updateAllPresetSelectors"
echo ""
echo "3. Add these new functions:"
echo "   - forceRefreshPresetSelectors"
echo "   - emergencyFixPresetSelectors" 
echo "   - initPresetSelectors"
echo ""
echo "4. In your main initialization (usually in \$(document).ready), add:"
echo "   this.initPresetSelectors();"
echo ""
echo "📁 The enhanced functions are available in: fix-preset-dropdown-issue.js"
echo ""
echo "🚨 EMERGENCY BROWSER CONSOLE FIX:"
echo "================================="
echo "If you need immediate relief, run this in your browser console:"
echo ""
cat << 'EOF'
setTimeout(() => {
    const foundPresets = [];
    $('.wpbnp-preset-item').each(function() {
        const $item = $(this);
        const id = $item.data('preset-id');
        const name = $item.find('.wpbnp-preset-name').text().trim();
        if (id && name) foundPresets.push({ id, name });
    });
    
    $('.wpbnp-preset-selector').each(function() {
        const $selector = $(this);
        $selector.find('optgroup').remove();
        if (foundPresets.length > 0) {
            let html = '<optgroup label="Custom Presets">';
            foundPresets.forEach(preset => {
                html += `<option value="${preset.id}">${preset.name}</option>`;
            });
            html += '</optgroup>';
            $selector.append(html);
        }
    });
    console.log('✅ Emergency fix applied - added', foundPresets.length, 'presets');
}, 1000);
EOF

echo ""
echo "📋 VERIFICATION STEPS:"
echo "======================"
echo "After applying the fix:"
echo "1. Create a custom preset in the Items tab"
echo "2. Click 'Save Changes'"
echo "3. Go to Page Targeting tab"
echo "4. Check if presets appear in 'Preset to Display' dropdown"
echo ""
echo "🔍 DEBUGGING:"
echo "============="
echo "If it still doesn't work, run in browser console:"
echo "WPBottomNavAdmin.debugPresets();"
echo "WPBottomNavAdmin.forceRefreshPresetSelectors();"
echo ""
echo "✅ Ready to apply the fix!"