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
            
            .wpbnp-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: <?php echo esc_attr($style['text_color']); ?>;
                font-size: <?php echo esc_attr($style['font_size']); ?>px;
                font-weight: <?php echo esc_attr($style['font_weight']); ?>;
                transition: all 0.3s ease;
                position: relative;
                flex: 1;
                max-width: 80px;
            }
            
            .wpbnp-nav-item:hover,
            .wpbnp-nav-item.active {
                color: <?php echo esc_attr($style['active_color']); ?>;
                text-decoration: none;
            }
            
            .wpbnp-nav-icon {
                font-size: <?php echo esc_attr($style['icon_size']); ?>px;
                margin-bottom: 2px;
                display: flex;
                align-items: center;
                justify-content: center;
                width: <?php echo esc_attr($style['icon_size'] + 4); ?>px;
                height: <?php echo esc_attr($style['icon_size'] + 4); ?>px;
            }
            
            .wpbnp-nav-label {
                font-size: <?php echo esc_attr($style['font_size']); ?>px;
                font-weight: <?php echo esc_attr($style['font_weight']); ?>;
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
            }
            
            /* Animation classes */
            <?php if ($settings['animations']['enabled']): ?>
            .wpbnp-nav-item {
                transition-duration: <?php echo esc_attr($settings['animations']['duration']); ?>ms;
            }
            
            <?php
            switch ($settings['animations']['type']) {
                case 'bounce':
                    ?>
                    .wpbnp-nav-item:active {
                        transform: scale(0.95);
                    }
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        animation: wpbnp-bounce 0.6s ease;
                    }
                    @keyframes wpbnp-bounce {
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
                    }
                    <?php
                    break;
                case 'pulse':
                    ?>
                    .wpbnp-nav-item:hover .wpbnp-nav-icon {
                        animation: wpbnp-pulse 1s infinite;
                    }
                    @keyframes wpbnp-pulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.1); }
                        100% { transform: scale(1); }
                    }
                    <?php
                    break;
            }
            ?>
            <?php endif; ?>
            
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
}
