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
                'icon' => 'bi bi-house-door',
                'label' => __('Home', 'wp-bottom-navigation-pro'),
                'url' => home_url(),
                'enabled' => true,
                'roles' => array(),
                'badge_count' => 0
            ),
            array(
                'id' => 'shop',
                'icon' => 'bi bi-cart',
                'label' => __('Shop', 'wp-bottom-navigation-pro'),
                'url' => '#',
                'enabled' => true,
                'roles' => array(),
                'badge_count' => 0
            ),
            array(
                'id' => 'account',
                'icon' => 'bi bi-person',
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
            'icon_color' => '#666666',
            'border_color' => '#e0e0e0',
            'height' => '70',
            'border_radius' => '0',
            'box_shadow' => '0 -2px 8px rgba(0,0,0,0.1)',
            'font_size' => '14',
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
        ),
        
        // Pro feature: Page targeting
        // NOTE: When merging with pro branch, merge this array with existing pro settings
        'page_targeting' => array(
            'enabled' => false,
            'configurations' => array()
        ),
        
        // Pro feature: Custom presets
        'custom_presets' => array(
            'enabled' => false,
            'presets' => array()
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
            'icon_color' => sanitize_hex_color($settings['style']['icon_color'] ?? '#666666'),
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
    
    // Page targeting settings (Pro feature)
    if (isset($settings['page_targeting']) && is_array($settings['page_targeting'])) {
        $sanitized['page_targeting'] = array(
            'enabled' => !empty($settings['page_targeting']['enabled']),
            'configurations' => array()
        );
        
        // Sanitize configurations
        if (isset($settings['page_targeting']['configurations']) && is_array($settings['page_targeting']['configurations'])) {
            foreach ($settings['page_targeting']['configurations'] as $config) {
                if (is_array($config)) {
                    $sanitized_config = array(
                        'id' => sanitize_key($config['id'] ?? ''),
                        'name' => sanitize_text_field($config['name'] ?? ''),
                        'priority' => absint($config['priority'] ?? 1),
                        'preset_id' => sanitize_key($config['preset_id'] ?? 'default'),
                        'conditions' => array(
                            'pages' => array(),
                            'post_types' => array(),
                            'categories' => array(),
                            'user_roles' => array()
                        )
                    );
                    
                    // Sanitize conditions
                    if (isset($config['conditions']) && is_array($config['conditions'])) {
                        $conditions = $config['conditions'];
                        
                        if (isset($conditions['pages']) && is_array($conditions['pages'])) {
                            $sanitized_config['conditions']['pages'] = array_map('absint', array_filter($conditions['pages']));
                        }
                        
                        if (isset($conditions['post_types']) && is_array($conditions['post_types'])) {
                            $sanitized_config['conditions']['post_types'] = array_map('sanitize_key', array_filter($conditions['post_types']));
                        }
                        
                        if (isset($conditions['categories']) && is_array($conditions['categories'])) {
                            $sanitized_config['conditions']['categories'] = array_map('absint', array_filter($conditions['categories']));
                        }
                        
                        if (isset($conditions['user_roles']) && is_array($conditions['user_roles'])) {
                            $sanitized_config['conditions']['user_roles'] = array_map('sanitize_key', array_filter($conditions['user_roles']));
                        }
                    }
                    
                    $sanitized['page_targeting']['configurations'][] = $sanitized_config;
                }
            }
        }
    }
    
    // Custom presets settings (Pro feature)
    if (isset($settings['custom_presets']) && is_array($settings['custom_presets'])) {
        $sanitized['custom_presets'] = array(
            'enabled' => !empty($settings['custom_presets']['enabled']),
            'presets' => array()
        );
        
        if (isset($settings['custom_presets']['presets']) && is_array($settings['custom_presets']['presets'])) {
            foreach ($settings['custom_presets']['presets'] as $preset) {
                if (is_array($preset)) {
                    $sanitized_preset = array(
                        'id' => sanitize_key($preset['id'] ?? ''),
                        'name' => sanitize_text_field($preset['name'] ?? ''),
                        'description' => sanitize_text_field($preset['description'] ?? ''),
                        'created_at' => absint($preset['created_at'] ?? time()),
                        'items' => array()
                    );
                    
                    // Sanitize preset items (same as regular items)
                    $preset_items = array();
                    if (isset($preset['items'])) {
                        // Handle both array and JSON string formats
                        if (is_string($preset['items'])) {
                            $preset_items = json_decode($preset['items'], true);
                            if (!is_array($preset_items)) {
                                $preset_items = array();
                            }
                        } elseif (is_array($preset['items'])) {
                            $preset_items = $preset['items'];
                        }
                    }
                    
                    if (!empty($preset_items)) {
                        foreach ($preset_items as $item) {
                            if (is_array($item)) {
                                $sanitized_item = array(
                                    'id' => sanitize_key($item['id'] ?? ''),
                                    'label' => sanitize_text_field($item['label'] ?? ''),
                                    'icon' => sanitize_text_field($item['icon'] ?? ''),
                                    'url' => esc_url_raw($item['url'] ?? ''),
                                    'enabled' => !empty($item['enabled']),
                                    'target' => in_array($item['target'] ?? '', array('_self', '_blank')) ? $item['target'] : '_self',
                                    'show_badge' => !empty($item['show_badge']),
                                    'badge_type' => in_array($item['badge_type'] ?? '', array('count', 'dot', 'custom')) ? $item['badge_type'] : 'count',
                                    'custom_badge_text' => sanitize_text_field($item['custom_badge_text'] ?? ''),
                                    'user_roles' => array()
                                );
                                
                                // Sanitize user roles
                                if (isset($item['user_roles']) && is_array($item['user_roles'])) {
                                    $sanitized_item['user_roles'] = array_map('sanitize_key', array_filter($item['user_roles']));
                                }
                                
                                $sanitized_preset['items'][] = $sanitized_item;
                            }
                        }
                    }
                    
                    $sanitized['custom_presets']['presets'][] = $sanitized_preset;
                }
            }
        }
    }
    
    return apply_filters('wpbnp_sanitize_settings', $sanitized, $settings);
}

/**
 * Get available presets
 */
function wpbnp_get_presets() {
    $presets_file = plugin_dir_path(__FILE__) . '../presets/default-presets.json';
    
    if (file_exists($presets_file)) {
        $presets_data = file_get_contents($presets_file);
        $presets = json_decode($presets_data, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($presets)) {
            return $presets;
        }
    }
    
    // Fallback presets if file doesn't exist or is invalid
    return array(
        'default' => array(
            'name' => 'Default',
            'description' => 'Clean and simple design',
            'style' => array(
                'background_color' => '#ffffff',
                'text_color' => '#333333',
                'active_color' => '#0073aa',
                'hover_color' => '#0085ba',
                'icon_color' => '#666666',
                'border_color' => '#e0e0e0',
                'height' => '60',
                'border_radius' => '0',
                'font_size' => '12',
                'font_weight' => '400',
                'icon_size' => '20',
                'padding' => '10',
                'box_shadow' => '0 -2px 8px rgba(0,0,0,0.1)'
            )
        ),
        'dark' => array(
            'name' => 'Dark Theme',
            'description' => 'Modern dark design',
            'style' => array(
                'background_color' => '#2c3e50',
                'text_color' => '#ecf0f1',
                'active_color' => '#3498db',
                'hover_color' => '#2980b9',
                'icon_color' => '#bdc3c7',
                'border_color' => '#34495e',
                'height' => '60',
                'border_radius' => '0',
                'font_size' => '12',
                'font_weight' => '400',
                'icon_size' => '20',
                'padding' => '10',
                'box_shadow' => '0 -2px 8px rgba(0,0,0,0.3)'
            )
        ),
        'material' => array(
            'name' => 'Material Design',
            'description' => 'Google Material Design inspired',
            'style' => array(
                'background_color' => '#ffffff',
                'text_color' => '#424242',
                'active_color' => '#2196f3',
                'hover_color' => '#1976d2',
                'icon_color' => '#757575',
                'border_color' => '#e0e0e0',
                'height' => '56',
                'border_radius' => '0',
                'font_size' => '12',
                'font_weight' => '500',
                'icon_size' => '24',
                'padding' => '8',
                'box_shadow' => '0 -2px 4px rgba(0,0,0,0.12)'
            )
        )
    );
}

/**
 * Get available icon libraries
 */
function wpbnp_get_icon_libraries() {
    return array(
        'bootstrap' => array(
            'name' => 'Bootstrap Icons',
            'description' => 'Modern, comprehensive icon library (Default)',
            'class_prefix' => 'bi bi-',
            'type' => 'font',
            'cdn' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css'
        ),
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
            'cdn' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css'
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
        // Navigation & Home
        'home' => 'Home',
        'house' => 'House',
        'apartment' => 'Apartment',
        'business' => 'Business',
        'location_on' => 'Location On',
        'location_off' => 'Location Off',
        'place' => 'Place',
        'map' => 'Map',
        'explore' => 'Explore',
        'navigation' => 'Navigation',
        'near_me' => 'Near Me',
        'my_location' => 'My Location',
        'compass_calibration' => 'Compass Calibration',
        'directions' => 'Directions',
        'directions_walk' => 'Walking Directions',
        'directions_run' => 'Running Directions',
        'directions_bike' => 'Bike Directions',
        'directions_car' => 'Car Directions',
        'directions_transit' => 'Transit Directions',
        'directions_boat' => 'Boat Directions',
        'flight' => 'Flight',
        'flight_takeoff' => 'Flight Takeoff',
        'flight_land' => 'Flight Land',
        
        // Users & People
        'person' => 'Person',
        'people' => 'People',
        'group' => 'Group',
        'groups' => 'Groups',
        'person_add' => 'Add Person',
        'person_remove' => 'Remove Person',
        'people_alt' => 'People Alt',
        'account_circle' => 'Account Circle',
        'account_box' => 'Account Box',
        'face' => 'Face',
        'sentiment_satisfied' => 'Satisfied',
        'sentiment_very_satisfied' => 'Very Satisfied',
        'sentiment_dissatisfied' => 'Dissatisfied',
        'sentiment_very_dissatisfied' => 'Very Dissatisfied',
        'mood' => 'Mood',
        'mood_bad' => 'Bad Mood',
        'family_restroom' => 'Family',
        'child_care' => 'Child Care',
        'elderly' => 'Elderly',
        'accessible' => 'Accessible',
        'accessibility' => 'Accessibility',
        'supervisor_account' => 'Supervisor',
        'admin_panel_settings' => 'Admin',
        'manage_accounts' => 'Manage Accounts',
        'switch_account' => 'Switch Account',
        'badge' => 'Badge',
        'card_membership' => 'Membership Card',
        'contact_mail' => 'Contact Mail',
        'contact_phone' => 'Contact Phone',
        'contacts' => 'Contacts',
        
        // Shopping & Commerce
        'shopping_cart' => 'Shopping Cart',
        'shopping_cart_checkout' => 'Cart Checkout',
        'add_shopping_cart' => 'Add to Cart',
        'remove_shopping_cart' => 'Remove from Cart',
        'shopping_bag' => 'Shopping Bag',
        'shopping_basket' => 'Shopping Basket',
        'store' => 'Store',
        'storefront' => 'Storefront',
        'local_mall' => 'Mall',
        'local_grocery_store' => 'Grocery Store',
        'local_convenience_store' => 'Convenience Store',
        'local_pharmacy' => 'Pharmacy',
        'local_gas_station' => 'Gas Station',
        'local_atm' => 'ATM',
        'payment' => 'Payment',
        'credit_card' => 'Credit Card',
        'credit_score' => 'Credit Score',
        'account_balance' => 'Account Balance',
        'account_balance_wallet' => 'Wallet',
        'attach_money' => 'Money',
        'money_off' => 'Money Off',
        'euro_symbol' => 'Euro',
        'currency_pound' => 'Pound',
        'currency_yen' => 'Yen',
        'currency_bitcoin' => 'Bitcoin',
        'savings' => 'Savings',
        'monetization_on' => 'Monetization',
        'sell' => 'Sell',
        'point_of_sale' => 'Point of Sale',
        'receipt' => 'Receipt',
        'receipt_long' => 'Long Receipt',
        'inventory' => 'Inventory',
        'warehouse' => 'Warehouse',
        'delivery_dining' => 'Delivery',
        'local_shipping' => 'Shipping',
        'track_changes' => 'Track Changes',
        'price_check' => 'Price Check',
        'price_change' => 'Price Change',
        'discount' => 'Discount',
        'percent' => 'Percent',
        'redeem' => 'Redeem',
        'card_giftcard' => 'Gift Card',
        'loyalty' => 'Loyalty',
        'shopping_cart_checkout' => 'Checkout',
        
        // Communication & Social
        'mail' => 'Mail',
        'email' => 'Email',
        'inbox' => 'Inbox',
        'outbox' => 'Outbox',
        'send' => 'Send',
        'forward_to_inbox' => 'Forward',
        'reply' => 'Reply',
        'reply_all' => 'Reply All',
        'mark_email_read' => 'Mark Read',
        'mark_email_unread' => 'Mark Unread',
        'drafts' => 'Drafts',
        'archive' => 'Archive',
        'unarchive' => 'Unarchive',
        'delete_forever' => 'Delete Forever',
        'phone' => 'Phone',
        'call' => 'Call',
        'call_end' => 'End Call',
        'call_made' => 'Call Made',
        'call_received' => 'Call Received',
        'call_missed' => 'Call Missed',
        'phone_in_talk' => 'Phone in Talk',
        'phone_callback' => 'Phone Callback',
        'phone_forwarded' => 'Phone Forwarded',
        'phone_locked' => 'Phone Locked',
        'phone_paused' => 'Phone Paused',
        'smartphone' => 'Smartphone',
        'tablet' => 'Tablet',
        'laptop' => 'Laptop',
        'desktop_windows' => 'Desktop',
        'computer' => 'Computer',
        'devices' => 'Devices',
        'chat' => 'Chat',
        'chat_bubble' => 'Chat Bubble',
        'chat_bubble_outline' => 'Chat Outline',
        'message' => 'Message',
        'sms' => 'SMS',
        'textsms' => 'Text SMS',
        'question_answer' => 'Q&A',
        'forum' => 'Forum',
        'comment' => 'Comment',
        'speaker_notes' => 'Speaker Notes',
        'record_voice_over' => 'Voice Over',
        'campaign' => 'Campaign',
        'announcement' => 'Announcement',
        'notifications' => 'Notifications',
        'notifications_active' => 'Notifications Active',
        'notifications_off' => 'Notifications Off',
        'notifications_none' => 'No Notifications',
        'notifications_paused' => 'Notifications Paused',
        'share' => 'Share',
        'ios_share' => 'iOS Share',
        'screen_share' => 'Screen Share',
        'rss_feed' => 'RSS Feed',
        'public' => 'Public',
        'language' => 'Language',
        'translate' => 'Translate',
        
        // Content & Media
        'image' => 'Image',
        'photo' => 'Photo',
        'photo_library' => 'Photo Library',
        'photo_album' => 'Photo Album',
        'collections' => 'Collections',
        'burst_mode' => 'Burst Mode',
        'camera' => 'Camera',
        'camera_alt' => 'Camera Alt',
        'camera_front' => 'Front Camera',
        'camera_rear' => 'Rear Camera',
        'camera_roll' => 'Camera Roll',
        'switch_camera' => 'Switch Camera',
        'add_a_photo' => 'Add Photo',
        'photo_camera' => 'Photo Camera',
        'videocam' => 'Video Camera',
        'videocam_off' => 'Video Off',
        'video_library' => 'Video Library',
        'video_call' => 'Video Call',
        'video_settings' => 'Video Settings',
        'movie' => 'Movie',
        'movie_creation' => 'Movie Creation',
        'theaters' => 'Theaters',
        'local_movies' => 'Local Movies',
        'play_arrow' => 'Play',
        'play_circle' => 'Play Circle',
        'play_circle_filled' => 'Play Filled',
        'play_circle_outline' => 'Play Outline',
        'pause' => 'Pause',
        'pause_circle' => 'Pause Circle',
        'pause_circle_filled' => 'Pause Filled',
        'pause_circle_outline' => 'Pause Outline',
        'stop' => 'Stop',
        'stop_circle' => 'Stop Circle',
        'fast_forward' => 'Fast Forward',
        'fast_rewind' => 'Fast Rewind',
        'skip_next' => 'Skip Next',
        'skip_previous' => 'Skip Previous',
        'replay' => 'Replay',
        'replay_10' => 'Replay 10',
        'replay_30' => 'Replay 30',
        'forward_10' => 'Forward 10',
        'forward_30' => 'Forward 30',
        'shuffle' => 'Shuffle',
        'repeat' => 'Repeat',
        'repeat_one' => 'Repeat One',
        'volume_up' => 'Volume Up',
        'volume_down' => 'Volume Down',
        'volume_mute' => 'Volume Mute',
        'volume_off' => 'Volume Off',
        'music_note' => 'Music Note',
        'library_music' => 'Music Library',
        'queue_music' => 'Music Queue',
        'playlist_add' => 'Add to Playlist',
        'playlist_add_check' => 'Playlist Added',
        'playlist_play' => 'Play Playlist',
        'album' => 'Album',
        'artist' => 'Artist',
        'equalizer' => 'Equalizer',
        'graphic_eq' => 'Graphic EQ',
        'mic' => 'Microphone',
        'mic_off' => 'Mic Off',
        'mic_none' => 'Mic None',
        'mic_external_on' => 'External Mic',
        'headset' => 'Headset',
        'headset_mic' => 'Headset Mic',
        'headset_off' => 'Headset Off',
        'hearing' => 'Hearing',
        'surround_sound' => 'Surround Sound',
        'audiotrack' => 'Audio Track',
        'radio' => 'Radio',
        'podcasts' => 'Podcasts',
        'library_add' => 'Add to Library',
        'library_books' => 'Library Books',
        'tv' => 'TV',
        'tv_off' => 'TV Off',
        'live_tv' => 'Live TV',
        'ondemand_video' => 'On Demand Video',
        'video_collection' => 'Video Collection',
        'closed_caption' => 'Closed Caption',
        'closed_caption_disabled' => 'Captions Off',
        'subtitles' => 'Subtitles',
        'subtitles_off' => 'Subtitles Off',
        'cast' => 'Cast',
        'cast_connected' => 'Cast Connected',
        'airplay' => 'AirPlay',
        'screen_rotation' => 'Screen Rotation',
        'stay_current_landscape' => 'Landscape',
        'stay_current_portrait' => 'Portrait',
        'crop' => 'Crop',
        'crop_original' => 'Crop Original',
        'crop_free' => 'Crop Free',
        'crop_landscape' => 'Crop Landscape',
        'crop_portrait' => 'Crop Portrait',
        'crop_square' => 'Crop Square',
        'rotate_90_degrees_ccw' => 'Rotate CCW',
        'rotate_left' => 'Rotate Left',
        'rotate_right' => 'Rotate Right',
        'flip' => 'Flip',
        'transform' => 'Transform',
        'tune' => 'Tune',
        'auto_fix_high' => 'Auto Fix High',
        'auto_fix_normal' => 'Auto Fix Normal',
        'auto_fix_off' => 'Auto Fix Off',
        'auto_awesome' => 'Auto Awesome',
        'photo_filter' => 'Photo Filter',
        'filter' => 'Filter',
        'filter_b_and_w' => 'B&W Filter',
        'filter_vintage' => 'Vintage Filter',
        'filter_drama' => 'Drama Filter',
        'filter_hdr' => 'HDR Filter',
        'filter_none' => 'No Filter',
        'palette' => 'Palette',
        'colorize' => 'Colorize',
        'color_lens' => 'Color Lens',
        'gradient' => 'Gradient',
        'brush' => 'Brush',
        'format_paint' => 'Format Paint',
        'gesture' => 'Gesture',
        'create' => 'Create',
        'edit' => 'Edit',
        'content_cut' => 'Cut',
        'content_copy' => 'Copy',
        'content_paste' => 'Paste',
        'undo' => 'Undo',
        'redo' => 'Redo',
        
        // Navigation & Controls
        'menu' => 'Menu',
        'menu_open' => 'Menu Open',
        'more_horiz' => 'More Horizontal',
        'more_vert' => 'More Vertical',
        'apps' => 'Apps',
        'app_registration' => 'App Registration',
        'dashboard' => 'Dashboard',
        'dashboard_customize' => 'Customize Dashboard',
        'widgets' => 'Widgets',
        'view_list' => 'List View',
        'view_module' => 'Module View',
        'view_quilt' => 'Quilt View',
        'view_stream' => 'Stream View',
        'view_agenda' => 'Agenda View',
        'view_array' => 'Array View',
        'view_carousel' => 'Carousel View',
        'view_column' => 'Column View',
        'view_compact' => 'Compact View',
        'view_comfortable' => 'Comfortable View',
        'view_comfy' => 'Comfy View',
        'view_headline' => 'Headline View',
        'view_sidebar' => 'Sidebar View',
        'view_week' => 'Week View',
        'table_chart' => 'Table Chart',
        'table_rows' => 'Table Rows',
        'reorder' => 'Reorder',
        'drag_handle' => 'Drag Handle',
        'drag_indicator' => 'Drag Indicator',
        'format_list_bulleted' => 'Bulleted List',
        'format_list_numbered' => 'Numbered List',
        'format_indent_increase' => 'Increase Indent',
        'format_indent_decrease' => 'Decrease Indent',
        'format_align_left' => 'Align Left',
        'format_align_center' => 'Align Center',
        'format_align_right' => 'Align Right',
        'format_align_justify' => 'Justify',
        'format_bold' => 'Bold',
        'format_italic' => 'Italic',
        'format_underlined' => 'Underline',
        'format_strikethrough' => 'Strikethrough',
        'format_clear' => 'Clear Format',
        'format_color_text' => 'Text Color',
        'format_color_fill' => 'Fill Color',
        'format_color_reset' => 'Reset Color',
        'format_size' => 'Font Size',
        'text_fields' => 'Text Fields',
        'title' => 'Title',
        'subject' => 'Subject',
        'short_text' => 'Short Text',
        'notes' => 'Notes',
        'article' => 'Article',
        'wrap_text' => 'Wrap Text',
        'vertical_align_top' => 'Align Top',
        'vertical_align_center' => 'Align Center',
        'vertical_align_bottom' => 'Align Bottom',
        'arrow_upward' => 'Arrow Up',
        'arrow_downward' => 'Arrow Down',
        'arrow_back' => 'Arrow Back',
        'arrow_forward' => 'Arrow Forward',
        'arrow_back_ios' => 'Back iOS',
        'arrow_forward_ios' => 'Forward iOS',
        'keyboard_arrow_up' => 'Keyboard Up',
        'keyboard_arrow_down' => 'Keyboard Down',
        'keyboard_arrow_left' => 'Keyboard Left',
        'keyboard_arrow_right' => 'Keyboard Right',
        'expand_less' => 'Expand Less',
        'expand_more' => 'Expand More',
        'chevron_left' => 'Chevron Left',
        'chevron_right' => 'Chevron Right',
        'first_page' => 'First Page',
        'last_page' => 'Last Page',
        'unfold_less' => 'Unfold Less',
        'unfold_more' => 'Unfold More',
        'fullscreen' => 'Fullscreen',
        'fullscreen_exit' => 'Exit Fullscreen',
        'open_in_full' => 'Open in Full',
        'close_fullscreen' => 'Close Fullscreen',
        'fit_screen' => 'Fit Screen',
        'zoom_in' => 'Zoom In',
        'zoom_out' => 'Zoom Out',
        'zoom_out_map' => 'Zoom Out Map',
        'center_focus_strong' => 'Center Focus',
        'center_focus_weak' => 'Center Focus Weak',
        'tab' => 'Tab',
        'tab_unselected' => 'Tab Unselected',
        'keyboard_tab' => 'Keyboard Tab',
        'keyboard_return' => 'Return',
        'keyboard_backspace' => 'Backspace',
        'keyboard_capslock' => 'Caps Lock',
        'space_bar' => 'Space Bar',
        
        // Actions & Controls
        'add' => 'Add',
        'add_circle' => 'Add Circle',
        'add_circle_outline' => 'Add Circle Outline',
        'add_box' => 'Add Box',
        'remove' => 'Remove',
        'remove_circle' => 'Remove Circle',
        'remove_circle_outline' => 'Remove Circle Outline',
        'clear' => 'Clear',
        'close' => 'Close',
        'cancel' => 'Cancel',
        'block' => 'Block',
        'do_not_disturb' => 'Do Not Disturb',
        'do_not_disturb_alt' => 'Do Not Disturb Alt',
        'check' => 'Check',
        'check_circle' => 'Check Circle',
        'check_circle_outline' => 'Check Circle Outline',
        'check_box' => 'Check Box',
        'check_box_outline_blank' => 'Check Box Blank',
        'radio_button_checked' => 'Radio Checked',
        'radio_button_unchecked' => 'Radio Unchecked',
        'toggle_on' => 'Toggle On',
        'toggle_off' => 'Toggle Off',
        'done' => 'Done',
        'done_all' => 'Done All',
        'done_outline' => 'Done Outline',
        'select_all' => 'Select All',
        'deselect' => 'Deselect',
        'indeterminate_check_box' => 'Indeterminate',
        'help' => 'Help',
        'help_outline' => 'Help Outline',
        'help_center' => 'Help Center',
        'contact_support' => 'Contact Support',
        'live_help' => 'Live Help',
        'info' => 'Info',
        'info_outline' => 'Info Outline',
        'error' => 'Error',
        'error_outline' => 'Error Outline',
        'warning' => 'Warning',
        'warning_amber' => 'Warning Amber',
        'report' => 'Report',
        'report_problem' => 'Report Problem',
        'report_off' => 'Report Off',
        'dangerous' => 'Dangerous',
        'verified' => 'Verified',
        'verified_user' => 'Verified User',
        'security' => 'Security',
        'gpp_good' => 'Good',
        'gpp_bad' => 'Bad',
        'gpp_maybe' => 'Maybe',
        'priority_high' => 'High Priority',
        'flag' => 'Flag',
        'flag_circle' => 'Flag Circle',
        'outlined_flag' => 'Outlined Flag',
        'assistant' => 'Assistant',
        'assistant_photo' => 'Assistant Photo',
        'recommend' => 'Recommend',
        'thumb_up' => 'Thumb Up',
        'thumb_down' => 'Thumb Down',
        'thumb_up_alt' => 'Thumb Up Alt',
        'thumb_down_alt' => 'Thumb Down Alt',
        'thumbs_up_down' => 'Thumbs Up Down',
        
        // Social & Favorites
        'favorite' => 'Favorite',
        'favorite_border' => 'Favorite Border',
        'favorite_outline' => 'Favorite Outline',
        'star' => 'Star',
        'star_border' => 'Star Border',
        'star_outline' => 'Star Outline',
        'star_half' => 'Star Half',
        'star_purple500' => 'Purple Star',
        'star_rate' => 'Star Rate',
        'grade' => 'Grade',
        'bookmark' => 'Bookmark',
        'bookmark_border' => 'Bookmark Border',
        'bookmark_outline' => 'Bookmark Outline',
        'bookmark_add' => 'Bookmark Add',
        'bookmark_added' => 'Bookmark Added',
        'bookmark_remove' => 'Bookmark Remove',
        'bookmarks' => 'Bookmarks',
        'turned_in' => 'Turned In',
        'turned_in_not' => 'Not Turned In',
        'label' => 'Label',
        'label_important' => 'Important Label',
        'label_important_outline' => 'Important Outline',
        'label_outline' => 'Label Outline',
        'label_off' => 'Label Off',
        'new_label' => 'New Label',
        'loyalty' => 'Loyalty',
        'card_membership' => 'Membership',
        'volunteer_activism' => 'Volunteer',
        'emoji_emotions' => 'Emotions',
        'emoji_events' => 'Events',
        'emoji_flags' => 'Flags',
        'emoji_food_beverage' => 'Food Beverage',
        'emoji_nature' => 'Nature',
        'emoji_objects' => 'Objects',
        'emoji_people' => 'People',
        'emoji_symbols' => 'Symbols',
        'emoji_transportation' => 'Transportation',
        'celebration' => 'Celebration',
        'party_mode' => 'Party Mode',
        'cake' => 'Cake',
        'local_florist' => 'Florist',
        'attractions' => 'Attractions',
        'festival' => 'Festival',
        'sports' => 'Sports',
        'sports_esports' => 'Esports',
        'sports_handball' => 'Handball',
        'sports_basketball' => 'Basketball',
        'sports_football' => 'Football',
        'sports_soccer' => 'Soccer',
        'sports_tennis' => 'Tennis',
        'sports_golf' => 'Golf',
        'sports_baseball' => 'Baseball',
        'sports_volleyball' => 'Volleyball',
        'sports_hockey' => 'Hockey',
        'sports_cricket' => 'Cricket',
        'sports_rugby' => 'Rugby',
        'pool' => 'Pool',
        'fitness_center' => 'Fitness Center',
        'self_improvement' => 'Self Improvement',
        'spa' => 'Spa',
        'hot_tub' => 'Hot Tub',
        
        // Search & Discovery
        'search' => 'Search',
        'search_off' => 'Search Off',
        'youtube_searched_for' => 'YouTube Search',
        'manage_search' => 'Manage Search',
        'filter_list' => 'Filter List',
        'filter_list_alt' => 'Filter List Alt',
        'filter_alt' => 'Filter Alt',
        'sort' => 'Sort',
        'sort_by_alpha' => 'Sort Alpha',
        'import_export' => 'Import Export',
        'swap_vert' => 'Swap Vertical',
        'swap_horiz' => 'Swap Horizontal',
        'low_priority' => 'Low Priority',
        'trending_up' => 'Trending Up',
        'trending_down' => 'Trending Down',
        'trending_flat' => 'Trending Flat',
        'timeline' => 'Timeline',
        'analytics' => 'Analytics',
        'insights' => 'Insights',
        'query_stats' => 'Query Stats',
        'assessment' => 'Assessment',
        'poll' => 'Poll',
        'quiz' => 'Quiz',
        'grading' => 'Grading',
        'visibility' => 'Visibility',
        'visibility_off' => 'Visibility Off',
        'preview' => 'Preview',
        'pageview' => 'Page View',
        'find_in_page' => 'Find in Page',
        'find_replace' => 'Find Replace',
        'saved_search' => 'Saved Search',
        'history' => 'History',
        'history_toggle_off' => 'History Off',
        'restore' => 'Restore',
        'restore_from_trash' => 'Restore from Trash',
        'restore_page' => 'Restore Page',
        'compare' => 'Compare',
        'compare_arrows' => 'Compare Arrows',
        'difference' => 'Difference',
        'screen_search_desktop' => 'Desktop Search',
        'track_changes' => 'Track Changes',
        'change_history' => 'Change History',
        'update' => 'Update',
        'refresh' => 'Refresh',
        'cached' => 'Cached',
        'offline_bolt' => 'Offline',
        'sync' => 'Sync',
        'sync_alt' => 'Sync Alt',
        'sync_disabled' => 'Sync Disabled',
        'sync_problem' => 'Sync Problem',
        'autorenew' => 'Auto Renew',
        'loop' => 'Loop',
        
        // Files & Documents
        'folder' => 'Folder',
        'folder_open' => 'Folder Open',
        'folder_shared' => 'Folder Shared',
        'folder_special' => 'Folder Special',
        'create_new_folder' => 'New Folder',
        'snippet_folder' => 'Snippet Folder',
        'rule_folder' => 'Rule Folder',
        'topic' => 'Topic',
        'insert_drive_file' => 'Insert File',
        'description' => 'Description',
        'article' => 'Article',
        'text_snippet' => 'Text Snippet',
        'note' => 'Note',
        'note_add' => 'Add Note',
        'note_alt' => 'Note Alt',
        'sticky_note_2' => 'Sticky Note',
        'post_add' => 'Add Post',
        'dynamic_feed' => 'Dynamic Feed',
        'feed' => 'Feed',
        'rss_feed' => 'RSS Feed',
        'list_alt' => 'List Alt',
        'assignment' => 'Assignment',
        'assignment_turned_in' => 'Assignment Turned In',
        'assignment_late' => 'Assignment Late',
        'assignment_return' => 'Assignment Return',
        'assignment_returned' => 'Assignment Returned',
        'assignment_ind' => 'Assignment Individual',
        'task' => 'Task',
        'task_alt' => 'Task Alt',
        'checklist' => 'Checklist',
        'checklist_rtl' => 'Checklist RTL',
        'fact_check' => 'Fact Check',
        'plagiarism' => 'Plagiarism',
        'source' => 'Source',
        'content_copy' => 'Copy',
        'content_cut' => 'Cut',
        'content_paste' => 'Paste',
        'content_paste_off' => 'Paste Off',
        'content_paste_go' => 'Paste Go',
        'content_paste_search' => 'Paste Search',
        'link' => 'Link',
        'link_off' => 'Link Off',
        'attachment' => 'Attachment',
        'attach_file' => 'Attach File',
        'attach_email' => 'Attach Email',
        'download' => 'Download',
        'download_done' => 'Download Done',
        'download_for_offline' => 'Download Offline',
        'downloading' => 'Downloading',
        'upload' => 'Upload',
        'upload_file' => 'Upload File',
        'drive_folder_upload' => 'Upload Folder',
        'cloud' => 'Cloud',
        'cloud_upload' => 'Cloud Upload',
        'cloud_download' => 'Cloud Download',
        'cloud_done' => 'Cloud Done',
        'cloud_off' => 'Cloud Off',
        'cloud_sync' => 'Cloud Sync',
        'cloud_circle' => 'Cloud Circle',
        'cloud_queue' => 'Cloud Queue',
        'backup' => 'Backup',
        'backup_table' => 'Backup Table',
        'restore' => 'Restore',
        'save' => 'Save',
        'save_alt' => 'Save Alt',
        'save_as' => 'Save As',
        'unarchive' => 'Unarchive',
        'inventory' => 'Inventory',
        'inventory_2' => 'Inventory 2',
        'file_copy' => 'File Copy',
        'file_present' => 'File Present',
        'file_download' => 'File Download',
        'file_download_done' => 'File Download Done',
        'file_download_off' => 'File Download Off',
        'file_upload' => 'File Upload',
        'folder_copy' => 'Folder Copy',
        'folder_delete' => 'Folder Delete',
        'folder_zip' => 'Folder Zip',
        'compress' => 'Compress',
        'storage' => 'Storage',
        'sd_storage' => 'SD Storage',
        'usb' => 'USB',
        'sim_card' => 'SIM Card',
        'sd_card' => 'SD Card',
        'memory' => 'Memory',
        'computer' => 'Computer',
        'laptop_chromebook' => 'Chromebook',
        'laptop_mac' => 'MacBook',
        'laptop_windows' => 'Windows Laptop',
        'tablet_mac' => 'iPad',
        'tablet_android' => 'Android Tablet',
        'phone_iphone' => 'iPhone',
        'phone_android' => 'Android Phone',
        'watch' => 'Watch',
        'speaker' => 'Speaker',
        'speaker_group' => 'Speaker Group',
        'headset' => 'Headset',
        'keyboard' => 'Keyboard',
        'mouse' => 'Mouse',
        'scanner' => 'Scanner',
        'print' => 'Print',
        'local_printshop' => 'Print Shop',
        
        // Settings & Tools
        'settings' => 'Settings',
        'settings_applications' => 'App Settings',
        'settings_backup_restore' => 'Backup Settings',
        'settings_bluetooth' => 'Bluetooth Settings',
        'settings_brightness' => 'Brightness Settings',
        'settings_cell' => 'Cell Settings',
        'settings_ethernet' => 'Ethernet Settings',
        'settings_input_antenna' => 'Antenna Settings',
        'settings_input_component' => 'Component Settings',
        'settings_input_composite' => 'Composite Settings',
        'settings_input_hdmi' => 'HDMI Settings',
        'settings_input_svideo' => 'S-Video Settings',
        'settings_overscan' => 'Overscan Settings',
        'settings_phone' => 'Phone Settings',
        'settings_power' => 'Power Settings',
        'settings_remote' => 'Remote Settings',
        'settings_suggest' => 'Suggest Settings',
        'settings_system_daydream' => 'Daydream Settings',
        'settings_voice' => 'Voice Settings',
        'build' => 'Build',
        'build_circle' => 'Build Circle',
        'construction' => 'Construction',
        'engineering' => 'Engineering',
        'handyman' => 'Handyman',
        'home_repair_service' => 'Home Repair',
        'plumbing' => 'Plumbing',
        'electrical_services' => 'Electrical',
        'miscellaneous_services' => 'Miscellaneous',
        'design_services' => 'Design Services',
        'architecture' => 'Architecture',
        'carpenter' => 'Carpenter',
        'cleaning_services' => 'Cleaning',
        'pest_control' => 'Pest Control',
        'roofing' => 'Roofing',
        'hvac' => 'HVAC',
        'yard_work' => 'Yard Work',
        'landscape' => 'Landscape',
        'agriculture' => 'Agriculture',
        'precision_manufacturing' => 'Manufacturing',
        'factory' => 'Factory',
        'inventory_2' => 'Inventory',
        'science' => 'Science',
        'biotech' => 'Biotech',
        'psychology' => 'Psychology',
        'psychology_alt' => 'Psychology Alt',
        'emoji_objects' => 'Objects',
        'lightbulb' => 'Lightbulb',
        'lightbulb_outline' => 'Lightbulb Outline',
        'wb_incandescent' => 'Incandescent',
        'tips_and_updates' => 'Tips',
        'auto_fix_high' => 'Auto Fix High',
        'auto_fix_normal' => 'Auto Fix Normal',
        'auto_fix_off' => 'Auto Fix Off',
        'tune' => 'Tune',
        'build' => 'Build',
        'developer_mode' => 'Developer Mode',
        'code' => 'Code',
        'terminal' => 'Terminal',
        'integration_instructions' => 'Integration',
        'api' => 'API',
        'webhook' => 'Webhook',
        'schema' => 'Schema',
        'data_object' => 'Data Object',
        'functions' => 'Functions',
        'javascript' => 'JavaScript',
        'css' => 'CSS',
        'html' => 'HTML',
        'extension' => 'Extension',
        'plugin' => 'Plugin',
        'widgets' => 'Widgets',
        'dashboard' => 'Dashboard',
        'admin_panel_settings' => 'Admin Panel',
        'control_panel' => 'Control Panel',
        'tune' => 'Tune',
        'equalizer' => 'Equalizer',
        'graphic_eq' => 'Graphic EQ',
        'linear_scale' => 'Linear Scale',
        'multiline_chart' => 'Multiline Chart',
        'show_chart' => 'Show Chart',
        'trending_up' => 'Trending Up',
        'trending_down' => 'Trending Down',
        'trending_flat' => 'Trending Flat',
        'leaderboard' => 'Leaderboard',
        'speed' => 'Speed',
        'shutter_speed' => 'Shutter Speed',
        'timer' => 'Timer',
        'timer_off' => 'Timer Off',
        'timer_3' => 'Timer 3',
        'timer_10' => 'Timer 10',
        'timelapse' => 'Timelapse',
        'slow_motion_video' => 'Slow Motion',
        'av_timer' => 'AV Timer',
        
        // Security & Privacy
        'lock' => 'Lock',
        'lock_open' => 'Lock Open',
        'lock_outline' => 'Lock Outline',
        'lock_clock' => 'Lock Clock',
        'vpn_lock' => 'VPN Lock',
        'enhanced_encryption' => 'Enhanced Encryption',
        'no_encryption' => 'No Encryption',
        'gpp_good' => 'Good',
        'gpp_bad' => 'Bad',
        'gpp_maybe' => 'Maybe',
        'verified' => 'Verified',
        'verified_user' => 'Verified User',
        'admin_panel_settings' => 'Admin',
        'supervisor_account' => 'Supervisor',
        'manage_accounts' => 'Manage Accounts',
        'shield' => 'Shield',
        'policy' => 'Policy',
        'privacy_tip' => 'Privacy Tip',
        'security' => 'Security',
        'fingerprint' => 'Fingerprint',
        'key' => 'Key',
        'key_off' => 'Key Off',
        'password' => 'Password',
        'pin' => 'PIN',
        'pattern' => 'Pattern',
        'face' => 'Face',
        'face_unlock' => 'Face Unlock',
        'remove_moderator' => 'Remove Moderator',
        'person_off' => 'Person Off',
        'person_remove' => 'Person Remove',
        'block' => 'Block',
        'visibility' => 'Visibility',
        'visibility_off' => 'Visibility Off',
        'remove_red_eye' => 'Remove Red Eye',
        'https' => 'HTTPS',
        'http' => 'HTTP',
        'vpn_key' => 'VPN Key',
        'vpn_key_off' => 'VPN Key Off',
        'wifi_protected_setup' => 'WiFi Protected',
        'network_locked' => 'Network Locked',
        'network_check' => 'Network Check',
        'power_off' => 'Power Off',
        'power_settings_new' => 'Power Settings',
        'logout' => 'Logout',
        'login' => 'Login',
        'app_blocking' => 'App Blocking',
        'disabled_by_default' => 'Disabled',
        'do_not_disturb' => 'Do Not Disturb',
        'do_not_disturb_alt' => 'Do Not Disturb Alt',
        'do_not_disturb_off' => 'Do Not Disturb Off',
        'do_not_disturb_on' => 'Do Not Disturb On',
        'phonelink_erase' => 'Phonelink Erase',
        'phonelink_lock' => 'Phonelink Lock',
        'screen_lock_landscape' => 'Screen Lock Landscape',
        'screen_lock_portrait' => 'Screen Lock Portrait',
        'screen_lock_rotation' => 'Screen Lock Rotation',
        'security_update' => 'Security Update',
        'security_update_good' => 'Security Update Good',
        'security_update_warning' => 'Security Update Warning',
        'gavel' => 'Gavel',
        'balance' => 'Balance',
        'account_balance' => 'Account Balance',
        'copyright' => 'Copyright',
        'policy' => 'Policy',
        
        // Time & Calendar
        'access_time' => 'Access Time',
        'access_time_filled' => 'Access Time Filled',
        'schedule' => 'Schedule',
        'today' => 'Today',
        'date_range' => 'Date Range',
        'event' => 'Event',
        'event_available' => 'Event Available',
        'event_busy' => 'Event Busy',
        'event_note' => 'Event Note',
        'event_seat' => 'Event Seat',
        'calendar_today' => 'Calendar Today',
        'calendar_month' => 'Calendar Month',
        'calendar_view_day' => 'Calendar Day',
        'calendar_view_week' => 'Calendar Week',
        'calendar_view_month' => 'Calendar Month View',
        'perm_contact_calendar' => 'Contact Calendar',
        'watch_later' => 'Watch Later',
        'query_builder' => 'Query Builder',
        'alarm' => 'Alarm',
        'alarm_add' => 'Add Alarm',
        'alarm_off' => 'Alarm Off',
        'alarm_on' => 'Alarm On',
        'snooze' => 'Snooze',
        'timer' => 'Timer',
        'timer_off' => 'Timer Off',
        'hourglass_empty' => 'Hourglass Empty',
        'hourglass_full' => 'Hourglass Full',
        'hourglass_top' => 'Hourglass Top',
        'hourglass_bottom' => 'Hourglass Bottom',
        'more_time' => 'More Time',
        'pending' => 'Pending',
        'pending_actions' => 'Pending Actions',
        'schedule_send' => 'Schedule Send',
        'upcoming' => 'Upcoming',
        'update' => 'Update',
        'history' => 'History',
        'access_alarms' => 'Access Alarms',
        'timelapse' => 'Timelapse',
        'time_to_leave' => 'Time to Leave',
        'av_timer' => 'AV Timer',
        'free_breakfast' => 'Free Breakfast',
        'schedule' => 'Schedule',
        'date_range' => 'Date Range',
        'event_repeat' => 'Event Repeat',
        'repeat' => 'Repeat',
        'repeat_one' => 'Repeat One',
        'repeat_on' => 'Repeat On',
        'repeat_one_on' => 'Repeat One On',
        'workspaces' => 'Workspaces',
        'weekend' => 'Weekend',
        'work' => 'Work',
        'work_off' => 'Work Off',
        'work_outline' => 'Work Outline',
        'business_center' => 'Business Center',
        'free_cancellation' => 'Free Cancellation',
        'cancel_presentation' => 'Cancel Presentation',
        'cancel_schedule_send' => 'Cancel Schedule',
        'next_week' => 'Next Week',
        'next_plan' => 'Next Plan',
        'today' => 'Today',
        'tomorrow' => 'Tomorrow',
        'date_range' => 'Date Range',
        'date_range_outlined' => 'Date Range Outlined',
        'start' => 'Start',
        'not_started' => 'Not Started',
        'pause_presentation' => 'Pause Presentation',
        'play_lesson' => 'Play Lesson',
        'skip_next' => 'Skip Next',
        'skip_previous' => 'Skip Previous'
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
        'house-door-fill' => 'House Door Fill',
        'house-fill' => 'House Fill',
        'house-heart' => 'House Heart',
        'house-heart-fill' => 'House Heart Fill',
        'building' => 'Building',
        'building-fill' => 'Building Fill',
        'buildings' => 'Buildings',
        'buildings-fill' => 'Buildings Fill',
        'geo-alt' => 'Location',
        'geo-alt-fill' => 'Location Fill',
        'geo' => 'Geo',
        'geo-fill' => 'Geo Fill',
        'map' => 'Map',
        'map-fill' => 'Map Fill',
        'compass' => 'Compass',
        'compass-fill' => 'Compass Fill',
        'signpost' => 'Signpost',
        'signpost-fill' => 'Signpost Fill',
        'signpost-2' => 'Signpost 2',
        'signpost-2-fill' => 'Signpost 2 Fill',
        'signpost-split' => 'Signpost Split',
        'signpost-split-fill' => 'Signpost Split Fill',
        
        // Users & People
        'person' => 'Person',
        'person-fill' => 'Person Fill',
        'person-circle' => 'Person Circle',
        'person-square' => 'Person Square',
        'person-badge' => 'Person Badge',
        'person-badge-fill' => 'Person Badge Fill',
        'person-plus' => 'Person Plus',
        'person-plus-fill' => 'Person Plus Fill',
        'person-dash' => 'Person Dash',
        'person-dash-fill' => 'Person Dash Fill',
        'person-check' => 'Person Check',
        'person-check-fill' => 'Person Check Fill',
        'person-x' => 'Person X',
        'person-x-fill' => 'Person X Fill',
        'people' => 'People',
        'people-fill' => 'People Fill',
        'person-hearts' => 'Person Hearts',
        'person-add' => 'Person Add',
        'person-workspace' => 'Person Workspace',
        'person-video' => 'Person Video',
        'person-video2' => 'Person Video 2',
        'person-video3' => 'Person Video 3',
        'person-rolodex' => 'Person Rolodex',
        'person-lines-fill' => 'Person Lines Fill',
        'person-vcard' => 'Person VCard',
        'person-vcard-fill' => 'Person VCard Fill',
        
        // Shopping & Commerce
        'cart' => 'Cart',
        'cart-fill' => 'Cart Fill',
        'cart-plus' => 'Cart Plus',
        'cart-plus-fill' => 'Cart Plus Fill',
        'cart-dash' => 'Cart Dash',
        'cart-dash-fill' => 'Cart Dash Fill',
        'cart-check' => 'Cart Check',
        'cart-check-fill' => 'Cart Check Fill',
        'cart-x' => 'Cart X',
        'cart-x-fill' => 'Cart X Fill',
        'cart2' => 'Cart 2',
        'cart3' => 'Cart 3',
        'cart4' => 'Cart 4',
        'basket' => 'Basket',
        'basket-fill' => 'Basket Fill',
        'basket2' => 'Basket 2',
        'basket2-fill' => 'Basket 2 Fill',
        'basket3' => 'Basket 3',
        'basket3-fill' => 'Basket 3 Fill',
        'bag' => 'Bag',
        'bag-fill' => 'Bag Fill',
        'bag-plus' => 'Bag Plus',
        'bag-plus-fill' => 'Bag Plus Fill',
        'bag-dash' => 'Bag Dash',
        'bag-dash-fill' => 'Bag Dash Fill',
        'bag-check' => 'Bag Check',
        'bag-check-fill' => 'Bag Check Fill',
        'bag-x' => 'Bag X',
        'bag-x-fill' => 'Bag X Fill',
        'handbag' => 'Handbag',
        'handbag-fill' => 'Handbag Fill',
        'shop' => 'Shop',
        'shop-window' => 'Shop Window',
        'currency-dollar' => 'Dollar',
        'currency-euro' => 'Euro',
        'currency-pound' => 'Pound',
        'currency-yen' => 'Yen',
        'currency-bitcoin' => 'Bitcoin',
        'currency-exchange' => 'Currency Exchange',
        'cash' => 'Cash',
        'cash-stack' => 'Cash Stack',
        'cash-coin' => 'Cash Coin',
        'coin' => 'Coin',
        'credit-card' => 'Credit Card',
        'credit-card-fill' => 'Credit Card Fill',
        'credit-card-2-back' => 'Credit Card Back',
        'credit-card-2-back-fill' => 'Credit Card Back Fill',
        'credit-card-2-front' => 'Credit Card Front',
        'credit-card-2-front-fill' => 'Credit Card Front Fill',
        'wallet' => 'Wallet',
        'wallet-fill' => 'Wallet Fill',
        'wallet2' => 'Wallet 2',
        'piggy-bank' => 'Piggy Bank',
        'piggy-bank-fill' => 'Piggy Bank Fill',
        'safe' => 'Safe',
        'safe-fill' => 'Safe Fill',
        'safe2' => 'Safe 2',
        'safe2-fill' => 'Safe 2 Fill',
        'receipt' => 'Receipt',
        'receipt-cutoff' => 'Receipt Cutoff',
        'tag' => 'Tag',
        'tag-fill' => 'Tag Fill',
        'tags' => 'Tags',
        'tags-fill' => 'Tags Fill',
        'percent' => 'Percent',
        'gift' => 'Gift',
        'gift-fill' => 'Gift Fill',
        
        // Communication & Social
        'envelope' => 'Envelope',
        'envelope-fill' => 'Envelope Fill',
        'envelope-open' => 'Envelope Open',
        'envelope-open-fill' => 'Envelope Open Fill',
        'envelope-plus' => 'Envelope Plus',
        'envelope-plus-fill' => 'Envelope Plus Fill',
        'envelope-dash' => 'Envelope Dash',
        'envelope-dash-fill' => 'Envelope Dash Fill',
        'envelope-check' => 'Envelope Check',
        'envelope-check-fill' => 'Envelope Check Fill',
        'envelope-x' => 'Envelope X',
        'envelope-x-fill' => 'Envelope X Fill',
        'envelope-heart' => 'Envelope Heart',
        'envelope-heart-fill' => 'Envelope Heart Fill',
        'envelope-exclamation' => 'Envelope Exclamation',
        'envelope-exclamation-fill' => 'Envelope Exclamation Fill',
        'envelope-slash' => 'Envelope Slash',
        'envelope-slash-fill' => 'Envelope Slash Fill',
        'postcard' => 'Postcard',
        'postcard-fill' => 'Postcard Fill',
        'postcard-heart' => 'Postcard Heart',
        'postcard-heart-fill' => 'Postcard Heart Fill',
        'mailbox' => 'Mailbox',
        'mailbox-flag' => 'Mailbox Flag',
        'mailbox2' => 'Mailbox 2',
        'mailbox2-flag' => 'Mailbox 2 Flag',
        'inbox' => 'Inbox',
        'inbox-fill' => 'Inbox Fill',
        'inboxes' => 'Inboxes',
        'inboxes-fill' => 'Inboxes Fill',
        'send' => 'Send',
        'send-fill' => 'Send Fill',
        'send-plus' => 'Send Plus',
        'send-plus-fill' => 'Send Plus Fill',
        'send-dash' => 'Send Dash',
        'send-dash-fill' => 'Send Dash Fill',
        'send-check' => 'Send Check',
        'send-check-fill' => 'Send Check Fill',
        'send-x' => 'Send X',
        'send-x-fill' => 'Send X Fill',
        'phone' => 'Phone',
        'phone-fill' => 'Phone Fill',
        'phone-flip' => 'Phone Flip',
        'phone-landscape' => 'Phone Landscape',
        'phone-landscape-fill' => 'Phone Landscape Fill',
        'phone-vibrate' => 'Phone Vibrate',
        'phone-vibrate-fill' => 'Phone Vibrate Fill',
        'telephone' => 'Telephone',
        'telephone-fill' => 'Telephone Fill',
        'telephone-forward' => 'Telephone Forward',
        'telephone-forward-fill' => 'Telephone Forward Fill',
        'telephone-inbound' => 'Telephone Inbound',
        'telephone-inbound-fill' => 'Telephone Inbound Fill',
        'telephone-outbound' => 'Telephone Outbound',
        'telephone-outbound-fill' => 'Telephone Outbound Fill',
        'telephone-minus' => 'Telephone Minus',
        'telephone-minus-fill' => 'Telephone Minus Fill',
        'telephone-plus' => 'Telephone Plus',
        'telephone-plus-fill' => 'Telephone Plus Fill',
        'telephone-x' => 'Telephone X',
        'telephone-x-fill' => 'Telephone X Fill',
        'chat' => 'Chat',
        'chat-fill' => 'Chat Fill',
        'chat-dots' => 'Chat Dots',
        'chat-dots-fill' => 'Chat Dots Fill',
        'chat-heart' => 'Chat Heart',
        'chat-heart-fill' => 'Chat Heart Fill',
        'chat-left' => 'Chat Left',
        'chat-left-fill' => 'Chat Left Fill',
        'chat-left-dots' => 'Chat Left Dots',
        'chat-left-dots-fill' => 'Chat Left Dots Fill',
        'chat-left-heart' => 'Chat Left Heart',
        'chat-left-heart-fill' => 'Chat Left Heart Fill',
        'chat-left-quote' => 'Chat Left Quote',
        'chat-left-quote-fill' => 'Chat Left Quote Fill',
        'chat-left-text' => 'Chat Left Text',
        'chat-left-text-fill' => 'Chat Left Text Fill',
        'chat-quote' => 'Chat Quote',
        'chat-quote-fill' => 'Chat Quote Fill',
        'chat-right' => 'Chat Right',
        'chat-right-fill' => 'Chat Right Fill',
        'chat-right-dots' => 'Chat Right Dots',
        'chat-right-dots-fill' => 'Chat Right Dots Fill',
        'chat-right-heart' => 'Chat Right Heart',
        'chat-right-heart-fill' => 'Chat Right Heart Fill',
        'chat-right-quote' => 'Chat Right Quote',
        'chat-right-quote-fill' => 'Chat Right Quote Fill',
        'chat-right-text' => 'Chat Right Text',
        'chat-right-text-fill' => 'Chat Right Text Fill',
        'chat-square' => 'Chat Square',
        'chat-square-fill' => 'Chat Square Fill',
        'chat-square-dots' => 'Chat Square Dots',
        'chat-square-dots-fill' => 'Chat Square Dots Fill',
        'chat-square-heart' => 'Chat Square Heart',
        'chat-square-heart-fill' => 'Chat Square Heart Fill',
        'chat-square-quote' => 'Chat Square Quote',
        'chat-square-quote-fill' => 'Chat Square Quote Fill',
        'chat-square-text' => 'Chat Square Text',
        'chat-square-text-fill' => 'Chat Square Text Fill',
        'chat-text' => 'Chat Text',
        'chat-text-fill' => 'Chat Text Fill',
        'messenger' => 'Messenger',
        'whatsapp' => 'WhatsApp',
        'wechat' => 'WeChat',
        'telegram' => 'Telegram',
        'skype' => 'Skype',
        'discord' => 'Discord',
        'slack' => 'Slack',
        'share' => 'Share',
        'share-fill' => 'Share Fill',
        
        // Content & Media
        'image' => 'Image',
        'image-fill' => 'Image Fill',
        'image-alt' => 'Image Alt',
        'images' => 'Images',
        'card-image' => 'Card Image',
        'file-earmark-image' => 'File Image',
        'file-earmark-image-fill' => 'File Image Fill',
        'camera' => 'Camera',
        'camera-fill' => 'Camera Fill',
        'camera2' => 'Camera 2',
        'camera-reels' => 'Camera Reels',
        'camera-reels-fill' => 'Camera Reels Fill',
        'camera-video' => 'Camera Video',
        'camera-video-fill' => 'Camera Video Fill',
        'camera-video-off' => 'Camera Video Off',
        'camera-video-off-fill' => 'Camera Video Off Fill',
        'webcam' => 'Webcam',
        'webcam-fill' => 'Webcam Fill',
        'film' => 'Film',
        'collection' => 'Collection',
        'collection-fill' => 'Collection Fill',
        'collection-play' => 'Collection Play',
        'collection-play-fill' => 'Collection Play Fill',
        'easel' => 'Easel',
        'easel-fill' => 'Easel Fill',
        'easel2' => 'Easel 2',
        'easel2-fill' => 'Easel 2 Fill',
        'easel3' => 'Easel 3',
        'easel3-fill' => 'Easel 3 Fill',
        'palette' => 'Palette',
        'palette-fill' => 'Palette Fill',
        'palette2' => 'Palette 2',
        'brush' => 'Brush',
        'brush-fill' => 'Brush Fill',
        'paint-bucket' => 'Paint Bucket',
        'eyedropper' => 'Eyedropper',
        'music-note' => 'Music Note',
        'music-note-beamed' => 'Music Note Beamed',
        'music-note-list' => 'Music Note List',
        'music-player' => 'Music Player',
        'music-player-fill' => 'Music Player Fill',
        'vinyl' => 'Vinyl',
        'vinyl-fill' => 'Vinyl Fill',
        'disc' => 'Disc',
        'disc-fill' => 'Disc Fill',
        'boombox' => 'Boombox',
        'boombox-fill' => 'Boombox Fill',
        'speaker' => 'Speaker',
        'speakers' => 'Speakers',
        'headphones' => 'Headphones',
        'earbuds' => 'Earbuds',
        'mic' => 'Microphone',
        'mic-fill' => 'Microphone Fill',
        'mic-mute' => 'Microphone Mute',
        'mic-mute-fill' => 'Microphone Mute Fill',
        'soundwave' => 'Soundwave',
        'volume-down' => 'Volume Down',
        'volume-down-fill' => 'Volume Down Fill',
        'volume-mute' => 'Volume Mute',
        'volume-mute-fill' => 'Volume Mute Fill',
        'volume-off' => 'Volume Off',
        'volume-off-fill' => 'Volume Off Fill',
        'volume-up' => 'Volume Up',
        'volume-up-fill' => 'Volume Up Fill',
        'play' => 'Play',
        'play-fill' => 'Play Fill',
        'play-btn' => 'Play Button',
        'play-btn-fill' => 'Play Button Fill',
        'play-circle' => 'Play Circle',
        'play-circle-fill' => 'Play Circle Fill',
        'pause' => 'Pause',
        'pause-fill' => 'Pause Fill',
        'pause-btn' => 'Pause Button',
        'pause-btn-fill' => 'Pause Button Fill',
        'pause-circle' => 'Pause Circle',
        'pause-circle-fill' => 'Pause Circle Fill',
        'stop' => 'Stop',
        'stop-fill' => 'Stop Fill',
        'stop-btn' => 'Stop Button',
        'stop-btn-fill' => 'Stop Button Fill',
        'stop-circle' => 'Stop Circle',
        'stop-circle-fill' => 'Stop Circle Fill',
        'record' => 'Record',
        'record-fill' => 'Record Fill',
        'record-btn' => 'Record Button',
        'record-btn-fill' => 'Record Button Fill',
        'record-circle' => 'Record Circle',
        'record-circle-fill' => 'Record Circle Fill',
        'record2' => 'Record 2',
        'record2-fill' => 'Record 2 Fill',
        'eject' => 'Eject',
        'eject-fill' => 'Eject Fill',
        'fast-forward' => 'Fast Forward',
        'fast-forward-fill' => 'Fast Forward Fill',
        'fast-forward-btn' => 'Fast Forward Button',
        'fast-forward-btn-fill' => 'Fast Forward Button Fill',
        'fast-forward-circle' => 'Fast Forward Circle',
        'fast-forward-circle-fill' => 'Fast Forward Circle Fill',
        'rewind' => 'Rewind',
        'rewind-fill' => 'Rewind Fill',
        'rewind-btn' => 'Rewind Button',
        'rewind-btn-fill' => 'Rewind Button Fill',
        'rewind-circle' => 'Rewind Circle',
        'rewind-circle-fill' => 'Rewind Circle Fill',
        'skip-backward' => 'Skip Backward',
        'skip-backward-fill' => 'Skip Backward Fill',
        'skip-backward-btn' => 'Skip Backward Button',
        'skip-backward-btn-fill' => 'Skip Backward Button Fill',
        'skip-backward-circle' => 'Skip Backward Circle',
        'skip-backward-circle-fill' => 'Skip Backward Circle Fill',
        'skip-end' => 'Skip End',
        'skip-end-fill' => 'Skip End Fill',
        'skip-end-btn' => 'Skip End Button',
        'skip-end-btn-fill' => 'Skip End Button Fill',
        'skip-end-circle' => 'Skip End Circle',
        'skip-end-circle-fill' => 'Skip End Circle Fill',
        'skip-forward' => 'Skip Forward',
        'skip-forward-fill' => 'Skip Forward Fill',
        'skip-forward-btn' => 'Skip Forward Button',
        'skip-forward-btn-fill' => 'Skip Forward Button Fill',
        'skip-forward-circle' => 'Skip Forward Circle',
        'skip-forward-circle-fill' => 'Skip Forward Circle Fill',
        'skip-start' => 'Skip Start',
        'skip-start-fill' => 'Skip Start Fill',
        'skip-start-btn' => 'Skip Start Button',
        'skip-start-btn-fill' => 'Skip Start Button Fill',
        'skip-start-circle' => 'Skip Start Circle',
        'skip-start-circle-fill' => 'Skip Start Circle Fill',
        'shuffle' => 'Shuffle',
        'repeat' => 'Repeat',
        'repeat-1' => 'Repeat 1',
        'cassette' => 'Cassette',
        'cassette-fill' => 'Cassette Fill',
        'tv' => 'TV',
        'tv-fill' => 'TV Fill',
        'display' => 'Display',
        'display-fill' => 'Display Fill',
        'laptop' => 'Laptop',
        'laptop-fill' => 'Laptop Fill',
        'tablet' => 'Tablet',
        'tablet-fill' => 'Tablet Fill',
        'tablet-landscape' => 'Tablet Landscape',
        'tablet-landscape-fill' => 'Tablet Landscape Fill',
        'smartwatch' => 'Smartwatch',
        'watch' => 'Watch',
        'cast' => 'Cast',
        'projector' => 'Projector',
        'projector-fill' => 'Projector Fill',
        
        // Navigation & Controls
        'list' => 'List',
        'list-ul' => 'List UL',
        'list-ol' => 'List OL',
        'list-check' => 'List Check',
        'list-nested' => 'List Nested',
        'list-stars' => 'List Stars',
        'list-task' => 'List Task',
        'justify' => 'Justify',
        'justify-left' => 'Justify Left',
        'justify-right' => 'Justify Right',
        'text-left' => 'Text Left',
        'text-center' => 'Text Center',
        'text-right' => 'Text Right',
        'text-indent-left' => 'Text Indent Left',
        'text-indent-right' => 'Text Indent Right',
        'text-paragraph' => 'Text Paragraph',
        'text-wrap' => 'Text Wrap',
        'grid' => 'Grid',
        'grid-fill' => 'Grid Fill',
        'grid-1x2' => 'Grid 1x2',
        'grid-1x2-fill' => 'Grid 1x2 Fill',
        'grid-3x2' => 'Grid 3x2',
        'grid-3x2-fill' => 'Grid 3x2 Fill',
        'grid-3x2-gap' => 'Grid 3x2 Gap',
        'grid-3x2-gap-fill' => 'Grid 3x2 Gap Fill',
        'grid-3x3' => 'Grid 3x3',
        'grid-3x3-fill' => 'Grid 3x3 Fill',
        'grid-3x3-gap' => 'Grid 3x3 Gap',
        'grid-3x3-gap-fill' => 'Grid 3x3 Gap Fill',
        'view-list' => 'View List',
        'view-stacked' => 'View Stacked',
        'kanban' => 'Kanban',
        'kanban-fill' => 'Kanban Fill',
        'columns' => 'Columns',
        'columns-gap' => 'Columns Gap',
        'layout-sidebar' => 'Layout Sidebar',
        'layout-sidebar-inset' => 'Layout Sidebar Inset',
        'layout-sidebar-inset-reverse' => 'Layout Sidebar Inset Reverse',
        'layout-sidebar-reverse' => 'Layout Sidebar Reverse',
        'layout-split' => 'Layout Split',
        'layout-text-sidebar' => 'Layout Text Sidebar',
        'layout-text-sidebar-reverse' => 'Layout Text Sidebar Reverse',
        'layout-text-window' => 'Layout Text Window',
        'layout-text-window-reverse' => 'Layout Text Window Reverse',
        'layout-three-columns' => 'Layout Three Columns',
        'layout-wtf' => 'Layout WTF',
        'distribute-horizontal' => 'Distribute Horizontal',
        'distribute-vertical' => 'Distribute Vertical',
        'align-bottom' => 'Align Bottom',
        'align-center' => 'Align Center',
        'align-end' => 'Align End',
        'align-middle' => 'Align Middle',
        'align-start' => 'Align Start',
        'align-top' => 'Align Top',
        'arrows' => 'Arrows',
        'arrows-angle-contract' => 'Arrows Angle Contract',
        'arrows-angle-expand' => 'Arrows Angle Expand',
        'arrows-collapse' => 'Arrows Collapse',
        'arrows-expand' => 'Arrows Expand',
        'arrows-fullscreen' => 'Arrows Fullscreen',
        'arrows-move' => 'Arrows Move',
        'arrow-up' => 'Arrow Up',
        'arrow-up-circle' => 'Arrow Up Circle',
        'arrow-up-circle-fill' => 'Arrow Up Circle Fill',
        'arrow-up-left' => 'Arrow Up Left',
        'arrow-up-left-circle' => 'Arrow Up Left Circle',
        'arrow-up-left-circle-fill' => 'Arrow Up Left Circle Fill',
        'arrow-up-left-square' => 'Arrow Up Left Square',
        'arrow-up-left-square-fill' => 'Arrow Up Left Square Fill',
        'arrow-up-right' => 'Arrow Up Right',
        'arrow-up-right-circle' => 'Arrow Up Right Circle',
        'arrow-up-right-circle-fill' => 'Arrow Up Right Circle Fill',
        'arrow-up-right-square' => 'Arrow Up Right Square',
        'arrow-up-right-square-fill' => 'Arrow Up Right Square Fill',
        'arrow-up-short' => 'Arrow Up Short',
        'arrow-up-square' => 'Arrow Up Square',
        'arrow-up-square-fill' => 'Arrow Up Square Fill',
        'arrow-down' => 'Arrow Down',
        'arrow-down-circle' => 'Arrow Down Circle',
        'arrow-down-circle-fill' => 'Arrow Down Circle Fill',
        'arrow-down-left' => 'Arrow Down Left',
        'arrow-down-left-circle' => 'Arrow Down Left Circle',
        'arrow-down-left-circle-fill' => 'Arrow Down Left Circle Fill',
        'arrow-down-left-square' => 'Arrow Down Left Square',
        'arrow-down-left-square-fill' => 'Arrow Down Left Square Fill',
        'arrow-down-right' => 'Arrow Down Right',
        'arrow-down-right-circle' => 'Arrow Down Right Circle',
        'arrow-down-right-circle-fill' => 'Arrow Down Right Circle Fill',
        'arrow-down-right-square' => 'Arrow Down Right Square',
        'arrow-down-right-square-fill' => 'Arrow Down Right Square Fill',
        'arrow-down-short' => 'Arrow Down Short',
        'arrow-down-square' => 'Arrow Down Square',
        'arrow-down-square-fill' => 'Arrow Down Square Fill',
        'arrow-left' => 'Arrow Left',
        'arrow-left-circle' => 'Arrow Left Circle',
        'arrow-left-circle-fill' => 'Arrow Left Circle Fill',
        'arrow-left-short' => 'Arrow Left Short',
        'arrow-left-square' => 'Arrow Left Square',
        'arrow-left-square-fill' => 'Arrow Left Square Fill',
        'arrow-right' => 'Arrow Right',
        'arrow-right-circle' => 'Arrow Right Circle',
        'arrow-right-circle-fill' => 'Arrow Right Circle Fill',
        'arrow-right-short' => 'Arrow Right Short',
        'arrow-right-square' => 'Arrow Right Square',
        'arrow-right-square-fill' => 'Arrow Right Square Fill',
        'chevron-up' => 'Chevron Up',
        'chevron-down' => 'Chevron Down',
        'chevron-left' => 'Chevron Left',
        'chevron-right' => 'Chevron Right',
        'chevron-bar-up' => 'Chevron Bar Up',
        'chevron-bar-down' => 'Chevron Bar Down',
        'chevron-bar-left' => 'Chevron Bar Left',
        'chevron-bar-right' => 'Chevron Bar Right',
        'chevron-compact-up' => 'Chevron Compact Up',
        'chevron-compact-down' => 'Chevron Compact Down',
        'chevron-compact-left' => 'Chevron Compact Left',
        'chevron-compact-right' => 'Chevron Compact Right',
        'chevron-double-up' => 'Chevron Double Up',
        'chevron-double-down' => 'Chevron Double Down',
        'chevron-double-left' => 'Chevron Double Left',
        'chevron-double-right' => 'Chevron Double Right',
        'chevron-expand' => 'Chevron Expand',
        'chevron-contract' => 'Chevron Contract',
        'caret-up' => 'Caret Up',
        'caret-up-fill' => 'Caret Up Fill',
        'caret-up-square' => 'Caret Up Square',
        'caret-up-square-fill' => 'Caret Up Square Fill',
        'caret-down' => 'Caret Down',
        'caret-down-fill' => 'Caret Down Fill',
        'caret-down-square' => 'Caret Down Square',
        'caret-down-square-fill' => 'Caret Down Square Fill',
        'caret-left' => 'Caret Left',
        'caret-left-fill' => 'Caret Left Fill',
        'caret-left-square' => 'Caret Left Square',
        'caret-left-square-fill' => 'Caret Left Square Fill',
        'caret-right' => 'Caret Right',
        'caret-right-fill' => 'Caret Right Fill',
        'caret-right-square' => 'Caret Right Square',
        'caret-right-square-fill' => 'Caret Right Square Fill',
        'triangle' => 'Triangle',
        'triangle-fill' => 'Triangle Fill',
        'triangle-half' => 'Triangle Half',
        'fullscreen' => 'Fullscreen',
        'fullscreen-exit' => 'Fullscreen Exit',
        'pip' => 'Picture in Picture',
        'pip-fill' => 'Picture in Picture Fill',
        'aspect-ratio' => 'Aspect Ratio',
        'aspect-ratio-fill' => 'Aspect Ratio Fill',
        'zoom-in' => 'Zoom In',
        'zoom-out' => 'Zoom Out',
        'border' => 'Border',
        'border-all' => 'Border All',
        'border-bottom' => 'Border Bottom',
        'border-center' => 'Border Center',
        'border-inner' => 'Border Inner',
        'border-left' => 'Border Left',
        'border-middle' => 'Border Middle',
        'border-outer' => 'Border Outer',
        'border-right' => 'Border Right',
        'border-style' => 'Border Style',
        'border-top' => 'Border Top',
        'border-width' => 'Border Width',
        'bounding-box' => 'Bounding Box',
        'bounding-box-circles' => 'Bounding Box Circles',
        'box' => 'Box',
        'box-fill' => 'Box Fill',
        'box-seam' => 'Box Seam',
        'box-seam-fill' => 'Box Seam Fill',
        'boxes' => 'Boxes',
        'square' => 'Square',
        'square-fill' => 'Square Fill',
        'square-half' => 'Square Half',
        'circle' => 'Circle',
        'circle-fill' => 'Circle Fill',
        'circle-half' => 'Circle Half',
        'circle-square' => 'Circle Square',
        'diamond' => 'Diamond',
        'diamond-fill' => 'Diamond Fill',
        'diamond-half' => 'Diamond Half',
        'octagon' => 'Octagon',
        'octagon-fill' => 'Octagon Fill',
        'octagon-half' => 'Octagon Half',
        'pentagon' => 'Pentagon',
        'pentagon-fill' => 'Pentagon Fill',
        'pentagon-half' => 'Pentagon Half',
        'hexagon' => 'Hexagon',
        'hexagon-fill' => 'Hexagon Fill',
        'hexagon-half' => 'Hexagon Half',
        'heptagon' => 'Heptagon',
        'heptagon-fill' => 'Heptagon Fill',
        'heptagon-half' => 'Heptagon Half',
        
        // Actions & Controls
        'plus' => 'Plus',
        'plus-circle' => 'Plus Circle',
        'plus-circle-fill' => 'Plus Circle Fill',
        'plus-circle-dotted' => 'Plus Circle Dotted',
        'plus-lg' => 'Plus Large',
        'plus-square' => 'Plus Square',
        'plus-square-fill' => 'Plus Square Fill',
        'plus-square-dotted' => 'Plus Square Dotted',
        'dash' => 'Dash',
        'dash-circle' => 'Dash Circle',
        'dash-circle-fill' => 'Dash Circle Fill',
        'dash-circle-dotted' => 'Dash Circle Dotted',
        'dash-lg' => 'Dash Large',
        'dash-square' => 'Dash Square',
        'dash-square-fill' => 'Dash Square Fill',
        'dash-square-dotted' => 'Dash Square Dotted',
        'x' => 'X',
        'x-circle' => 'X Circle',
        'x-circle-fill' => 'X Circle Fill',
        'x-diamond' => 'X Diamond',
        'x-diamond-fill' => 'X Diamond Fill',
        'x-lg' => 'X Large',
        'x-octagon' => 'X Octagon',
        'x-octagon-fill' => 'X Octagon Fill',
        'x-square' => 'X Square',
        'x-square-fill' => 'X Square Fill',
        'check' => 'Check',
        'check-all' => 'Check All',
        'check-circle' => 'Check Circle',
        'check-circle-fill' => 'Check Circle Fill',
        'check-lg' => 'Check Large',
        'check-square' => 'Check Square',
        'check-square-fill' => 'Check Square Fill',
        'check2' => 'Check 2',
        'check2-all' => 'Check 2 All',
        'check2-circle' => 'Check 2 Circle',
        'check2-square' => 'Check 2 Square',
        'toggle-off' => 'Toggle Off',
        'toggle-on' => 'Toggle On',
        'toggle2-off' => 'Toggle 2 Off',
        'toggle2-on' => 'Toggle 2 On',
        'toggles' => 'Toggles',
        'toggles2' => 'Toggles 2',
        'radioactive' => 'Radioactive',
        'record-btn' => 'Record Button',
        'record-btn-fill' => 'Record Button Fill',
        'record-circle' => 'Record Circle',
        'record-circle-fill' => 'Record Circle Fill',
        'record-fill' => 'Record Fill',
        'app' => 'App',
        'app-indicator' => 'App Indicator',
        'archive' => 'Archive',
        'archive-fill' => 'Archive Fill',
        'award' => 'Award',
        'award-fill' => 'Award Fill',
        'backspace' => 'Backspace',
        'backspace-fill' => 'Backspace Fill',
        'backspace-reverse' => 'Backspace Reverse',
        'backspace-reverse-fill' => 'Backspace Reverse Fill',
        'badge-3d' => 'Badge 3D',
        'badge-3d-fill' => 'Badge 3D Fill',
        'badge-4k' => 'Badge 4K',
        'badge-4k-fill' => 'Badge 4K Fill',
        'badge-8k' => 'Badge 8K',
        'badge-8k-fill' => 'Badge 8K Fill',
        'badge-ad' => 'Badge AD',
        'badge-ad-fill' => 'Badge AD Fill',
        'badge-ar' => 'Badge AR',
        'badge-ar-fill' => 'Badge AR Fill',
        'badge-cc' => 'Badge CC',
        'badge-cc-fill' => 'Badge CC Fill',
        'badge-hd' => 'Badge HD',
        'badge-hd-fill' => 'Badge HD Fill',
        'badge-tm' => 'Badge TM',
        'badge-tm-fill' => 'Badge TM Fill',
        'badge-vo' => 'Badge VO',
        'badge-vo-fill' => 'Badge VO Fill',
        'badge-vr' => 'Badge VR',
        'badge-vr-fill' => 'Badge VR Fill',
        'badge-wc' => 'Badge WC',
        'badge-wc-fill' => 'Badge WC Fill',
        
        // Social & Favorites
        'heart' => 'Heart',
        'heart-fill' => 'Heart Fill',
        'heart-half' => 'Heart Half',
        'heart-arrow' => 'Heart Arrow',
        'heart-pulse' => 'Heart Pulse',
        'heart-pulse-fill' => 'Heart Pulse Fill',
        'heartbreak' => 'Heartbreak',
        'heartbreak-fill' => 'Heartbreak Fill',
        'hearts' => 'Hearts',
        'suit-heart' => 'Suit Heart',
        'suit-heart-fill' => 'Suit Heart Fill',
        'star' => 'Star',
        'star-fill' => 'Star Fill',
        'star-half' => 'Star Half',
        'stars' => 'Stars',
        'bookmark' => 'Bookmark',
        'bookmark-fill' => 'Bookmark Fill',
        'bookmark-check' => 'Bookmark Check',
        'bookmark-check-fill' => 'Bookmark Check Fill',
        'bookmark-dash' => 'Bookmark Dash',
        'bookmark-dash-fill' => 'Bookmark Dash Fill',
        'bookmark-heart' => 'Bookmark Heart',
        'bookmark-heart-fill' => 'Bookmark Heart Fill',
        'bookmark-plus' => 'Bookmark Plus',
        'bookmark-plus-fill' => 'Bookmark Plus Fill',
        'bookmark-star' => 'Bookmark Star',
        'bookmark-star-fill' => 'Bookmark Star Fill',
        'bookmark-x' => 'Bookmark X',
        'bookmark-x-fill' => 'Bookmark X Fill',
        'bookmarks' => 'Bookmarks',
        'bookmarks-fill' => 'Bookmarks Fill',
        'bookshelf' => 'Bookshelf',
        'emoji-angry' => 'Emoji Angry',
        'emoji-angry-fill' => 'Emoji Angry Fill',
        'emoji-dizzy' => 'Emoji Dizzy',
        'emoji-dizzy-fill' => 'Emoji Dizzy Fill',
        'emoji-expressionless' => 'Emoji Expressionless',
        'emoji-expressionless-fill' => 'Emoji Expressionless Fill',
        'emoji-frown' => 'Emoji Frown',
        'emoji-frown-fill' => 'Emoji Frown Fill',
        'emoji-heart-eyes' => 'Emoji Heart Eyes',
        'emoji-heart-eyes-fill' => 'Emoji Heart Eyes Fill',
        'emoji-kiss' => 'Emoji Kiss',
        'emoji-kiss-fill' => 'Emoji Kiss Fill',
        'emoji-laughing' => 'Emoji Laughing',
        'emoji-laughing-fill' => 'Emoji Laughing Fill',
        'emoji-neutral' => 'Emoji Neutral',
        'emoji-neutral-fill' => 'Emoji Neutral Fill',
        'emoji-smile' => 'Emoji Smile',
        'emoji-smile-fill' => 'Emoji Smile Fill',
        'emoji-smile-upside-down' => 'Emoji Smile Upside Down',
        'emoji-smile-upside-down-fill' => 'Emoji Smile Upside Down Fill',
        'emoji-sunglasses' => 'Emoji Sunglasses',
        'emoji-sunglasses-fill' => 'Emoji Sunglasses Fill',
        'emoji-wink' => 'Emoji Wink',
        'emoji-wink-fill' => 'Emoji Wink Fill',
        'hand-thumbs-up' => 'Thumbs Up',
        'hand-thumbs-up-fill' => 'Thumbs Up Fill',
        'hand-thumbs-down' => 'Thumbs Down',
        'hand-thumbs-down-fill' => 'Thumbs Down Fill',
        'hand-index' => 'Hand Index',
        'hand-index-fill' => 'Hand Index Fill',
        'hand-index-thumb' => 'Hand Index Thumb',
        'hand-index-thumb-fill' => 'Hand Index Thumb Fill',
        'trophy' => 'Trophy',
        'trophy-fill' => 'Trophy Fill',
        'flag' => 'Flag',
        'flag-fill' => 'Flag Fill',
        
        // Search & Discovery
        'search' => 'Search',
        'search-heart' => 'Search Heart',
        'search-heart-fill' => 'Search Heart Fill',
        'binoculars' => 'Binoculars',
        'binoculars-fill' => 'Binoculars Fill',
        'eye' => 'Eye',
        'eye-fill' => 'Eye Fill',
        'eye-slash' => 'Eye Slash',
        'eye-slash-fill' => 'Eye Slash Fill',
        'eyeglasses' => 'Eyeglasses',
        'sunglasses' => 'Sunglasses',
        'question' => 'Question',
        'question-circle' => 'Question Circle',
        'question-circle-fill' => 'Question Circle Fill',
        'question-diamond' => 'Question Diamond',
        'question-diamond-fill' => 'Question Diamond Fill',
        'question-lg' => 'Question Large',
        'question-octagon' => 'Question Octagon',
        'question-octagon-fill' => 'Question Octagon Fill',
        'question-square' => 'Question Square',
        'question-square-fill' => 'Question Square Fill',
        'sort-down' => 'Sort Down',
        'sort-down-alt' => 'Sort Down Alt',
        'sort-numeric-down' => 'Sort Numeric Down',
        'sort-numeric-down-alt' => 'Sort Numeric Down Alt',
        'sort-numeric-up' => 'Sort Numeric Up',
        'sort-numeric-up-alt' => 'Sort Numeric Up Alt',
        'sort-up' => 'Sort Up',
        'sort-up-alt' => 'Sort Up Alt',
        'sort-alpha-down' => 'Sort Alpha Down',
        'sort-alpha-down-alt' => 'Sort Alpha Down Alt',
        'sort-alpha-up' => 'Sort Alpha Up',
        'sort-alpha-up-alt' => 'Sort Alpha Up Alt',
        'filter' => 'Filter',
        'filter-circle' => 'Filter Circle',
        'filter-circle-fill' => 'Filter Circle Fill',
        'filter-left' => 'Filter Left',
        'filter-right' => 'Filter Right',
        'filter-square' => 'Filter Square',
        'filter-square-fill' => 'Filter Square Fill',
        'funnel' => 'Funnel',
        'funnel-fill' => 'Funnel Fill',
        'hourglass' => 'Hourglass',
        'hourglass-bottom' => 'Hourglass Bottom',
        'hourglass-split' => 'Hourglass Split',
        'hourglass-top' => 'Hourglass Top',
        'hurricane' => 'Hurricane',
        'tornado' => 'Tornado',
        'lightbulb' => 'Lightbulb',
        'lightbulb-fill' => 'Lightbulb Fill',
        'lightbulb-off' => 'Lightbulb Off',
        'lightbulb-off-fill' => 'Lightbulb Off Fill',
        'lamp' => 'Lamp',
        'lamp-fill' => 'Lamp Fill',
        'flashlight' => 'Flashlight',
        'flashlight-fill' => 'Flashlight Fill',
        'file-earmark-text' => 'File Text',
        'file-earmark-text-fill' => 'File Text Fill',
        'file-text' => 'File Text Alt',
        'file-text-fill' => 'File Text Alt Fill',
        'files' => 'Files',
        'files-alt' => 'Files Alt',
        'folder' => 'Folder',
        'folder-fill' => 'Folder Fill',
        'folder-symlink' => 'Folder Symlink',
        'folder-symlink-fill' => 'Folder Symlink Fill',
        'folder-plus' => 'Folder Plus',
        'folder-minus' => 'Folder Minus',
        'folder-check' => 'Folder Check',
        'folder-x' => 'Folder X',
        'folder2' => 'Folder 2',
        'folder2-open' => 'Folder 2 Open',
        
        // Files & Documents
        'file-earmark' => 'File',
        'file-earmark-fill' => 'File Fill',
        'file-earmark-arrow-down' => 'File Download',
        'file-earmark-arrow-down-fill' => 'File Download Fill',
        'file-earmark-arrow-up' => 'File Upload',
        'file-earmark-arrow-up-fill' => 'File Upload Fill',
        'file-earmark-bar-graph' => 'File Bar Graph',
        'file-earmark-bar-graph-fill' => 'File Bar Graph Fill',
        'file-earmark-binary' => 'File Binary',
        'file-earmark-binary-fill' => 'File Binary Fill',
        'file-earmark-break' => 'File Break',
        'file-earmark-break-fill' => 'File Break Fill',
        'file-earmark-check' => 'File Check',
        'file-earmark-check-fill' => 'File Check Fill',
        'file-earmark-code' => 'File Code',
        'file-earmark-code-fill' => 'File Code Fill',
        'file-earmark-diff' => 'File Diff',
        'file-earmark-diff-fill' => 'File Diff Fill',
        'file-earmark-easel' => 'File Easel',
        'file-earmark-easel-fill' => 'File Easel Fill',
        'file-earmark-excel' => 'File Excel',
        'file-earmark-excel-fill' => 'File Excel Fill',
        'file-earmark-font' => 'File Font',
        'file-earmark-font-fill' => 'File Font Fill',
        'file-earmark-lock' => 'File Lock',
        'file-earmark-lock-fill' => 'File Lock Fill',
        'file-earmark-lock2' => 'File Lock 2',
        'file-earmark-lock2-fill' => 'File Lock 2 Fill',
        'file-earmark-medical' => 'File Medical',
        'file-earmark-medical-fill' => 'File Medical Fill',
        'file-earmark-minus' => 'File Minus',
        'file-earmark-minus-fill' => 'File Minus Fill',
        'file-earmark-music' => 'File Music',
        'file-earmark-music-fill' => 'File Music Fill',
        'file-earmark-pdf' => 'File PDF',
        'file-earmark-pdf-fill' => 'File PDF Fill',
        'file-earmark-person' => 'File Person',
        'file-earmark-person-fill' => 'File Person Fill',
        'file-earmark-play' => 'File Play',
        'file-earmark-play-fill' => 'File Play Fill',
        'file-earmark-plus' => 'File Plus',
        'file-earmark-plus-fill' => 'File Plus Fill',
        'file-earmark-post' => 'File Post',
        'file-earmark-post-fill' => 'File Post Fill',
        'file-earmark-ppt' => 'File PowerPoint',
        'file-earmark-ppt-fill' => 'File PowerPoint Fill',
        'file-earmark-richtext' => 'File Rich Text',
        'file-earmark-richtext-fill' => 'File Rich Text Fill',
        'file-earmark-ruled' => 'File Ruled',
        'file-earmark-ruled-fill' => 'File Ruled Fill',
        'file-earmark-slides' => 'File Slides',
        'file-earmark-slides-fill' => 'File Slides Fill',
        'file-earmark-spreadsheet' => 'File Spreadsheet',
        'file-earmark-spreadsheet-fill' => 'File Spreadsheet Fill',
        'file-earmark-word' => 'File Word',
        'file-earmark-word-fill' => 'File Word Fill',
        'file-earmark-x' => 'File X',
        'file-earmark-x-fill' => 'File X Fill',
        'file-earmark-zip' => 'File Zip',
        'file-earmark-zip-fill' => 'File Zip Fill',
        'cloud' => 'Cloud',
        'cloud-fill' => 'Cloud Fill',
        'cloud-arrow-down' => 'Cloud Download',
        'cloud-arrow-down-fill' => 'Cloud Download Fill',
        'cloud-arrow-up' => 'Cloud Upload',
        'cloud-arrow-up-fill' => 'Cloud Upload Fill',
        'cloud-check' => 'Cloud Check',
        'cloud-check-fill' => 'Cloud Check Fill',
        'cloud-download' => 'Cloud Download Alt',
        'cloud-download-fill' => 'Cloud Download Alt Fill',
        'cloud-drizzle' => 'Cloud Drizzle',
        'cloud-drizzle-fill' => 'Cloud Drizzle Fill',
        'cloud-fog' => 'Cloud Fog',
        'cloud-fog-fill' => 'Cloud Fog Fill',
        'cloud-fog2' => 'Cloud Fog 2',
        'cloud-fog2-fill' => 'Cloud Fog 2 Fill',
        'cloud-hail' => 'Cloud Hail',
        'cloud-hail-fill' => 'Cloud Hail Fill',
        'cloud-haze' => 'Cloud Haze',
        'cloud-haze-fill' => 'Cloud Haze Fill',
        'cloud-haze2' => 'Cloud Haze 2',
        'cloud-haze2-fill' => 'Cloud Haze 2 Fill',
        'cloud-lightning' => 'Cloud Lightning',
        'cloud-lightning-fill' => 'Cloud Lightning Fill',
        'cloud-lightning-rain' => 'Cloud Lightning Rain',
        'cloud-lightning-rain-fill' => 'Cloud Lightning Rain Fill',
        'cloud-minus' => 'Cloud Minus',
        'cloud-minus-fill' => 'Cloud Minus Fill',
        'cloud-moon' => 'Cloud Moon',
        'cloud-moon-fill' => 'Cloud Moon Fill',
        'cloud-plus' => 'Cloud Plus',
        'cloud-plus-fill' => 'Cloud Plus Fill',
        'cloud-rain' => 'Cloud Rain',
        'cloud-rain-fill' => 'Cloud Rain Fill',
        'cloud-rain-heavy' => 'Cloud Rain Heavy',
        'cloud-rain-heavy-fill' => 'Cloud Rain Heavy Fill',
        'cloud-slash' => 'Cloud Slash',
        'cloud-slash-fill' => 'Cloud Slash Fill',
        'cloud-sleet' => 'Cloud Sleet',
        'cloud-sleet-fill' => 'Cloud Sleet Fill',
        'cloud-snow' => 'Cloud Snow',
        'cloud-snow-fill' => 'Cloud Snow Fill',
        'cloud-sun' => 'Cloud Sun',
        'cloud-sun-fill' => 'Cloud Sun Fill',
        'cloud-upload' => 'Cloud Upload Alt',
        'cloud-upload-fill' => 'Cloud Upload Alt Fill',
        'clouds' => 'Clouds',
        'clouds-fill' => 'Clouds Fill',
        'cloudy' => 'Cloudy',
        'cloudy-fill' => 'Cloudy Fill',
        'download' => 'Download',
        'upload' => 'Upload',
        'save' => 'Save',
        'save-fill' => 'Save Fill',
        'save2' => 'Save 2',
        'save2-fill' => 'Save 2 Fill',
        
        // Settings & Tools
        'gear' => 'Gear',
        'gear-fill' => 'Gear Fill',
        'gear-wide' => 'Gear Wide',
        'gear-wide-connected' => 'Gear Wide Connected',
        'gears' => 'Gears',
        'tools' => 'Tools',
        'hammer' => 'Hammer',
        'screwdriver' => 'Screwdriver',
        'wrench' => 'Wrench',
        'wrench-adjustable' => 'Wrench Adjustable',
        'wrench-adjustable-circle' => 'Wrench Adjustable Circle',
        'wrench-adjustable-circle-fill' => 'Wrench Adjustable Circle Fill',
        'nut' => 'Nut',
        'nut-fill' => 'Nut Fill',
        'rulers' => 'Rulers',
        'scissors' => 'Scissors',
        'paperclip' => 'Paperclip',
        'pin' => 'Pin',
        'pin-fill' => 'Pin Fill',
        'pin-angle' => 'Pin Angle',
        'pin-angle-fill' => 'Pin Angle Fill',
        'pin-map' => 'Pin Map',
        'pin-map-fill' => 'Pin Map Fill',
        'pushpin' => 'Pushpin',
        'pushpin-fill' => 'Pushpin Fill',
        'bookmark-star' => 'Bookmark Star',
        'bookmark-star-fill' => 'Bookmark Star Fill',
        'sliders' => 'Sliders',
        'sliders2' => 'Sliders 2',
        'sliders2-vertical' => 'Sliders 2 Vertical',
        'toggles' => 'Toggles',
        'toggles2' => 'Toggles 2',
        'options' => 'Options',
        'toggle-off' => 'Toggle Off',
        'toggle-on' => 'Toggle On',
        'toggle2-off' => 'Toggle 2 Off',
        'toggle2-on' => 'Toggle 2 On',
        'ui-checks' => 'UI Checks',
        'ui-checks-grid' => 'UI Checks Grid',
        'ui-radios' => 'UI Radios',
        'ui-radios-grid' => 'UI Radios Grid',
        'input-cursor' => 'Input Cursor',
        'input-cursor-text' => 'Input Cursor Text',
        'menu-app' => 'Menu App',
        'menu-app-fill' => 'Menu App Fill',
        'menu-button' => 'Menu Button',
        'menu-button-fill' => 'Menu Button Fill',
        'menu-button-wide' => 'Menu Button Wide',
        'menu-button-wide-fill' => 'Menu Button Wide Fill',
        'menu-down' => 'Menu Down',
        'menu-up' => 'Menu Up',
        'three-dots' => 'Three Dots',
        'three-dots-vertical' => 'Three Dots Vertical',
        'brightness-alt-high' => 'Brightness High',
        'brightness-alt-high-fill' => 'Brightness High Fill',
        'brightness-alt-low' => 'Brightness Low',
        'brightness-alt-low-fill' => 'Brightness Low Fill',
        'brightness-high' => 'Brightness High Alt',
        'brightness-high-fill' => 'Brightness High Alt Fill',
        'brightness-low' => 'Brightness Low Alt',
        'brightness-low-fill' => 'Brightness Low Alt Fill',
        'contrast' => 'Contrast',
        'transparent' => 'Transparent',
        'exclude' => 'Exclude',
        'intersect' => 'Intersect',
        'subtract' => 'Subtract',
        'symmetry-horizontal' => 'Symmetry Horizontal',
        'symmetry-vertical' => 'Symmetry Vertical',
        'union' => 'Union',
        'bezier' => 'Bezier',
        'bezier2' => 'Bezier 2',
        'vector-pen' => 'Vector Pen',
        'pen' => 'Pen',
        'pen-fill' => 'Pen Fill',
        'pencil' => 'Pencil',
        'pencil-fill' => 'Pencil Fill',
        'pencil-square' => 'Pencil Square',
        'eraser' => 'Eraser',
        'eraser-fill' => 'Eraser Fill',
        'fonts' => 'Fonts',
        'type' => 'Type',
        'type-bold' => 'Type Bold',
        'type-h1' => 'Type H1',
        'type-h2' => 'Type H2',
        'type-h3' => 'Type H3',
        'type-italic' => 'Type Italic',
        'type-strikethrough' => 'Type Strikethrough',
        'type-underline' => 'Type Underline',
        'textarea' => 'Textarea',
        'textarea-resize' => 'Textarea Resize',
        'textarea-t' => 'Textarea T',
        'hr' => 'Horizontal Rule',
        'blockquote-left' => 'Blockquote Left',
        'blockquote-right' => 'Blockquote Right',
        'code' => 'Code',
        'code-slash' => 'Code Slash',
        'code-square' => 'Code Square',
        'terminal' => 'Terminal',
        'terminal-fill' => 'Terminal Fill',
        'braces' => 'Braces',
        'braces-asterisk' => 'Braces Asterisk',
        'file-code' => 'File Code',
        'file-code-fill' => 'File Code Fill',
        
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
        'shield-exclamation' => 'Shield Exclamation',
        'shield-fill-exclamation' => 'Shield Fill Exclamation',
        'shield-lock' => 'Shield Lock',
        'shield-lock-fill' => 'Shield Lock Fill',
        'shield-minus' => 'Shield Minus',
        'shield-fill-minus' => 'Shield Fill Minus',
        'shield-plus' => 'Shield Plus',
        'shield-fill-plus' => 'Shield Fill Plus',
        'shield-slash' => 'Shield Slash',
        'shield-slash-fill' => 'Shield Slash Fill',
        'shield-x' => 'Shield X',
        'shield-fill-x' => 'Shield Fill X',
        'fingerprint' => 'Fingerprint',
        'incognito' => 'Incognito',
        'mask' => 'Mask',
        'cone' => 'Cone',
        'cone-striped' => 'Cone Striped',
        'radioactive' => 'Radioactive',
        'biohazard' => 'Biohazard',
        'virus' => 'Virus',
        'virus2' => 'Virus 2',
        'virus3' => 'Virus 3',
        'bug' => 'Bug',
        'bug-fill' => 'Bug Fill',
        'exclamation' => 'Exclamation',
        'exclamation-circle' => 'Exclamation Circle',
        'exclamation-circle-fill' => 'Exclamation Circle Fill',
        'exclamation-diamond' => 'Exclamation Diamond',
        'exclamation-diamond-fill' => 'Exclamation Diamond Fill',
        'exclamation-lg' => 'Exclamation Large',
        'exclamation-octagon' => 'Exclamation Octagon',
        'exclamation-octagon-fill' => 'Exclamation Octagon Fill',
        'exclamation-square' => 'Exclamation Square',
        'exclamation-square-fill' => 'Exclamation Square Fill',
        'exclamation-triangle' => 'Exclamation Triangle',
        'exclamation-triangle-fill' => 'Exclamation Triangle Fill',
        'sign-stop' => 'Stop Sign',
        'sign-stop-fill' => 'Stop Sign Fill',
        'sign-turn-left' => 'Turn Left Sign',
        'sign-turn-left-fill' => 'Turn Left Sign Fill',
        'sign-turn-right' => 'Turn Right Sign',
        'sign-turn-right-fill' => 'Turn Right Sign Fill',
        'sign-turn-slight-left' => 'Turn Slight Left Sign',
        'sign-turn-slight-left-fill' => 'Turn Slight Left Sign Fill',
        'sign-turn-slight-right' => 'Turn Slight Right Sign',
        'sign-turn-slight-right-fill' => 'Turn Slight Right Sign Fill',
        'sign-yield' => 'Yield Sign',
        'sign-yield-fill' => 'Yield Sign Fill',
        'slash' => 'Slash',
        'slash-circle' => 'Slash Circle',
        'slash-circle-fill' => 'Slash Circle Fill',
        'slash-lg' => 'Slash Large',
        'slash-square' => 'Slash Square',
        'slash-square-fill' => 'Slash Square Fill',
        'power' => 'Power',
        'bootstrap' => 'Bootstrap',
        'bootstrap-fill' => 'Bootstrap Fill',
        'bootstrap-reboot' => 'Bootstrap Reboot',
        'hospital' => 'Hospital',
        'hospital-fill' => 'Hospital Fill',
        'bandaid' => 'Bandaid',
        'bandaid-fill' => 'Bandaid Fill',
        'thermometer' => 'Thermometer',
        'thermometer-half' => 'Thermometer Half',
        'thermometer-high' => 'Thermometer High',
        'thermometer-low' => 'Thermometer Low',
        'thermometer-snow' => 'Thermometer Snow',
        'thermometer-sun' => 'Thermometer Sun',
        'activity' => 'Activity',
        'sunrise' => 'Sunrise',
        'sunrise-fill' => 'Sunrise Fill',
        'sunset' => 'Sunset',
        'sunset-fill' => 'Sunset Fill',
        'sun' => 'Sun',
        'sun-fill' => 'Sun Fill',
        'moon' => 'Moon',
        'moon-fill' => 'Moon Fill',
        'moon-stars' => 'Moon Stars',
        'moon-stars-fill' => 'Moon Stars Fill',
        
        // Time & Calendar
        'calendar' => 'Calendar',
        'calendar-fill' => 'Calendar Fill',
        'calendar-check' => 'Calendar Check',
        'calendar-check-fill' => 'Calendar Check Fill',
        'calendar-date' => 'Calendar Date',
        'calendar-date-fill' => 'Calendar Date Fill',
        'calendar-day' => 'Calendar Day',
        'calendar-day-fill' => 'Calendar Day Fill',
        'calendar-event' => 'Calendar Event',
        'calendar-event-fill' => 'Calendar Event Fill',
        'calendar-heart' => 'Calendar Heart',
        'calendar-heart-fill' => 'Calendar Heart Fill',
        'calendar-minus' => 'Calendar Minus',
        'calendar-minus-fill' => 'Calendar Minus Fill',
        'calendar-month' => 'Calendar Month',
        'calendar-month-fill' => 'Calendar Month Fill',
        'calendar-plus' => 'Calendar Plus',
        'calendar-plus-fill' => 'Calendar Plus Fill',
        'calendar-range' => 'Calendar Range',
        'calendar-range-fill' => 'Calendar Range Fill',
        'calendar-week' => 'Calendar Week',
        'calendar-week-fill' => 'Calendar Week Fill',
        'calendar-x' => 'Calendar X',
        'calendar-x-fill' => 'Calendar X Fill',
        'calendar2' => 'Calendar 2',
        'calendar2-check' => 'Calendar 2 Check',
        'calendar2-check-fill' => 'Calendar 2 Check Fill',
        'calendar2-date' => 'Calendar 2 Date',
        'calendar2-date-fill' => 'Calendar 2 Date Fill',
        'calendar2-day' => 'Calendar 2 Day',
        'calendar2-day-fill' => 'Calendar 2 Day Fill',
        'calendar2-event' => 'Calendar 2 Event',
        'calendar2-event-fill' => 'Calendar 2 Event Fill',
        'calendar2-fill' => 'Calendar 2 Fill',
        'calendar2-heart' => 'Calendar 2 Heart',
        'calendar2-heart-fill' => 'Calendar 2 Heart Fill',
        'calendar2-minus' => 'Calendar 2 Minus',
        'calendar2-minus-fill' => 'Calendar 2 Minus Fill',
        'calendar2-month' => 'Calendar 2 Month',
        'calendar2-month-fill' => 'Calendar 2 Month Fill',
        'calendar2-plus' => 'Calendar 2 Plus',
        'calendar2-plus-fill' => 'Calendar 2 Plus Fill',
        'calendar2-range' => 'Calendar 2 Range',
        'calendar2-range-fill' => 'Calendar 2 Range Fill',
        'calendar2-week' => 'Calendar 2 Week',
        'calendar2-week-fill' => 'Calendar 2 Week Fill',
        'calendar2-x' => 'Calendar 2 X',
        'calendar2-x-fill' => 'Calendar 2 X Fill',
        'calendar3' => 'Calendar 3',
        'calendar3-event' => 'Calendar 3 Event',
        'calendar3-event-fill' => 'Calendar 3 Event Fill',
        'calendar3-fill' => 'Calendar 3 Fill',
        'calendar3-range' => 'Calendar 3 Range',
        'calendar3-range-fill' => 'Calendar 3 Range Fill',
        'calendar3-week' => 'Calendar 3 Week',
        'calendar3-week-fill' => 'Calendar 3 Week Fill',
        'calendar4' => 'Calendar 4',
        'calendar4-event' => 'Calendar 4 Event',
        'calendar4-range' => 'Calendar 4 Range',
        'calendar4-week' => 'Calendar 4 Week',
        'clock' => 'Clock',
        'clock-fill' => 'Clock Fill',
        'clock-history' => 'Clock History',
        'stopwatch' => 'Stopwatch',
        'stopwatch-fill' => 'Stopwatch Fill',
        'alarm' => 'Alarm',
        'alarm-fill' => 'Alarm Fill',
        'smartwatch' => 'Smartwatch',
        'watch' => 'Watch'
    );
}

/**
 * Get Apple SF Symbols (iOS) icons data
 */
/**
 * Get Apple SF Symbols (iOS) icons - CLEAN VERSION with only CSS-defined icons
 */
function wpbnp_get_apple_icons() {
    // Only includes icons with proper CSS definitions and previews
    return array(
        // Navigation & Home
        'house' => 'House',
        'house-fill' => 'House Fill',
        'house-circle' => 'House Circle',
        'house-circle-fill' => 'House Circle Fill',
        'building' => 'Building',
        'building-fill' => 'Building Fill',
        'building-2' => 'Building 2',
        'building-2-fill' => 'Building 2 Fill',
        'map' => 'Map',
        'map-fill' => 'Map Fill',
        'mappin' => 'Map Pin',
        'mappin-circle' => 'Map Pin Circle',
        'mappin-circle-fill' => 'Map Pin Circle Fill',
        'location' => 'Location',
        'location-fill' => 'Location Fill',
        'location-north' => 'Location North',
        'location-north-fill' => 'Location North Fill',
        'location-slash' => 'Location Slash',
        'location-slash-fill' => 'Location Slash Fill',
        'scope' => 'Scope',
        'airplane' => 'Airplane',
        'airplane-circle' => 'Airplane Circle',
        'airplane-circle-fill' => 'Airplane Circle Fill',
        'car' => 'Car',
        'car-fill' => 'Car Fill',
        'car-circle' => 'Car Circle',
        'car-circle-fill' => 'Car Circle Fill',
        'tram' => 'Tram',
        'tram-fill' => 'Tram Fill',
        'train-side-front-car' => 'Train',
        'bicycle' => 'Bicycle',
        'bicycle-circle' => 'Bicycle Circle',
        'bicycle-circle-fill' => 'Bicycle Circle Fill',
        'figure-walk' => 'Walk',
        'figure-walk-circle' => 'Walk Circle',
        'figure-walk-circle-fill' => 'Walk Circle Fill',
        
        // Users & People
        'person' => 'Person',
        'person-fill' => 'Person Fill',
        'person-circle' => 'Person Circle',
        'person-circle-fill' => 'Person Circle Fill',
        'person-crop-circle' => 'Person Crop Circle',
        'person-crop-circle-fill' => 'Person Crop Circle Fill',
        'person-crop-square' => 'Person Crop Square',
        'person-crop-square-fill' => 'Person Crop Square Fill',
        'person-crop-artframe' => 'Person Crop Frame',
        'person-badge-plus' => 'Person Badge Plus',
        'person-badge-plus-fill' => 'Person Badge Plus Fill',
        'person-badge-minus' => 'Person Badge Minus',
        'person-badge-minus-fill' => 'Person Badge Minus Fill',
        'person-and-background-dotted' => 'Person Background Dotted',
        'person-2' => 'Person 2',
        'person-2-fill' => 'Person 2 Fill',
        'person-3' => 'Person 3',
        'person-3-fill' => 'Person 3 Fill',
        'person-2-circle' => 'Person 2 Circle',
        'person-2-circle-fill' => 'Person 2 Circle Fill',
        'person-2-square-stack' => 'Person 2 Square Stack',
        'person-2-square-stack-fill' => 'Person 2 Square Stack Fill',
        'person-crop-circle-badge-plus' => 'Person Circle Badge Plus',
        'person-crop-circle-badge-minus' => 'Person Circle Badge Minus',
        'person-crop-circle-badge-checkmark' => 'Person Circle Badge Check',
        'person-crop-circle-badge-xmark' => 'Person Circle Badge X',
        'person-crop-circle-badge-exclamationmark' => 'Person Circle Badge !',
        'person-crop-rectangle-stack' => 'Person Rectangle Stack',
        'person-crop-rectangle-stack-fill' => 'Person Rectangle Stack Fill',
        'person-line-dotted-person' => 'Person Line Person',
        'person-line-dotted-person-fill' => 'Person Line Person Fill',
        'person-wave-2' => 'Person Wave 2',
        'person-wave-2-fill' => 'Person Wave 2 Fill',
        'person-text-rectangle' => 'Person Text Rectangle',
        'person-text-rectangle-fill' => 'Person Text Rectangle Fill',
        'personalhotspot' => 'Personal Hotspot',
        'shareplay' => 'SharePlay',
        'facetime' => 'FaceTime',
        'faceid' => 'Face ID',
        'touchid' => 'Touch ID',
        
        // Shopping & Commerce
        'cart' => 'Cart',
        'cart-fill' => 'Cart Fill',
        'cart-circle' => 'Cart Circle',
        'cart-circle-fill' => 'Cart Circle Fill',
        'cart-badge-plus' => 'Cart Badge Plus',
        'cart-badge-minus' => 'Cart Badge Minus',
        'bag' => 'Bag',
        'bag-fill' => 'Bag Fill',
        'bag-badge-plus' => 'Bag Badge Plus',
        'bag-badge-minus' => 'Bag Badge Minus',
        'handbag' => 'Handbag',
        'handbag-fill' => 'Handbag Fill',
        'briefcase' => 'Briefcase',
        'briefcase-fill' => 'Briefcase Fill',
        'briefcase-circle' => 'Briefcase Circle',
        'briefcase-circle-fill' => 'Briefcase Circle Fill',
        'case' => 'Case',
        'case-fill' => 'Case Fill',
        'suitcase' => 'Suitcase',
        'suitcase-fill' => 'Suitcase Fill',
        'suitcase-cart' => 'Suitcase Cart',
        'suitcase-cart-fill' => 'Suitcase Cart Fill',
        'creditcard' => 'Credit Card',
        'creditcard-fill' => 'Credit Card Fill',
        'creditcard-circle' => 'Credit Card Circle',
        'creditcard-circle-fill' => 'Credit Card Circle Fill',
        'banknote' => 'Banknote',
        'banknote-fill' => 'Banknote Fill',
        'dollarsign' => 'Dollar Sign',
        'dollarsign-circle' => 'Dollar Circle',
        'dollarsign-circle-fill' => 'Dollar Circle Fill',
        'dollarsign-square' => 'Dollar Square',
        'dollarsign-square-fill' => 'Dollar Square Fill',
        'eurosign' => 'Euro Sign',
        'eurosign-circle' => 'Euro Circle',
        'eurosign-circle-fill' => 'Euro Circle Fill',
        'sterlingsign' => 'Sterling Sign',
        'sterlingsign-circle' => 'Sterling Circle',
        'sterlingsign-circle-fill' => 'Sterling Circle Fill',
        'yensign' => 'Yen Sign',
        'yensign-circle' => 'Yen Circle',
        'yensign-circle-fill' => 'Yen Circle Fill',
        'bitcoinsign' => 'Bitcoin Sign',
        'bitcoinsign-circle' => 'Bitcoin Circle',
        'bitcoinsign-circle-fill' => 'Bitcoin Circle Fill',
        'tag' => 'Tag',
        'tag-fill' => 'Tag Fill',
        'tag-circle' => 'Tag Circle',
        'tag-circle-fill' => 'Tag Circle Fill',
        'tags' => 'Tags',
        'tags-fill' => 'Tags Fill',
        'percent' => 'Percent',
        'gift' => 'Gift',
        'gift-fill' => 'Gift Fill',
        'gift-circle' => 'Gift Circle',
        'gift-circle-fill' => 'Gift Circle Fill',
        'giftcard' => 'Gift Card',
        'giftcard-fill' => 'Gift Card Fill',
        'purchased' => 'Purchased',
        'purchased-circle' => 'Purchased Circle',
        'purchased-circle-fill' => 'Purchased Circle Fill',
        
        // Communication & Social
        'message' => 'Message',
        'message-fill' => 'Message Fill',
        'message-circle' => 'Message Circle',
        'message-circle-fill' => 'Message Circle Fill',
        'message-badge' => 'Message Badge',
        'message-badge-filled-fill' => 'Message Badge Fill',
        'envelope' => 'Mail',
        'envelope-fill' => 'Mail Fill',
        'envelope-circle' => 'Mail Circle',
        'envelope-circle-fill' => 'Mail Circle Fill',
        'envelope-badge' => 'Mail Badge',
        'envelope-badge-fill' => 'Mail Badge Fill',
        'envelope-open' => 'Mail Open',
        'envelope-open-fill' => 'Mail Open Fill',
        'mail-stack' => 'Mail Stack',
        'mail-stack-fill' => 'Mail Stack Fill',
        'mail-and-text-magnifyingglass' => 'Mail Search',
        'paperplane' => 'Paper Plane',
        'paperplane-fill' => 'Paper Plane Fill',
        'paperplane-circle' => 'Paper Plane Circle',
        'paperplane-circle-fill' => 'Paper Plane Circle Fill',
        'phone' => 'Phone',
        'phone-fill' => 'Phone Fill',
        'phone-circle' => 'Phone Circle',
        'phone-circle-fill' => 'Phone Circle Fill',
        'phone-badge-plus' => 'Phone Badge Plus',
        'phone-connection' => 'Phone Connection',
        'phone-arrow-up-right' => 'Phone Arrow Up Right',
        'phone-arrow-down-left' => 'Phone Arrow Down Left',
        'phone-down' => 'Phone Down',
        'phone-down-fill' => 'Phone Down Fill',
        'phone-down-circle' => 'Phone Down Circle',
        'text-bubble' => 'Text Bubble',
        'text-bubble-fill' => 'Text Bubble Fill',
        'megaphone' => 'Megaphone',
        'megaphone-fill' => 'Megaphone Fill',
        'speaker' => 'Speaker',
        'speaker-fill' => 'Speaker Fill',
        'wifi' => 'WiFi',
        'airpods' => 'AirPods',
        'homepod' => 'HomePod',
        'homepod-fill' => 'HomePod Fill',
        
        // Content & Media
        'camera' => 'Camera',
        'camera-fill' => 'Camera Fill',
        'camera-circle' => 'Camera Circle',
        'camera-circle-fill' => 'Camera Circle Fill',
        'video' => 'Video',
        'video-fill' => 'Video Fill',
        'photo' => 'Photo',
        'photo-fill' => 'Photo Fill',
        'music-note' => 'Music Note',
        'headphones' => 'Headphones',
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
        'backward' => 'Backward',
        'backward-fill' => 'Backward Fill',
        'forward' => 'Forward',
        'forward-fill' => 'Forward Fill',
        'shuffle' => 'Shuffle',
        'repeat' => 'Repeat',
        'tv' => 'TV',
        'tv-fill' => 'TV Fill',
        'appletv' => 'Apple TV',
        'appletv-fill' => 'Apple TV Fill',
        
        // Navigation & Controls
        'list-bullet' => 'List Bullet',
        'list-bullet-circle' => 'List Bullet Circle',
        'list-bullet-circle-fill' => 'List Bullet Circle Fill',
        'square-grid-2x2' => 'Square Grid 2x2',
        'square-grid-2x2-fill' => 'Square Grid 2x2 Fill',
        'square-grid-3x2' => 'Square Grid 3x2',
        'square-grid-3x2-fill' => 'Square Grid 3x2 Fill',
        'square-grid-3x3' => 'Square Grid 3x3',
        'square-grid-3x3-fill' => 'Square Grid 3x3 Fill',
        'rectangle' => 'Rectangle',
        'rectangle-fill' => 'Rectangle Fill',
        'arrow-up' => 'Arrow Up',
        'arrow-up-circle' => 'Arrow Up Circle',
        'arrow-up-circle-fill' => 'Arrow Up Circle Fill',
        'arrow-down' => 'Arrow Down',
        'arrow-down-circle' => 'Arrow Down Circle',
        'arrow-down-circle-fill' => 'Arrow Down Circle Fill',
        'arrow-left' => 'Arrow Left',
        'arrow-left-circle' => 'Arrow Left Circle',
        'arrow-left-circle-fill' => 'Arrow Left Circle Fill',
        'arrow-right' => 'Arrow Right',
        'arrow-right-circle' => 'Arrow Right Circle',
        'arrow-right-circle-fill' => 'Arrow Right Circle Fill',
        'chevron-up' => 'Chevron Up',
        'chevron-down' => 'Chevron Down',
        'chevron-left' => 'Chevron Left',
        'chevron-right' => 'Chevron Right',
        
        // Actions & Controls
        'plus' => 'Plus',
        'plus-circle' => 'Plus Circle',
        'plus-circle-fill' => 'Plus Circle Fill',
        'minus' => 'Minus',
        'minus-circle' => 'Minus Circle',
        'minus-circle-fill' => 'Minus Circle Fill',
        'multiply' => 'Multiply',
        'xmark' => 'X Mark',
        'xmark-circle' => 'X Mark Circle',
        'xmark-circle-fill' => 'X Mark Circle Fill',
        'checkmark' => 'Checkmark',
        'checkmark-circle' => 'Checkmark Circle',
        'checkmark-circle-fill' => 'Checkmark Circle Fill',
        'trash' => 'Trash',
        'trash-fill' => 'Trash Fill',
        'archivebox' => 'Archive Box',
        'archivebox-fill' => 'Archive Box Fill',
        
        // Social & Favorites
        'heart' => 'Heart',
        'heart-fill' => 'Heart Fill',
        'heart-circle' => 'Heart Circle',
        'heart-circle-fill' => 'Heart Circle Fill',
        'star' => 'Star',
        'star-fill' => 'Star Fill',
        'star-circle' => 'Star Circle',
        'star-circle-fill' => 'Star Circle Fill',
        'bookmark' => 'Bookmark',
        'bookmark-fill' => 'Bookmark Fill',
        'bookmark-circle' => 'Bookmark Circle',
        'bookmark-circle-fill' => 'Bookmark Circle Fill',
        'trophy' => 'Trophy',
        'trophy-fill' => 'Trophy Fill',
        'flag' => 'Flag',
        'flag-fill' => 'Flag Fill',
        'bell' => 'Bell',
        'bell-fill' => 'Bell Fill',
        'bell-circle' => 'Bell Circle',
        'bell-circle-fill' => 'Bell Circle Fill',
        
        // Search & Discovery
        'magnifyingglass' => 'Magnifying Glass',
        'magnifyingglass-circle' => 'Magnifying Glass Circle',
        'magnifyingglass-circle-fill' => 'Magnifying Glass Circle Fill',
        'binoculars' => 'Binoculars',
        'binoculars-fill' => 'Binoculars Fill',
        'eye' => 'Eye',
        'eye-fill' => 'Eye Fill',
        'eye-slash' => 'Eye Slash',
        'eye-slash-fill' => 'Eye Slash Fill',
        'questionmark' => 'Question Mark',
        'questionmark-circle' => 'Question Mark Circle',
        'questionmark-circle-fill' => 'Question Mark Circle Fill',
        
        // Files & Documents
        'doc' => 'Document',
        'doc-fill' => 'Document Fill',
        'doc-text' => 'Document Text',
        'doc-text-fill' => 'Document Text Fill',
        'folder' => 'Folder',
        'folder-fill' => 'Folder Fill',
        'folder-circle' => 'Folder Circle',
        'folder-circle-fill' => 'Folder Circle Fill',
        'icloud' => 'iCloud',
        'icloud-fill' => 'iCloud Fill',
        'book' => 'Book',
        'book-fill' => 'Book Fill',
        'newspaper' => 'Newspaper',
        'newspaper-fill' => 'Newspaper Fill',
        
        // Settings & Tools
        'gearshape' => 'Gear Shape',
        'gearshape-fill' => 'Gear Shape Fill',
        'gearshape-2' => 'Gear Shape 2',
        'gearshape-2-fill' => 'Gear Shape 2 Fill',
        'wrench' => 'Wrench',
        'wrench-fill' => 'Wrench Fill',
        'hammer' => 'Hammer',
        'hammer-fill' => 'Hammer Fill',
        'scissors' => 'Scissors',
        'app' => 'App',
        'app-fill' => 'App Fill',
        'bolt' => 'Bolt',
        'bolt-fill' => 'Bolt Fill',
        'power' => 'Power',
        'moon' => 'Moon',
        'moon-fill' => 'Moon Fill',
        'sun-max' => 'Sun Max',
        'sun-max-fill' => 'Sun Max Fill',
        'lightbulb' => 'Light Bulb',
        'lightbulb-fill' => 'Light Bulb Fill',
        
        // Security & Privacy
        'lock' => 'Lock',
        'lock-fill' => 'Lock Fill',
        'lock-circle' => 'Lock Circle',
        'lock-circle-fill' => 'Lock Circle Fill',
        'key' => 'Key',
        'key-fill' => 'Key Fill',
        'shield' => 'Shield',
        'shield-fill' => 'Shield Fill',
        'exclamationmark' => 'Exclamation Mark',
        'exclamationmark-circle' => 'Exclamation Mark Circle',
        'exclamationmark-circle-fill' => 'Exclamation Mark Circle Fill',
        'info' => 'Info',
        'info-circle' => 'Info Circle',
        'info-circle-fill' => 'Info Circle Fill',
        
        // Time & Calendar
        'clock' => 'Clock',
        'clock-fill' => 'Clock Fill',
        'clock-circle' => 'Clock Circle',
        'clock-circle-fill' => 'Clock Circle Fill',
        'alarm' => 'Alarm',
        'alarm-fill' => 'Alarm Fill',
        'stopwatch' => 'Stopwatch',
        'stopwatch-fill' => 'Stopwatch Fill',
        'timer' => 'Timer',
        'calendar' => 'Calendar',
        'hourglass' => 'Hourglass'
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
    
    return isset($icon_sets[$preset]) ? $icon_sets[$preset] : wpbnp_get_dashicons();
}

/**
 * Get cart count (WooCommerce integration)
 */
function wpbnp_get_cart_count() {
    if (function_exists('WC') && WC()->cart) {
        return WC()->cart->get_cart_contents_count();
    }
    return 0;
}

/**
 * Get badge count for specific item
 */
function wpbnp_get_badge_count($item_id) {
    switch ($item_id) {
        case 'cart':
            return wpbnp_get_cart_count();
        case 'notifications':
            // Add your notification count logic here
            return 0;
        default:
            return 0;
    }
}

/**
 * Check if user can see navigation item
 */
function wpbnp_can_user_see_item($item) {
    // Check if item is enabled
    if (!isset($item['enabled']) || !$item['enabled']) {
        return false;
    }
    
    // Check user roles if specified
    if (!empty($item['roles']) && is_array($item['roles'])) {
        $current_user = wp_get_current_user();
        
        // If user is not logged in and roles are required
        if (!$current_user->ID) {
            return false;
        }
        
        // Check if user has any of the required roles
        $user_roles = $current_user->roles;
        $has_required_role = false;
        
        foreach ($item['roles'] as $required_role) {
            if (in_array($required_role, $user_roles)) {
                $has_required_role = true;
                break;
            }
        }
        
        if (!$has_required_role) {
            return false;
        }
    }
    
    // Item is visible
    return true;
}

/**
 * Check if pro license is active
 * NOTE: When merging with pro branch, ensure this doesn't conflict with existing license system
 */
function wpbnp_is_pro_license_active() {
    $license_key = get_option('wpbnp_pro_license_key', '');
    $license_status = get_option('wpbnp_pro_license_status', 'inactive');
    
    return !empty($license_key) && $license_status === 'active';
}

/**
 * Check if page targeting is enabled and get active configuration
 */
function wpbnp_get_active_page_targeting_config() {
    // Check if pro license is active
    if (!wpbnp_is_pro_license_active()) {
        error_log('WPBNP Page Targeting - Pro license not active');
        return null;
    }
    
    $settings = wpbnp_get_settings();
    $page_targeting = isset($settings['page_targeting']) ? $settings['page_targeting'] : array();
    
    if (!isset($page_targeting['enabled']) || !$page_targeting['enabled']) {
        return null;
    }
    
    $configurations = isset($page_targeting['configurations']) ? $page_targeting['configurations'] : array();
    
    if (empty($configurations)) {
        return null;
    }
    
    // Sort configurations by priority (higher first)
    usort($configurations, function($a, $b) {
        $priority_a = isset($a['priority']) ? intval($a['priority']) : 1;
        $priority_b = isset($b['priority']) ? intval($b['priority']) : 1;
        return $priority_b - $priority_a;
    });
    
    // Check each configuration's conditions
    foreach ($configurations as $index => $config) {
        if (wpbnp_check_page_targeting_conditions($config)) {
            return $config;
        }
    }
    
    return null;
}

/**
 * Check if current page matches targeting conditions
 */
function wpbnp_check_page_targeting_conditions($config) {
    if (!isset($config['conditions'])) {
        return false;
    }
    
    $conditions = $config['conditions'];
    
    // If all conditions are empty, this is a fallback configuration
    $has_conditions = false;
    $matches = array();
    
    // Check specific pages
    if (!empty($conditions['pages'])) {
        $has_conditions = true;
        $page_match = false;
        if (is_page() && in_array(get_the_ID(), $conditions['pages'])) {
            $page_match = true;
        }
        $matches['pages'] = $page_match;
    }
    
    // Check post types
    if (!empty($conditions['post_types'])) {
        $has_conditions = true;
        $post_type_match = false;
        $current_post_type = get_post_type();
        if ($current_post_type && in_array($current_post_type, $conditions['post_types'])) {
            $post_type_match = true;
        }
        $matches['post_types'] = $post_type_match;
    }
    
    // Check categories
    if (!empty($conditions['categories'])) {
        $has_conditions = true;
        $category_match = false;
        
        if (is_single() || is_category()) {
            $post_categories = wp_get_post_categories(get_the_ID());
            if (array_intersect($post_categories, $conditions['categories'])) {
                $category_match = true;
            }
        }
        if (is_category() && in_array(get_queried_object_id(), $conditions['categories'])) {
            $category_match = true;
        }
        $matches['categories'] = $category_match;
    }
    
    // Check user roles
    if (!empty($conditions['user_roles'])) {
        $has_conditions = true;
        $role_match = false;
        
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (array_intersect($user->roles, $conditions['user_roles'])) {
                $role_match = true;
            }
        }
        $matches['user_roles'] = $role_match;
        
        // Debug logging
        $user_roles = is_user_logged_in() ? wp_get_current_user()->roles : array('not_logged_in');
        error_log('WPBNP Page Targeting - User roles check: Current roles: ' . implode(',', $user_roles) . ', Target roles: ' . implode(',', $conditions['user_roles']) . ', Match: ' . ($role_match ? 'YES' : 'NO'));
    }
    
    // If no conditions were set, this is a fallback configuration
    if (!$has_conditions) {
        error_log('WPBNP Page Targeting - No conditions set, using as fallback');
        return true;
    }
    
    // ALL conditions must match (AND logic)
    $all_match = true;
    foreach ($matches as $condition_type => $match) {
        if (!$match) {
            $all_match = false;
            break;
        }
    }
    
    return $all_match;
}

/**
 * Debug function to test page targeting
 */
function wpbnp_debug_page_targeting() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo '<div style="background: #fff; border: 1px solid #ccc; padding: 20px; margin: 20px; font-family: monospace;">';
    echo '<h3>🔍 WP Bottom Navigation Pro - Page Targeting Debug</h3>';
    
    // Current page info
    echo '<h4>📍 Current Page Info:</h4>';
    echo '<ul>';
    echo '<li><strong>Page ID:</strong> ' . get_the_ID() . '</li>';
    echo '<li><strong>Post Type:</strong> ' . get_post_type() . '</li>';
    echo '<li><strong>Is Page:</strong> ' . (is_page() ? 'Yes' : 'No') . '</li>';
    echo '<li><strong>Is Single:</strong> ' . (is_single() ? 'Yes' : 'No') . '</li>';
    echo '<li><strong>Is Category:</strong> ' . (is_category() ? 'Yes' : 'No') . '</li>';
    echo '<li><strong>Is User Logged In:</strong> ' . (is_user_logged_in() ? 'Yes' : 'No') . '</li>';
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        echo '<li><strong>User Roles:</strong> ' . implode(', ', $user->roles) . '</li>';
    }
    echo '</ul>';
    
    // License status
    echo '<h4>🔑 License Status:</h4>';
    echo '<ul>';
    echo '<li><strong>License Key:</strong> ' . (get_option('wpbnp_pro_license_key', '') ? 'Set' : 'Not Set') . '</li>';
    echo '<li><strong>License Status:</strong> ' . get_option('wpbnp_pro_license_status', 'inactive') . '</li>';
    echo '<li><strong>Pro Active:</strong> ' . (wpbnp_is_pro_license_active() ? 'Yes' : 'No') . '</li>';
    echo '</ul>';
    
    // Page targeting status
    echo '<h4>🎯 Page Targeting Status:</h4>';
    $settings = wpbnp_get_settings();
    $page_targeting = isset($settings['page_targeting']) ? $settings['page_targeting'] : array();
    echo '<ul>';
    echo '<li><strong>Page Targeting Enabled:</strong> ' . (isset($page_targeting['enabled']) && $page_targeting['enabled'] ? 'Yes' : 'No') . '</li>';
    echo '<li><strong>Configurations Count:</strong> ' . count(isset($page_targeting['configurations']) ? $page_targeting['configurations'] : array()) . '</li>';
    echo '</ul>';
    
    // Active configuration
    echo '<h4>⚡ Active Configuration:</h4>';
    $active_config = wpbnp_get_active_page_targeting_config();
    if ($active_config) {
        echo '<ul>';
        echo '<li><strong>Name:</strong> ' . ($active_config['name'] ?? 'Unnamed') . '</li>';
        echo '<li><strong>Priority:</strong> ' . ($active_config['priority'] ?? 1) . '</li>';
        echo '<li><strong>Conditions:</strong></li>';
        $conditions = isset($active_config['conditions']) ? $active_config['conditions'] : array();
        echo '<ul>';
        if (!empty($conditions['pages'])) {
            echo '<li><strong>Pages:</strong> ' . implode(', ', $conditions['pages']) . '</li>';
        }
        if (!empty($conditions['post_types'])) {
            echo '<li><strong>Post Types:</strong> ' . implode(', ', $conditions['post_types']) . '</li>';
        }
        if (!empty($conditions['categories'])) {
            echo '<li><strong>Categories:</strong> ' . implode(', ', $conditions['categories']) . '</li>';
        }
        if (!empty($conditions['user_roles'])) {
            echo '<li><strong>User Roles:</strong> ' . implode(', ', $conditions['user_roles']) . '</li>';
        }
        echo '</ul>';
        echo '</ul>';
    } else {
        echo '<p><strong>No active configuration (using default navigation)</strong></p>';
    }
    
    echo '</div>';
}

/**
 * Get navigation items based on page targeting
 */
function wpbnp_get_targeted_navigation_items() {
    $active_config = wpbnp_get_active_page_targeting_config();
    
    if ($active_config) {
        $preset_id = isset($active_config['preset_id']) ? $active_config['preset_id'] : 'default';
        
        // If using a custom preset, get items from that preset
        if ($preset_id !== 'default') {
            $settings = wpbnp_get_settings();
            $custom_presets = isset($settings['custom_presets']['presets']) ? $settings['custom_presets']['presets'] : array();
            
            foreach ($custom_presets as $preset) {
                if (isset($preset['id']) && $preset['id'] === $preset_id) {
                    return isset($preset['items']) ? $preset['items'] : array();
                }
            }
        }
        
        // Use default items from the main settings
        $settings = wpbnp_get_settings();
        return isset($settings['items']) ? $settings['items'] : array();
    }
    
    // Return default items
    $settings = wpbnp_get_settings();
    return isset($settings['items']) ? $settings['items'] : array();
}
