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
        
        // Render navigation HTML only (CSS is handled in main plugin file)
        $this->output_navigation_html($settings);
    }
    
    /**
     * Output navigation HTML
     */
    private function output_navigation_html($settings) {
        // Check for page targeting (Pro feature)
        $active_config = wpbnp_get_active_page_targeting_config();
        
        if ($active_config) {
            // Use page targeting configuration
            $items = wpbnp_get_targeted_navigation_items();
            
            // Add configuration info as HTML comment for debugging
            echo '<!-- WP Bottom Navigation Pro: Using page targeting configuration "' . esc_html($active_config['name'] ?? 'Unnamed') . '" (Priority: ' . esc_html($active_config['priority'] ?? 1) . ') -->' . "\n";
        } else {
            // Use default items
            $items = $settings['items'];
        }
        
        if (empty($items)) {
            return;
        }
        
        $preset = $settings['preset'] ?? 'minimal';
        $preset_class = 'wpbnp-preset-' . esc_attr($preset);
        
        ?>
        <nav class="wpbnp-bottom-nav <?php echo esc_attr($preset_class); ?>" id="wpbnp-bottom-nav" role="navigation" aria-label="<?php esc_attr_e('Bottom Navigation', 'wp-bottom-navigation-pro'); ?>">
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
                        <?php 
                        $icon = $item['icon'];
                        if (strpos($icon, 'dashicons-') === 0): ?>
                            <span class="dashicons <?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
                        <?php elseif (strpos($icon, 'fas fa-') === 0 || strpos($icon, 'far fa-') === 0 || strpos($icon, 'fab fa-') === 0): ?>
                            <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                        <?php elseif (strpos($icon, 'bi bi-') === 0): ?>
                            <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                        <?php elseif (strpos($icon, 'feather-') === 0): ?>
                            <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                        <?php elseif ($this->is_apple_icon($icon)): ?>
                            <!-- Apple SF Symbols (direct class names like 'house-fill', 'cart-fill') -->
                            <span class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
                        <?php elseif (!empty($icon) && !strpos($icon, '<') && !strpos($icon, 'fa-') && !strpos($icon, 'bi-') && !strpos($icon, '-')): ?>
                            <!-- Material Icons (text content) -->
                            <span class="material-icons" aria-hidden="true"><?php echo esc_attr($icon); ?></span>
                        <?php else: ?>
                            <span class="wpbnp-custom-icon" aria-hidden="true"><?php echo wp_kses_post($icon); ?></span>
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
     * Check if icon is an Apple SF Symbol
     */
    private function is_apple_icon($icon_class) {
        // Get the list of valid Apple icons from functions.php
        $apple_icons = wpbnp_get_apple_icons();
        return array_key_exists($icon_class, $apple_icons);
    }
}
