<?php
/**
 * Core functions for WP Bottom Navigation Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin settings
 */
function wpbnp_get_settings() {
    $defaults = wpbnp_get_default_settings();
    $settings = get_option('wpbnp_settings', $defaults);
    
    return wp_parse_args($settings, $defaults);
}

/**
 * Get default settings
 */
function wpbnp_get_default_settings() {
    return apply_filters('wpbnp_default_settings', array(
        'enabled' => true,
        'preset' => 'minimal',
        'items' => array(
            array(
                'id' => 'home',
                'icon' => 'dashicons-admin-home',
                'label' => __('Home', 'wp-bottom-navigation-pro'),
                'url' => home_url(),
                'enabled' => true,
                'roles' => array(),
                'badge_count' => 0
            ),
            array(
                'id' => 'shop',
                'icon' => 'dashicons-cart',
                'label' => __('Shop', 'wp-bottom-navigation-pro'),
                'url' => '#',
                'enabled' => true,
                'roles' => array(),
                'badge_count' => 0
            ),
            array(
                'id' => 'account',
                'icon' => 'dashicons-admin-users',
                'label' => __('Account', 'wp-bottom-navigation-pro'),
                'url' => '#',
                'enabled' => true,
                'roles' => array(),
                'badge_count' => 0
            )
        ),
        'style' => array(
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'active_color' => '#0073aa',
            'border_color' => '#e0e0e0',
            'height' => '60',
            'border_radius' => '0',
            'box_shadow' => '0 -2px 8px rgba(0,0,0,0.1)',
            'font_size' => '12',
            'font_weight' => '400',
            'icon_size' => '20',
            'padding' => '10'
        ),
        'animations' => array(
            'enabled' => true,
            'type' => 'bounce',
            'duration' => '300'
        ),
        'devices' => array(
            'mobile' => array(
                'enabled' => true,
                'breakpoint' => '768'
            ),
            'tablet' => array(
                'enabled' => true,
                'breakpoint' => '1024'
            ),
            'desktop' => array(
                'enabled' => false,
                'breakpoint' => '1025'
            )
        ),
        'display_rules' => array(
            'user_roles' => array(),
            'pages' => array(),
            'hide_on_admin' => true
        ),
        'badges' => array(
            'enabled' => true,
            'background_color' => '#ff4444',
            'text_color' => '#ffffff',
            'border_radius' => '50'
        ),
        'advanced' => array(
            'z_index' => '9999',
            'fixed_position' => 'bottom',
            'custom_css' => ''
        )
    ));
}

/**
 * Sanitize plugin settings
 */
function wpbnp_sanitize_settings($settings) {
    $sanitized = array();
    
    // Boolean values
    $sanitized['enabled'] = !empty($settings['enabled']);
    
    // String values
    $sanitized['preset'] = sanitize_text_field($settings['preset'] ?? 'minimal');
    
    // Items array
    if (isset($settings['items']) && is_array($settings['items'])) {
        $sanitized['items'] = array();
        foreach ($settings['items'] as $item) {
            $sanitized_item = array(
                'id' => sanitize_key($item['id'] ?? ''),
                'icon' => sanitize_text_field($item['icon'] ?? ''),
                'label' => sanitize_text_field($item['label'] ?? ''),
                'url' => esc_url_raw($item['url'] ?? ''),
                'enabled' => !empty($item['enabled']),
                'roles' => array_map('sanitize_text_field', (array) ($item['roles'] ?? array())),
                'badge_count' => absint($item['badge_count'] ?? 0)
            );
            $sanitized['items'][] = $sanitized_item;
        }
    }
    
    // Style settings
    if (isset($settings['style']) && is_array($settings['style'])) {
        $sanitized['style'] = array(
            'background_color' => sanitize_hex_color($settings['style']['background_color'] ?? '#ffffff'),
            'text_color' => sanitize_hex_color($settings['style']['text_color'] ?? '#333333'),
            'active_color' => sanitize_hex_color($settings['style']['active_color'] ?? '#0073aa'),
            'border_color' => sanitize_hex_color($settings['style']['border_color'] ?? '#e0e0e0'),
            'height' => absint($settings['style']['height'] ?? 60),
            'border_radius' => absint($settings['style']['border_radius'] ?? 0),
            'box_shadow' => sanitize_text_field($settings['style']['box_shadow'] ?? ''),
            'font_size' => absint($settings['style']['font_size'] ?? 12),
            'font_weight' => sanitize_text_field($settings['style']['font_weight'] ?? '400'),
            'icon_size' => absint($settings['style']['icon_size'] ?? 20),
            'padding' => absint($settings['style']['padding'] ?? 10)
        );
    }
    
    // Animation settings
    if (isset($settings['animations']) && is_array($settings['animations'])) {
        $sanitized['animations'] = array(
            'enabled' => !empty($settings['animations']['enabled']),
            'type' => sanitize_text_field($settings['animations']['type'] ?? 'bounce'),
            'duration' => absint($settings['animations']['duration'] ?? 300)
        );
    }
    
    // Device settings
    if (isset($settings['devices']) && is_array($settings['devices'])) {
        $sanitized['devices'] = array();
        foreach (array('mobile', 'tablet', 'desktop') as $device) {
            if (isset($settings['devices'][$device])) {
                $sanitized['devices'][$device] = array(
                    'enabled' => !empty($settings['devices'][$device]['enabled']),
                    'breakpoint' => absint($settings['devices'][$device]['breakpoint'] ?? 0)
                );
            }
        }
    }
    
    // Display rules
    if (isset($settings['display_rules']) && is_array($settings['display_rules'])) {
        $sanitized['display_rules'] = array(
            'user_roles' => array_map('sanitize_text_field', (array) ($settings['display_rules']['user_roles'] ?? array())),
            'pages' => array_map('absint', (array) ($settings['display_rules']['pages'] ?? array())),
            'hide_on_admin' => !empty($settings['display_rules']['hide_on_admin'])
        );
    }
    
    // Badge settings
    if (isset($settings['badges']) && is_array($settings['badges'])) {
        $sanitized['badges'] = array(
            'enabled' => !empty($settings['badges']['enabled']),
            'background_color' => sanitize_hex_color($settings['badges']['background_color'] ?? '#ff4444'),
            'text_color' => sanitize_hex_color($settings['badges']['text_color'] ?? '#ffffff'),
            'border_radius' => absint($settings['badges']['border_radius'] ?? 50)
        );
    }
    
    // Advanced settings
    if (isset($settings['advanced']) && is_array($settings['advanced'])) {
        $sanitized['advanced'] = array(
            'z_index' => absint($settings['advanced']['z_index'] ?? 9999),
            'fixed_position' => sanitize_text_field($settings['advanced']['fixed_position'] ?? 'bottom'),
            'custom_css' => wp_strip_all_tags($settings['advanced']['custom_css'] ?? '')
        );
    }
    
    return apply_filters('wpbnp_sanitize_settings', $sanitized, $settings);
}

/**
 * Get available presets
 */
function wpbnp_get_presets() {
    $presets_file = WPBNP_PLUGIN_DIR . 'presets/default-presets.json';
    
    if (file_exists($presets_file)) {
        $presets_json = file_get_contents($presets_file);
        $presets = json_decode($presets_json, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $presets;
        }
    }
    
    // Fallback presets
    return array(
        'minimal' => array(
            'name' => __('Minimal', 'wp-bottom-navigation-pro'),
            'description' => __('Clean and simple design', 'wp-bottom-navigation-pro'),
            'style' => array(
                'background_color' => '#ffffff',
                'text_color' => '#333333',
                'active_color' => '#0073aa',
                'border_color' => '#e0e0e0',
                'height' => 60,
                'border_radius' => 0,
                'box_shadow' => '0 -2px 8px rgba(0,0,0,0.1)'
            )
        ),
        'dark' => array(
            'name' => __('Dark Mode', 'wp-bottom-navigation-pro'),
            'description' => __('Dark theme for better night viewing', 'wp-bottom-navigation-pro'),
            'style' => array(
                'background_color' => '#1a1a1a',
                'text_color' => '#ffffff',
                'active_color' => '#4a9eff',
                'border_color' => '#333333',
                'height' => 60,
                'border_radius' => 0,
                'box_shadow' => '0 -2px 8px rgba(0,0,0,0.3)'
            )
        ),
        'material' => array(
            'name' => __('Material Design', 'wp-bottom-navigation-pro'),
            'description' => __('Google Material Design inspired', 'wp-bottom-navigation-pro'),
            'style' => array(
                'background_color' => '#f5f5f5',
                'text_color' => '#424242',
                'active_color' => '#2196f3',
                'border_color' => '#e0e0e0',
                'height' => 56,
                'border_radius' => 0,
                'box_shadow' => '0 -4px 8px rgba(0,0,0,0.12)'
            )
        ),
        'ios' => array(
            'name' => __('iOS Style', 'wp-bottom-navigation-pro'),
            'description' => __('Apple iOS inspired design', 'wp-bottom-navigation-pro'),
            'style' => array(
                'background_color' => '#f8f8f8',
                'text_color' => '#8e8e93',
                'active_color' => '#007aff',
                'border_color' => '#c6c6c8',
                'height' => 83,
                'border_radius' => 0,
                'box_shadow' => '0 -1px 0 rgba(0,0,0,0.1)'
            )
        )
    );
}

/**
 * Get available Dashicons
 */
function wpbnp_get_dashicons() {
    return array(
        'dashicons-admin-home' => __('Home', 'wp-bottom-navigation-pro'),
        'dashicons-cart' => __('Cart', 'wp-bottom-navigation-pro'),
        'dashicons-admin-users' => __('Users', 'wp-bottom-navigation-pro'),
        'dashicons-heart' => __('Heart', 'wp-bottom-navigation-pro'),
        'dashicons-search' => __('Search', 'wp-bottom-navigation-pro'),
        'dashicons-menu' => __('Menu', 'wp-bottom-navigation-pro'),
        'dashicons-phone' => __('Phone', 'wp-bottom-navigation-pro'),
        'dashicons-email' => __('Email', 'wp-bottom-navigation-pro'),
        'dashicons-location' => __('Location', 'wp-bottom-navigation-pro'),
        'dashicons-info' => __('Info', 'wp-bottom-navigation-pro'),
        'dashicons-star-filled' => __('Star', 'wp-bottom-navigation-pro'),
        'dashicons-share' => __('Share', 'wp-bottom-navigation-pro'),
        'dashicons-download' => __('Download', 'wp-bottom-navigation-pro'),
        'dashicons-upload' => __('Upload', 'wp-bottom-navigation-pro'),
        'dashicons-calendar' => __('Calendar', 'wp-bottom-navigation-pro'),
        'dashicons-clock' => __('Clock', 'wp-bottom-navigation-pro'),
        'dashicons-dashboard' => __('Dashboard', 'wp-bottom-navigation-pro'),
        'dashicons-admin-tools' => __('Tools', 'wp-bottom-navigation-pro'),
        'dashicons-admin-settings' => __('Settings', 'wp-bottom-navigation-pro'),
        'dashicons-camera' => __('Camera', 'wp-bottom-navigation-pro')
    );
}

/**
 * Get WooCommerce cart count
 */
function wpbnp_get_cart_count() {
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }
    
    return WC()->cart->get_cart_contents_count();
}

/**
 * Get notification badge count for item
 */
function wpbnp_get_badge_count($item_id) {
    $count = 0;
    
    switch ($item_id) {
        case 'cart':
        case 'shop':
            $count = wpbnp_get_cart_count();
            break;
        default:
            $count = apply_filters('wpbnp_badge_count', 0, $item_id);
            break;
    }
    
    return absint($count);
}

/**
 * Check if user can see navigation item
 */
function wpbnp_can_user_see_item($item) {
    // Check if item is enabled
    if (empty($item['enabled'])) {
        return false;
    }
    
    // Check user roles
    if (!empty($item['roles'])) {
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        if (empty(array_intersect($user_roles, $item['roles']))) {
            return false;
        }
    }
    
    return apply_filters('wpbnp_can_user_see_item', true, $item);
}
