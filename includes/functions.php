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
 * Get available icon libraries and icons
 */
function wpbnp_get_icon_libraries() {
    return array(
        'dashicons' => array(
            'name' => __('Dashicons', 'wp-bottom-navigation-pro'),
            'description' => __('WordPress default icons', 'wp-bottom-navigation-pro'),
            'icons' => wpbnp_get_dashicons()
        ),
        'apple' => array(
            'name' => __('Apple SF Symbols', 'wp-bottom-navigation-pro'),
            'description' => __('iOS style system icons', 'wp-bottom-navigation-pro'),
            'icons' => wpbnp_get_apple_icons()
        ),
        'material' => array(
            'name' => __('Material Icons', 'wp-bottom-navigation-pro'),
            'description' => __('Google Material Design icons', 'wp-bottom-navigation-pro'),
            'icons' => wpbnp_get_material_icons()
        ),
        'custom' => array(
            'name' => __('Custom Icons', 'wp-bottom-navigation-pro'),
            'description' => __('Upload your own icons', 'wp-bottom-navigation-pro'),
            'icons' => array()
        )
    );
}

/**
 * Get available Dashicons
 */
function wpbnp_get_dashicons() {
    return array(
        // Navigation & Home
        'dashicons-admin-home' => __('Home', 'wp-bottom-navigation-pro'),
        'dashicons-dashboard' => __('Dashboard', 'wp-bottom-navigation-pro'),
        'dashicons-menu' => __('Menu', 'wp-bottom-navigation-pro'),
        'dashicons-menu-alt' => __('Menu Alt', 'wp-bottom-navigation-pro'),
        'dashicons-arrow-up-alt' => __('Arrow Up', 'wp-bottom-navigation-pro'),
        'dashicons-arrow-down-alt' => __('Arrow Down', 'wp-bottom-navigation-pro'),
        
        // E-commerce
        'dashicons-cart' => __('Cart', 'wp-bottom-navigation-pro'),
        'dashicons-products' => __('Products', 'wp-bottom-navigation-pro'),
        'dashicons-money' => __('Money', 'wp-bottom-navigation-pro'),
        'dashicons-tag' => __('Tag', 'wp-bottom-navigation-pro'),
        
        // Social & Communication
        'dashicons-admin-users' => __('Users', 'wp-bottom-navigation-pro'),
        'dashicons-groups' => __('Groups', 'wp-bottom-navigation-pro'),
        'dashicons-heart' => __('Heart', 'wp-bottom-navigation-pro'),
        'dashicons-phone' => __('Phone', 'wp-bottom-navigation-pro'),
        'dashicons-email' => __('Email', 'wp-bottom-navigation-pro'),
        'dashicons-share' => __('Share', 'wp-bottom-navigation-pro'),
        'dashicons-networking' => __('Network', 'wp-bottom-navigation-pro'),
        
        // Content & Media
        'dashicons-search' => __('Search', 'wp-bottom-navigation-pro'),
        'dashicons-camera' => __('Camera', 'wp-bottom-navigation-pro'),
        'dashicons-video-alt3' => __('Video', 'wp-bottom-navigation-pro'),
        'dashicons-format-gallery' => __('Gallery', 'wp-bottom-navigation-pro'),
        'dashicons-media-audio' => __('Audio', 'wp-bottom-navigation-pro'),
        'dashicons-download' => __('Download', 'wp-bottom-navigation-pro'),
        'dashicons-upload' => __('Upload', 'wp-bottom-navigation-pro'),
        
        // Information & Status
        'dashicons-info' => __('Info', 'wp-bottom-navigation-pro'),
        'dashicons-star-filled' => __('Star', 'wp-bottom-navigation-pro'),
        'dashicons-star-empty' => __('Star Empty', 'wp-bottom-navigation-pro'),
        'dashicons-flag' => __('Flag', 'wp-bottom-navigation-pro'),
        'dashicons-warning' => __('Warning', 'wp-bottom-navigation-pro'),
        
        // Time & Location
        'dashicons-calendar' => __('Calendar', 'wp-bottom-navigation-pro'),
        'dashicons-clock' => __('Clock', 'wp-bottom-navigation-pro'),
        'dashicons-location' => __('Location', 'wp-bottom-navigation-pro'),
        'dashicons-location-alt' => __('Location Alt', 'wp-bottom-navigation-pro'),
        
        // Settings & Tools
        'dashicons-admin-settings' => __('Settings', 'wp-bottom-navigation-pro'),
        'dashicons-admin-tools' => __('Tools', 'wp-bottom-navigation-pro'),
        'dashicons-admin-generic' => __('Generic', 'wp-bottom-navigation-pro'),
        'dashicons-filter' => __('Filter', 'wp-bottom-navigation-pro')
    );
}

/**
 * Get Apple SF Symbols style icons
 */
function wpbnp_get_apple_icons() {
    return array(
        // Navigation & Home
        'apple-house' => __('House', 'wp-bottom-navigation-pro'),
        'apple-house-fill' => __('House Fill', 'wp-bottom-navigation-pro'),
        'apple-square-grid-2x2' => __('Grid', 'wp-bottom-navigation-pro'),
        'apple-list-bullet' => __('List', 'wp-bottom-navigation-pro'),
        'apple-ellipsis' => __('More', 'wp-bottom-navigation-pro'),
        
        // E-commerce
        'apple-cart' => __('Cart', 'wp-bottom-navigation-pro'),
        'apple-cart-fill' => __('Cart Fill', 'wp-bottom-navigation-pro'),
        'apple-bag' => __('Bag', 'wp-bottom-navigation-pro'),
        'apple-bag-fill' => __('Bag Fill', 'wp-bottom-navigation-pro'),
        'apple-creditcard' => __('Credit Card', 'wp-bottom-navigation-pro'),
        
        // Social & Communication  
        'apple-person' => __('Person', 'wp-bottom-navigation-pro'),
        'apple-person-fill' => __('Person Fill', 'wp-bottom-navigation-pro'),
        'apple-person-2' => __('People', 'wp-bottom-navigation-pro'),
        'apple-person-2-fill' => __('People Fill', 'wp-bottom-navigation-pro'),
        'apple-heart' => __('Heart', 'wp-bottom-navigation-pro'),
        'apple-heart-fill' => __('Heart Fill', 'wp-bottom-navigation-pro'),
        'apple-message' => __('Message', 'wp-bottom-navigation-pro'),
        'apple-message-fill' => __('Message Fill', 'wp-bottom-navigation-pro'),
        'apple-phone' => __('Phone', 'wp-bottom-navigation-pro'),
        'apple-phone-fill' => __('Phone Fill', 'wp-bottom-navigation-pro'),
        'apple-envelope' => __('Mail', 'wp-bottom-navigation-pro'),
        'apple-envelope-fill' => __('Mail Fill', 'wp-bottom-navigation-pro'),
        
        // Content & Media
        'apple-magnifyingglass' => __('Search', 'wp-bottom-navigation-pro'),
        'apple-camera' => __('Camera', 'wp-bottom-navigation-pro'),
        'apple-camera-fill' => __('Camera Fill', 'wp-bottom-navigation-pro'),
        'apple-photo' => __('Photo', 'wp-bottom-navigation-pro'),
        'apple-photo-fill' => __('Photo Fill', 'wp-bottom-navigation-pro'),
        'apple-play' => __('Play', 'wp-bottom-navigation-pro'),
        'apple-play-fill' => __('Play Fill', 'wp-bottom-navigation-pro'),
        'apple-music-note' => __('Music', 'wp-bottom-navigation-pro'),
        
        // Information & Status
        'apple-info-circle' => __('Info', 'wp-bottom-navigation-pro'),
        'apple-info-circle-fill' => __('Info Fill', 'wp-bottom-navigation-pro'),
        'apple-star' => __('Star', 'wp-bottom-navigation-pro'),
        'apple-star-fill' => __('Star Fill', 'wp-bottom-navigation-pro'),
        'apple-bookmark' => __('Bookmark', 'wp-bottom-navigation-pro'),
        'apple-bookmark-fill' => __('Bookmark Fill', 'wp-bottom-navigation-pro'),
        
        // Time & Location
        'apple-calendar' => __('Calendar', 'wp-bottom-navigation-pro'),
        'apple-clock' => __('Clock', 'wp-bottom-navigation-pro'),
        'apple-clock-fill' => __('Clock Fill', 'wp-bottom-navigation-pro'),
        'apple-location' => __('Location', 'wp-bottom-navigation-pro'),
        'apple-location-fill' => __('Location Fill', 'wp-bottom-navigation-pro'),
        'apple-map' => __('Map', 'wp-bottom-navigation-pro'),
        'apple-map-fill' => __('Map Fill', 'wp-bottom-navigation-pro'),
        
        // Settings & Tools
        'apple-gearshape' => __('Settings', 'wp-bottom-navigation-pro'),
        'apple-gearshape-fill' => __('Settings Fill', 'wp-bottom-navigation-pro'),
        'apple-wrench' => __('Tools', 'wp-bottom-navigation-pro'),
        'apple-wrench-fill' => __('Tools Fill', 'wp-bottom-navigation-pro'),
        'apple-slider-horizontal-3' => __('Controls', 'wp-bottom-navigation-pro')
    );
}

/**
 * Get Material Design icons
 */
function wpbnp_get_material_icons() {
    return array(
        // Navigation & Home
        'material-home' => __('Home', 'wp-bottom-navigation-pro'),
        'material-dashboard' => __('Dashboard', 'wp-bottom-navigation-pro'),
        'material-menu' => __('Menu', 'wp-bottom-navigation-pro'),
        'material-more-vert' => __('More Vertical', 'wp-bottom-navigation-pro'),
        'material-more-horiz' => __('More Horizontal', 'wp-bottom-navigation-pro'),
        
        // E-commerce
        'material-shopping-cart' => __('Shopping Cart', 'wp-bottom-navigation-pro'),
        'material-shopping-bag' => __('Shopping Bag', 'wp-bottom-navigation-pro'),
        'material-store' => __('Store', 'wp-bottom-navigation-pro'),
        'material-payment' => __('Payment', 'wp-bottom-navigation-pro'),
        'material-local-offer' => __('Offer', 'wp-bottom-navigation-pro'),
        
        // Social & Communication
        'material-person' => __('Person', 'wp-bottom-navigation-pro'),
        'material-people' => __('People', 'wp-bottom-navigation-pro'),
        'material-favorite' => __('Favorite', 'wp-bottom-navigation-pro'),
        'material-favorite-border' => __('Favorite Border', 'wp-bottom-navigation-pro'),
        'material-message' => __('Message', 'wp-bottom-navigation-pro'),
        'material-phone' => __('Phone', 'wp-bottom-navigation-pro'),
        'material-email' => __('Email', 'wp-bottom-navigation-pro'),
        'material-share' => __('Share', 'wp-bottom-navigation-pro'),
        
        // Content & Media
        'material-search' => __('Search', 'wp-bottom-navigation-pro'),
        'material-camera-alt' => __('Camera', 'wp-bottom-navigation-pro'),
        'material-photo-library' => __('Photo Library', 'wp-bottom-navigation-pro'),
        'material-videocam' => __('Video Camera', 'wp-bottom-navigation-pro'),
        'material-music-note' => __('Music', 'wp-bottom-navigation-pro'),
        'material-play-arrow' => __('Play', 'wp-bottom-navigation-pro'),
        
        // Information & Status
        'material-info' => __('Info', 'wp-bottom-navigation-pro'),
        'material-star' => __('Star', 'wp-bottom-navigation-pro'),
        'material-star-border' => __('Star Border', 'wp-bottom-navigation-pro'),
        'material-bookmark' => __('Bookmark', 'wp-bottom-navigation-pro'),
        'material-bookmark-border' => __('Bookmark Border', 'wp-bottom-navigation-pro'),
        
        // Time & Location
        'material-event' => __('Event', 'wp-bottom-navigation-pro'),
        'material-schedule' => __('Schedule', 'wp-bottom-navigation-pro'),
        'material-location-on' => __('Location', 'wp-bottom-navigation-pro'),
        'material-map' => __('Map', 'wp-bottom-navigation-pro'),
        
        // Settings & Tools
        'material-settings' => __('Settings', 'wp-bottom-navigation-pro'),
        'material-build' => __('Build', 'wp-bottom-navigation-pro'),
        'material-tune' => __('Tune', 'wp-bottom-navigation-pro'),
        'material-filter-list' => __('Filter', 'wp-bottom-navigation-pro')
    );
}

/**
 * Get icons for a specific preset
 */
function wpbnp_get_preset_icons($preset) {
    $icon_sets = array(
        'minimal' => wpbnp_get_dashicons(),
        'dark' => wpbnp_get_dashicons(),
        'material' => wpbnp_get_material_icons(),
        'ios' => wpbnp_get_apple_icons(),
        'glassmorphism' => wpbnp_get_apple_icons(),
        'neumorphism' => wpbnp_get_apple_icons(),
        'cyberpunk' => wpbnp_get_material_icons(),
        'vintage' => wpbnp_get_dashicons(),
        'gradient' => wpbnp_get_apple_icons(),
        'floating' => wpbnp_get_apple_icons()
    );
    
    return $icon_sets[$preset] ?? wpbnp_get_dashicons();
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
