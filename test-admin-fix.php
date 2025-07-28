<?php
/**
 * Test file to verify the Enable Bottom Navigation checkbox fix
 * 
 * Place this file in your WordPress root and access it via browser
 * Example: http://yoursite.com/test-admin-fix.php
 */

// Load WordPress environment
require_once('wp-config.php');
require_once(ABSPATH . 'wp-load.php');

// Simple test interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>WP Bottom Navigation Pro - Admin Fix Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test-section { background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>WP Bottom Navigation Pro - Admin Fix Test</h1>
    
    <div class="test-section">
        <h2>Current Settings Test</h2>
        <?php
        if (function_exists('wpbnp_get_settings')) {
            $settings = wpbnp_get_settings();
            echo '<p class="info">Settings function exists ✓</p>';
            echo '<p><strong>Enable Bottom Navigation:</strong> ' . ($settings['enabled'] ? '<span class="success">ENABLED</span>' : '<span class="error">DISABLED</span>') . '</p>';
            echo '<details><summary>Full Settings (click to expand)</summary><pre>' . print_r($settings, true) . '</pre></details>';
        } else {
            echo '<p class="error">Settings function does not exist ✗</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>JavaScript Console Test</h2>
        <p class="info">Open your browser's developer tools (F12) and check the console for debug messages when:</p>
        <ul>
            <li>Loading the admin page</li>
            <li>Switching between tabs</li>
            <li>Changing the Enable Bottom Navigation checkbox</li>
            <li>Saving settings</li>
        </ul>
        <p>You should see messages like:</p>
        <ul>
            <li>"Set enabled checkbox to: true/false"</li>
            <li>"Auto-saved form state due to field change"</li>
            <li>"Saved state due to Enable Bottom Navigation change"</li>
            <li>"Form state restored successfully"</li>
        </ul>
    </div>
    
    <div class="test-section">
        <h2>Step-by-Step Test Instructions</h2>
        <ol>
            <li><strong>Go to Admin Panel:</strong> Navigate to Appearance → Bottom Navigation</li>
            <li><strong>Enable Navigation:</strong> Check the "Enable Bottom Navigation" checkbox in the Items tab</li>
            <li><strong>Switch Tabs:</strong> Click on the "Styles" or "Devices" tab</li>
            <li><strong>Check State:</strong> Go back to the "Items" tab and verify the checkbox is still checked</li>
            <li><strong>Make Changes:</strong> In any other tab (like Styles), change a setting (like background color)</li>
            <li><strong>Verify Persistence:</strong> Return to Items tab - the "Enable Bottom Navigation" should still be checked</li>
            <li><strong>Save Settings:</strong> Click "Save Changes" button</li>
            <li><strong>Final Test:</strong> Refresh the page and verify all settings are preserved</li>
        </ol>
    </div>
    
    <div class="test-section">
        <h2>LocalStorage Debug</h2>
        <p class="info">You can check the browser's localStorage for the 'wpbnp_form_state' key:</p>
        <ol>
            <li>Open developer tools (F12)</li>
            <li>Go to Application tab (Chrome) or Storage tab (Firefox)</li>
            <li>Look for localStorage → 'wpbnp_form_state'</li>
            <li>This should contain your form data when switching tabs</li>
        </ol>
        
        <script>
        function checkLocalStorage() {
            const state = localStorage.getItem('wpbnp_form_state');
            if (state) {
                try {
                    const parsed = JSON.parse(state);
                    document.getElementById('localStorage-content').innerHTML = 
                        '<pre>' + JSON.stringify(parsed, null, 2) + '</pre>';
                } catch (e) {
                    document.getElementById('localStorage-content').innerHTML = 
                        '<p class="error">Error parsing localStorage data</p>';
                }
            } else {
                document.getElementById('localStorage-content').innerHTML = 
                    '<p class="info">No localStorage data found</p>';
            }
        }
        </script>
        
        <button onclick="checkLocalStorage()">Check Current localStorage</button>
        <div id="localStorage-content"></div>
    </div>
    
    <div class="test-section">
        <h2>Plugin Files Status</h2>
        <?php
        $files_to_check = [
            'wp-bottom-navigation-pro.php',
            'assets/js/admin.js',
            'assets/css/admin.css',
            'admin/settings-ui.php',
            'includes/functions.php'
        ];
        
        echo '<ul>';
        foreach ($files_to_check as $file) {
            $file_path = ABSPATH . $file;
            if (file_exists($file_path)) {
                $modified = date('Y-m-d H:i:s', filemtime($file_path));
                echo '<li class="success">✓ ' . $file . ' (modified: ' . $modified . ')</li>';
            } else {
                echo '<li class="error">✗ ' . $file . ' - FILE NOT FOUND</li>';
            }
        }
        echo '</ul>';
        ?>
    </div>
    
    <div class="test-section">
        <h2>Quick Access Links</h2>
        <p><a href="<?php echo admin_url('themes.php?page=wp-bottom-navigation-pro'); ?>" target="_blank">→ Go to Bottom Navigation Admin</a></p>
        <p><a href="<?php echo home_url(); ?>" target="_blank">→ View Frontend</a></p>
    </div>
    
</body>
</html>