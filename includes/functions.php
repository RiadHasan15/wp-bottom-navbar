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
 * Get Dashicons data
 */
function wpbnp_get_dashicons() {
    return array(
        // Navigation & Home
        'dashicons-admin-home' => 'Home',
        'dashicons-admin-site' => 'Site',
        'dashicons-admin-site-alt' => 'Site Alt',
        'dashicons-admin-site-alt2' => 'Site Alt 2',
        'dashicons-admin-site-alt3' => 'Site Alt 3',
        
        // Users & People
        'dashicons-admin-users' => 'Users',
        'dashicons-admin-user' => 'User',
        'dashicons-buddicons-activity' => 'Activity',
        'dashicons-buddicons-buddypress-logo' => 'Community',
        'dashicons-buddicons-community' => 'Community Alt',
        'dashicons-buddicons-friends' => 'Friends',
        'dashicons-buddicons-groups' => 'Groups',
        'dashicons-buddicons-pm' => 'Messages',
        'dashicons-buddicons-replies' => 'Replies',
        'dashicons-buddicons-topics' => 'Topics',
        
        // Shopping & Commerce
        'dashicons-cart' => 'Cart',
        'dashicons-store' => 'Store',
        'dashicons-money' => 'Money',
        'dashicons-money-alt' => 'Money Alt',
        'dashicons-products' => 'Products',
        'dashicons-businesswoman' => 'Business',
        'dashicons-businessperson' => 'Business Person',
        
        // Content & Media
        'dashicons-admin-media' => 'Media',
        'dashicons-admin-page' => 'Page',
        'dashicons-admin-post' => 'Post',
        'dashicons-admin-comments' => 'Comments',
        'dashicons-format-image' => 'Image',
        'dashicons-format-gallery' => 'Gallery',
        'dashicons-format-video' => 'Video',
        'dashicons-format-audio' => 'Audio',
        'dashicons-format-chat' => 'Chat',
        'dashicons-camera' => 'Camera',
        'dashicons-camera-alt' => 'Camera Alt',
        'dashicons-images-alt' => 'Images',
        'dashicons-images-alt2' => 'Images Alt',
        'dashicons-video-alt' => 'Video Alt',
        'dashicons-video-alt2' => 'Video Alt 2',
        'dashicons-video-alt3' => 'Video Alt 3',
        
        // Communication
        'dashicons-email' => 'Email',
        'dashicons-email-alt' => 'Email Alt',
        'dashicons-email-alt2' => 'Email Alt 2',
        'dashicons-phone' => 'Phone',
        'dashicons-smartphone' => 'Smartphone',
        'dashicons-networking' => 'Networking',
        'dashicons-rss' => 'RSS',
        'dashicons-share' => 'Share',
        'dashicons-share-alt' => 'Share Alt',
        'dashicons-share-alt2' => 'Share Alt 2',
        
        // Navigation & Controls
        'dashicons-menu' => 'Menu',
        'dashicons-menu-alt' => 'Menu Alt',
        'dashicons-menu-alt2' => 'Menu Alt 2',
        'dashicons-menu-alt3' => 'Menu Alt 3',
        'dashicons-arrow-up' => 'Arrow Up',
        'dashicons-arrow-down' => 'Arrow Down',
        'dashicons-arrow-left' => 'Arrow Left',
        'dashicons-arrow-right' => 'Arrow Right',
        'dashicons-arrow-up-alt' => 'Arrow Up Alt',
        'dashicons-arrow-down-alt' => 'Arrow Down Alt',
        'dashicons-arrow-left-alt' => 'Arrow Left Alt',
        'dashicons-arrow-right-alt' => 'Arrow Right Alt',
        'dashicons-arrow-up-alt2' => 'Arrow Up Alt 2',
        'dashicons-arrow-down-alt2' => 'Arrow Down Alt 2',
        'dashicons-arrow-left-alt2' => 'Arrow Left Alt 2',
        'dashicons-arrow-right-alt2' => 'Arrow Right Alt 2',
        
        // Actions & Controls
        'dashicons-plus' => 'Plus',
        'dashicons-plus-alt' => 'Plus Alt',
        'dashicons-plus-alt2' => 'Plus Alt 2',
        'dashicons-minus' => 'Minus',
        'dashicons-dismiss' => 'Dismiss',
        'dashicons-yes' => 'Yes',
        'dashicons-yes-alt' => 'Yes Alt',
        'dashicons-no' => 'No',
        'dashicons-no-alt' => 'No Alt',
        'dashicons-editor-help' => 'Help',
        'dashicons-info' => 'Info',
        'dashicons-warning' => 'Warning',
        'dashicons-editor-removeformatting' => 'Clear',
        
        // Settings & Tools
        'dashicons-admin-settings' => 'Settings',
        'dashicons-admin-generic' => 'Settings Alt',
        'dashicons-admin-tools' => 'Tools',
        'dashicons-admin-network' => 'Network',
        'dashicons-admin-plugins' => 'Plugins',
        'dashicons-admin-appearance' => 'Appearance',
        'dashicons-admin-customizer' => 'Customizer',
        'dashicons-hammer' => 'Hammer',
        'dashicons-art' => 'Art',
        'dashicons-performance' => 'Performance',
        'dashicons-universal-access' => 'Accessibility',
        'dashicons-universal-access-alt' => 'Accessibility Alt',
        
        // Social & Favorites
        'dashicons-heart' => 'Heart',
        'dashicons-star-filled' => 'Star Filled',
        'dashicons-star-empty' => 'Star Empty',
        'dashicons-star-half' => 'Star Half',
        'dashicons-thumbs-up' => 'Thumbs Up',
        'dashicons-thumbs-down' => 'Thumbs Down',
        'dashicons-flag' => 'Flag',
        'dashicons-awards' => 'Awards',
        'dashicons-facebook' => 'Facebook',
        'dashicons-facebook-alt' => 'Facebook Alt',
        'dashicons-twitter' => 'Twitter',
        'dashicons-twitter-alt' => 'Twitter Alt',
        'dashicons-instagram' => 'Instagram',
        'dashicons-youtube' => 'YouTube',
        'dashicons-linkedin' => 'LinkedIn',
        'dashicons-pinterest' => 'Pinterest',
        'dashicons-google' => 'Google',
        'dashicons-whatsapp' => 'WhatsApp',
        
        // Location & Time
        'dashicons-location' => 'Location',
        'dashicons-location-alt' => 'Location Alt',
        'dashicons-building' => 'Building',
        'dashicons-car' => 'Car',
        'dashicons-clock' => 'Clock',
        'dashicons-calendar' => 'Calendar',
        'dashicons-calendar-alt' => 'Calendar Alt',
        'dashicons-schedule' => 'Schedule',
        
        // Search & Discovery
        'dashicons-search' => 'Search',
        'dashicons-filter' => 'Filter',
        'dashicons-sort' => 'Sort',
        'dashicons-randomize' => 'Random',
        'dashicons-list-view' => 'List View',
        'dashicons-grid-view' => 'Grid View',
        'dashicons-excerpt-view' => 'Excerpt View',
        
        // Files & Documents
        'dashicons-media-document' => 'Document',
        'dashicons-media-archive' => 'Archive',
        'dashicons-media-audio' => 'Audio File',
        'dashicons-media-code' => 'Code',
        'dashicons-media-default' => 'File',
        'dashicons-media-spreadsheet' => 'Spreadsheet',
        'dashicons-media-text' => 'Text',
        'dashicons-pdf' => 'PDF',
        'dashicons-book' => 'Book',
        'dashicons-book-alt' => 'Book Alt',
        
        // Security & Privacy
        'dashicons-lock' => 'Lock',
        'dashicons-unlock' => 'Unlock',
        'dashicons-privacy' => 'Privacy',
        'dashicons-hidden' => 'Hidden',
        'dashicons-visibility' => 'Visibility',
        'dashicons-shield' => 'Shield',
        'dashicons-shield-alt' => 'Shield Alt',
        
        // System & Technical
        'dashicons-update' => 'Update',
        'dashicons-update-alt' => 'Update Alt',
        'dashicons-backup' => 'Backup',
        'dashicons-migrate' => 'Migrate',
        'dashicons-download' => 'Download',
        'dashicons-upload' => 'Upload',
        'dashicons-cloud' => 'Cloud',
        'dashicons-database' => 'Database',
        'dashicons-database-add' => 'Database Add',
        'dashicons-database-export' => 'Database Export',
        'dashicons-database-import' => 'Database Import',
        'dashicons-database-remove' => 'Database Remove',
        'dashicons-database-view' => 'Database View',
        
        // Creative & Design
        'dashicons-editor-bold' => 'Bold',
        'dashicons-editor-italic' => 'Italic',
        'dashicons-editor-ul' => 'List',
        'dashicons-editor-ol' => 'Ordered List',
        'dashicons-editor-quote' => 'Quote',
        'dashicons-editor-alignleft' => 'Align Left',
        'dashicons-editor-aligncenter' => 'Align Center',
        'dashicons-editor-alignright' => 'Align Right',
        'dashicons-editor-insertmore' => 'Insert More',
        'dashicons-editor-spellcheck' => 'Spellcheck',
        'dashicons-editor-expand' => 'Expand',
        'dashicons-editor-contract' => 'Contract',
        'dashicons-editor-kitchensink' => 'Kitchen Sink',
        'dashicons-editor-underline' => 'Underline',
        'dashicons-editor-justify' => 'Justify',
        'dashicons-editor-textcolor' => 'Text Color',
        'dashicons-editor-paste-word' => 'Paste Word',
        'dashicons-editor-paste-text' => 'Paste Text',
        'dashicons-editor-removeformatting' => 'Remove Formatting',
        'dashicons-editor-video' => 'Video Editor',
        'dashicons-editor-customchar' => 'Custom Character',
        
        // Games & Entertainment
        'dashicons-games' => 'Games',
        'dashicons-screenoptions' => 'Screen Options',
        'dashicons-info-outline' => 'Info Outline',
        'dashicons-marker' => 'Marker',
        'dashicons-palmtree' => 'Palm Tree',
        'dashicons-tickets-alt' => 'Tickets',
        'dashicons-money-alt' => 'Currency',
        'dashicons-smiley' => 'Smiley',
        'dashicons-sos' => 'SOS',
        'dashicons-superhero' => 'Superhero',
        'dashicons-superhero-alt' => 'Superhero Alt'
    );
}

/**
 * Get FontAwesome icons data
 */
function wpbnp_get_fontawesome_icons() {
    return array(
        // Navigation & Home
        'home' => 'Home',
        'house' => 'House',
        'house-user' => 'House User',
        'house-flag' => 'House Flag',
        'compass' => 'Compass',
        'location-dot' => 'Location',
        'map' => 'Map',
        'map-location-dot' => 'Map Location',
        'route' => 'Route',
        
        // Users & People
        'user' => 'User',
        'users' => 'Users',
        'user-group' => 'User Group',
        'user-friends' => 'Friends',
        'user-circle' => 'User Circle',
        'user-check' => 'User Check',
        'user-plus' => 'Add User',
        'user-minus' => 'Remove User',
        'user-edit' => 'Edit User',
        'user-cog' => 'User Settings',
        'user-shield' => 'User Shield',
        'user-secret' => 'User Secret',
        'user-tie' => 'Business User',
        'user-graduate' => 'Graduate',
        'user-astronaut' => 'Astronaut',
        'user-ninja' => 'Ninja',
        'baby' => 'Baby',
        'child' => 'Child',
        'person' => 'Person',
        'people-group' => 'People Group',
        
        // Shopping & Commerce
        'shopping-cart' => 'Shopping Cart',
        'cart-plus' => 'Add to Cart',
        'cart-arrow-down' => 'Download Cart',
        'shopping-bag' => 'Shopping Bag',
        'shopping-basket' => 'Shopping Basket',
        'store' => 'Store',
        'cash-register' => 'Cash Register',
        'credit-card' => 'Credit Card',
        'money-bill' => 'Money Bill',
        'money-bill-wave' => 'Money Wave',
        'coins' => 'Coins',
        'wallet' => 'Wallet',
        'receipt' => 'Receipt',
        'tag' => 'Tag',
        'tags' => 'Tags',
        'barcode' => 'Barcode',
        'qrcode' => 'QR Code',
        'percent' => 'Percent',
        'dollar-sign' => 'Dollar',
        'euro-sign' => 'Euro',
        'pound-sign' => 'Pound',
        'yen-sign' => 'Yen',
        'bitcoin-sign' => 'Bitcoin',
        
        // Communication & Social
        'envelope' => 'Email',
        'envelope-open' => 'Open Email',
        'inbox' => 'Inbox',
        'paper-plane' => 'Send',
        'phone' => 'Phone',
        'phone-flip' => 'Phone Flip',
        'mobile' => 'Mobile',
        'fax' => 'Fax',
        'comment' => 'Comment',
        'comments' => 'Comments',
        'message' => 'Message',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'discord' => 'Discord',
        'slack' => 'Slack',
        'skype' => 'Skype',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'snapchat' => 'Snapchat',
        'pinterest' => 'Pinterest',
        'reddit' => 'Reddit',
        'tumblr' => 'Tumblr',
        'share' => 'Share',
        'share-nodes' => 'Share Network',
        'rss' => 'RSS',
        
        // Content & Media
        'image' => 'Image',
        'images' => 'Images',
        'camera' => 'Camera',
        'camera-retro' => 'Retro Camera',
        'video' => 'Video',
        'play' => 'Play',
        'pause' => 'Pause',
        'stop' => 'Stop',
        'forward' => 'Forward',
        'backward' => 'Backward',
        'step-forward' => 'Step Forward',
        'step-backward' => 'Step Backward',
        'eject' => 'Eject',
        'volume-high' => 'Volume High',
        'volume-low' => 'Volume Low',
        'volume-off' => 'Volume Off',
        'volume-mute' => 'Mute',
        'music' => 'Music',
        'headphones' => 'Headphones',
        'microphone' => 'Microphone',
        'podcast' => 'Podcast',
        'film' => 'Film',
        'photo-film' => 'Photo Film',
        'clapperboard' => 'Clapperboard',
        'tv' => 'TV',
        'display' => 'Display',
        'desktop' => 'Desktop',
        'laptop' => 'Laptop',
        'tablet' => 'Tablet',
        'mobile-screen' => 'Mobile Screen',
        
        // Navigation & Controls
        'bars' => 'Menu',
        'bars-staggered' => 'Menu Staggered',
        'ellipsis' => 'More',
        'ellipsis-vertical' => 'More Vertical',
        'arrow-up' => 'Arrow Up',
        'arrow-down' => 'Arrow Down',
        'arrow-left' => 'Arrow Left',
        'arrow-right' => 'Arrow Right',
        'arrow-up-long' => 'Long Arrow Up',
        'arrow-down-long' => 'Long Arrow Down',
        'arrow-left-long' => 'Long Arrow Left',
        'arrow-right-long' => 'Long Arrow Right',
        'chevron-up' => 'Chevron Up',
        'chevron-down' => 'Chevron Down',
        'chevron-left' => 'Chevron Left',
        'chevron-right' => 'Chevron Right',
        'angle-up' => 'Angle Up',
        'angle-down' => 'Angle Down',
        'angle-left' => 'Angle Left',
        'angle-right' => 'Angle Right',
        'caret-up' => 'Caret Up',
        'caret-down' => 'Caret Down',
        'caret-left' => 'Caret Left',
        'caret-right' => 'Caret Right',
        
        // Actions & Controls
        'plus' => 'Plus',
        'minus' => 'Minus',
        'xmark' => 'Close',
        'check' => 'Check',
        'check-circle' => 'Check Circle',
        'times-circle' => 'Times Circle',
        'exclamation' => 'Exclamation',
        'exclamation-circle' => 'Warning',
        'exclamation-triangle' => 'Warning Triangle',
        'question' => 'Question',
        'question-circle' => 'Question Circle',
        'info' => 'Info',
        'info-circle' => 'Info Circle',
        'bell' => 'Bell',
        'bell-slash' => 'Bell Off',
        'bookmark' => 'Bookmark',
        'heart' => 'Heart',
        'thumbs-up' => 'Thumbs Up',
        'thumbs-down' => 'Thumbs Down',
        'star' => 'Star',
        'flag' => 'Flag',
        'eye' => 'Eye',
        'eye-slash' => 'Eye Slash',
        'hand' => 'Hand',
        'peace' => 'Peace',
        
        // Search & Discovery
        'magnifying-glass' => 'Search',
        'filter' => 'Filter',
        'sort' => 'Sort',
        'sort-up' => 'Sort Up',
        'sort-down' => 'Sort Down',
        'sort-alpha-up' => 'Sort A-Z',
        'sort-alpha-down' => 'Sort Z-A',
        'sort-numeric-up' => 'Sort 1-9',
        'sort-numeric-down' => 'Sort 9-1',
        'list' => 'List',
        'list-ul' => 'Bullet List',
        'list-ol' => 'Numbered List',
        'th' => 'Grid',
        'th-large' => 'Large Grid',
        'th-list' => 'List View',
        'table' => 'Table',
        'border-all' => 'Table All',
        'grip-horizontal' => 'Grip Horizontal',
        'grip-vertical' => 'Grip Vertical',
        
        // Files & Documents
        'file' => 'File',
        'file-text' => 'Text File',
        'file-pdf' => 'PDF',
        'file-word' => 'Word',
        'file-excel' => 'Excel',
        'file-powerpoint' => 'PowerPoint',
        'file-image' => 'Image File',
        'file-video' => 'Video File',
        'file-audio' => 'Audio File',
        'file-archive' => 'Archive',
        'file-code' => 'Code File',
        'file-csv' => 'CSV',
        'folder' => 'Folder',
        'folder-open' => 'Open Folder',
        'folder-plus' => 'Add Folder',
        'folder-minus' => 'Remove Folder',
        'copy' => 'Copy',
        'paste' => 'Paste',
        'cut' => 'Cut',
        'scissors' => 'Scissors',
        'paperclip' => 'Attachment',
        'download' => 'Download',
        'upload' => 'Upload',
        'cloud' => 'Cloud',
        'cloud-arrow-up' => 'Cloud Upload',
        'cloud-arrow-down' => 'Cloud Download',
        
        // Settings & Tools
        'gear' => 'Settings',
        'cog' => 'Cog',
        'sliders' => 'Sliders',
        'wrench' => 'Wrench',
        'screwdriver' => 'Screwdriver',
        'hammer' => 'Hammer',
        'tools' => 'Tools',
        'toolbox' => 'Toolbox',
        'brush' => 'Brush',
        'palette' => 'Palette',
        'paint-roller' => 'Paint Roller',
        'spray-can' => 'Spray Can',
        'pen' => 'Pen',
        'pencil' => 'Pencil',
        'pen-to-square' => 'Edit',
        'marker' => 'Marker',
        'highlighter' => 'Highlighter',
        'eraser' => 'Eraser',
        'ruler' => 'Ruler',
        'compass-drafting' => 'Drafting Compass',
        
        // Security & Privacy
        'lock' => 'Lock',
        'unlock' => 'Unlock',
        'key' => 'Key',
        'shield' => 'Shield',
        'shield-halved' => 'Shield Half',
        'user-shield' => 'User Shield',
        'fingerprint' => 'Fingerprint',
        'mask' => 'Mask',
        'user-secret' => 'Secret User',
        'eye-slash' => 'Hide',
        'ban' => 'Ban',
        'circle-exclamation' => 'Alert',
        'triangle-exclamation' => 'Warning',
        'bug' => 'Bug',
        'virus' => 'Virus',
        
        // Time & Calendar
        'clock' => 'Clock',
        'stopwatch' => 'Stopwatch',
        'hourglass' => 'Hourglass',
        'calendar' => 'Calendar',
        'calendar-days' => 'Calendar Days',
        'calendar-week' => 'Calendar Week',
        'calendar-check' => 'Calendar Check',
        'calendar-plus' => 'Calendar Plus',
        'calendar-minus' => 'Calendar Minus',
        'history' => 'History',
        'rotate-left' => 'Undo',
        'rotate-right' => 'Redo',
        'clock-rotate-left' => 'History Clock',
        
        // Transportation
        'car' => 'Car',
        'taxi' => 'Taxi',
        'bus' => 'Bus',
        'truck' => 'Truck',
        'motorcycle' => 'Motorcycle',
        'bicycle' => 'Bicycle',
        'plane' => 'Plane',
        'helicopter' => 'Helicopter',
        'rocket' => 'Rocket',
        'ship' => 'Ship',
        'anchor' => 'Anchor',
        'train' => 'Train',
        'subway' => 'Subway',
        'gas-pump' => 'Gas Pump',
        'charging-station' => 'Charging Station',
        'road' => 'Road',
        'traffic-light' => 'Traffic Light',
        
        // Weather & Nature
        'sun' => 'Sun',
        'moon' => 'Moon',
        'cloud' => 'Cloud',
        'cloud-rain' => 'Rain',
        'cloud-snow' => 'Snow',
        'bolt' => 'Lightning',
        'rainbow' => 'Rainbow',
        'temperature-high' => 'Hot',
        'temperature-low' => 'Cold',
        'wind' => 'Wind',
        'tornado' => 'Tornado',
        'hurricane' => 'Hurricane',
        'tree' => 'Tree',
        'leaf' => 'Leaf',
        'seedling' => 'Seedling',
        'flower' => 'Flower',
        'mountain' => 'Mountain',
        'water' => 'Water',
        'fire' => 'Fire',
        'snowflake' => 'Snowflake',
        
        // Food & Dining
        'utensils' => 'Utensils',
        'plate-wheat' => 'Plate',
        'bowl-food' => 'Bowl',
        'cup-straw' => 'Drink',
        'mug-saucer' => 'Coffee',
        'wine-glass' => 'Wine',
        'beer-mug-empty' => 'Beer',
        'pizza-slice' => 'Pizza',
        'burger' => 'Burger',
        'hotdog' => 'Hotdog',
        'ice-cream' => 'Ice Cream',
        'candy-cane' => 'Candy',
        'apple-whole' => 'Apple',
        'carrot' => 'Carrot',
        'fish' => 'Fish',
        'drumstick-bite' => 'Chicken',
        'bread-slice' => 'Bread',
        'cheese' => 'Cheese',
        'egg' => 'Egg',
        
        // Health & Medical
        'heart-pulse' => 'Heart Rate',
        'stethoscope' => 'Stethoscope',
        'syringe' => 'Syringe',
        'pills' => 'Pills',
        'capsules' => 'Capsules',
        'bandage' => 'Bandage',
        'user-doctor' => 'Doctor',
        'user-nurse' => 'Nurse',
        'hospital' => 'Hospital',
        'ambulance' => 'Ambulance',
        'wheelchair' => 'Wheelchair',
        'crutch' => 'Crutch',
        'tooth' => 'Tooth',
        'brain' => 'Brain',
        'dna' => 'DNA',
        'virus' => 'Virus',
        'bacteria' => 'Bacteria',
        'microscope' => 'Microscope',
        
        // Sports & Activities
        'football' => 'Football',
        'basketball' => 'Basketball',
        'baseball' => 'Baseball',
        'volleyball' => 'Volleyball',
        'table-tennis-paddle-ball' => 'Ping Pong',
        'golf-ball-tee' => 'Golf',
        'bowling-ball' => 'Bowling',
        'dumbbell' => 'Dumbbell',
        'weight-hanging' => 'Weight',
        'running' => 'Running',
        'person-biking' => 'Biking',
        'person-swimming' => 'Swimming',
        'person-skiing' => 'Skiing',
        'trophy' => 'Trophy',
        'medal' => 'Medal',
        'award' => 'Award',
        'ribbon' => 'Ribbon',
        'target' => 'Target',
        'bullseye' => 'Bullseye',
        
        // Education & Learning
        'graduation-cap' => 'Graduation',
        'school' => 'School',
        'book' => 'Book',
        'book-open' => 'Open Book',
        'bookmark' => 'Bookmark',
        'pen-fancy' => 'Pen Fancy',
        'pencil-ruler' => 'Pencil Ruler',
        'calculator' => 'Calculator',
        'microscope' => 'Microscope',
        'flask' => 'Flask',
        'atom' => 'Atom',
        'dna' => 'DNA',
        'globe' => 'Globe',
        'language' => 'Language',
        'spell-check' => 'Spell Check',
        'chalkboard' => 'Chalkboard',
        'chalkboard-user' => 'Teacher',
        'apple-whole' => 'Apple',
        
        // Gaming & Entertainment
        'gamepad' => 'Gamepad',
        'chess' => 'Chess',
        'dice' => 'Dice',
        'puzzle-piece' => 'Puzzle',
        'cards-blank' => 'Cards',
        'mask' => 'Theater Mask',
        'music' => 'Music',
        'guitar' => 'Guitar',
        'drum' => 'Drum',
        'microphone' => 'Microphone',
        'headphones' => 'Headphones',
        'ticket' => 'Ticket',
        'film' => 'Film',
        'camera-movie' => 'Movie Camera',
        'popcorn' => 'Popcorn',
        'couch' => 'Couch',
        'tv' => 'TV',
        'radio' => 'Radio'
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
 * Get Bootstrap Icons data
 */
function wpbnp_get_bootstrap_icons() {
    return array(
        // Navigation & Home
        'house' => 'House',
        'house-door' => 'House Door',
        'house-fill' => 'House Fill',
        'house-heart' => 'House Heart',
        'building' => 'Building',
        'buildings' => 'Buildings',
        'geo-alt' => 'Location',
        'geo-alt-fill' => 'Location Fill',
        'compass' => 'Compass',
        'compass-fill' => 'Compass Fill',
        'map' => 'Map',
        'map-fill' => 'Map Fill',
        'signpost' => 'Signpost',
        'signpost-2' => 'Signpost 2',
        
        // Users & People
        'person' => 'Person',
        'person-fill' => 'Person Fill',
        'person-circle' => 'Person Circle',
        'person-square' => 'Person Square',
        'people' => 'People',
        'people-fill' => 'People Fill',
        'person-plus' => 'Add Person',
        'person-plus-fill' => 'Add Person Fill',
        'person-dash' => 'Remove Person',
        'person-check' => 'Check Person',
        'person-x' => 'X Person',
        'person-gear' => 'Person Settings',
        'person-workspace' => 'Person Workspace',
        'person-badge' => 'Person Badge',
        'person-heart' => 'Person Heart',
        'person-arms-up' => 'Person Arms Up',
        'emoji-smile' => 'Smile',
        'emoji-laughing' => 'Laughing',
        'emoji-heart-eyes' => 'Heart Eyes',
        'emoji-wink' => 'Wink',
        
        // Shopping & Commerce
        'cart' => 'Cart',
        'cart-fill' => 'Cart Fill',
        'cart-plus' => 'Cart Plus',
        'cart-plus-fill' => 'Cart Plus Fill',
        'cart-dash' => 'Cart Dash',
        'cart-check' => 'Cart Check',
        'cart-x' => 'Cart X',
        'bag' => 'Bag',
        'bag-fill' => 'Bag Fill',
        'bag-plus' => 'Bag Plus',
        'bag-dash' => 'Bag Dash',
        'bag-check' => 'Bag Check',
        'bag-x' => 'Bag X',
        'shop' => 'Shop',
        'shop-window' => 'Shop Window',
        'credit-card' => 'Credit Card',
        'credit-card-2-front' => 'Credit Card Front',
        'credit-card-2-back' => 'Credit Card Back',
        'cash' => 'Cash',
        'cash-stack' => 'Cash Stack',
        'coin' => 'Coin',
        'currency-dollar' => 'Dollar',
        'currency-euro' => 'Euro',
        'currency-pound' => 'Pound',
        'currency-bitcoin' => 'Bitcoin',
        'tag' => 'Tag',
        'tag-fill' => 'Tag Fill',
        'tags' => 'Tags',
        'tags-fill' => 'Tags Fill',
        'receipt' => 'Receipt',
        'receipt-cutoff' => 'Receipt Cutoff',
        'percent' => 'Percent',
        'gift' => 'Gift',
        'gift-fill' => 'Gift Fill',
        
        // Communication & Social
        'envelope' => 'Envelope',
        'envelope-fill' => 'Envelope Fill',
        'envelope-open' => 'Envelope Open',
        'envelope-open-fill' => 'Envelope Open Fill',
        'envelope-plus' => 'Envelope Plus',
        'envelope-dash' => 'Envelope Dash',
        'envelope-check' => 'Envelope Check',
        'envelope-x' => 'Envelope X',
        'inbox' => 'Inbox',
        'inbox-fill' => 'Inbox Fill',
        'send' => 'Send',
        'send-fill' => 'Send Fill',
        'telephone' => 'Telephone',
        'telephone-fill' => 'Telephone Fill',
        'phone' => 'Phone',
        'phone-fill' => 'Phone Fill',
        'phone-vibrate' => 'Phone Vibrate',
        'chat' => 'Chat',
        'chat-fill' => 'Chat Fill',
        'chat-dots' => 'Chat Dots',
        'chat-dots-fill' => 'Chat Dots Fill',
        'chat-left' => 'Chat Left',
        'chat-left-fill' => 'Chat Left Fill',
        'chat-right' => 'Chat Right',
        'chat-right-fill' => 'Chat Right Fill',
        'chat-square' => 'Chat Square',
        'chat-square-fill' => 'Chat Square Fill',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'messenger' => 'Messenger',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'discord' => 'Discord',
        'slack' => 'Slack',
        'skype' => 'Skype',
        'share' => 'Share',
        'share-fill' => 'Share Fill',
        
        // Content & Media
        'image' => 'Image',
        'image-fill' => 'Image Fill',
        'images' => 'Images',
        'camera' => 'Camera',
        'camera-fill' => 'Camera Fill',
        'camera2' => 'Camera 2',
        'camera-reels' => 'Camera Reels',
        'camera-video' => 'Video Camera',
        'camera-video-fill' => 'Video Camera Fill',
        'film' => 'Film',
        'play' => 'Play',
        'play-fill' => 'Play Fill',
        'play-circle' => 'Play Circle',
        'play-circle-fill' => 'Play Circle Fill',
        'pause' => 'Pause',
        'pause-fill' => 'Pause Fill',
        'pause-circle' => 'Pause Circle',
        'pause-circle-fill' => 'Pause Circle Fill',
        'stop' => 'Stop',
        'stop-fill' => 'Stop Fill',
        'stop-circle' => 'Stop Circle',
        'stop-circle-fill' => 'Stop Circle Fill',
        'skip-forward' => 'Skip Forward',
        'skip-forward-fill' => 'Skip Forward Fill',
        'skip-backward' => 'Skip Backward',
        'skip-backward-fill' => 'Skip Backward Fill',
        'skip-start' => 'Skip Start',
        'skip-start-fill' => 'Skip Start Fill',
        'skip-end' => 'Skip End',
        'skip-end-fill' => 'Skip End Fill',
        'volume-up' => 'Volume Up',
        'volume-up-fill' => 'Volume Up Fill',
        'volume-down' => 'Volume Down',
        'volume-down-fill' => 'Volume Down Fill',
        'volume-mute' => 'Volume Mute',
        'volume-mute-fill' => 'Volume Mute Fill',
        'volume-off' => 'Volume Off',
        'volume-off-fill' => 'Volume Off Fill',
        'music-note' => 'Music Note',
        'music-note-beamed' => 'Music Note Beamed',
        'music-note-list' => 'Music Note List',
        'headphones' => 'Headphones',
        'mic' => 'Microphone',
        'mic-fill' => 'Microphone Fill',
        'mic-mute' => 'Microphone Mute',
        'mic-mute-fill' => 'Microphone Mute Fill',
        'broadcast' => 'Broadcast',
        'broadcast-pin' => 'Broadcast Pin',
        'tv' => 'TV',
        'tv-fill' => 'TV Fill',
        'display' => 'Display',
        'display-fill' => 'Display Fill',
        'laptop' => 'Laptop',
        'laptop-fill' => 'Laptop Fill',
        'tablet' => 'Tablet',
        'tablet-fill' => 'Tablet Fill',
        'phone' => 'Phone',
        'phone-fill' => 'Phone Fill',
        
        // Navigation & Controls
        'list' => 'List',
        'list-ul' => 'List UL',
        'list-ol' => 'List OL',
        'list-stars' => 'List Stars',
        'list-check' => 'List Check',
        'list-task' => 'List Task',
        'list-nested' => 'List Nested',
        'grid' => 'Grid',
        'grid-fill' => 'Grid Fill',
        'grid-3x3' => 'Grid 3x3',
        'grid-3x3-gap' => 'Grid 3x3 Gap',
        'grid-3x3-gap-fill' => 'Grid 3x3 Gap Fill',
        'grid-1x2' => 'Grid 1x2',
        'grid-1x2-fill' => 'Grid 1x2 Fill',
        'table' => 'Table',
        'columns' => 'Columns',
        'columns-gap' => 'Columns Gap',
        'justify' => 'Justify',
        'justify-left' => 'Justify Left',
        'justify-right' => 'Justify Right',
        'text-center' => 'Text Center',
        'text-left' => 'Text Left',
        'text-right' => 'Text Right',
        'text-wrap' => 'Text Wrap',
        'text-paragraph' => 'Text Paragraph',
        'type' => 'Type',
        'type-bold' => 'Type Bold',
        'type-italic' => 'Type Italic',
        'type-underline' => 'Type Underline',
        'type-strikethrough' => 'Type Strikethrough',
        'type-h1' => 'Type H1',
        'type-h2' => 'Type H2',
        'type-h3' => 'Type H3',
        'menu-up' => 'Menu Up',
        'menu-down' => 'Menu Down',
        'menu-button' => 'Menu Button',
        'menu-button-fill' => 'Menu Button Fill',
        'menu-button-wide' => 'Menu Button Wide',
        'menu-button-wide-fill' => 'Menu Button Wide Fill',
        'three-dots' => 'Three Dots',
        'three-dots-vertical' => 'Three Dots Vertical',
        
        // Arrows & Directions
        'arrow-up' => 'Arrow Up',
        'arrow-up-circle' => 'Arrow Up Circle',
        'arrow-up-circle-fill' => 'Arrow Up Circle Fill',
        'arrow-up-square' => 'Arrow Up Square',
        'arrow-up-square-fill' => 'Arrow Up Square Fill',
        'arrow-down' => 'Arrow Down',
        'arrow-down-circle' => 'Arrow Down Circle',
        'arrow-down-circle-fill' => 'Arrow Down Circle Fill',
        'arrow-down-square' => 'Arrow Down Square',
        'arrow-down-square-fill' => 'Arrow Down Square Fill',
        'arrow-left' => 'Arrow Left',
        'arrow-left-circle' => 'Arrow Left Circle',
        'arrow-left-circle-fill' => 'Arrow Left Circle Fill',
        'arrow-left-square' => 'Arrow Left Square',
        'arrow-left-square-fill' => 'Arrow Left Square Fill',
        'arrow-right' => 'Arrow Right',
        'arrow-right-circle' => 'Arrow Right Circle',
        'arrow-right-circle-fill' => 'Arrow Right Circle Fill',
        'arrow-right-square' => 'Arrow Right Square',
        'arrow-right-square-fill' => 'Arrow Right Square Fill',
        'chevron-up' => 'Chevron Up',
        'chevron-down' => 'Chevron Down',
        'chevron-left' => 'Chevron Left',
        'chevron-right' => 'Chevron Right',
        'chevron-double-up' => 'Chevron Double Up',
        'chevron-double-down' => 'Chevron Double Down',
        'chevron-double-left' => 'Chevron Double Left',
        'chevron-double-right' => 'Chevron Double Right',
        'chevron-bar-up' => 'Chevron Bar Up',
        'chevron-bar-down' => 'Chevron Bar Down',
        'chevron-bar-left' => 'Chevron Bar Left',
        'chevron-bar-right' => 'Chevron Bar Right',
        'chevron-expand' => 'Chevron Expand',
        'chevron-contract' => 'Chevron Contract',
        'caret-up' => 'Caret Up',
        'caret-up-fill' => 'Caret Up Fill',
        'caret-down' => 'Caret Down',
        'caret-down-fill' => 'Caret Down Fill',
        'caret-left' => 'Caret Left',
        'caret-left-fill' => 'Caret Left Fill',
        'caret-right' => 'Caret Right',
        'caret-right-fill' => 'Caret Right Fill',
        
        // Actions & Controls
        'plus' => 'Plus',
        'plus-circle' => 'Plus Circle',
        'plus-circle-fill' => 'Plus Circle Fill',
        'plus-square' => 'Plus Square',
        'plus-square-fill' => 'Plus Square Fill',
        'dash' => 'Dash',
        'dash-circle' => 'Dash Circle',
        'dash-circle-fill' => 'Dash Circle Fill',
        'dash-square' => 'Dash Square',
        'dash-square-fill' => 'Dash Square Fill',
        'x' => 'X',
        'x-circle' => 'X Circle',
        'x-circle-fill' => 'X Circle Fill',
        'x-square' => 'X Square',
        'x-square-fill' => 'X Square Fill',
        'check' => 'Check',
        'check-circle' => 'Check Circle',
        'check-circle-fill' => 'Check Circle Fill',
        'check-square' => 'Check Square',
        'check-square-fill' => 'Check Square Fill',
        'check2' => 'Check 2',
        'check2-circle' => 'Check 2 Circle',
        'check2-square' => 'Check 2 Square',
        'check-all' => 'Check All',
        'exclamation' => 'Exclamation',
        'exclamation-circle' => 'Exclamation Circle',
        'exclamation-circle-fill' => 'Exclamation Circle Fill',
        'exclamation-triangle' => 'Exclamation Triangle',
        'exclamation-triangle-fill' => 'Exclamation Triangle Fill',
        'exclamation-square' => 'Exclamation Square',
        'exclamation-square-fill' => 'Exclamation Square Fill',
        'question' => 'Question',
        'question-circle' => 'Question Circle',
        'question-circle-fill' => 'Question Circle Fill',
        'question-square' => 'Question Square',
        'question-square-fill' => 'Question Square Fill',
        'info' => 'Info',
        'info-circle' => 'Info Circle',
        'info-circle-fill' => 'Info Circle Fill',
        'info-square' => 'Info Square',
        'info-square-fill' => 'Info Square Fill',
        
        // Social & Favorites
        'heart' => 'Heart',
        'heart-fill' => 'Heart Fill',
        'heart-half' => 'Heart Half',
        'heartbreak' => 'Heartbreak',
        'heartbreak-fill' => 'Heartbreak Fill',
        'hearts' => 'Hearts',
        'star' => 'Star',
        'star-fill' => 'Star Fill',
        'star-half' => 'Star Half',
        'bookmark' => 'Bookmark',
        'bookmark-fill' => 'Bookmark Fill',
        'bookmark-plus' => 'Bookmark Plus',
        'bookmark-plus-fill' => 'Bookmark Plus Fill',
        'bookmark-dash' => 'Bookmark Dash',
        'bookmark-dash-fill' => 'Bookmark Dash Fill',
        'bookmark-check' => 'Bookmark Check',
        'bookmark-check-fill' => 'Bookmark Check Fill',
        'bookmark-x' => 'Bookmark X',
        'bookmark-x-fill' => 'Bookmark X Fill',
        'bookmark-star' => 'Bookmark Star',
        'bookmark-star-fill' => 'Bookmark Star Fill',
        'bookmark-heart' => 'Bookmark Heart',
        'bookmark-heart-fill' => 'Bookmark Heart Fill',
        'hand-thumbs-up' => 'Thumbs Up',
        'hand-thumbs-up-fill' => 'Thumbs Up Fill',
        'hand-thumbs-down' => 'Thumbs Down',
        'hand-thumbs-down-fill' => 'Thumbs Down Fill',
        'flag' => 'Flag',
        'flag-fill' => 'Flag Fill',
        'trophy' => 'Trophy',
        'trophy-fill' => 'Trophy Fill',
        'award' => 'Award',
        'award-fill' => 'Award Fill',
        'suit-heart' => 'Suit Heart',
        'suit-heart-fill' => 'Suit Heart Fill',
        'suit-diamond' => 'Suit Diamond',
        'suit-diamond-fill' => 'Suit Diamond Fill',
        'suit-club' => 'Suit Club',
        'suit-club-fill' => 'Suit Club Fill',
        'suit-spade' => 'Suit Spade',
        'suit-spade-fill' => 'Suit Spade Fill',
        
        // Search & Discovery
        'search' => 'Search',
        'zoom-in' => 'Zoom In',
        'zoom-out' => 'Zoom Out',
        'funnel' => 'Filter',
        'funnel-fill' => 'Filter Fill',
        'sort-down' => 'Sort Down',
        'sort-down-alt' => 'Sort Down Alt',
        'sort-up' => 'Sort Up',
        'sort-up-alt' => 'Sort Up Alt',
        'sort-alpha-down' => 'Sort Alpha Down',
        'sort-alpha-down-alt' => 'Sort Alpha Down Alt',
        'sort-alpha-up' => 'Sort Alpha Up',
        'sort-alpha-up-alt' => 'Sort Alpha Up Alt',
        'sort-numeric-down' => 'Sort Numeric Down',
        'sort-numeric-down-alt' => 'Sort Numeric Down Alt',
        'sort-numeric-up' => 'Sort Numeric Up',
        'sort-numeric-up-alt' => 'Sort Numeric Up Alt',
        'binoculars' => 'Binoculars',
        'binoculars-fill' => 'Binoculars Fill',
        'eyeglasses' => 'Eyeglasses',
        'eye' => 'Eye',
        'eye-fill' => 'Eye Fill',
        'eye-slash' => 'Eye Slash',
        'eye-slash-fill' => 'Eye Slash Fill',
        
        // Files & Documents
        'file-earmark' => 'File',
        'file-earmark-fill' => 'File Fill',
        'file-earmark-text' => 'Text File',
        'file-earmark-text-fill' => 'Text File Fill',
        'file-earmark-pdf' => 'PDF File',
        'file-earmark-pdf-fill' => 'PDF File Fill',
        'file-earmark-word' => 'Word File',
        'file-earmark-word-fill' => 'Word File Fill',
        'file-earmark-excel' => 'Excel File',
        'file-earmark-excel-fill' => 'Excel File Fill',
        'file-earmark-ppt' => 'PowerPoint File',
        'file-earmark-ppt-fill' => 'PowerPoint File Fill',
        'file-earmark-image' => 'Image File',
        'file-earmark-image-fill' => 'Image File Fill',
        'file-earmark-music' => 'Music File',
        'file-earmark-music-fill' => 'Music File Fill',
        'file-earmark-play' => 'Video File',
        'file-earmark-play-fill' => 'Video File Fill',
        'file-earmark-zip' => 'Archive File',
        'file-earmark-zip-fill' => 'Archive File Fill',
        'file-earmark-code' => 'Code File',
        'file-earmark-code-fill' => 'Code File Fill',
        'file-earmark-medical' => 'Medical File',
        'file-earmark-medical-fill' => 'Medical File Fill',
        'file-earmark-font' => 'Font File',
        'file-earmark-font-fill' => 'Font File Fill',
        'folder' => 'Folder',
        'folder-fill' => 'Folder Fill',
        'folder2' => 'Folder 2',
        'folder2-open' => 'Folder Open',
        'folder-plus' => 'Folder Plus',
        'folder-minus' => 'Folder Minus',
        'folder-check' => 'Folder Check',
        'folder-x' => 'Folder X',
        'folder-symlink' => 'Folder Symlink',
        'folder-symlink-fill' => 'Folder Symlink Fill',
        'clipboard' => 'Clipboard',
        'clipboard-fill' => 'Clipboard Fill',
        'clipboard-data' => 'Clipboard Data',
        'clipboard-data-fill' => 'Clipboard Data Fill',
        'clipboard-check' => 'Clipboard Check',
        'clipboard-check-fill' => 'Clipboard Check Fill',
        'clipboard-x' => 'Clipboard X',
        'clipboard-x-fill' => 'Clipboard X Fill',
        'clipboard-plus' => 'Clipboard Plus',
        'clipboard-plus-fill' => 'Clipboard Plus Fill',
        'clipboard-minus' => 'Clipboard Minus',
        'clipboard-minus-fill' => 'Clipboard Minus Fill',
        'paperclip' => 'Paperclip',
        'link' => 'Link',
        'link-45deg' => 'Link 45',
        'download' => 'Download',
        'upload' => 'Upload',
        'cloud' => 'Cloud',
        'cloud-fill' => 'Cloud Fill',
        'cloud-download' => 'Cloud Download',
        'cloud-download-fill' => 'Cloud Download Fill',
        'cloud-upload' => 'Cloud Upload',
        'cloud-upload-fill' => 'Cloud Upload Fill',
        'cloud-arrow-down' => 'Cloud Arrow Down',
        'cloud-arrow-down-fill' => 'Cloud Arrow Down Fill',
        'cloud-arrow-up' => 'Cloud Arrow Up',
        'cloud-arrow-up-fill' => 'Cloud Arrow Up Fill',
        'cloud-check' => 'Cloud Check',
        'cloud-check-fill' => 'Cloud Check Fill',
        'cloud-plus' => 'Cloud Plus',
        'cloud-plus-fill' => 'Cloud Plus Fill',
        'cloud-minus' => 'Cloud Minus',
        'cloud-minus-fill' => 'Cloud Minus Fill',
        'cloud-slash' => 'Cloud Slash',
        'cloud-slash-fill' => 'Cloud Slash Fill',
        'hdd' => 'Hard Drive',
        'hdd-fill' => 'Hard Drive Fill',
        'hdd-network' => 'Network Drive',
        'hdd-network-fill' => 'Network Drive Fill',
        'hdd-rack' => 'Server Rack',
        'hdd-rack-fill' => 'Server Rack Fill',
        'hdd-stack' => 'Drive Stack',
        'hdd-stack-fill' => 'Drive Stack Fill',
        'archive' => 'Archive',
        'archive-fill' => 'Archive Fill',
        
        // Settings & Tools
        'gear' => 'Gear',
        'gear-fill' => 'Gear Fill',
        'gear-wide' => 'Gear Wide',
        'gear-wide-connected' => 'Gear Wide Connected',
        'sliders' => 'Sliders',
        'tools' => 'Tools',
        'wrench' => 'Wrench',
        'hammer' => 'Hammer',
        'screwdriver' => 'Screwdriver',
        'nut' => 'Nut',
        'nut-fill' => 'Nut Fill',
        'palette' => 'Palette',
        'palette-fill' => 'Palette Fill',
        'palette2' => 'Palette 2',
        'brush' => 'Brush',
        'brush-fill' => 'Brush Fill',
        'paint-bucket' => 'Paint Bucket',
        'scissors' => 'Scissors',
        'pen' => 'Pen',
        'pen-fill' => 'Pen Fill',
        'pencil' => 'Pencil',
        'pencil-fill' => 'Pencil Fill',
        'pencil-square' => 'Pencil Square',
        'eraser' => 'Eraser',
        'eraser-fill' => 'Eraser Fill',
        'vector-pen' => 'Vector Pen',
        'bezier' => 'Bezier',
        'bezier2' => 'Bezier 2',
        'rulers' => 'Rulers',
        'triangle' => 'Triangle',
        'triangle-fill' => 'Triangle Fill',
        'triangle-half' => 'Triangle Half',
        'square' => 'Square',
        'square-fill' => 'Square Fill',
        'square-half' => 'Square Half',
        'circle' => 'Circle',
        'circle-fill' => 'Circle Fill',
        'circle-half' => 'Circle Half',
        'hexagon' => 'Hexagon',
        'hexagon-fill' => 'Hexagon Fill',
        'hexagon-half' => 'Hexagon Half',
        'octagon' => 'Octagon',
        'octagon-fill' => 'Octagon Fill',
        'octagon-half' => 'Octagon Half',
        'pentagon' => 'Pentagon',
        'pentagon-fill' => 'Pentagon Fill',
        'pentagon-half' => 'Pentagon Half',
        'diamond' => 'Diamond',
        'diamond-fill' => 'Diamond Fill',
        'diamond-half' => 'Diamond Half',
        
        // Security & Privacy
        'lock' => 'Lock',
        'lock-fill' => 'Lock Fill',
        'unlock' => 'Unlock',
        'unlock-fill' => 'Unlock Fill',
        'key' => 'Key',
        'key-fill' => 'Key Fill',
        'shield' => 'Shield',
        'shield-fill' => 'Shield Fill',
        'shield-check' => 'Shield Check',
        'shield-fill-check' => 'Shield Fill Check',
        'shield-x' => 'Shield X',
        'shield-fill-x' => 'Shield Fill X',
        'shield-plus' => 'Shield Plus',
        'shield-fill-plus' => 'Shield Fill Plus',
        'shield-minus' => 'Shield Minus',
        'shield-fill-minus' => 'Shield Fill Minus',
        'shield-exclamation' => 'Shield Exclamation',
        'shield-fill-exclamation' => 'Shield Fill Exclamation',
        'shield-slash' => 'Shield Slash',
        'shield-slash-fill' => 'Shield Slash Fill',
        'fingerprint' => 'Fingerprint',
        'safe' => 'Safe',
        'safe-fill' => 'Safe Fill',
        'safe2' => 'Safe 2',
        'safe2-fill' => 'Safe 2 Fill',
        'incognito' => 'Incognito',
        'mask' => 'Mask',
        'person-x' => 'Person X',
        'person-check' => 'Person Check',
        'person-dash' => 'Person Dash',
        'person-plus' => 'Person Plus',
        'person-gear' => 'Person Gear',
        'person-lock' => 'Person Lock',
        'person-fill-lock' => 'Person Fill Lock',
        'person-fill-check' => 'Person Fill Check',
        'person-fill-x' => 'Person Fill X',
        'person-fill-dash' => 'Person Fill Dash',
        'person-fill-add' => 'Person Fill Add',
        'person-fill-gear' => 'Person Fill Gear',
        
        // Time & Calendar
        'clock' => 'Clock',
        'clock-fill' => 'Clock Fill',
        'clock-history' => 'Clock History',
        'stopwatch' => 'Stopwatch',
        'stopwatch-fill' => 'Stopwatch Fill',
        'hourglass' => 'Hourglass',
        'hourglass-split' => 'Hourglass Split',
        'hourglass-top' => 'Hourglass Top',
        'hourglass-bottom' => 'Hourglass Bottom',
        'alarm' => 'Alarm',
        'alarm-fill' => 'Alarm Fill',
        'calendar' => 'Calendar',
        'calendar-fill' => 'Calendar Fill',
        'calendar2' => 'Calendar 2',
        'calendar2-fill' => 'Calendar 2 Fill',
        'calendar3' => 'Calendar 3',
        'calendar3-fill' => 'Calendar 3 Fill',
        'calendar4' => 'Calendar 4',
        'calendar-event' => 'Calendar Event',
        'calendar-event-fill' => 'Calendar Event Fill',
        'calendar-date' => 'Calendar Date',
        'calendar-date-fill' => 'Calendar Date Fill',
        'calendar-day' => 'Calendar Day',
        'calendar-day-fill' => 'Calendar Day Fill',
        'calendar-week' => 'Calendar Week',
        'calendar-week-fill' => 'Calendar Week Fill',
        'calendar-month' => 'Calendar Month',
        'calendar-month-fill' => 'Calendar Month Fill',
        'calendar-range' => 'Calendar Range',
        'calendar-range-fill' => 'Calendar Range Fill',
        'calendar-check' => 'Calendar Check',
        'calendar-check-fill' => 'Calendar Check Fill',
        'calendar-x' => 'Calendar X',
        'calendar-x-fill' => 'Calendar X Fill',
        'calendar-plus' => 'Calendar Plus',
        'calendar-plus-fill' => 'Calendar Plus Fill',
        'calendar-minus' => 'Calendar Minus',
        'calendar-minus-fill' => 'Calendar Minus Fill',
        'calendar2-check' => 'Calendar 2 Check',
        'calendar2-check-fill' => 'Calendar 2 Check Fill',
        'calendar2-x' => 'Calendar 2 X',
        'calendar2-x-fill' => 'Calendar 2 X Fill',
        'calendar2-plus' => 'Calendar 2 Plus',
        'calendar2-plus-fill' => 'Calendar 2 Plus Fill',
        'calendar2-minus' => 'Calendar 2 Minus',
        'calendar2-minus-fill' => 'Calendar 2 Minus Fill',
        'calendar2-date' => 'Calendar 2 Date',
        'calendar2-date-fill' => 'Calendar 2 Date Fill',
        'calendar2-day' => 'Calendar 2 Day',
        'calendar2-day-fill' => 'Calendar 2 Day Fill',
        'calendar2-week' => 'Calendar 2 Week',
        'calendar2-week-fill' => 'Calendar 2 Week Fill',
        'calendar2-month' => 'Calendar 2 Month',
        'calendar2-month-fill' => 'Calendar 2 Month Fill',
        'calendar2-range' => 'Calendar 2 Range',
        'calendar2-range-fill' => 'Calendar 2 Range Fill',
        'calendar2-event' => 'Calendar 2 Event',
        'calendar2-event-fill' => 'Calendar 2 Event Fill',
        'calendar3-event' => 'Calendar 3 Event',
        'calendar3-event-fill' => 'Calendar 3 Event Fill',
        'calendar3-range' => 'Calendar 3 Range',
        'calendar3-range-fill' => 'Calendar 3 Range Fill',
        'calendar3-week' => 'Calendar 3 Week',
        'calendar3-week-fill' => 'Calendar 3 Week Fill',
        'calendar4-event' => 'Calendar 4 Event',
        'calendar4-range' => 'Calendar 4 Range',
        'calendar4-week' => 'Calendar 4 Week',
        'smartwatch' => 'Smartwatch',
        'watch' => 'Watch',
        
        // Transportation
        'car-front' => 'Car Front',
        'car-front-fill' => 'Car Front Fill',
        'truck' => 'Truck',
        'truck-flatbed' => 'Truck Flatbed',
        'bus-front' => 'Bus Front',
        'bus-front-fill' => 'Bus Front Fill',
        'bicycle' => 'Bicycle',
        'scooter' => 'Scooter',
        'motorcycle' => 'Motorcycle',
        'airplane' => 'Airplane',
        'airplane-fill' => 'Airplane Fill',
        'airplane-engines' => 'Airplane Engines',
        'airplane-engines-fill' => 'Airplane Engines Fill',
        'train-freight-front' => 'Train Freight Front',
        'train-freight-front-fill' => 'Train Freight Front Fill',
        'train-front' => 'Train Front',
        'train-front-fill' => 'Train Front Fill',
        'train-lightrail-front' => 'Train Lightrail Front',
        'train-lightrail-front-fill' => 'Train Lightrail Front Fill',
        'subway' => 'Subway',
        'ev-front' => 'Electric Vehicle Front',
        'ev-front-fill' => 'Electric Vehicle Front Fill',
        'ev-station' => 'EV Station',
        'ev-station-fill' => 'EV Station Fill',
        'fuel-pump' => 'Fuel Pump',
        'fuel-pump-fill' => 'Fuel Pump Fill',
        'fuel-pump-diesel' => 'Fuel Pump Diesel',
        'fuel-pump-diesel-fill' => 'Fuel Pump Diesel Fill',
        'traffic-light' => 'Traffic Light',
        'traffic-light-fill' => 'Traffic Light Fill',
        'cone' => 'Cone',
        'cone-striped' => 'Cone Striped',
        'sign-stop' => 'Stop Sign',
        'sign-stop-fill' => 'Stop Sign Fill',
        'sign-yield' => 'Yield Sign',
        'sign-yield-fill' => 'Yield Sign Fill',
        'sign-turn-left' => 'Turn Left Sign',
        'sign-turn-left-fill' => 'Turn Left Sign Fill',
        'sign-turn-right' => 'Turn Right Sign',
        'sign-turn-right-fill' => 'Turn Right Sign Fill',
        'sign-turn-slight-left' => 'Turn Slight Left Sign',
        'sign-turn-slight-left-fill' => 'Turn Slight Left Sign Fill',
        'sign-turn-slight-right' => 'Turn Slight Right Sign',
        'sign-turn-slight-right-fill' => 'Turn Slight Right Sign Fill',
        'sign-intersection' => 'Intersection Sign',
        'sign-intersection-fill' => 'Intersection Sign Fill',
        'sign-intersection-side' => 'Intersection Side Sign',
        'sign-intersection-side-fill' => 'Intersection Side Sign Fill',
        'sign-intersection-t' => 'Intersection T Sign',
        'sign-intersection-t-fill' => 'Intersection T Sign Fill',
        'sign-intersection-y' => 'Intersection Y Sign',
        'sign-intersection-y-fill' => 'Intersection Y Sign Fill',
        'sign-merge-left' => 'Merge Left Sign',
        'sign-merge-left-fill' => 'Merge Left Sign Fill',
        'sign-merge-right' => 'Merge Right Sign',
        'sign-merge-right-fill' => 'Merge Right Sign Fill',
        'sign-no-left-turn' => 'No Left Turn Sign',
        'sign-no-left-turn-fill' => 'No Left Turn Sign Fill',
        'sign-no-right-turn' => 'No Right Turn Sign',
        'sign-no-right-turn-fill' => 'No Right Turn Sign Fill',
        'sign-no-parking' => 'No Parking Sign',
        'sign-no-parking-fill' => 'No Parking Sign Fill',
        'sign-railroad' => 'Railroad Sign',
        'sign-railroad-fill' => 'Railroad Sign Fill',
        'stoplights' => 'Stoplights',
        'stoplights-fill' => 'Stoplights Fill',
        'taxi-front' => 'Taxi Front',
        'taxi-front-fill' => 'Taxi Front Fill'
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
