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
define('WPBNP_VERSION', '1.0.0');
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
        
        // Footer hook for navigation display
        add_action('wp_footer', array($this, 'display_navigation'), 999);
        
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
        
        wp_enqueue_style(
            'wpbnp-frontend',
            WPBNP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WPBNP_VERSION
        );
        
        wp_enqueue_script(
            'wpbnp-frontend',
            WPBNP_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WPBNP_VERSION,
            true
        );
        
        // Localize script with settings
        wp_localize_script('wpbnp-frontend', 'wpbnp_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpbnp_nonce'),
            'settings' => wpbnp_get_settings()
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('appearance_page_wp-bottom-navigation-pro' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'wpbnp-admin',
            WPBNP_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            WPBNP_VERSION
        );
        
        wp_enqueue_script(
            'wpbnp-admin',
            WPBNP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            WPBNP_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('wpbnp-admin', 'wpbnp_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpbnp_admin_nonce'),
            'presets' => wpbnp_get_presets(),
            'dashicons' => wpbnp_get_dashicons(),
            'strings' => array(
                'saving' => __('Saving...', 'wp-bottom-navigation-pro'),
                'saved' => __('Settings saved!', 'wp-bottom-navigation-pro'),
                'error' => __('Error saving settings', 'wp-bottom-navigation-pro'),
                'confirm_reset' => __('Are you sure you want to reset all settings?', 'wp-bottom-navigation-pro')
            ),
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
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'wp-bottom-navigation-pro')
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
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        do_action('wpbnp_deactivate');
    }
}

// Initialize plugin
WP_Bottom_Navigation_Pro::get_instance();
