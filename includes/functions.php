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
            'text_color' => '#666666',
            'active_color' => '#0073aa',
            'hover_color' => '#0085ba',
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
            'text_color' => sanitize_hex_color($settings['style']['text_color'] ?? '#666666'),
            'active_color' => sanitize_hex_color($settings['style']['active_color'] ?? '#0073aa'),
            'hover_color' => sanitize_hex_color($settings['style']['hover_color'] ?? '#0085ba'),
            'border_color' => sanitize_hex_color($settings['style']['border_color'] ?? '#e0e0e0'),
            'height' => absint($settings['style']['height'] ?? 60),
            'border_radius' => absint($settings['style']['border_radius'] ?? 0),
            'box_shadow' => sanitize_text_field($settings['style']['box_shadow'] ?? '0 -2px 8px rgba(0,0,0,0.1)'),
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
 * Get available icon libraries
 */
function wpbnp_get_icon_libraries() {
    return array(
        'dashicons' => array(
            'name' => 'Dashicons',
            'description' => 'WordPress native icons',
            'class_prefix' => 'dashicons',
            'type' => 'font'
        ),
        'fontawesome' => array(
            'name' => 'FontAwesome',
            'description' => 'Most popular icon library',
            'class_prefix' => 'fas fa-',
            'type' => 'font',
            'cdn' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
        ),
        'feather' => array(
            'name' => 'Feather Icons',
            'description' => 'Simple and beautiful icons',
            'class_prefix' => 'feather-',
            'type' => 'svg'
        ),
        'material' => array(
            'name' => 'Material Icons',
            'description' => 'Google Material Design',
            'class_prefix' => 'material-icons',
            'type' => 'font',
            'cdn' => 'https://fonts.googleapis.com/icon?family=Material+Icons'
        ),
        'bootstrap' => array(
            'name' => 'Bootstrap Icons',
            'description' => 'Bootstrap icon library',
            'class_prefix' => 'bi bi-',
            'type' => 'font',
            'cdn' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css'
        ),
        'apple' => array(
            'name' => 'Apple SF Symbols',
            'description' => 'iOS-style icons',
            'class_prefix' => 'apple-',
            'type' => 'custom'
        )
    );
}

/**
 * Get available Dashicons
 */
function wpbnp_get_dashicons() {
    return array(
        // Navigation & Home
        'dashicons-admin-home' => 'Home',
        'dashicons-dashboard' => 'Dashboard',
        'dashicons-menu' => 'Menu',
        'dashicons-menu-alt' => 'Menu Alt',
        'dashicons-arrow-up-alt' => 'Arrow Up',
        'dashicons-arrow-down-alt' => 'Arrow Down',
        
        // E-commerce
        'dashicons-cart' => 'Cart',
        'dashicons-products' => 'Products',
        'dashicons-money' => 'Money',
        'dashicons-tag' => 'Tag',
        
        // Social & Communication
        'dashicons-admin-users' => 'Users',
        'dashicons-groups' => 'Groups',
        'dashicons-heart' => 'Heart',
        'dashicons-phone' => 'Phone',
        'dashicons-email' => 'Email',
        'dashicons-share' => 'Share',
        'dashicons-networking' => 'Network',
        
        // Content & Media
        'dashicons-search' => 'Search',
        'dashicons-camera' => 'Camera',
        'dashicons-video-alt3' => 'Video',
        'dashicons-format-gallery' => 'Gallery',
        'dashicons-media-audio' => 'Audio',
        'dashicons-download' => 'Download',
        'dashicons-upload' => 'Upload',
        
        // Information & Status
        'dashicons-info' => 'Info',
        'dashicons-star-filled' => 'Star',
        'dashicons-star-empty' => 'Star Empty',
        'dashicons-flag' => 'Flag',
        'dashicons-warning' => 'Warning',
        
        // Time & Location
        'dashicons-calendar' => 'Calendar',
        'dashicons-clock' => 'Clock',
        'dashicons-location' => 'Location',
        'dashicons-location-alt' => 'Location Alt',
        
        // Settings & Tools
        'dashicons-admin-settings' => 'Settings',
        'dashicons-admin-tools' => 'Tools',
        'dashicons-admin-generic' => 'Generic',
        'dashicons-filter' => 'Filter'
    );
}

/**
 * Get FontAwesome icons
 */
function wpbnp_get_fontawesome_icons() {
    return array(
        // Navigation & Home
        'home' => 'Home',
        'house' => 'House',
        'dashboard' => 'Dashboard',
        'menu' => 'Menu',
        'bars' => 'Menu Bars',
        'ellipsis' => 'More Options',
        'grid' => 'Grid',
        'th' => 'Grid View',
        'list' => 'List',
        
        // E-commerce
        'shopping-cart' => 'Shopping Cart',
        'cart-shopping' => 'Shopping Cart',
        'bag-shopping' => 'Shopping Bag',
        'store' => 'Store',
        'credit-card' => 'Credit Card',
        'money-bill' => 'Money',
        'tag' => 'Price Tag',
        'percent' => 'Discount',
        
        // Social & Communication
        'user' => 'User',
        'users' => 'Users',
        'user-circle' => 'User Profile',
        'heart' => 'Heart',
        'thumbs-up' => 'Like',
        'message' => 'Message',
        'comment' => 'Comment',
        'phone' => 'Phone',
        'envelope' => 'Email',
        'share' => 'Share',
        
        // Content & Media
        'search' => 'Search',
        'camera' => 'Camera',
        'image' => 'Image',
        'video' => 'Video',
        'music' => 'Music',
        'play' => 'Play',
        'pause' => 'Pause',
        'download' => 'Download',
        'upload' => 'Upload',
        'file' => 'File',
        
        // Information & Status
        'info' => 'Info',
        'info-circle' => 'Info Circle',
        'star' => 'Star',
        'bookmark' => 'Bookmark',
        'bell' => 'Notification',
        'flag' => 'Flag',
        'check' => 'Check',
        'times' => 'Close',
        'plus' => 'Add',
        'minus' => 'Remove',
        
        // Time & Location
        'calendar' => 'Calendar',
        'clock' => 'Clock',
        'map-marker' => 'Location',
        'map' => 'Map',
        'globe' => 'Globe',
        
        // Settings & Tools
        'cog' => 'Settings',
        'gear' => 'Gear',
        'wrench' => 'Tools',
        'sliders' => 'Controls',
        'filter' => 'Filter',
        'sort' => 'Sort',
        'key' => 'Key',
        'lock' => 'Lock',
        'eye' => 'View',
        'edit' => 'Edit'
    );
}

/**
 * Get Feather icons (SVG-based)
 */
function wpbnp_get_feather_icons() {
    return array(
        'home' => 'Home',
        'menu' => 'Menu',
        'grid' => 'Grid',
        'shopping-cart' => 'Shopping Cart',
        'shopping-bag' => 'Shopping Bag',
        'user' => 'User',
        'users' => 'Users',
        'heart' => 'Heart',
        'message-circle' => 'Message',
        'phone' => 'Phone',
        'mail' => 'Email',
        'search' => 'Search',
        'camera' => 'Camera',
        'image' => 'Image',
        'video' => 'Video',
        'music' => 'Music',
        'play' => 'Play',
        'star' => 'Star',
        'bookmark' => 'Bookmark',
        'bell' => 'Bell',
        'calendar' => 'Calendar',
        'clock' => 'Clock',
        'map-pin' => 'Location',
        'settings' => 'Settings',
        'tool' => 'Tools'
    );
}

/**
 * Get Material icons
 */
function wpbnp_get_material_icons() {
    return array(
        'home' => 'Home',
        'dashboard' => 'Dashboard',
        'menu' => 'Menu',
        'shopping_cart' => 'Shopping Cart',
        'store' => 'Store',
        'person' => 'Person',
        'people' => 'People',
        'favorite' => 'Favorite',
        'message' => 'Message',
        'phone' => 'Phone',
        'email' => 'Email',
        'search' => 'Search',
        'camera_alt' => 'Camera',
        'image' => 'Image',
        'video_library' => 'Video',
        'music_note' => 'Music',
        'play_arrow' => 'Play',
        'star' => 'Star',
        'bookmark' => 'Bookmark',
        'notifications' => 'Notifications',
        'event' => 'Calendar',
        'schedule' => 'Clock',
        'location_on' => 'Location',
        'settings' => 'Settings',
        'build' => 'Tools'
    );
}

/**
 * Get Bootstrap icons
 */
function wpbnp_get_bootstrap_icons() {
    return array(
        'house' => 'House',
        'house-fill' => 'House Fill',
        'grid' => 'Grid',
        'list' => 'List',
        'cart' => 'Cart',
        'bag' => 'Bag',
        'person' => 'Person',
        'people' => 'People',
        'heart' => 'Heart',
        'heart-fill' => 'Heart Fill',
        'chat' => 'Chat',
        'telephone' => 'Phone',
        'envelope' => 'Email',
        'search' => 'Search',
        'camera' => 'Camera',
        'image' => 'Image',
        'play' => 'Play',
        'star' => 'Star',
        'star-fill' => 'Star Fill',
        'bookmark' => 'Bookmark',
        'bell' => 'Bell',
        'calendar' => 'Calendar',
        'clock' => 'Clock',
        'geo-alt' => 'Location',
        'gear' => 'Settings',
        'tools' => 'Tools'
    );
}

/**
 * Get Apple SF Symbols (using Unicode and custom styling)
 */
function wpbnp_get_apple_icons() {
    return array(
        'house' => 'House',
        'house-fill' => 'House Fill',
        'cart' => 'Cart',
        'cart-fill' => 'Cart Fill',
        'person' => 'Person',
        'person-fill' => 'Person Fill',
        'heart' => 'Heart',
        'heart-fill' => 'Heart Fill',
        'message' => 'Message',
        'phone' => 'Phone',
        'envelope' => 'Mail',
        'magnifyingglass' => 'Search',
        'camera' => 'Camera',
        'gearshape' => 'Settings',
        'star' => 'Star',
        'star-fill' => 'Star Fill',
        'list-bullet' => 'Menu',
        'square-grid-2x2' => 'Grid'
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
