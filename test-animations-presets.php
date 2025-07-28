<?php
/**
 * Animation & Preset Test Tool for WP Bottom Navigation Pro
 * 
 * Place this file in your WordPress root directory and visit:
 * yoursite.com/test-animations-presets.php
 * 
 * This will help debug and verify animations and presets are working.
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

// Get current settings
$settings = wpbnp_get_settings();
?>
<!DOCTYPE html>
<html>
<head>
    <title>WP Bottom Navigation Pro - Animation & Preset Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .test-container { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status { 
            padding: 10px; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .status.success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .status.error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        .status.warning { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeaa7;
        }
        .code-block { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 4px; 
            font-family: monospace; 
            border-left: 4px solid #007cba;
            margin: 10px 0;
        }
        .test-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        .test-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
        }
        .test-btn.animation { background: #007cba; }
        .test-btn.preset { background: #00a32a; }
        .settings-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .settings-table th,
        .settings-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .settings-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>🎬 WP Bottom Navigation Pro - Animation & Preset Test</h1>
    
    <div class="test-container">
        <h2>📊 Current Settings Status</h2>
        
        <table class="settings-table">
            <tr>
                <th>Setting</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Plugin Enabled</td>
                <td><?php echo $settings['enabled'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <?php if ($settings['enabled']): ?>
                        <span class="status success">✅ Active</span>
                    <?php else: ?>
                        <span class="status error">❌ Disabled</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Animation Enabled</td>
                <td><?php echo $settings['animations']['enabled'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <?php if ($settings['animations']['enabled']): ?>
                        <span class="status success">✅ Enabled</span>
                    <?php else: ?>
                        <span class="status warning">⚠️ Disabled</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Animation Type</td>
                <td><?php echo esc_html($settings['animations']['type']); ?></td>
                <td>
                    <?php if ($settings['animations']['type'] !== 'none'): ?>
                        <span class="status success">✅ Set</span>
                    <?php else: ?>
                        <span class="status warning">⚠️ None selected</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Animation Duration</td>
                <td><?php echo esc_html($settings['animations']['duration']); ?>ms</td>
                <td><span class="status success">✅ Set</span></td>
            </tr>
            <tr>
                <td>Current Preset</td>
                <td><?php echo esc_html($settings['preset'] ?? 'minimal'); ?></td>
                <td><span class="status success">✅ Set</span></td>
            </tr>
            <tr>
                <td>Navigation Items</td>
                <td><?php echo count($settings['items']); ?> items</td>
                <td>
                    <?php if (count($settings['items']) > 0): ?>
                        <span class="status success">✅ Has items</span>
                    <?php else: ?>
                        <span class="status error">❌ No items</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="test-container">
        <h2>🧪 Dynamic CSS Generation Test</h2>
        <?php
        // Test CSS generation
        $plugin = WP_Bottom_Navigation_Pro::get_instance();
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod('generate_dynamic_css');
        $method->setAccessible(true);
        
        try {
            $generated_css = $method->invoke($plugin, $settings);
            $css_length = strlen($generated_css);
            
            echo '<div class="status success">✅ CSS Generation: Success</div>';
            echo "<p><strong>Generated CSS Length:</strong> {$css_length} characters</p>";
            
            // Check for specific animation CSS
            $animation_type = $settings['animations']['type'];
            if ($settings['animations']['enabled'] && $animation_type !== 'none') {
                if (strpos($generated_css, "wpbnp-hover-{$animation_type}") !== false) {
                    echo '<div class="status success">✅ Animation CSS: Found keyframes for ' . $animation_type . '</div>';
                } else {
                    echo '<div class="status error">❌ Animation CSS: Missing keyframes for ' . $animation_type . '</div>';
                }
            }
            
            // Check for preset CSS
            $current_preset = $settings['preset'] ?? 'minimal';
            if (strpos($generated_css, "Preset CSS: {$current_preset}") !== false) {
                echo '<div class="status success">✅ Preset CSS: Found styles for ' . $current_preset . '</div>';
            } else {
                echo '<div class="status warning">⚠️ Preset CSS: No special styles for ' . $current_preset . '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ CSS Generation Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <div class="test-container">
        <h2>🎨 Test Different Animations</h2>
        <p>Click these buttons to temporarily test different animations on your frontend:</p>
        
        <div class="test-buttons">
            <button class="test-btn animation" onclick="testAnimation('bounce')">Bounce</button>
            <button class="test-btn animation" onclick="testAnimation('zoom')">Zoom</button>
            <button class="test-btn animation" onclick="testAnimation('pulse')">Pulse</button>
            <button class="test-btn animation" onclick="testAnimation('fade')">Fade</button>
            <button class="test-btn animation" onclick="testAnimation('slide')">Slide</button>
            <button class="test-btn animation" onclick="testAnimation('rotate')">Rotate</button>
            <button class="test-btn animation" onclick="testAnimation('shake')">Shake</button>
            <button class="test-btn animation" onclick="testAnimation('heartbeat')">Heartbeat</button>
            <button class="test-btn animation" onclick="testAnimation('swing')">Swing</button>
            <button class="test-btn animation" onclick="testAnimation('ripple')">Ripple</button>
        </div>
    </div>

    <div class="test-container">
        <h2>🎭 Test Different Presets</h2>
        <p>Click these buttons to temporarily test different presets on your frontend:</p>
        
        <div class="test-buttons">
            <button class="test-btn preset" onclick="testPreset('minimal')">Minimal</button>
            <button class="test-btn preset" onclick="testPreset('dark')">Dark</button>
            <button class="test-btn preset" onclick="testPreset('material')">Material</button>
            <button class="test-btn preset" onclick="testPreset('ios')">iOS</button>
            <button class="test-btn preset" onclick="testPreset('glassmorphism')">Glassmorphism</button>
            <button class="test-btn preset" onclick="testPreset('neumorphism')">Neumorphism</button>
            <button class="test-btn preset" onclick="testPreset('cyberpunk')">Cyberpunk</button>
            <button class="test-btn preset" onclick="testPreset('vintage')">Vintage</button>
            <button class="test-btn preset" onclick="testPreset('gradient')">Gradient</button>
            <button class="test-btn preset" onclick="testPreset('floating')">Floating</button>
        </div>
    </div>

    <div class="test-container">
        <h2>🔧 Frontend Debug Code</h2>
        <p>Copy this code and paste it in your browser console on the frontend to test:</p>
        
        <div class="code-block">
// Test animation<br>
debugTestAnimation('bounce'); // Replace 'bounce' with any animation type<br><br>

// Test preset<br>
debugApplyPreset('dark'); // Replace 'dark' with any preset name<br><br>

// Check current styles<br>
debugCheckPreset();
        </div>
    </div>

    <div class="test-container">
        <h2>📋 Troubleshooting Steps</h2>
        
        <ol>
            <li><strong>Verify Plugin is Enabled:</strong> Make sure "Enable Bottom Navigation" is checked</li>
            <li><strong>Check Animation Settings:</strong> Go to Animations tab and enable animations</li>
            <li><strong>Clear Cache:</strong> Clear any caching plugins and browser cache</li>
            <li><strong>Check Console:</strong> Open browser developer tools and check for JavaScript errors</li>
            <li><strong>Inspect CSS:</strong> Look for the dynamic CSS in the page source</li>
            <li><strong>Test Frontend:</strong> Visit your frontend and hover/click navigation items</li>
        </ol>
    </div>

    <script>
        function testAnimation(type) {
            // Open frontend in new tab with test animation
            const testUrl = '<?php echo home_url(); ?>?wpbnp_test_animation=' + type;
            window.open(testUrl, '_blank');
            alert('Frontend opened in new tab. Hover over navigation items to see ' + type + ' animation.');
        }

        function testPreset(preset) {
            // Open frontend in new tab with test preset
            const testUrl = '<?php echo home_url(); ?>?wpbnp_test_preset=' + preset;
            window.open(testUrl, '_blank');
            alert('Frontend opened in new tab with ' + preset + ' preset applied.');
        }
    </script>
</body>
</html>