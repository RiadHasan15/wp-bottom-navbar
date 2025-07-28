<?php
/**
 * Frontend display functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend display class
 */
class WPBNP_Frontend {
    
    /**
     * Render the bottom navigation
     */
    public function render_navigation() {
        $settings = wpbnp_get_settings();
        
        if (empty($settings['enabled'])) {
            return;
        }
        
        // Generate dynamic CSS
        $this->output_dynamic_css($settings);
        
        // Render navigation HTML
        $this->output_navigation_html($settings);
    }
    
    /**
     * Output dynamic CSS
     */
    private function output_dynamic_css($settings) {
        $style = $settings['style'];
        $devices = $settings['devices'];
        $advanced = $settings['advanced'];
        $animations = $settings['animations'];
        $preset = $settings['preset'] ?? 'minimal';
        
        ?>
        <style id="wpbnp-dynamic-css">
            .wpbnp-bottom-nav {
                position: fixed;
                <?php echo esc_attr($advanced['fixed_position']); ?>: 0;
                left: 0;
                right: 0;
                background-color: <?php echo esc_attr($style['background_color']); ?>;
                border-top: 1px solid <?php echo esc_attr($style['border_color']); ?>;
                height: <?php echo esc_attr($style['height']); ?>px;
                z-index: <?php echo esc_attr($advanced['z_index']); ?>;
                display: flex;
                align-items: center;
                justify-content: space-around;
                padding: <?php echo esc_attr($style['padding']); ?>px;
                box-shadow: <?php echo esc_attr($style['box_shadow']); ?>;
                border-radius: <?php echo esc_attr($style['border_radius']); ?>px;
                transition: all 0.3s ease;
            }
            
            /* Add preset-specific class for enhanced styling */
            .wpbnp-bottom-nav {
                /* Preset: <?php echo esc_attr($preset); ?> */
            }
            
            .wpbnp-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: <?php echo esc_attr($style['text_color']); ?>;
                font-size: <?php echo esc_attr($style['font_size']); ?>px;
                font-weight: <?php echo esc_attr($style['font_weight'] ?? '400'); ?>;
                transition: all 0.3s ease;
                position: relative;
                flex: 1;
                max-width: 80px;
                min-height: 40px;
                border-radius: 4px;
                padding: 4px;
                overflow: hidden;
                <?php if ($animations['enabled']): ?>
                transition-duration: <?php echo esc_attr($animations['duration']); ?>ms;
                <?php endif; ?>
            }
            
            .wpbnp-nav-item:hover,
            .wpbnp-nav-item:focus {
                color: <?php echo esc_attr($style['active_color']); ?>;
                text-decoration: none;
                outline: none;
            }
            
            .wpbnp-nav-item.active {
                color: <?php echo esc_attr($style['active_color']); ?>;
            }
            
            .wpbnp-nav-icon {
                font-size: <?php echo esc_attr($style['icon_size']); ?>px;
                margin-bottom: 2px;
                display: flex;
                align-items: center;
                justify-content: center;
                width: <?php echo esc_attr($style['icon_size'] + 4); ?>px;
                height: <?php echo esc_attr($style['icon_size'] + 4); ?>px;
                position: relative;
            }
            
            .wpbnp-nav-label {
                font-size: <?php echo esc_attr($style['font_size']); ?>px;
                font-weight: <?php echo esc_attr($style['font_weight'] ?? '400'); ?>;
                line-height: 1.2;
                text-align: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
            }
            
            .wpbnp-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background-color: <?php echo esc_attr($settings['badges']['background_color']); ?>;
                color: <?php echo esc_attr($settings['badges']['text_color']); ?>;
                border-radius: <?php echo esc_attr($settings['badges']['border_radius']); ?>%;
                font-size: 10px;
                font-weight: bold;
                min-width: 18px;
                height: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 4px;
                line-height: 1;
                z-index: 1;
            }
            
            /* COMPREHENSIVE ANIMATION SYSTEM */
            <?php if ($animations['enabled'] && $animations['type'] !== 'none'): ?>
            
            /* Add animation class to navigation */
            .wpbnp-bottom-nav {
                /* Animation enabled: <?php echo esc_attr($animations['type']); ?> */
            }
            
            <?php
            $animationType = $animations['type'];
            $duration = $animations['duration'];
            
            switch ($animationType) {
                case 'bounce':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        animation: wpbnp-hover-bounce <?php echo esc_attr($duration); ?>ms ease;
                    }
                    .wpbnp-nav-item:active .wpbnp-nav-icon {
                        animation: wpbnp-click-bounce <?php echo esc_attr($duration); ?>ms ease;
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
                    }
                    <?php
                    break;
                    
                case 'zoom':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        transform: scale(1.2);
                        transition: transform <?php echo esc_attr($duration); ?>ms ease;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-zoom <?php echo esc_attr($duration); ?>ms ease;
                    }
                    @keyframes wpbnp-click-zoom {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.2); }
                        100% { transform: scale(1); }
                    }
                    <?php
                    break;
                    
                case 'pulse':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        animation: wpbnp-hover-pulse 1s infinite;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-pulse <?php echo esc_attr($duration); ?>ms ease;
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
                    }
                    <?php
                    break;
                    
                case 'fade':
                    ?>
                    .wpbnp-nav-item:hover {
                        opacity: 0.7;
                        transition: opacity <?php echo esc_attr($duration); ?>ms ease;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-fade <?php echo esc_attr($duration); ?>ms ease;
                    }
                    @keyframes wpbnp-click-fade {
                        0% { opacity: 1; }
                        50% { opacity: 0.5; }
                        100% { opacity: 1; }
                    }
                    <?php
                    break;
                    
                case 'slide':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        transform: translateY(-5px);
                        transition: transform <?php echo esc_attr($duration); ?>ms ease;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-slide <?php echo esc_attr($duration); ?>ms ease;
                    }
                    @keyframes wpbnp-click-slide {
                        0% { transform: translateY(0); }
                        50% { transform: translateY(-10px); }
                        100% { transform: translateY(0); }
                    }
                    <?php
                    break;
                    
                case 'rotate':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        transform: rotate(15deg);
                        transition: transform <?php echo esc_attr($duration); ?>ms ease;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-rotate <?php echo esc_attr($duration); ?>ms ease;
                    }
                    @keyframes wpbnp-click-rotate {
                        0% { transform: rotate(0deg); }
                        50% { transform: rotate(180deg); }
                        100% { transform: rotate(360deg); }
                    }
                    <?php
                    break;
                    
                case 'shake':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        animation: wpbnp-hover-shake 0.5s ease infinite;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-shake <?php echo esc_attr($duration); ?>ms ease;
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
                    }
                    <?php
                    break;
                    
                case 'heartbeat':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        animation: wpbnp-hover-heartbeat 1s ease infinite;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-heartbeat <?php echo esc_attr($duration * 2); ?>ms ease;
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
                    }
                    <?php
                    break;
                    
                case 'swing':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        animation: wpbnp-hover-swing <?php echo esc_attr($duration); ?>ms ease;
                        transform-origin: top center;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-swing <?php echo esc_attr($duration); ?>ms ease;
                        transform-origin: top center;
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
                    }
                    <?php
                    break;
                    
                case 'ripple':
                    ?>
                    .wpbnp-nav-item:hover {
                        animation: wpbnp-hover-ripple <?php echo esc_attr($duration); ?>ms ease;
                    }
                    .wpbnp-nav-item:active {
                        animation: wpbnp-click-ripple <?php echo esc_attr($duration); ?>ms ease;
                    }
                    @keyframes wpbnp-hover-ripple {
                        0% { box-shadow: 0 0 0 0 rgba(<?php echo esc_attr($this->hex_to_rgb($style['active_color'])); ?>, 0.4); }
                        70% { box-shadow: 0 0 0 8px rgba(<?php echo esc_attr($this->hex_to_rgb($style['active_color'])); ?>, 0); }
                        100% { box-shadow: 0 0 0 0 rgba(<?php echo esc_attr($this->hex_to_rgb($style['active_color'])); ?>, 0); }
                    }
                    @keyframes wpbnp-click-ripple {
                        0% { box-shadow: 0 0 0 0 rgba(<?php echo esc_attr($this->hex_to_rgb($style['active_color'])); ?>, 0.7); }
                        70% { box-shadow: 0 0 0 10px rgba(<?php echo esc_attr($this->hex_to_rgb($style['active_color'])); ?>, 0); }
                        100% { box-shadow: 0 0 0 0 rgba(<?php echo esc_attr($this->hex_to_rgb($style['active_color'])); ?>, 0); }
                    }
                    <?php
                    break;
            }
            ?>
            
            <?php endif; // End animations ?>
            
            /* PRESET-SPECIFIC STYLES */
            <?php $this->output_preset_styles($preset, $settings); ?>
            
            /* Device-specific visibility */
            <?php if (!$devices['mobile']['enabled']): ?>
            @media (max-width: <?php echo esc_attr($devices['mobile']['breakpoint']); ?>px) {
                .wpbnp-bottom-nav { display: none !important; }
            }
            <?php endif; ?>
            
            <?php if (!$devices['tablet']['enabled']): ?>
            @media (min-width: <?php echo esc_attr($devices['mobile']['breakpoint'] + 1); ?>px) and (max-width: <?php echo esc_attr($devices['tablet']['breakpoint']); ?>px) {
                .wpbnp-bottom-nav { display: none !important; }
            }
            <?php endif; ?>
            
            <?php if (!$devices['desktop']['enabled']): ?>
            @media (min-width: <?php echo esc_attr($devices['desktop']['breakpoint']); ?>px) {
                .wpbnp-bottom-nav { display: none !important; }
            }
            <?php endif; ?>
            
            /* Custom CSS */
            <?php echo wp_strip_all_tags($advanced['custom_css']); ?>
        </style>
        <?php
    }
    
    /**
     * Output navigation HTML
     */
    private function output_navigation_html($settings) {
        $items = $settings['items'];
        
        if (empty($items)) {
            return;
        }
        
        ?>
        <nav class="wpbnp-bottom-nav" id="wpbnp-bottom-nav" role="navigation" aria-label="<?php esc_attr_e('Bottom Navigation', 'wp-bottom-navigation-pro'); ?>">
            <?php
            foreach ($items as $item) {
                if (!wpbnp_can_user_see_item($item)) {
                    continue;
                }
                
                $badge_count = wpbnp_get_badge_count($item['id']);
                $is_current = $this->is_current_page($item['url']);
                
                ?>
                <a href="<?php echo esc_url($item['url']); ?>" 
                   class="wpbnp-nav-item <?php echo $is_current ? 'active' : ''; ?>"
                   data-item-id="<?php echo esc_attr($item['id']); ?>"
                   role="menuitem"
                   tabindex="0"
                   <?php if (!empty($item['label'])): ?>
                   aria-label="<?php echo esc_attr($item['label']); ?>"
                   <?php endif; ?>>
                   
                    <span class="wpbnp-nav-icon">
                        <?php if (strpos($item['icon'], 'dashicons-') === 0): ?>
                            <span class="dashicons <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></span>
                        <?php else: ?>
                            <span class="wpbnp-custom-icon" aria-hidden="true"><?php echo wp_kses_post($item['icon']); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($settings['badges']['enabled'] && $badge_count > 0): ?>
                            <span class="wpbnp-badge" aria-label="<?php echo esc_attr(sprintf(__('%d notifications', 'wp-bottom-navigation-pro'), $badge_count)); ?>">
                                <?php echo esc_html($badge_count > 99 ? '99+' : $badge_count); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    
                    <?php if (!empty($item['label'])): ?>
                        <span class="wpbnp-nav-label"><?php echo esc_html($item['label']); ?></span>
                    <?php endif; ?>
                </a>
                <?php
            }
            ?>
        </nav>
        <?php
    }
    
    /**
     * Check if current page matches item URL
     */
    private function is_current_page($url) {
        if (empty($url) || $url === '#') {
            return false;
        }
        
        $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));
        
        return $current_url === $url || trailingslashit($current_url) === trailingslashit($url);
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
     * Output preset-specific styles based on current preset
     */
    private function output_preset_styles($preset, $settings) {
        // Get preset data
        $presets = wpbnp_get_presets();
        $preset_data = $presets[$preset] ?? null;
        
        if (!$preset_data) {
            return;
        }
        
        // Output preset-specific enhancements
        switch ($preset) {
            case 'glassmorphism':
                ?>
                .wpbnp-bottom-nav {
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                    border: 1px solid rgba(255,255,255,0.18);
                }
                <?php
                break;
                
            case 'neumorphism':
                ?>
                .wpbnp-nav-item:hover {
                    box-shadow: inset 3px 3px 6px #a3b1c6, inset -3px -3px 6px #ffffff;
                }
                <?php
                break;
                
            case 'cyberpunk':
                ?>
                .wpbnp-nav-item {
                    text-shadow: 0 0 10px currentColor;
                }
                .wpbnp-nav-item:hover {
                    text-shadow: 0 0 15px currentColor;
                }
                <?php
                break;
                
            case 'gradient':
                ?>
                .wpbnp-bottom-nav {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                <?php
                break;
                
            case 'floating':
                ?>
                .wpbnp-bottom-nav {
                    margin: 0 20px 20px 20px;
                    border-radius: 30px;
                    left: 20px;
                    right: 20px;
                    bottom: 20px;
                }
                <?php
                break;
        }
    }
}
