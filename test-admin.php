<?php
/**
 * Test Admin Interface for WP Bottom Navigation Pro
 * This simulates the admin interface outside of WordPress
 */

// Mock WordPress functions for testing
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); }
function esc_textarea($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html_e($text) { echo esc_html($text); }
function checked($checked, $current = true, $echo = true) {
    $result = $checked == $current ? 'checked="checked"' : '';
    if ($echo) echo $result;
    return $result;
}
function selected($selected, $current = true, $echo = true) {
    $result = $selected == $current ? 'selected="selected"' : '';
    if ($echo) echo $result;
    return $result;
}
function admin_url($path) { return $path; }

// Include plugin files
define('WPBNP_PLUGIN_DIR', __DIR__ . '/');
define('WPBNP_PLUGIN_URL', '/');
define('WPBNP_VERSION', '1.0.0');

require_once 'includes/functions.php';

// Get sample settings
$settings = array(
    'enabled' => true,
    'items' => array(
        array(
            'id' => 'home',
            'label' => 'Home',
            'icon' => 'dashicons-admin-home',
            'url' => '/',
            'enabled' => true
        ),
        array(
            'id' => 'shop',
            'label' => 'Shop',
            'icon' => 'dashicons-cart',
            'url' => '/shop',
            'enabled' => true
        )
    ),
    'style' => array(
        'background_color' => '#ffffff',
        'text_color' => '#333333',
        'active_color' => '#0073aa',
        'border_color' => '#e0e0e0',
        'height' => 60,
        'border_radius' => 0,
        'box_shadow' => '0 -2px 8px rgba(0,0,0,0.1)'
    ),
    'animations' => array(
        'enabled' => true,
        'type' => 'bounce',
        'duration' => 300
    ),
    'badges' => array(
        'enabled' => true,
        'background_color' => '#ff4444',
        'text_color' => '#ffffff',
        'border_radius' => 50
    ),
    'devices' => array(
        'mobile' => array('enabled' => true, 'breakpoint' => 768),
        'tablet' => array('enabled' => true, 'breakpoint' => 1024),
        'desktop' => array('enabled' => false, 'breakpoint' => 1200)
    ),
    'display_rules' => array(
        'user_roles' => array(),
        'pages' => array(),
        'hide_on_admin' => true
    ),
    'advanced' => array(
        'z_index' => 9999,
        'fixed_position' => 'bottom',
        'custom_css' => ''
    ),
    'preset' => 'minimal'
);

$presets = wpbnp_get_presets();
$dashicons = wpbnp_get_dashicons();

// Mock WordPress roles
function wp_roles() {
    return (object) array(
        'get_names' => function() {
            return array(
                'administrator' => 'Administrator',
                'editor' => 'Editor',
                'author' => 'Author',
                'contributor' => 'Contributor',
                'subscriber' => 'Subscriber'
            );
        }
    );
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'items';
$tabs = array(
    'items' => 'Navigation Items',
    'styles' => 'Appearance',
    'devices' => 'Device Settings', 
    'animations' => 'Animations',
    'badges' => 'Badges',
    'display_rules' => 'Display Rules',
    'presets' => 'Design Presets',
    'advanced' => 'Advanced'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Bottom Navigation Pro - Admin Interface</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dashicons/0.9.0/dashicons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f1f1f1; }
        .wrap { max-width: 1200px; margin: 0 auto; }
        h1 { color: #23282d; margin-bottom: 20px; }
        .nav-tab-wrapper { border-bottom: 1px solid #ccc; margin-bottom: 20px; }
        .nav-tab { display: inline-block; padding: 8px 12px; margin: 0 3px -1px 0; text-decoration: none; border: 1px solid #ccc; background: #f1f1f1; color: #555; }
        .nav-tab-active { background: #fff; border-bottom-color: #fff; color: #000; }
        .wpbnp-checkbox-label { display: block; margin: 5px 0; }
        .description { font-style: italic; color: #666; font-size: 13px; }
        .button { display: inline-block; padding: 8px 12px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 3px; border: none; cursor: pointer; }
        .button-secondary { background: #f7f7f7; color: #555; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>WP Bottom Navigation Pro</h1>
        
        <div class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_key => $tab_label): ?>
                <a href="?tab=<?php echo $tab_key; ?>" 
                   class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <form id="wpbnp-settings-form" method="post">
            <div class="wpbnp-admin-container">
                <div class="wpbnp-admin-content">
                    <?php
                    // Render current tab content
                    switch ($current_tab) {
                        case 'items':
                            echo '<div class="wpbnp-section">
                                <h2>Navigation Items</h2>
                                <p>Configure up to 7 navigation items. Drag to reorder.</p>
                                
                                <div class="wpbnp-field">
                                    <label>
                                        <input type="checkbox" name="settings[enabled]" value="1" ' . checked($settings['enabled']) . '>
                                        Enable Bottom Navigation
                                    </label>
                                </div>
                                
                                <div id="wpbnp-items-list" class="wpbnp-sortable-list">
                                    <!-- Items will be populated by JavaScript -->
                                </div>
                                
                                <button type="button" id="wpbnp-add-item" class="button button-secondary">
                                    Add Item
                                </button>
                            </div>';
                            break;
                            
                        case 'presets':
                            echo '<div class="wpbnp-section">
                                <h2>Design Presets</h2>
                                <p>Choose a preset to quickly apply a design style.</p>
                                
                                <div class="wpbnp-preset-grid">';
                            foreach ($presets as $preset_key => $preset_data) {
                                $active = isset($settings['preset']) && $settings['preset'] === $preset_key ? 'active' : '';
                                echo '<div class="wpbnp-preset-card ' . $active . '" data-preset="' . esc_attr($preset_key) . '">
                                    <h4>' . esc_html($preset_data['name']) . '</h4>
                                    <p>' . esc_html($preset_data['description']) . '</p>
                                    <div class="wpbnp-preset-preview" style="background: ' . esc_attr($preset_data['style']['background_color']) . '; height: 30px; border-radius: 4px; margin: 10px 0;"></div>
                                    <button type="button" class="button wpbnp-apply-preset" data-preset="' . esc_attr($preset_key) . '">
                                        Apply Preset
                                    </button>
                                </div>';
                            }
                            echo '</div>
                                <input type="hidden" name="settings[preset]" value="' . esc_attr($settings['preset']) . '">
                            </div>';
                            break;
                            
                        default:
                            echo '<div class="wpbnp-section">
                                <h2>' . esc_html($tabs[$current_tab]) . '</h2>
                                <p>Tab content for ' . esc_html($current_tab) . ' will be implemented here.</p>
                            </div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="wpbnp-form-actions">
                <button type="submit" class="button wpbnp-save-settings">Save Settings</button>
                <button type="button" class="button button-secondary" id="wpbnp-export-settings">Export Settings</button>
                <button type="button" class="button button-secondary" id="wpbnp-import-settings">Import Settings</button>
            </div>
            
            <div id="wpbnp-notifications"></div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script>
        // Mock WordPress admin data
        window.wpbnp_admin = {
            settings: <?php echo json_encode($settings); ?>,
            presets: <?php echo json_encode($presets); ?>,
            dashicons: <?php echo json_encode($dashicons); ?>,
            nonce: 'test_nonce',
            ajax_url: 'test-ajax.php'
        };
        
        // Mock ajaxurl
        window.ajaxurl = 'test-ajax.php';
    </script>
    <script src="assets/js/admin.js"></script>
</body>
</html>