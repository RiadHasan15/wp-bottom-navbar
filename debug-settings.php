<?php
// Debug script to check what's in the settings
require_once 'wp-bottom-navigation-pro.php';
require_once 'includes/functions.php';

echo "<h1>WP Bottom Navigation Pro - Settings Debug</h1>";

// Check if WordPress functions are available
if (!function_exists('get_option')) {
    echo "<p><strong>Error:</strong> WordPress functions not available. This script needs to be run in WordPress context.</p>";
    echo "<p>Instead, add this code to your functions.php or create a WordPress page with this code:</p>";
    echo "<pre>";
    echo htmlspecialchars('
$settings = get_option("wpbnp_settings", array());
echo "<h2>Current Settings:</h2>";
echo "<pre>";
print_r($settings);
echo "</pre>";

if (isset($settings["custom_presets"])) {
    echo "<h2>Custom Presets:</h2>";
    echo "<pre>";
    print_r($settings["custom_presets"]);
    echo "</pre>";
    
    if (isset($settings["custom_presets"]["presets"]) && !empty($settings["custom_presets"]["presets"])) {
        echo "<h2>Individual Presets:</h2>";
        foreach ($settings["custom_presets"]["presets"] as $index => $preset) {
            echo "<h3>Preset " . ($index + 1) . ":</h3>";
            echo "<pre>";
            print_r($preset);
            echo "</pre>";
        }
    } else {
        echo "<p><strong>No custom presets found in database!</strong></p>";
    }
} else {
    echo "<p><strong>custom_presets key not found in settings!</strong></p>";
}
    ');
    echo "</pre>";
    exit;
}

// Get current settings
$settings = get_option('wpbnp_settings', array());

echo "<h2>Current Settings:</h2>";
echo "<pre>";
print_r($settings);
echo "</pre>";

if (isset($settings['custom_presets'])) {
    echo "<h2>Custom Presets:</h2>";
    echo "<pre>";
    print_r($settings['custom_presets']);
    echo "</pre>";
    
    if (isset($settings['custom_presets']['presets']) && !empty($settings['custom_presets']['presets'])) {
        echo "<h2>Individual Presets:</h2>";
        foreach ($settings['custom_presets']['presets'] as $index => $preset) {
            echo "<h3>Preset " . ($index + 1) . ":</h3>";
            echo "<pre>";
            print_r($preset);
            echo "</pre>";
        }
    } else {
        echo "<p><strong>No custom presets found in database!</strong></p>";
    }
} else {
    echo "<p><strong>custom_presets key not found in settings!</strong></p>";
}

// Check default settings
echo "<h2>Default Settings (for comparison):</h2>";
$defaults = wpbnp_get_default_settings();
echo "<pre>";
print_r($defaults['custom_presets']);
echo "</pre>";
?>