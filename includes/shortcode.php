<?php
/**
 * Shortcode functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register shortcodes
 */
add_shortcode('wp_bottom_nav', 'wpbnp_shortcode_handler');

/**
 * Handle shortcode display
 */
function wpbnp_shortcode_handler($atts) {
    $atts = shortcode_atts(array(
        'preset' => '',
        'items' => '',
        'class' => ''
    ), $atts, 'wp_bottom_nav');
    
    // Don't display if plugin is disabled
    $settings = wpbnp_get_settings();
    if (empty($settings['enabled'])) {
        return '';
    }
    
    // Override settings if shortcode attributes provided
    if (!empty($atts['preset'])) {
        $presets = wpbnp_get_presets();
        if (isset($presets[$atts['preset']])) {
            $settings = wp_parse_args($presets[$atts['preset']], $settings);
        }
    }
    
    // Override items if provided
    if (!empty($atts['items'])) {
        $item_ids = array_map('trim', explode(',', $atts['items']));
        $filtered_items = array();
        
        foreach ($settings['items'] as $item) {
            if (in_array($item['id'], $item_ids)) {
                $filtered_items[] = $item;
            }
        }
        
        $settings['items'] = $filtered_items;
    }
    
    // Start output buffering
    ob_start();
    
    // Create frontend instance and render
    $frontend = new WPBNP_Frontend();
    
    // Add custom class if provided
    if (!empty($atts['class'])) {
        add_filter('wpbnp_nav_classes', function($classes) use ($atts) {
            $classes[] = sanitize_html_class($atts['class']);
            return $classes;
        });
    }
    
    // Temporarily override settings
    add_filter('wpbnp_get_settings', function($original_settings) use ($settings) {
        return $settings;
    });
    
    $frontend->render_navigation();
    
    // Remove temporary filters
    remove_all_filters('wpbnp_get_settings');
    remove_all_filters('wpbnp_nav_classes');
    
    return ob_get_clean();
}

/**
 * Add shortcode button to editor (optional)
 */
add_action('media_buttons', 'wpbnp_add_shortcode_button');

function wpbnp_add_shortcode_button() {
    $screen = get_current_screen();
    
    if (!$screen || !in_array($screen->post_type, array('post', 'page'))) {
        return;
    }
    
    echo '<button type="button" class="button wpbnp-shortcode-button" data-shortcode="[wp_bottom_nav]">';
    echo '<span class="dashicons dashicons-menu" style="vertical-align: middle;"></span> ';
    echo esc_html__('Bottom Nav', 'wp-bottom-navigation-pro');
    echo '</button>';
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.wpbnp-shortcode-button').on('click', function() {
            var shortcode = $(this).data('shortcode');
            if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                wp.media.editor.insert(shortcode);
            } else {
                // Fallback for classic editor
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    tinymce.activeEditor.insertContent(shortcode);
                } else {
                    var textarea = document.getElementById('content');
                    if (textarea) {
                        var cursorPos = textarea.selectionStart;
                        var textBefore = textarea.value.substring(0, cursorPos);
                        var textAfter = textarea.value.substring(cursorPos);
                        textarea.value = textBefore + shortcode + textAfter;
                    }
                }
            }
        });
    });
    </script>
    <?php
}
