<?php
/**
 * Comprehensive Diagnostic Script for Preset Display Issue
 * 
 * This script should be run in WordPress admin context to diagnose
 * why custom presets are not showing in Page Targeting dropdowns.
 */

// Ensure we're in WordPress context
if (!function_exists('get_option')) {
    die('This script must be run in WordPress context. Add this code to a WordPress page or plugin.');
}

echo "<h1>🔍 WP Bottom Navigation Pro - Preset Display Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 5px; }
    .error { background: #ffebee; border-left: 4px solid #f44336; }
    .success { background: #e8f5e8; border-left: 4px solid #4caf50; }
    .warning { background: #fff3e0; border-left: 4px solid #ff9800; }
    .code { background: #f5f5f5; padding: 10px; font-family: monospace; border-radius: 3px; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
</style>";

// Test 1: Check if plugin files exist
echo "<div class='section'>";
echo "<h2>📁 Test 1: Plugin Files Check</h2>";

$plugin_files = [
    'wp-bottom-navigation-pro.php',
    'includes/functions.php',
    'admin/settings-ui.php', 
    'assets/js/admin.js'
];

foreach ($plugin_files as $file) {
    if (file_exists($file)) {
        echo "<div class='test-result success'>✅ {$file} - EXISTS</div>";
    } else {
        echo "<div class='test-result error'>❌ {$file} - MISSING</div>";
    }
}
echo "</div>";

// Test 2: Check database settings
echo "<div class='section'>";
echo "<h2>💾 Test 2: Database Settings Check</h2>";

$settings = get_option('wpbnp_settings', []);
echo "<div class='test-result'>";
echo "<strong>Raw Settings from Database:</strong>";
echo "<div class='code'>" . print_r($settings, true) . "</div>";
echo "</div>";

if (isset($settings['custom_presets'])) {
    echo "<div class='test-result success'>✅ custom_presets key exists in database</div>";
    echo "<div class='test-result'>";
    echo "<strong>Custom Presets Structure:</strong>";
    echo "<div class='code'>" . print_r($settings['custom_presets'], true) . "</div>";
    echo "</div>";
    
    $enabled = $settings['custom_presets']['enabled'] ?? false;
    $presets = $settings['custom_presets']['presets'] ?? [];
    
    echo "<div class='test-result " . ($enabled ? 'success' : 'warning') . "'>";
    echo ($enabled ? '✅' : '⚠️') . " Custom presets enabled: " . ($enabled ? 'YES' : 'NO');
    echo "</div>";
    
    echo "<div class='test-result'>";
    echo "<strong>Presets count:</strong> " . count($presets);
    echo "</div>";
    
    if (!empty($presets)) {
        foreach ($presets as $index => $preset) {
            echo "<div class='test-result'>";
            echo "<strong>Preset " . ($index + 1) . ":</strong>";
            echo "<div class='code'>" . print_r($preset, true) . "</div>";
            echo "</div>";
        }
    } else {
        echo "<div class='test-result warning'>⚠️ No presets found in database</div>";
    }
} else {
    echo "<div class='test-result error'>❌ custom_presets key missing from database</div>";
}
echo "</div>";

// Test 3: Check if functions exist
echo "<div class='section'>";
echo "<h2>🔧 Test 3: Required Functions Check</h2>";

$required_functions = [
    'wpbnp_get_settings',
    'wpbnp_get_default_settings',
    'wpbnp_sanitize_settings'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "<div class='test-result success'>✅ {$func}() - EXISTS</div>";
    } else {
        echo "<div class='test-result error'>❌ {$func}() - MISSING</div>";
    }
}
echo "</div>";

// Test 4: Test wpbnp_get_settings() function
echo "<div class='section'>";
echo "<h2>⚙️ Test 4: wpbnp_get_settings() Function Test</h2>";

if (function_exists('wpbnp_get_settings')) {
    $function_settings = wpbnp_get_settings();
    echo "<div class='test-result success'>✅ wpbnp_get_settings() callable</div>";
    
    echo "<div class='test-result'>";
    echo "<strong>Settings from wpbnp_get_settings():</strong>";
    echo "<div class='code'>" . print_r($function_settings['custom_presets'] ?? 'NOT FOUND', true) . "</div>";
    echo "</div>";
    
    // Compare with direct database call
    if (isset($function_settings['custom_presets']) && isset($settings['custom_presets'])) {
        if ($function_settings['custom_presets'] === $settings['custom_presets']) {
            echo "<div class='test-result success'>✅ Function settings match database settings</div>";
        } else {
            echo "<div class='test-result warning'>⚠️ Function settings differ from database settings</div>";
        }
    }
} else {
    echo "<div class='test-result error'>❌ wpbnp_get_settings() not available</div>";
}
echo "</div>";

// Test 5: Check JavaScript localization
echo "<div class='section'>";
echo "<h2>🌐 Test 5: JavaScript Localization Check</h2>";

// Simulate what should be passed to JavaScript
if (function_exists('wpbnp_get_settings')) {
    $js_settings = wpbnp_get_settings();
    echo "<div class='test-result'>";
    echo "<strong>Settings that should be passed to JavaScript:</strong>";
    echo "<div class='code'>" . print_r($js_settings['custom_presets'] ?? 'NOT FOUND', true) . "</div>";
    echo "</div>";
    
    if (isset($js_settings['custom_presets']['presets']) && !empty($js_settings['custom_presets']['presets'])) {
        echo "<div class='test-result success'>✅ Presets should be available to JavaScript</div>";
    } else {
        echo "<div class='test-result warning'>⚠️ No presets available for JavaScript</div>";
    }
} else {
    echo "<div class='test-result error'>❌ Cannot simulate JavaScript data</div>";
}
echo "</div>";

// Test 6: Pro License Check
echo "<div class='section'>";
echo "<h2>🔐 Test 6: Pro License Check</h2>";

if (function_exists('wpbnp_is_pro_license_active')) {
    $is_pro = wpbnp_is_pro_license_active();
    echo "<div class='test-result " . ($is_pro ? 'success' : 'warning') . "'>";
    echo ($is_pro ? '✅' : '⚠️') . " Pro license active: " . ($is_pro ? 'YES' : 'NO');
    echo "</div>";
} else {
    echo "<div class='test-result warning'>⚠️ wpbnp_is_pro_license_active() function not found</div>";
}

// Check license option directly
$license_status = get_option('wpbnp_license_status', 'inactive');
echo "<div class='test-result'>";
echo "<strong>License status from database:</strong> " . $license_status;
echo "</div>";
echo "</div>";

// Test 7: Check default settings
echo "<div class='section'>";
echo "<h2>🏗️ Test 7: Default Settings Check</h2>";

if (function_exists('wpbnp_get_default_settings')) {
    $defaults = wpbnp_get_default_settings();
    echo "<div class='test-result success'>✅ wpbnp_get_default_settings() callable</div>";
    
    echo "<div class='test-result'>";
    echo "<strong>Default custom_presets structure:</strong>";
    echo "<div class='code'>" . print_r($defaults['custom_presets'] ?? 'NOT FOUND', true) . "</div>";
    echo "</div>";
} else {
    echo "<div class='test-result error'>❌ wpbnp_get_default_settings() not available</div>";
}
echo "</div>";

// Test 8: Recommendations
echo "<div class='section'>";
echo "<h2>💡 Test 8: Diagnostic Results & Recommendations</h2>";

$issues = [];
$recommendations = [];

// Check for common issues
if (!isset($settings['custom_presets'])) {
    $issues[] = "custom_presets key missing from database";
    $recommendations[] = "Run plugin activation or save settings once to initialize structure";
}

if (isset($settings['custom_presets']) && !$settings['custom_presets']['enabled']) {
    $issues[] = "custom_presets disabled in settings";
    $recommendations[] = "Activate Pro license or manually enable custom_presets";
}

if (isset($settings['custom_presets']['presets']) && empty($settings['custom_presets']['presets'])) {
    $issues[] = "No custom presets saved in database";
    $recommendations[] = "Create custom presets in Items tab and click 'Save Changes'";
}

if (empty($issues)) {
    echo "<div class='test-result success'>✅ No obvious issues detected</div>";
    echo "<div class='test-result'>The issue might be in JavaScript processing or DOM interaction.</div>";
} else {
    echo "<div class='test-result error'>";
    echo "<strong>Issues Found:</strong><ul>";
    foreach ($issues as $issue) {
        echo "<li>" . $issue . "</li>";
    }
    echo "</ul></div>";
    
    echo "<div class='test-result warning'>";
    echo "<strong>Recommendations:</strong><ul>";
    foreach ($recommendations as $rec) {
        echo "<li>" . $rec . "</li>";
    }
    echo "</ul></div>";
}
echo "</div>";

// Test 9: JavaScript Debug Code
echo "<div class='section'>";
echo "<h2>🔬 Test 9: JavaScript Debug Code</h2>";
echo "<p>Add this code to your browser console on the admin page:</p>";
echo "<div class='code'>";
echo htmlspecialchars("
// Check if wpbnp_admin object exists
console.log('wpbnp_admin exists:', typeof wpbnp_admin !== 'undefined');

// Check settings structure
if (typeof wpbnp_admin !== 'undefined') {
    console.log('wpbnp_admin object:', wpbnp_admin);
    console.log('Settings:', wpbnp_admin.settings);
    console.log('Custom presets:', wpbnp_admin.settings?.custom_presets);
}

// Check DOM elements
console.log('Preset items in DOM:', $('.wpbnp-preset-item').length);
console.log('Preset selectors in DOM:', $('.wpbnp-preset-selector').length);

// Check if admin object has debug function
if (typeof WPBottomNavAdmin !== 'undefined' && WPBottomNavAdmin.debugPresets) {
    console.log('Running debug function...');
    WPBottomNavAdmin.debugPresets();
} else {
    console.log('WPBottomNavAdmin.debugPresets not available');
}
");
echo "</div>";
echo "</div>";

echo "<div class='section success'>";
echo "<h2>✅ Diagnostic Complete</h2>";
echo "<p>Review the results above to identify the root cause of the preset display issue.</p>";
echo "<p>If all tests pass but presets still don't show, the issue is likely in the JavaScript processing logic.</p>";
echo "</div>";
?>