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
}
