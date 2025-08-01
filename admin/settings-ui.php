<?php
/**
 * Admin UI for WP Bottom Navigation Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin UI class
 */
class WPBNP_Admin_UI {
    
    /**
     * Current tab
     */
    private $current_tab = 'items';
    
    /**
     * Available tabs
     */
    private $tabs = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'items';
        
        $this->tabs = array(
            'items' => __('Items', 'wp-bottom-navigation-pro'),
            'devices' => __('Devices', 'wp-bottom-navigation-pro'),
            'styles' => __('Styles', 'wp-bottom-navigation-pro'),
            'animations' => __('Animations', 'wp-bottom-navigation-pro'),
            'badges' => __('Badges', 'wp-bottom-navigation-pro'),
            'display_rules' => __('Display Rules', 'wp-bottom-navigation-pro'),
            'page_targeting' => __('Page Targeting', 'wp-bottom-navigation-pro') . ' <span class="wpbnp-pro-badge">PRO</span>',
            'presets' => __('Presets', 'wp-bottom-navigation-pro'),
            'advanced' => __('Advanced', 'wp-bottom-navigation-pro')
        );
    }
    
    /**
     * Render admin page
     */
    public function render() {
        $settings = wpbnp_get_settings();
        ?>
        <div class="wrap wpbnp-admin-wrap">
            <h1><?php esc_html_e('Bottom Navigation Settings', 'wp-bottom-navigation-pro'); ?></h1>
            
            <div class="wpbnp-admin-container">
                <div class="wpbnp-admin-sidebar">
                    <?php $this->render_tabs(); ?>
                </div>
                
                <div class="wpbnp-admin-content">
                    <form id="wpbnp-settings-form" method="post">
                        <?php wp_nonce_field('wpbnp_admin_nonce', 'wpbnp_nonce'); ?>
                        
                        <?php if ($this->current_tab !== 'items'): ?>
                        <!-- Hidden field to preserve Enable Bottom Navigation state on non-Items tabs -->
                        <input type="hidden" name="settings[enabled]" value="<?php echo $settings['enabled'] ? '1' : '0'; ?>" id="wpbnp-enabled-hidden">
                        
                        <!-- Critical: Restore hidden field state immediately -->
                        <script>
                        (function() {
                            // Restore the hidden field state from localStorage immediately
                            try {
                                const savedState = localStorage.getItem('wpbnp_form_state');
                                if (savedState) {
                                    const formData = JSON.parse(savedState);
                                    if (formData['settings[enabled]'] !== undefined) {
                                        const shouldBeChecked = Boolean(formData['settings[enabled]']);
                                        const hiddenField = document.getElementById('wpbnp-enabled-hidden');
                                        if (hiddenField) {
                                            hiddenField.value = shouldBeChecked ? '1' : '0';
                                            console.log('Immediately restored enabled hidden field to:', shouldBeChecked);
                                        }
                                    }
                                }
                            } catch (e) {
                                console.warn('Error restoring hidden field state:', e);
                            }
                        })();
                        </script>
                        <?php endif; ?>
                        
                        <?php if ($this->current_tab !== 'styles' && $this->current_tab !== 'presets'): ?>
                                <!-- Hidden fields to preserve style settings on non-style tabs -->
        <input type="hidden" name="settings[style][background_color]" value="<?php echo esc_attr($settings['style']['background_color']); ?>">
        <input type="hidden" name="settings[style][text_color]" value="<?php echo esc_attr($settings['style']['text_color']); ?>">
        <input type="hidden" name="settings[style][active_color]" value="<?php echo esc_attr($settings['style']['active_color']); ?>">
        <input type="hidden" name="settings[style][hover_color]" value="<?php echo esc_attr($settings['style']['hover_color'] ?? '#0085ba'); ?>">
        <input type="hidden" name="settings[style][icon_color]" value="<?php echo esc_attr($settings['style']['icon_color'] ?? '#666666'); ?>">
        <input type="hidden" name="settings[style][border_color]" value="<?php echo esc_attr($settings['style']['border_color']); ?>">
                        <input type="hidden" name="settings[style][height]" value="<?php echo esc_attr($settings['style']['height']); ?>">
                        <input type="hidden" name="settings[style][border_radius]" value="<?php echo esc_attr($settings['style']['border_radius']); ?>">
                        <input type="hidden" name="settings[style][font_size]" value="<?php echo esc_attr($settings['style']['font_size']); ?>">
                        <input type="hidden" name="settings[style][font_weight]" value="<?php echo esc_attr($settings['style']['font_weight'] ?? '400'); ?>">
                        <input type="hidden" name="settings[style][icon_size]" value="<?php echo esc_attr($settings['style']['icon_size']); ?>">
                        <input type="hidden" name="settings[style][padding]" value="<?php echo esc_attr($settings['style']['padding'] ?? '10'); ?>">
                        <input type="hidden" name="settings[style][box_shadow]" value="<?php echo esc_attr($settings['style']['box_shadow']); ?>">
                        <?php endif; ?>
                        
                        <?php if ($this->current_tab !== 'animations'): ?>
                        <!-- Hidden fields to preserve animation settings on non-animation tabs -->
                        <input type="hidden" name="settings[animations][enabled]" value="<?php echo $settings['animations']['enabled'] ? '1' : '0'; ?>">
                        <input type="hidden" name="settings[animations][type]" value="<?php echo esc_attr($settings['animations']['type']); ?>">
                        <input type="hidden" name="settings[animations][duration]" value="<?php echo esc_attr($settings['animations']['duration']); ?>">
                        <?php endif; ?>
                        
                        <?php if ($this->current_tab !== 'presets'): ?>
                        <!-- Hidden field to preserve preset selection on non-preset tabs -->
                        <input type="hidden" name="settings[preset]" value="<?php echo esc_attr($settings['preset'] ?? 'minimal'); ?>">
                        <?php endif; ?>
                        
                        <div class="wpbnp-tab-content">
                            <?php $this->render_tab_content($this->current_tab, $settings); ?>
                        </div>
                        
                        <div class="wpbnp-form-actions">
                            <button type="submit" class="button button-primary wpbnp-save-settings">
                                <?php esc_html_e('Save Changes', 'wp-bottom-navigation-pro'); ?>
                            </button>
                            
                            <button type="button" class="button wpbnp-reset-settings">
                                <?php esc_html_e('Reset to Defaults', 'wp-bottom-navigation-pro'); ?>
                            </button>
                            
                            <button type="button" class="button wpbnp-export-settings">
                                <?php esc_html_e('Export Settings', 'wp-bottom-navigation-pro'); ?>
                            </button>
                            
                            <button type="button" class="button wpbnp-import-settings">
                                <?php esc_html_e('Import Settings', 'wp-bottom-navigation-pro'); ?>
                            </button>
                            <input type="file" id="wpbnp-import-file" accept=".json" style="display: none;">
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Developer Credit -->
            <div class="wpbnp-developer-credit">
                <p>
                    <?php esc_html_e('Made with', 'wp-bottom-navigation-pro'); ?> ❤️ <?php esc_html_e('by', 'wp-bottom-navigation-pro'); ?> 
                    <a href="https://riadhasan.info/" target="_blank" rel="noopener noreferrer">
                        <strong>Riad Hasan</strong>
                    </a>
                </p>
            </div>
            
            <div id="wpbnp-notifications"></div>
        </div>
        <?php
    }
    
    /**
     * Render tabs
     */
    private function render_tabs() {
        ?>
        <div class="wpbnp-tabs">
            <?php foreach ($this->tabs as $tab_key => $tab_label): ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wp-bottom-navigation-pro&tab=' . $tab_key)); ?>" 
                   class="wpbnp-tab <?php echo $this->current_tab === $tab_key ? 'active' : ''; ?>">
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render tab content
     */
    private function render_tab_content($tab, $settings) {
        switch ($tab) {
            case 'items':
                $this->render_items_tab($settings);
                break;
            case 'devices':
                $this->render_devices_tab($settings);
                break;
            case 'styles':
                $this->render_styles_tab($settings);
                break;
            case 'animations':
                $this->render_animations_tab($settings);
                break;
            case 'badges':
                $this->render_badges_tab($settings);
                break;
            case 'display_rules':
                $this->render_display_rules_tab($settings);
                break;
            case 'page_targeting':
                $this->render_page_targeting_tab($settings);
                break;
            case 'presets':
                $this->render_presets_tab($settings);
                break;
            case 'advanced':
                $this->render_advanced_tab($settings);
                break;
        }
    }
    
    /**
     * Render items tab
     */
    private function render_items_tab($settings) {
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Navigation Items', 'wp-bottom-navigation-pro'); ?></h2>
            <p><?php esc_html_e('Configure up to 7 navigation items. Drag to reorder.', 'wp-bottom-navigation-pro'); ?></p>
            
            <div class="wpbnp-field">
                <label>
                    <input type="checkbox" name="settings[enabled]" value="1" <?php checked($settings['enabled']); ?> id="wpbnp-enabled-checkbox">
                    <?php esc_html_e('Enable Bottom Navigation', 'wp-bottom-navigation-pro'); ?>
                </label>
                
                <!-- Critical: Restore checkbox state immediately -->
                <script>
                (function() {
                    // Restore the checkbox state from localStorage immediately
                    try {
                        const savedState = localStorage.getItem('wpbnp_form_state');
                        if (savedState) {
                            const formData = JSON.parse(savedState);
                            if (formData['settings[enabled]'] !== undefined) {
                                const shouldBeChecked = Boolean(formData['settings[enabled]']);
                                
                                // Handle visible checkbox
                                const checkbox = document.getElementById('wpbnp-enabled-checkbox');
                                if (checkbox) {
                                    checkbox.checked = shouldBeChecked;
                                    console.log('Immediately restored enabled checkbox (visible) to:', checkbox.checked);
                                }
                                
                                // Also ensure hidden field is updated if it exists
                                const hiddenField = document.getElementById('wpbnp-enabled-hidden');
                                if (hiddenField) {
                                    hiddenField.value = shouldBeChecked ? '1' : '0';
                                    console.log('Immediately restored enabled checkbox (hidden) to:', shouldBeChecked);
                                }
                            }
                        }
                    } catch (e) {
                        console.warn('Error restoring checkbox state:', e);
                    }
                })();
                </script>
            </div>
            
            <div id="wpbnp-items-list" class="wpbnp-sortable-list">
                <!-- Items will be populated by JavaScript -->
            </div>
            
            <button type="button" id="wpbnp-add-item" class="button button-secondary">
                <?php esc_html_e('Add Item', 'wp-bottom-navigation-pro'); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Render styles tab
     */
    private function render_styles_tab($settings) {
        $style = $settings['style'];
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Appearance Settings', 'wp-bottom-navigation-pro'); ?></h2>
            
            <div class="wpbnp-field-group">
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Background Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[style][background_color]" 
                           value="<?php echo esc_attr($style['background_color']); ?>" 
                           class="wpbnp-color-picker">
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Text Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[style][text_color]" 
                           value="<?php echo esc_attr($style['text_color']); ?>" 
                           class="wpbnp-color-picker">
                </div>
            </div>
            
            <div class="wpbnp-field-group">
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Active Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[style][active_color]" 
                           value="<?php echo esc_attr($style['active_color']); ?>" 
                           class="wpbnp-color-picker">
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Hover Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[style][hover_color]" 
                           value="<?php echo esc_attr($style['hover_color'] ?? '#0085ba'); ?>" 
                           class="wpbnp-color-picker">
                </div>
            </div>
            
            <div class="wpbnp-field-group">
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Icon Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[style][icon_color]" 
                           value="<?php echo esc_attr($style['icon_color'] ?? '#666666'); ?>" 
                           class="wpbnp-color-picker">
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Border Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[style][border_color]" 
                           value="<?php echo esc_attr($style['border_color']); ?>" 
                           class="wpbnp-color-picker">
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Height (px)', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="number" name="settings[style][height]" 
                           value="<?php echo esc_attr($style['height']); ?>" 
                           min="40" max="120">
                </div>
            </div>
            
            <div class="wpbnp-field-group">
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Border Radius (px)', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="number" name="settings[style][border_radius]" 
                           value="<?php echo esc_attr($style['border_radius']); ?>" 
                           min="0" max="50">
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Font Size (px)', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="number" name="settings[style][font_size]" 
                           value="<?php echo esc_attr($style['font_size']); ?>" 
                           min="8" max="20">
                </div>
            </div>
            
            <div class="wpbnp-field-group">
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Font Weight', 'wp-bottom-navigation-pro'); ?></label>
                    <select name="settings[style][font_weight]">
                        <option value="300" <?php selected($style['font_weight'] ?? '400', '300'); ?>><?php esc_html_e('Light (300)', 'wp-bottom-navigation-pro'); ?></option>
                        <option value="400" <?php selected($style['font_weight'] ?? '400', '400'); ?>><?php esc_html_e('Normal (400)', 'wp-bottom-navigation-pro'); ?></option>
                        <option value="500" <?php selected($style['font_weight'] ?? '400', '500'); ?>><?php esc_html_e('Medium (500)', 'wp-bottom-navigation-pro'); ?></option>
                        <option value="600" <?php selected($style['font_weight'] ?? '400', '600'); ?>><?php esc_html_e('Semi-Bold (600)', 'wp-bottom-navigation-pro'); ?></option>
                        <option value="700" <?php selected($style['font_weight'] ?? '400', '700'); ?>><?php esc_html_e('Bold (700)', 'wp-bottom-navigation-pro'); ?></option>
                    </select>
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Icon Size (px)', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="number" name="settings[style][icon_size]" 
                           value="<?php echo esc_attr($style['icon_size']); ?>" 
                           min="16" max="40">
                </div>
            </div>
            
            <div class="wpbnp-field-group">
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Padding (px)', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="number" name="settings[style][padding]" 
                           value="<?php echo esc_attr($style['padding'] ?? '10'); ?>" 
                           min="0" max="30">
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Box Shadow', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[style][box_shadow]" 
                           value="<?php echo esc_attr($style['box_shadow']); ?>" 
                           placeholder="0 -2px 8px rgba(0,0,0,0.1)">
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render devices tab
     */
    private function render_devices_tab($settings) {
        $devices = $settings['devices'];
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Device Settings', 'wp-bottom-navigation-pro'); ?></h2>
            <p><?php esc_html_e('Control which devices display the bottom navigation.', 'wp-bottom-navigation-pro'); ?></p>
            
            <?php foreach ($devices as $device_key => $device_settings): ?>
            <div class="wpbnp-device-section">
                <h3><?php echo esc_html(ucfirst($device_key)); ?></h3>
                
                <div class="wpbnp-field">
                    <label>
                        <input type="checkbox" name="settings[devices][<?php echo esc_attr($device_key); ?>][enabled]" 
                               value="1" <?php checked($device_settings['enabled']); ?>>
                        <?php printf(esc_html__('Show on %s', 'wp-bottom-navigation-pro'), ucfirst($device_key)); ?>
                    </label>
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Breakpoint (px)', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="number" name="settings[devices][<?php echo esc_attr($device_key); ?>][breakpoint]" 
                           value="<?php echo esc_attr($device_settings['breakpoint']); ?>" 
                           min="0" max="2000">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render animations tab
     */
    private function render_animations_tab($settings) {
        $animations = $settings['animations'];
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Animation Settings', 'wp-bottom-navigation-pro'); ?></h2>
            
            <div class="wpbnp-field">
                <label>
                    <input type="checkbox" name="settings[animations][enabled]" 
                           value="1" <?php checked($animations['enabled']); ?>>
                    <?php esc_html_e('Enable Animations', 'wp-bottom-navigation-pro'); ?>
                </label>
            </div>
            
            <div class="wpbnp-field">
                <label><?php esc_html_e('Animation Type', 'wp-bottom-navigation-pro'); ?></label>
                <select name="settings[animations][type]">
                    <option value="bounce" <?php selected($animations['type'], 'bounce'); ?>><?php esc_html_e('Bounce', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="zoom" <?php selected($animations['type'], 'zoom'); ?>><?php esc_html_e('Zoom', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="pulse" <?php selected($animations['type'], 'pulse'); ?>><?php esc_html_e('Pulse', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="fade" <?php selected($animations['type'], 'fade'); ?>><?php esc_html_e('Fade', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="slide" <?php selected($animations['type'], 'slide'); ?>><?php esc_html_e('Slide Up', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="rotate" <?php selected($animations['type'], 'rotate'); ?>><?php esc_html_e('Rotate', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="shake" <?php selected($animations['type'], 'shake'); ?>><?php esc_html_e('Shake', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="heartbeat" <?php selected($animations['type'], 'heartbeat'); ?>><?php esc_html_e('Heartbeat', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="swing" <?php selected($animations['type'], 'swing'); ?>><?php esc_html_e('Swing', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="ripple" <?php selected($animations['type'], 'ripple'); ?>><?php esc_html_e('Ripple', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="none" <?php selected($animations['type'], 'none'); ?>><?php esc_html_e('None', 'wp-bottom-navigation-pro'); ?></option>
                </select>
            </div>
            
            <div class="wpbnp-field">
                <label><?php esc_html_e('Duration (ms)', 'wp-bottom-navigation-pro'); ?></label>
                <input type="number" name="settings[animations][duration]" 
                       value="<?php echo esc_attr($animations['duration']); ?>" 
                       min="100" max="1000" step="50">
            </div>
        </div>
        <?php
    }
    
    /**
     * Render badges tab
     */
    private function render_badges_tab($settings) {
        $badges = $settings['badges'];
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Badge Settings', 'wp-bottom-navigation-pro'); ?></h2>
            
            <div class="wpbnp-field">
                <label>
                    <input type="checkbox" name="settings[badges][enabled]" 
                           value="1" <?php checked($badges['enabled']); ?>>
                    <?php esc_html_e('Enable Badges', 'wp-bottom-navigation-pro'); ?>
                </label>
            </div>
            
            <div class="wpbnp-field-group">
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Background Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[badges][background_color]" 
                           value="<?php echo esc_attr($badges['background_color']); ?>" 
                           class="wpbnp-color-picker">
                </div>
                
                <div class="wpbnp-field">
                    <label><?php esc_html_e('Text Color', 'wp-bottom-navigation-pro'); ?></label>
                    <input type="text" name="settings[badges][text_color]" 
                           value="<?php echo esc_attr($badges['text_color']); ?>" 
                           class="wpbnp-color-picker">
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render display rules tab
     */
    private function render_display_rules_tab($settings) {
        $display_rules = $settings['display_rules'];
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Display Rules', 'wp-bottom-navigation-pro'); ?></h2>
            
            <div class="wpbnp-field">
                <label><?php esc_html_e('User Roles', 'wp-bottom-navigation-pro'); ?></label>
                <?php
                $roles = wp_roles()->get_names();
                foreach ($roles as $role_key => $role_name):
                ?>
                <label class="wpbnp-checkbox-label">
                    <input type="checkbox" name="settings[display_rules][user_roles][]" 
                           value="<?php echo esc_attr($role_key); ?>"
                           <?php checked(in_array($role_key, $display_rules['user_roles'])); ?>>
                    <?php echo esc_html($role_name); ?>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div class="wpbnp-field">
                <label>
                    <input type="checkbox" name="settings[display_rules][hide_on_admin]" 
                           value="1" <?php checked($display_rules['hide_on_admin']); ?>>
                    <?php esc_html_e('Hide on Admin Pages', 'wp-bottom-navigation-pro'); ?>
                </label>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render presets tab
     */
    private function render_presets_tab($settings) {
        $presets = wpbnp_get_presets();
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Design Presets', 'wp-bottom-navigation-pro'); ?></h2>
            <p><?php esc_html_e('Choose a preset to quickly apply a design style.', 'wp-bottom-navigation-pro'); ?></p>
            
            <div class="wpbnp-preset-grid">
                <?php foreach ($presets as $preset_key => $preset_data): ?>
                <div class="wpbnp-preset-card <?php echo isset($settings['preset']) && $settings['preset'] === $preset_key ? 'active' : ''; ?>" 
                     data-preset="<?php echo esc_attr($preset_key); ?>">
                    <div class="wpbnp-preset-preview">
                        <!-- Preview thumbnail would go here -->
                        <div class="wpbnp-preset-demo" style="background-color: <?php echo esc_attr($preset_data['style']['background_color']); ?>;">
                            <span style="color: <?php echo esc_attr($preset_data['style']['text_color']); ?>;">●</span>
                            <span style="color: <?php echo esc_attr($preset_data['style']['active_color']); ?>;">●</span>
                            <span style="color: <?php echo esc_attr($preset_data['style']['text_color']); ?>;">●</span>
                        </div>
                    </div>
                    <h4><?php echo esc_html($preset_data['name']); ?></h4>
                    <p><?php echo esc_html($preset_data['description']); ?></p>
                    <button type="button" class="button wpbnp-apply-preset" data-preset="<?php echo esc_attr($preset_key); ?>">
                        <?php esc_html_e('Apply Preset', 'wp-bottom-navigation-pro'); ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <input type="hidden" name="settings[preset]" value="<?php echo esc_attr($settings['preset']); ?>">
        </div>
        <?php
    }
    
    /**
     * Render advanced tab
     */
    private function render_advanced_tab($settings) {
        $advanced = $settings['advanced'];
        ?>
        <div class="wpbnp-section">
            <h2><?php esc_html_e('Advanced Settings', 'wp-bottom-navigation-pro'); ?></h2>
            
            <div class="wpbnp-field">
                <label><?php esc_html_e('Z-Index', 'wp-bottom-navigation-pro'); ?></label>
                <input type="number" name="settings[advanced][z_index]" 
                       value="<?php echo esc_attr($advanced['z_index']); ?>" 
                       min="1" max="99999">
            </div>
            
            <div class="wpbnp-field">
                <label><?php esc_html_e('Position', 'wp-bottom-navigation-pro'); ?></label>
                <select name="settings[advanced][fixed_position]">
                    <option value="bottom" <?php selected($advanced['fixed_position'], 'bottom'); ?>><?php esc_html_e('Bottom', 'wp-bottom-navigation-pro'); ?></option>
                    <option value="top" <?php selected($advanced['fixed_position'], 'top'); ?>><?php esc_html_e('Top', 'wp-bottom-navigation-pro'); ?></option>
                </select>
            </div>
            
            <div class="wpbnp-field">
                <label><?php esc_html_e('Custom CSS', 'wp-bottom-navigation-pro'); ?></label>
                <textarea name="settings[advanced][custom_css]" rows="10" cols="50"><?php echo esc_textarea($advanced['custom_css']); ?></textarea>
                <p class="description"><?php esc_html_e('Add custom CSS rules. Use .wpbnp-bottom-nav as the base selector.', 'wp-bottom-navigation-pro'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render page targeting tab (PRO feature)
     */
    private function render_page_targeting_tab($settings) {
        $page_targeting = isset($settings['page_targeting']) ? $settings['page_targeting'] : array();
        $is_pro_active = $this->is_pro_license_active();
        
        ?>
        <div class="wpbnp-tab-content" id="wpbnp-page-targeting">
            <h2><?php esc_html_e('Page Targeting', 'wp-bottom-navigation-pro'); ?> <span class="wpbnp-pro-badge">PRO</span></h2>
            <p class="description"><?php esc_html_e('Create different navigation bars for specific pages, posts, or post types.', 'wp-bottom-navigation-pro'); ?></p>
            
            <?php if (!$is_pro_active): ?>
                <div class="wpbnp-pro-notice">
                    <div class="wpbnp-pro-notice-content">
                        <h3><?php esc_html_e('🚀 Unlock Page Targeting (PRO Feature)', 'wp-bottom-navigation-pro'); ?></h3>
                        <p><?php esc_html_e('Create multiple navigation configurations and display them on specific pages, posts, or post types. Perfect for e-commerce sites, blogs, and complex websites.', 'wp-bottom-navigation-pro'); ?></p>
                        
                        <div class="wpbnp-pro-features">
                            <ul>
                                <li>✅ <?php esc_html_e('Multiple Navigation Configurations', 'wp-bottom-navigation-pro'); ?></li>
                                <li>✅ <?php esc_html_e('Page-Specific Navigation Bars', 'wp-bottom-navigation-pro'); ?></li>
                                <li>✅ <?php esc_html_e('Post Type Targeting', 'wp-bottom-navigation-pro'); ?></li>
                                <li>✅ <?php esc_html_e('Category & Tag Targeting', 'wp-bottom-navigation-pro'); ?></li>
                                <li>✅ <?php esc_html_e('User Role Based Display', 'wp-bottom-navigation-pro'); ?></li>
                                <li>✅ <?php esc_html_e('Priority System', 'wp-bottom-navigation-pro'); ?></li>
                                <li>✅ <?php esc_html_e('Advanced Conditional Logic', 'wp-bottom-navigation-pro'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="wpbnp-pro-actions">
                            <a href="#" class="wpbnp-pro-button" id="wpbnp-activate-license">
                                <?php esc_html_e('Enter License Key', 'wp-bottom-navigation-pro'); ?>
                            </a>
                            <a href="https://riadhasan.info/" target="_blank" class="wpbnp-pro-button wpbnp-pro-button-secondary">
                                <?php esc_html_e('Get Pro License', 'wp-bottom-navigation-pro'); ?>
                            </a>
                        </div>
                        
                        <div class="wpbnp-demo-note">
                            <p><strong>🧪 Demo Testing:</strong> For testing purposes, use any license key that is at least 10 characters long and contains both letters and numbers (e.g., "demo123456789").</p>
                        </div>
                    </div>
                </div>
                
                <!-- License Activation Modal -->
                <div id="wpbnp-license-modal" class="wpbnp-modal" style="display: none;">
                    <div class="wpbnp-modal-content">
                        <div class="wpbnp-modal-header">
                            <h3><?php esc_html_e('Activate Pro License', 'wp-bottom-navigation-pro'); ?></h3>
                            <span class="wpbnp-modal-close">&times;</span>
                        </div>
                        <div class="wpbnp-modal-body">
                            <form id="wpbnp-license-form">
                                <div class="wpbnp-field">
                                    <label for="wpbnp-license-key"><?php esc_html_e('License Key', 'wp-bottom-navigation-pro'); ?></label>
                                    <input type="text" id="wpbnp-license-key" name="license_key" placeholder="<?php esc_attr_e('Enter your license key...', 'wp-bottom-navigation-pro'); ?>" required>
                                    <p class="description"><?php esc_html_e('Enter your Pro license key to unlock advanced features.', 'wp-bottom-navigation-pro'); ?></p>
                                </div>
                                <div class="wpbnp-field">
                                    <button type="submit" class="wpbnp-pro-button">
                                        <?php esc_html_e('Activate License', 'wp-bottom-navigation-pro'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- PRO Features - Page Targeting Interface -->
                <div class="wpbnp-page-targeting-interface">
                    <div class="wpbnp-targeting-header">
                        <h3><?php esc_html_e('Navigation Configurations', 'wp-bottom-navigation-pro'); ?></h3>
                        <button type="button" class="wpbnp-add-config-btn" id="wpbnp-add-config">
                            <?php esc_html_e('+ Add Configuration', 'wp-bottom-navigation-pro'); ?>
                        </button>
                    </div>
                    
                    <div class="wpbnp-configurations-list" id="wpbnp-configurations-list">
                        <?php $this->render_page_targeting_configurations($page_targeting); ?>
                    </div>
                </div>
                
                <!-- Configuration Template (Hidden) -->
                <div id="wpbnp-config-template" style="display: none;">
                    <?php $this->render_configuration_template(); ?>
                </div>
                
            <?php endif; ?>
            
            <!-- Hidden fields for page targeting settings -->
            <input type="hidden" name="settings[page_targeting][enabled]" value="<?php echo $is_pro_active ? '1' : '0'; ?>">
        </div>
        <?php
    }
    
    /**
     * Check if pro license is active
     */
    private function is_pro_license_active() {
        $license_key = get_option('wpbnp_pro_license_key', '');
        $license_status = get_option('wpbnp_pro_license_status', 'inactive');
        
        // For demo purposes, you can temporarily return true to test the interface
        // return true;
        
        return !empty($license_key) && $license_status === 'active';
    }
    
    /**
     * Render page targeting configurations
     */
    private function render_page_targeting_configurations($page_targeting) {
        $configurations = isset($page_targeting['configurations']) ? $page_targeting['configurations'] : array();
        
        if (empty($configurations)) {
            echo '<p class="wpbnp-no-configs">' . esc_html__('No configurations created yet. Click "Add Configuration" to get started.', 'wp-bottom-navigation-pro') . '</p>';
            return;
        }
        
        foreach ($configurations as $index => $config) {
            $this->render_configuration_item($config, $index);
        }
    }
    
    /**
     * Render individual configuration item
     */
    private function render_configuration_item($config, $index) {
        $config_id = isset($config['id']) ? $config['id'] : 'config_' . $index;
        $config_name = isset($config['name']) ? $config['name'] : __('Untitled Configuration', 'wp-bottom-navigation-pro');
        $priority = isset($config['priority']) ? $config['priority'] : 1;
        $conditions = isset($config['conditions']) ? $config['conditions'] : array();
        
        ?>
        <div class="wpbnp-config-item" data-config-id="<?php echo esc_attr($config_id); ?>">
            <div class="wpbnp-config-header">
                <div class="wpbnp-config-title">
                    <span class="wpbnp-config-name"><?php echo esc_html($config_name); ?></span>
                    <span class="wpbnp-config-priority">Priority: <?php echo esc_html($priority); ?></span>
                </div>
                <div class="wpbnp-config-actions">
                    <button type="button" class="wpbnp-config-toggle" title="<?php esc_attr_e('Expand/Collapse', 'wp-bottom-navigation-pro'); ?>">
                        <span class="dashicons dashicons-arrow-down"></span>
                    </button>
                    <button type="button" class="wpbnp-config-delete" title="<?php esc_attr_e('Delete Configuration', 'wp-bottom-navigation-pro'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            
            <div class="wpbnp-config-content" style="display: none;">
                <div class="wpbnp-config-settings">
                    <div class="wpbnp-field">
                        <label><?php esc_html_e('Configuration Name', 'wp-bottom-navigation-pro'); ?></label>
                        <input type="text" name="settings[page_targeting][configurations][<?php echo $index; ?>][name]" 
                               value="<?php echo esc_attr($config_name); ?>" placeholder="<?php esc_attr_e('Enter configuration name...', 'wp-bottom-navigation-pro'); ?>">
                    </div>
                    
                    <div class="wpbnp-field">
                        <label><?php esc_html_e('Priority', 'wp-bottom-navigation-pro'); ?></label>
                        <input type="number" name="settings[page_targeting][configurations][<?php echo $index; ?>][priority]" 
                               value="<?php echo esc_attr($priority); ?>" min="1" max="100">
                        <p class="description"><?php esc_html_e('Higher priority configurations will override lower ones when conditions match.', 'wp-bottom-navigation-pro'); ?></p>
                    </div>
                    
                    <div class="wpbnp-targeting-conditions">
                        <h4><?php esc_html_e('Display Conditions', 'wp-bottom-navigation-pro'); ?></h4>
                        
                        <div class="wpbnp-condition-group">
                            <label><?php esc_html_e('Specific Pages', 'wp-bottom-navigation-pro'); ?></label>
                            <?php $this->render_page_selector($conditions, $index); ?>
                        </div>
                        
                        <div class="wpbnp-condition-group">
                            <label><?php esc_html_e('Post Types', 'wp-bottom-navigation-pro'); ?></label>
                            <?php $this->render_post_type_selector($conditions, $index); ?>
                        </div>
                        
                        <div class="wpbnp-condition-group">
                            <label><?php esc_html_e('Categories', 'wp-bottom-navigation-pro'); ?></label>
                            <?php $this->render_category_selector($conditions, $index); ?>
                        </div>
                        
                        <div class="wpbnp-condition-group">
                            <label><?php esc_html_e('User Roles', 'wp-bottom-navigation-pro'); ?></label>
                            <?php $this->render_user_role_selector($conditions, $index); ?>
                        </div>
                    </div>
                    
                    <div class="wpbnp-navigation-config">
                        <h4><?php esc_html_e('Navigation Configuration', 'wp-bottom-navigation-pro'); ?></h4>
                        <div class="wpbnp-field">
                            <label><?php esc_html_e('Navigation Items', 'wp-bottom-navigation-pro'); ?></label>
                            <p class="description"><?php esc_html_e('This configuration will use the items defined in the main Items tab with the conditions above.', 'wp-bottom-navigation-pro'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden fields -->
            <input type="hidden" name="settings[page_targeting][configurations][<?php echo $index; ?>][id]" value="<?php echo esc_attr($config_id); ?>">
        </div>
        <?php
    }
    
    /**
     * Render page selector
     */
    private function render_page_selector($conditions, $index) {
        $selected_pages = isset($conditions['pages']) ? $conditions['pages'] : array();
        $pages = get_pages();
        
        ?>
        <select name="settings[page_targeting][configurations][<?php echo $index; ?>][conditions][pages][]" multiple class="wpbnp-multiselect">
            <option value=""><?php esc_html_e('Select pages...', 'wp-bottom-navigation-pro'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected(in_array($page->ID, $selected_pages)); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render post type selector
     */
    private function render_post_type_selector($conditions, $index) {
        $selected_post_types = isset($conditions['post_types']) ? $conditions['post_types'] : array();
        $post_types = get_post_types(array('public' => true), 'objects');
        
        ?>
        <select name="settings[page_targeting][configurations][<?php echo $index; ?>][conditions][post_types][]" multiple class="wpbnp-multiselect">
            <option value=""><?php esc_html_e('Select post types...', 'wp-bottom-navigation-pro'); ?></option>
            <?php foreach ($post_types as $post_type): ?>
                <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected(in_array($post_type->name, $selected_post_types)); ?>>
                    <?php echo esc_html($post_type->label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render category selector
     */
    private function render_category_selector($conditions, $index) {
        $selected_categories = isset($conditions['categories']) ? $conditions['categories'] : array();
        $categories = get_categories();
        
        ?>
        <select name="settings[page_targeting][configurations][<?php echo $index; ?>][conditions][categories][]" multiple class="wpbnp-multiselect">
            <option value=""><?php esc_html_e('Select categories...', 'wp-bottom-navigation-pro'); ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected(in_array($category->term_id, $selected_categories)); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render user role selector
     */
    private function render_user_role_selector($conditions, $index) {
        $selected_roles = isset($conditions['user_roles']) ? $conditions['user_roles'] : array();
        $roles = wp_roles()->get_names();
        
        ?>
        <select name="settings[page_targeting][configurations][<?php echo $index; ?>][conditions][user_roles][]" multiple class="wpbnp-multiselect">
            <option value=""><?php esc_html_e('Select user roles...', 'wp-bottom-navigation-pro'); ?></option>
            <?php foreach ($roles as $role_key => $role_name): ?>
                <option value="<?php echo esc_attr($role_key); ?>" <?php selected(in_array($role_key, $selected_roles)); ?>>
                    <?php echo esc_html($role_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}
