<?php
/**
 * Plugin Name: WP Bottom Navigation Pro
 * Plugin URI: https://wordpress.org/plugins/wp-bottom-navigation-pro
 * Description: A fully customizable, mobile-first bottom navigation bar with visual design presets, notification badges, animations, and role/device-based display rules.
 * Version: 1.0.0
 * Author: WP Bottom Navigation Pro
 * Text Domain: wp-bottom-navigation-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
// NOTE: When merging with pro branch, use semantic versioning (e.g., 1.2.0)
define('WPBNP_VERSION', '1.2.2'); // Fixed Custom Presets Display in Page Targeting
define('WPBNP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPBNP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPBNP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main WP Bottom Navigation Pro Class
 */
class WP_Bottom_Navigation_Pro {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Load essential files early so they're available during activation
        $this->load_essential_files();
        
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load essential files needed during activation
     */
    private function load_essential_files() {
        require_once WPBNP_PLUGIN_DIR . 'includes/functions.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wp-bottom-navigation-pro', false, dirname(WPBNP_PLUGIN_BASENAME) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // functions.php is already loaded in constructor
        require_once WPBNP_PLUGIN_DIR . 'includes/frontend.php';
        require_once WPBNP_PLUGIN_DIR . 'includes/shortcode.php';
        
        if (is_admin()) {
            require_once WPBNP_PLUGIN_DIR . 'admin/settings-ui.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Admin menu
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
        
        // AJAX handlers
        add_action('wp_ajax_wpbnp_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_wpbnp_reset_settings', array($this, 'reset_settings'));
        add_action('wp_ajax_wpbnp_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_wpbnp_import_settings', array($this, 'import_settings'));
        add_action('wp_ajax_wpbnp_get_cart_count', array($this, 'get_cart_count'));
        add_action('wp_ajax_nopriv_wpbnp_get_cart_count', array($this, 'get_cart_count'));
        
        // Pro feature AJAX handlers
        // NOTE: When merging with pro branch, ensure these don't conflict with existing handlers
        add_action('wp_ajax_wpbnp_activate_license', array($this, 'activate_license'));
        add_action('wp_ajax_wpbnp_deactivate_license', array($this, 'deactivate_license'));
        
        // Footer hook for navigation display
        add_action('wp_footer', array($this, 'display_navigation'), 999);
        
        // Debug hook for page targeting (add ?wpbnp_debug=1 to any URL)
        if (isset($_GET['wpbnp_debug']) && $_GET['wpbnp_debug'] == '1' && current_user_can('manage_options')) {
            add_action('wp_footer', function() {
                wpbnp_debug_page_targeting();
            }, 1000);
        }
        
        // Developer hooks
        do_action('wpbnp_init', $this);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_display_navigation()) {
            return;
        }
        
        // Enqueue FontAwesome
        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
            array(),
            '6.7.2'
        );
        
        // Enqueue Material Icons
        wp_enqueue_style(
            'material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            array(),
            WPBNP_VERSION
        );
        
        // Enqueue Bootstrap Icons
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
            array(),
            '1.10.0'
        );
        
        wp_enqueue_style(
            'wpbnp-frontend',
            WPBNP_PLUGIN_URL . 'assets/css/frontend.css',
            array('fontawesome', 'material-icons', 'bootstrap-icons'),
            WPBNP_VERSION
        );
        
        wp_enqueue_style(
            'wpbnp-icons',
            WPBNP_PLUGIN_URL . 'assets/css/icons.css',
            array('wpbnp-frontend'),
            WPBNP_VERSION
        );
        
        wp_enqueue_script(
            'wpbnp-frontend',
            WPBNP_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WPBNP_VERSION,
            true
        );
        
        wp_localize_script('wpbnp-frontend', 'wpbnp_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpbnp_nonce'),
            'settings' => wpbnp_get_settings()
        ));
        
        $this->add_dynamic_css();
    }
    
    /**
     * Add dynamic CSS inline to override static styles
     */
    private function add_dynamic_css() {
        $settings = wpbnp_get_settings();
        $style = $settings['style'];
        $animations = $settings['animations'];
        $preset = $settings['preset'] ?? 'minimal';
        
        $css = $this->generate_dynamic_css($settings);
        
        wp_add_inline_style('wpbnp-frontend', $css);
    }
    
    /**
     * Generate dynamic CSS based on settings
     */
    private function generate_dynamic_css($settings) {
        $style = $settings['style'];
        $devices = $settings['devices'];
        $advanced = $settings['advanced'];
        $animations = $settings['animations'];
        $preset = $settings['preset'] ?? 'minimal';
        
        $css = "
        /* Dynamic CSS - Higher Specificity */
        .wpbnp-bottom-nav {
            background-color: {$style['background_color']} !important;
            border-top-color: {$style['border_color']} !important;
            height: {$style['height']}px !important;
            padding: {$style['padding']}px !important;
            box-shadow: {$style['box_shadow']} !important;
            border-radius: {$style['border_radius']}px !important;
            z-index: {$advanced['z_index']} !important;
        }
        
        .wpbnp-nav-item {
            color: {$style['text_color']} !important;
            font-size: {$style['font_size']}px !important;
            font-weight: {$style['font_weight']} !important;
            padding: {$style['padding']}px !important;
        }
        
        .wpbnp-nav-item:hover {
            color: " . ($style['hover_color'] ?? $style['active_color']) . " !important;
        }
        
        .wpbnp-nav-item:hover .wpbnp-nav-icon,
        .wpbnp-nav-item:hover .wpbnp-nav-icon * {
            color: " . ($style['hover_color'] ?? $style['active_color']) . " !important;
        }
        
        .wpbnp-nav-item.active {
            color: {$style['active_color']} !important;
        }
        
        .wpbnp-nav-item.active .wpbnp-nav-icon,
        .wpbnp-nav-item.active .wpbnp-nav-icon * {
            color: {$style['active_color']} !important;
        }
        
        .wpbnp-nav-icon {
            font-size: {$style['icon_size']}px !important;
            width: " . ($style['icon_size'] + 4) . "px !important;
            height: " . ($style['icon_size'] + 4) . "px !important;
            color: " . ($style['icon_color'] ?? $style['text_color']) . " !important;
        }
        
        .wpbnp-nav-icon * {
            color: " . ($style['icon_color'] ?? $style['text_color']) . " !important;
        }
        
        .wpbnp-badge {
            background-color: {$settings['badges']['background_color']} !important;
            color: {$settings['badges']['text_color']} !important;
            border-radius: {$settings['badges']['border_radius']}% !important;
        }
        ";
        
        // Add animation CSS
        if ($animations['enabled'] && $animations['type'] !== 'none') {
            $css .= $this->generate_animation_css($animations, $style);
        }
        
        // Add preset-specific CSS
        $css .= $this->generate_preset_css($preset, $settings);
        
        // Add device-specific CSS
        $css .= $this->generate_device_css($devices);
        
        // Add custom CSS
        if (!empty($advanced['custom_css'])) {
            $css .= "\n/* Custom CSS */\n" . wp_strip_all_tags($advanced['custom_css']);
        }
        
        return $css;
    }
    
    /**
     * Generate animation CSS
     */
    private function generate_animation_css($animations, $style) {
        $type = $animations['type'];
        $duration = $animations['duration'];
        
        $css = "\n/* Animation CSS */\n";
        $css .= ".wpbnp-nav-item { transition-duration: {$duration}ms !important; }\n";
        
        switch ($type) {
            case 'bounce':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    animation: wpbnp-hover-bounce {$duration}ms ease !important;
                }
                .wpbnp-nav-item:active .wpbnp-nav-icon {
                    animation: wpbnp-click-bounce {$duration}ms ease !important;
                }
                @keyframes wpbnp-hover-bounce {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-8px); }
                    60% { transform: translateY(-4px); }
                }
                @keyframes wpbnp-click-bounce {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-10px); }
                    60% { transform: translateY(-5px); }
                }";
                break;
                
            case 'zoom':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    transform: scale(1.2) !important;
                    transition: transform {$duration}ms ease !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-zoom {$duration}ms ease !important;
                }
                @keyframes wpbnp-click-zoom {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.2); }
                    100% { transform: scale(1); }
                }";
                break;
                
            case 'pulse':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    animation: wpbnp-hover-pulse 1s infinite !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-pulse {$duration}ms ease !important;
                }
                @keyframes wpbnp-hover-pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                @keyframes wpbnp-click-pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.15); }
                    100% { transform: scale(1); }
                }";
                break;
                
            case 'fade':
                $css .= "
                .wpbnp-nav-item:hover {
                    opacity: 0.7 !important;
                    transition: opacity {$duration}ms ease !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-fade {$duration}ms ease !important;
                }
                @keyframes wpbnp-click-fade {
                    0% { opacity: 1; }
                    50% { opacity: 0.5; }
                    100% { opacity: 1; }
                }";
                break;
                
            case 'slide':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    transform: translateY(-5px) !important;
                    transition: transform {$duration}ms ease !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-slide {$duration}ms ease !important;
                }
                @keyframes wpbnp-click-slide {
                    0% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                    100% { transform: translateY(0); }
                }";
                break;
                
            case 'rotate':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    transform: rotate(8deg) scale(1.05) !important;
                    transition: transform {$duration}ms cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-rotate {$duration}ms cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
                }
                @keyframes wpbnp-click-rotate {
                    0% { transform: rotate(0deg) scale(1); }
                    25% { transform: rotate(90deg) scale(1.1); }
                    50% { transform: rotate(180deg) scale(1.05); }
                    75% { transform: rotate(270deg) scale(1.1); }
                    100% { transform: rotate(360deg) scale(1); }
                }
                /* Enhanced rotation for floating pill preset */
                .wpbnp-preset-floating .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    transform: rotate(12deg) scale(1.08) translateY(-2px) !important;
                    transition: transform {$duration}ms cubic-bezier(0.34, 1.56, 0.64, 1) !important;
                }
                .wpbnp-preset-floating .wpbnp-nav-item:active {
                    animation: wpbnp-floating-rotate {$duration}ms cubic-bezier(0.34, 1.56, 0.64, 1) !important;
                }
                @keyframes wpbnp-floating-rotate {
                    0% { transform: rotate(0deg) scale(1) translateY(0); }
                    20% { transform: rotate(72deg) scale(1.15) translateY(-3px); }
                    40% { transform: rotate(144deg) scale(1.1) translateY(-2px); }
                    60% { transform: rotate(216deg) scale(1.15) translateY(-3px); }
                    80% { transform: rotate(288deg) scale(1.05) translateY(-1px); }
                    100% { transform: rotate(360deg) scale(1) translateY(0); }
                }";
                break;
                
            case 'shake':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    animation: wpbnp-hover-shake 0.5s ease infinite !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-shake {$duration}ms ease !important;
                }
                @keyframes wpbnp-hover-shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-2px); }
                    75% { transform: translateX(2px); }
                }
                @keyframes wpbnp-click-shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                    20%, 40%, 60%, 80% { transform: translateX(5px); }
                }";
                break;
                
            case 'heartbeat':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    animation: wpbnp-hover-heartbeat 1s ease infinite !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-heartbeat " . ($duration * 2) . "ms ease !important;
                }
                @keyframes wpbnp-hover-heartbeat {
                    0% { transform: scale(1); }
                    14% { transform: scale(1.1); }
                    28% { transform: scale(1); }
                    42% { transform: scale(1.1); }
                    70% { transform: scale(1); }
                }
                @keyframes wpbnp-click-heartbeat {
                    0% { transform: scale(1); }
                    14% { transform: scale(1.2); }
                    28% { transform: scale(1); }
                    42% { transform: scale(1.2); }
                    70% { transform: scale(1); }
                }";
                break;
                
            case 'swing':
                $css .= "
                .wpbnp-nav-item:hover .wpbnp-nav-icon {
                    animation: wpbnp-hover-swing {$duration}ms ease !important;
                    transform-origin: top center !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-swing {$duration}ms ease !important;
                    transform-origin: top center !important;
                }
                @keyframes wpbnp-hover-swing {
                    20% { transform: rotate3d(0, 0, 1, 10deg); }
                    40% { transform: rotate3d(0, 0, 1, -8deg); }
                    60% { transform: rotate3d(0, 0, 1, 4deg); }
                    80% { transform: rotate3d(0, 0, 1, -2deg); }
                    100% { transform: rotate3d(0, 0, 1, 0deg); }
                }
                @keyframes wpbnp-click-swing {
                    20% { transform: rotate3d(0, 0, 1, 15deg); }
                    40% { transform: rotate3d(0, 0, 1, -10deg); }
                    60% { transform: rotate3d(0, 0, 1, 5deg); }
                    80% { transform: rotate3d(0, 0, 1, -5deg); }
                    100% { transform: rotate3d(0, 0, 1, 0deg); }
                }";
                break;
                
            case 'ripple':
                $rgb = $this->hex_to_rgb($style['active_color']);
                $css .= "
                .wpbnp-nav-item:hover {
                    animation: wpbnp-hover-ripple {$duration}ms ease !important;
                }
                .wpbnp-nav-item:active {
                    animation: wpbnp-click-ripple {$duration}ms ease !important;
                }
                @keyframes wpbnp-hover-ripple {
                    0% { box-shadow: 0 0 0 0 rgba({$rgb}, 0.4); }
                    70% { box-shadow: 0 0 0 8px rgba({$rgb}, 0); }
                    100% { box-shadow: 0 0 0 0 rgba({$rgb}, 0); }
                }
                @keyframes wpbnp-click-ripple {
                    0% { box-shadow: 0 0 0 0 rgba({$rgb}, 0.7); }
                    70% { box-shadow: 0 0 0 10px rgba({$rgb}, 0); }
                    100% { box-shadow: 0 0 0 0 rgba({$rgb}, 0); }
                }";
                break;
        }
        
        return $css;
    }
    
    /**
     * Generate preset-specific CSS
     */
    private function generate_preset_css($preset, $settings) {
        $css = "\n/* Preset CSS: {$preset} */\n";
        $css .= ".wpbnp-bottom-nav { /* Preset: {$preset} */ }\n";
        
        // Add preset class to navigation
        $css .= ".wpbnp-bottom-nav.wpbnp-preset-{$preset} {\n";
        
        switch ($preset) {
            case 'minimal':
                $css .= "
                    /* Minimal preset enhancements */
                }
                .wpbnp-preset-minimal .wpbnp-nav-item {
                    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                }
                .wpbnp-preset-minimal .wpbnp-nav-item:hover {
                    transform: translateY(-1px) !important;
                }";
                break;
                
            case 'dark':
                $css .= "
                    /* Dark mode enhancements - Force dark background */
                    background-color: #1f2937 !important;
                    border-top: 1px solid rgba(55, 65, 81, 0.8) !important;
                    /* Ensure proper positioning */
                    position: fixed !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                }
                .wpbnp-preset-dark .wpbnp-nav-item {
                    color: #9ca3af !important;
                }
                .wpbnp-preset-dark .wpbnp-nav-item:hover {
                    background-color: rgba(75, 85, 99, 0.3) !important;
                    border-radius: 8px !important;
                    color: #60a5fa !important;
                }
                .wpbnp-preset-dark .wpbnp-nav-item.active {
                    background-color: rgba(96, 165, 250, 0.2) !important;
                    border-radius: 8px !important;
                    color: #60a5fa !important;
                }";
                break;
                
            case 'material':
                $css .= "
                    /* Material Design enhancements */
                    elevation: 8;
                }
                .wpbnp-preset-material .wpbnp-nav-item {
                    border-radius: 12px !important;
                    margin: 4px !important;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                }
                .wpbnp-preset-material .wpbnp-nav-item:hover {
                    background-color: rgba(33, 150, 243, 0.08) !important;
                    transform: translateY(-2px) !important;
                    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3) !important;
                }
                .wpbnp-preset-material .wpbnp-nav-item.active {
                    background-color: rgba(33, 150, 243, 0.12) !important;
                    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.4) !important;
                }";
                break;
                
            case 'ios':
                $css .= "
                    /* iOS native enhancements */
                    backdrop-filter: blur(20px) !important;
                    -webkit-backdrop-filter: blur(20px) !important;
                    background-color: rgba(242, 242, 247, 0.8) !important;
                }
                .wpbnp-preset-ios .wpbnp-nav-item {
                    transition: all 0.25s ease-out !important;
                    border-radius: 10px !important;
                    margin: 0 2px !important;
                }
                .wpbnp-preset-ios .wpbnp-nav-item:hover {
                    background-color: rgba(0, 122, 255, 0.1) !important;
                    transform: scale(1.05) !important;
                }
                .wpbnp-preset-ios .wpbnp-nav-item.active {
                    background-color: rgba(0, 122, 255, 0.15) !important;
                    transform: scale(1.02) !important;
                }";
                break;
                
            case 'glassmorphism':
                $css .= "
                    backdrop-filter: blur(16px) !important;
                    -webkit-backdrop-filter: blur(16px) !important;
                    border: 1px solid rgba(255,255,255,0.2) !important;
                    background-color: rgba(255,255,255,0.1) !important;
                    box-shadow: 0 -8px 32px rgba(0,0,0,0.1), 0 0 0 1px rgba(255,255,255,0.05) !important;
                }
                .wpbnp-preset-glassmorphism .wpbnp-nav-item {
                    backdrop-filter: blur(8px) !important;
                    border-radius: 12px !important;
                    border: 1px solid rgba(255,255,255,0.1) !important;
                    transition: all 0.3s ease !important;
                }
                .wpbnp-preset-glassmorphism .wpbnp-nav-item:hover {
                    background-color: rgba(139, 92, 246, 0.1) !important;
                    border-color: rgba(139, 92, 246, 0.2) !important;
                    transform: translateY(-3px) !important;
                    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15) !important;
                }";
                break;
                
            case 'neumorphism':
                $css .= "
                    background-color: #e0e5ec !important;
                    box-shadow: 
                        9px 9px 16px #a3b1c6, 
                        -9px -9px 16px #ffffff,
                        inset 0 0 0 1px rgba(255,255,255,0.8) !important;
                    border: none !important;
                }
                .wpbnp-preset-neumorphism .wpbnp-nav-item {
                    background: #e0e5ec !important;
                    border-radius: 14px !important;
                    box-shadow: 
                        5px 5px 10px #a3b1c6, 
                        -5px -5px 10px #ffffff !important;
                    border: none !important;
                    transition: all 0.3s ease !important;
                }
                .wpbnp-preset-neumorphism .wpbnp-nav-item:hover {
                    box-shadow: 
                        inset 3px 3px 6px #a3b1c6, 
                        inset -3px -3px 6px #ffffff !important;
                    transform: translateY(1px) !important;
                }
                .wpbnp-preset-neumorphism .wpbnp-nav-item.active {
                    box-shadow: 
                        inset 5px 5px 10px #a3b1c6, 
                        inset -5px -5px 10px #ffffff !important;
                }";
                break;
                
            case 'cyberpunk':
                $css .= "
                    background: linear-gradient(135deg, #0d1421 0%, #1a1f3a 100%) !important;
                    border-top: 2px solid #00ff88 !important;
                    box-shadow: 
                        0 -4px 20px rgba(0,255,136,0.4), 
                        inset 0 1px 0 rgba(0,255,136,0.3) !important;
                    position: fixed !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                }
                .wpbnp-preset-cyberpunk:before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 1px;
                    background: linear-gradient(90deg, transparent, #00ff88, transparent);
                    animation: cyberpunk-scan 3s ease-in-out infinite;
                }
                @keyframes cyberpunk-scan {
                    0%, 100% { opacity: 0; }
                    50% { opacity: 1; }
                }
                .wpbnp-preset-cyberpunk .wpbnp-nav-item {
                    border: 1px solid rgba(0,255,136,0.3) !important;
                    border-radius: 4px !important;
                    transition: all 0.3s ease !important;
                    position: relative !important;
                    overflow: hidden !important;
                }
                .wpbnp-preset-cyberpunk .wpbnp-nav-item:hover {
                    border-color: #ff0080 !important;
                    box-shadow: 
                        0 0 15px rgba(255,0,128,0.5),
                        inset 0 0 15px rgba(255,0,128,0.1) !important;
                    transform: translateY(-2px) !important;
                }
                .wpbnp-preset-cyberpunk .wpbnp-nav-item:active {
                    animation: cyberpunk-glitch 0.3s ease !important;
                }
                @keyframes cyberpunk-glitch {
                    0%, 100% { transform: translateY(0); }
                    20% { transform: translateY(-2px) translateX(2px); }
                    40% { transform: translateY(2px) translateX(-2px); }
                    60% { transform: translateY(-1px) translateX(1px); }
                    80% { transform: translateY(1px) translateX(-1px); }
                }";
                break;
                
            case 'vintage':
                $css .= "
                    background: linear-gradient(135deg, #f5f1eb 0%, #e8dcc0 100%) !important;
                    border-top: 2px solid #d4c4a8 !important;
                    box-shadow: 
                        0 -3px 12px rgba(139,115,85,0.2),
                        inset 0 1px 0 rgba(255,255,255,0.8) !important;
                }
                .wpbnp-preset-vintage .wpbnp-nav-item {
                    border-radius: 6px !important;
                    transition: all 0.4s ease !important;
                    border: 1px solid rgba(139,115,85,0.2) !important;
                }
                .wpbnp-preset-vintage .wpbnp-nav-item:hover {
                    background: linear-gradient(135deg, rgba(210,105,30,0.1), rgba(139,115,85,0.1)) !important;
                    border-color: rgba(210,105,30,0.3) !important;
                    box-shadow: 0 2px 8px rgba(210,105,30,0.2) !important;
                    transform: translateY(-1px) !important;
                }";
                break;
                
            case 'gradient':
                $css .= "
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    border: none !important;
                    box-shadow: 
                        0 -4px 20px rgba(102,126,234,0.4),
                        0 0 0 1px rgba(255,255,255,0.1) !important;
                    position: fixed !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    overflow: hidden !important;
                }
                .wpbnp-preset-gradient:before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    animation: gradient-shine 3s ease-in-out infinite;
                }
                @keyframes gradient-shine {
                    0% { left: -100%; }
                    50% { left: 100%; }
                    100% { left: 100%; }
                }
                .wpbnp-preset-gradient .wpbnp-nav-item {
                    border-radius: 8px !important;
                    border: 1px solid rgba(255,255,255,0.1) !important;
                    transition: all 0.3s ease !important;
                }
                .wpbnp-preset-gradient .wpbnp-nav-item:hover {
                    background-color: rgba(255,215,0,0.2) !important;
                    border-color: rgba(255,215,0,0.3) !important;
                    transform: translateY(-2px) scale(1.05) !important;
                    box-shadow: 0 8px 20px rgba(255,215,0,0.3) !important;
                }";
                break;
                
            case 'floating':
                $css .= "
                    margin: 0 20px 20px 20px !important;
                    border-radius: 28px !important;
                    left: 20px !important;
                    right: 20px !important;
                    bottom: 20px !important;
                    box-shadow: 
                        0 -10px 30px rgba(0,0,0,0.15),
                        0 0 0 1px rgba(0,0,0,0.05),
                        inset 0 1px 0 rgba(255,255,255,0.8) !important;
                    border: none !important;
                }
                .wpbnp-preset-floating .wpbnp-nav-item {
                    border-radius: 20px !important;
                    margin: 4px !important;
                    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
                }
                .wpbnp-preset-floating .wpbnp-nav-item:hover {
                    background-color: rgba(16, 185, 129, 0.1) !important;
                    transform: translateY(-3px) scale(1.05) !important;
                    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3) !important;
                }
                .wpbnp-preset-floating .wpbnp-nav-item.active {
                    background-color: rgba(16, 185, 129, 0.15) !important;
                    transform: translateY(-1px) !important;
                }";
                break;
        }
        
        return $css;
    }
    
    /**
     * Generate device-specific CSS
     */
    private function generate_device_css($devices) {
        $css = "\n/* Device CSS */\n";
        
        if (!$devices['mobile']['enabled']) {
            $css .= "@media (max-width: {$devices['mobile']['breakpoint']}px) {
                .wpbnp-bottom-nav { display: none !important; }
            }\n";
        }
        
        if (!$devices['tablet']['enabled']) {
            $mobile_max = $devices['mobile']['breakpoint'] + 1;
            $css .= "@media (min-width: {$mobile_max}px) and (max-width: {$devices['tablet']['breakpoint']}px) {
                .wpbnp-bottom-nav { display: none !important; }
            }\n";
        }
        
        if (!$devices['desktop']['enabled']) {
            $desktop_min = $devices['desktop']['breakpoint'];
            $css .= "@media (min-width: {$desktop_min}px) {
                .wpbnp-bottom-nav { display: none !important; }
            }\n";
        }
        
        return $css;
    }
    
    /**
     * Helper to convert hex color to RGB for CSS rgba values
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);
        
        if ($length == 3) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "$r, $g, $b";
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'appearance_page_wp-bottom-navigation-pro') {
            return;
        }
        
        // Enqueue FontAwesome for admin with preload
        wp_enqueue_style(
            'fontawesome-admin',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
            array(),
            '6.7.2'
        );
        
        // Add preload hint for FontAwesome
        add_action('admin_head', function() {
            echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
        });
        
        // Enqueue Material Icons for admin
        wp_enqueue_style(
            'material-icons-admin',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            array(),
            WPBNP_VERSION
        );
        
        // Enqueue Bootstrap Icons for admin
        wp_enqueue_style(
            'bootstrap-icons-admin',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
            array(),
            '1.10.0'
        );

        // Enqueue dashicons for admin (still needed for other WordPress admin elements)
        wp_enqueue_style('dashicons');
        
        wp_enqueue_style(
            'wpbnp-admin',
            WPBNP_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker', 'dashicons', 'fontawesome-admin', 'material-icons-admin', 'bootstrap-icons-admin'),
            WPBNP_VERSION
        );
        
        wp_enqueue_style(
            'wpbnp-icons-admin',
            WPBNP_PLUGIN_URL . 'assets/css/icons.css',
            array('wpbnp-admin'),
            WPBNP_VERSION
        );
        
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_script(
            'wpbnp-admin',
            WPBNP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            WPBNP_VERSION,
            true
        );
        
        // Get icon library data for JavaScript
        $icon_libraries = array(
            'dashicons' => wpbnp_get_dashicons(),
            'fontawesome' => wpbnp_get_fontawesome_icons(),
            'feather' => wpbnp_get_feather_icons(),
            'material' => wpbnp_get_material_icons(),
            'bootstrap' => wpbnp_get_bootstrap_icons(),
            'apple' => wpbnp_get_apple_icons()
        );
        
        wp_localize_script('wpbnp-admin', 'wpbnp_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpbnp_admin_nonce'),
            'strings' => array(
                'confirm_reset' => __('Are you sure you want to reset all settings?', 'wp-bottom-navigation-pro'),
                'confirm_delete_item' => __('Are you sure you want to delete this item?', 'wp-bottom-navigation-pro'),
                'import_success' => __('Settings imported successfully!', 'wp-bottom-navigation-pro'),
                'export_success' => __('Settings exported successfully!', 'wp-bottom-navigation-pro'),
                'reset_success' => __('Settings reset successfully!', 'wp-bottom-navigation-pro'),
                'save_success' => __('Settings saved successfully!', 'wp-bottom-navigation-pro'),
                'error_occurred' => __('An error occurred. Please try again.', 'wp-bottom-navigation-pro')
            ),
            'icon_libraries' => $icon_libraries,
            'presets' => wpbnp_get_presets(),
            'settings' => wpbnp_get_settings()
        ));
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_theme_page(
            __('Bottom Navigation', 'wp-bottom-navigation-pro'),
            __('Bottom Navigation', 'wp-bottom-navigation-pro'),
            'manage_options',
            'wp-bottom-navigation-pro',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        $admin_ui = new WPBNP_Admin_UI();
        $admin_ui->render();
    }
    
    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        check_ajax_referer('wpbnp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-bottom-navigation-pro'));
        }
        
        $settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        $sanitized_settings = wpbnp_sanitize_settings($settings);
        
        update_option('wpbnp_settings', $sanitized_settings);
        
        // Get the complete updated settings
        $updated_settings = wpbnp_get_settings();
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'wp-bottom-navigation-pro'),
            'settings' => $updated_settings
        ));
    }
    
    /**
     * Reset settings via AJAX
     */
    public function reset_settings() {
        check_ajax_referer('wpbnp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-bottom-navigation-pro'));
        }
        
        delete_option('wpbnp_settings');
        
        wp_send_json_success(array(
            'message' => __('Settings reset to defaults!', 'wp-bottom-navigation-pro'),
            'settings' => wpbnp_get_default_settings()
        ));
    }
    
    /**
     * Export settings via AJAX
     */
    public function export_settings() {
        check_ajax_referer('wpbnp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-bottom-navigation-pro'));
        }
        
        $settings = wpbnp_get_settings();
        
        wp_send_json_success(array(
            'data' => wp_json_encode($settings, JSON_PRETTY_PRINT),
            'filename' => 'wpbnp-settings-' . date('Y-m-d-H-i-s') . '.json'
        ));
    }
    
    /**
     * Import settings via AJAX
     */
    public function import_settings() {
        check_ajax_referer('wpbnp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-bottom-navigation-pro'));
        }
        
        $import_data = isset($_POST['import_data']) ? wp_unslash($_POST['import_data']) : '';
        $settings = json_decode($import_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array(
                'message' => __('Invalid JSON data', 'wp-bottom-navigation-pro')
            ));
        }
        
        $sanitized_settings = wpbnp_sanitize_settings($settings);
        update_option('wpbnp_settings', $sanitized_settings);
        
        wp_send_json_success(array(
            'message' => __('Settings imported successfully!', 'wp-bottom-navigation-pro'),
            'settings' => $sanitized_settings
        ));
    }
    
    /**
     * Display navigation in footer
     */
    public function display_navigation() {
        if ($this->should_display_navigation()) {
            $frontend = new WPBNP_Frontend();
            $frontend->render_navigation();
        }
    }
    
    /**
     * Check if navigation should be displayed
     */
    private function should_display_navigation() {
        $settings = wpbnp_get_settings();
        
        // Check if plugin is enabled
        if (empty($settings['enabled'])) {
            return false;
        }
        
        // Check display rules
        $display_rules = $settings['display_rules'];
        
        // Check user roles
        if (!empty($display_rules['user_roles'])) {
            $user = wp_get_current_user();
            $user_roles = $user->roles;
            
            if (empty(array_intersect($user_roles, $display_rules['user_roles']))) {
                return false;
            }
        }
        
        // Check pages
        if (!empty($display_rules['pages'])) {
            $current_page = get_queried_object_id();
            if (!in_array($current_page, $display_rules['pages'])) {
                return false;
            }
        }
        
        // Check devices (handled by CSS)
        
        return apply_filters('wpbnp_should_display_navigation', true);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        if (!get_option('wpbnp_settings')) {
            update_option('wpbnp_settings', wpbnp_get_default_settings());
        }
        
        // Create capabilities if needed
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_bottom_navigation');
        }
        
        do_action('wpbnp_activate');
    }
    
    /**
     * Get cart count via AJAX
     */
    public function get_cart_count() {
        check_ajax_referer('wpbnp_nonce', 'nonce');
        
        $count = wpbnp_get_cart_count();
        
        wp_send_json_success($count);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        do_action('wpbnp_deactivate');
    }
    
    /**
     * Activate pro license
     */
    public function activate_license() {
        // Log that the function is being called
        error_log('WPBNP: License activation function called');
        
        // Verify nonce
        if (!check_ajax_referer('wpbnp_admin_nonce', 'nonce', false)) {
            error_log('WPBNP: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            error_log('WPBNP: User capabilities check failed');
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $license_key = sanitize_text_field(wp_unslash($_POST['license_key'] ?? ''));
        error_log('WPBNP: License key received: ' . $license_key);
        
        if (empty($license_key)) {
            error_log('WPBNP: Empty license key');
            wp_send_json_error(array('message' => 'License key is required'));
            return;
        }
        
        // For demo purposes, we'll accept any non-empty license key
        // In a real implementation, you would validate against your license server
        $is_valid = $this->validate_license_key($license_key);
        error_log('WPBNP: License validation result: ' . ($is_valid ? 'valid' : 'invalid'));
        
        if ($is_valid) {
            update_option('wpbnp_pro_license_key', $license_key);
            update_option('wpbnp_pro_license_status', 'active');
            update_option('wpbnp_pro_license_activated_at', current_time('timestamp'));
            
            // Enable page targeting by default when license is activated
            $settings = wpbnp_get_settings();
            $settings['page_targeting']['enabled'] = true;
            update_option('wpbnp_settings', $settings);
            
            error_log('WPBNP: License activated successfully');
            wp_send_json_success(array(
                'message' => 'License activated successfully!',
                'license_key' => $license_key,
                'debug' => 'License activation completed'
            ));
        } else {
            error_log('WPBNP: License validation failed');
            wp_send_json_error(array('message' => 'Invalid license key. Please use a key with at least 10 characters containing both letters and numbers.'));
        }
    }
    
    /**
     * Deactivate pro license
     */
    public function deactivate_license() {
        // Verify nonce
        if (!check_ajax_referer('wpbnp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        delete_option('wpbnp_pro_license_key');
        delete_option('wpbnp_pro_license_status');
        delete_option('wpbnp_pro_license_activated_at');
        
        wp_send_json_success(array('message' => 'License deactivated successfully!'));
    }
    
    /**
     * Validate license key (demo implementation)
     */
    private function validate_license_key($license_key) {
        // Demo validation - accept any key that:
        // 1. Is at least 10 characters long
        // 2. Contains both letters and numbers
        // 3. Doesn't contain spaces
        
        if (strlen($license_key) < 10) {
            return false;
        }
        
        if (strpos($license_key, ' ') !== false) {
            return false;
        }
        
        $has_letter = preg_match('/[a-zA-Z]/', $license_key);
        $has_number = preg_match('/[0-9]/', $license_key);
        
        return $has_letter && $has_number;
        
        // In a real implementation, you would make an API call to your license server:
        /*
        $response = wp_remote_post('https://your-license-server.com/api/validate', array(
            'body' => array(
                'license_key' => $license_key,
                'domain' => home_url(),
                'product' => 'wp-bottom-navigation-pro'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return isset($data['valid']) && $data['valid'] === true;
        */
    }
}

// Initialize plugin
WP_Bottom_Navigation_Pro::get_instance();
