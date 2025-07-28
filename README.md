# WP Bottom Navigation Pro

A powerful and customizable WordPress plugin that adds a modern bottom navigation bar to your website, perfect for mobile-first designs and improved user experience.

## 🚀 Features

### ✨ **Modern Design System**
- **10 Premium Presets**: Minimal, Dark Mode, Material Design, iOS Native, Glassmorphism, Neumorphism, Cyberpunk, Vintage, Gradient Flow, and Floating Pill
- **Bootstrap Icons**: 800+ modern outline icons included
- **Responsive Design**: Perfect on all devices and screen sizes
- **Custom Animations**: Smooth transitions and hover effects

### 🎨 **Complete Customization**
- **Visual Style Editor**: Colors, fonts, spacing, shadows, and borders
- **Icon Library**: Multiple icon libraries (Bootstrap, FontAwesome, Material, Apple SF, Dashicons, Feather)
- **Animation Controls**: Enable/disable animations with duration settings
- **Device Targeting**: Show/hide on specific devices with custom breakpoints

### 🛠️ **Advanced Functionality**
- **Drag & Drop Builder**: Easy item management with sortable interface
- **User Role Restrictions**: Control visibility per user role
- **Badge System**: Show notification counts on navigation items
- **Import/Export**: Backup and share your configurations
- **Reset Options**: Quick reset to defaults

### 📱 **Mobile Optimized**
- **Touch-Friendly**: Optimized for mobile interactions
- **App-Like Experience**: Native mobile app feel
- **Performance Focused**: Lightweight and fast loading
- **Cross-Browser Compatible**: Works on all modern browsers

## 📦 Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

### Manual Installation
1. Upload the `wp-bottom-navigation-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress 'Plugins' menu
3. Go to Appearance → Bottom Navigation to configure

## ⚙️ Configuration

### Quick Setup
1. **Enable the Plugin**: Check "Enable Bottom Navigation" in the Items tab
2. **Add Navigation Items**: Use the drag-and-drop interface to add items
3. **Choose a Preset**: Select from 10 beautiful presets in the Styles tab
4. **Customize**: Adjust colors, fonts, and animations to match your brand

### Navigation Items
Each navigation item supports:
- **Custom Label**: Display text for the item
- **Icon Selection**: Choose from 800+ Bootstrap icons
- **URL/Link**: Internal or external links
- **User Roles**: Restrict visibility to specific user roles
- **Badge Counts**: Show notification numbers
- **Enable/Disable**: Toggle item visibility

### Style Customization
- **Colors**: Background, text, active, hover, and icon colors
- **Typography**: Font size, weight, and styling
- **Layout**: Height, padding, border radius
- **Effects**: Box shadows, borders, and transitions
- **Animations**: Bounce, fade, slide, scale, rotate, and pulse effects

### Device Settings
- **Mobile**: Customize mobile breakpoint and visibility
- **Tablet**: Control tablet display settings
- **Desktop**: Desktop-specific configurations

## 🎨 Available Presets

| Preset | Description | Best For |
|--------|-------------|----------|
| **Minimal** | Clean and simple design | Professional websites |
| **Dark Mode** | Elegant dark theme | Night-time browsing |
| **Material Design** | Google's Material Design | Modern web apps |
| **iOS Native** | Apple iOS style | Mobile-first sites |
| **Glassmorphism** | Modern glass effect | Creative portfolios |
| **Neumorphism** | Soft UI with depth | Minimalist designs |
| **Cyberpunk** | Futuristic neon design | Gaming/tech sites |
| **Vintage** | Classic retro design | Traditional websites |
| **Gradient Flow** | Dynamic gradients | Creative agencies |
| **Floating Pill** | Rounded floating design | Modern mobile apps |

## 🔧 Technical Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Modern Browser**: Chrome, Firefox, Safari, Edge

## 📁 File Structure

```
wp-bottom-navigation-pro/
├── wp-bottom-navigation-pro.php    # Main plugin file
├── README.md                       # Documentation
├── index.php                       # Security file
├── includes/                       # Core functionality
│   ├── functions.php              # Plugin functions
│   ├── frontend.php               # Frontend rendering
│   ├── shortcode.php              # Shortcode support
│   └── index.php                  # Security file
├── admin/                         # Admin interface
│   ├── settings-ui.php            # Settings page UI
│   └── index.php                  # Security file
├── assets/                        # Static assets
│   ├── css/                       # Stylesheets
│   │   ├── admin.css             # Admin styles
│   │   ├── frontend.css          # Frontend styles
│   │   ├── icons.css             # Icon definitions
│   │   └── index.php             # Security file
│   ├── js/                       # JavaScript files
│   │   ├── admin.js              # Admin functionality
│   │   ├── frontend.js           # Frontend behavior
│   │   └── index.php             # Security file
│   └── index.php                 # Security file
├── presets/                      # Design presets
│   ├── default-presets.json     # Preset definitions
│   └── index.php                # Security file
└── languages/                   # Translations
    ├── wp-bottom-navigation-pro.pot  # Translation template
    └── index.php                     # Security file
```

## 🛡️ Security Features

- **Nonce Verification**: All AJAX requests are secured with WordPress nonces
- **Capability Checks**: Proper user permission validation
- **Data Sanitization**: All input data is sanitized and validated
- **SQL Injection Prevention**: Uses WordPress database API
- **XSS Protection**: Output escaping and input filtering
- **Directory Protection**: Index files prevent directory browsing

## 🎯 Usage Examples

### Basic Navigation
```php
// The plugin automatically adds the navigation to your site
// No code required - just configure in the admin panel
```

### Shortcode Support
```php
// Display navigation anywhere with shortcode
[wp_bottom_navigation]
```

### Programmatic Control
```php
// Check if plugin is active
if (function_exists('wpbnp_is_enabled')) {
    if (wpbnp_is_enabled()) {
        // Navigation is active
    }
}
```

## 🔄 Updates & Maintenance

The plugin follows WordPress.org coding standards and best practices:
- **Semantic Versioning**: Clear version numbering
- **Backward Compatibility**: Maintains compatibility with older versions
- **Performance Optimized**: Minimal database queries and efficient code
- **Translation Ready**: Full i18n support with .pot file included

## 🤝 Contributing

This plugin follows WordPress coding standards. Key guidelines:
- Use WordPress hooks and filters
- Sanitize all input data
- Escape all output data
- Follow WordPress naming conventions
- Include proper documentation

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🆘 Support

For support and questions:
1. Check the plugin settings and documentation
2. Review common issues in the troubleshooting section
3. Contact the plugin developer

## 📊 Changelog

### Version 1.0.0
- Initial release
- 10 premium presets
- Bootstrap Icons integration
- Complete admin interface
- Mobile-responsive design
- Animation system
- Import/export functionality

---

**Made with ❤️ by [Riad Hasan](https://riadhasan.info/) for the WordPress community**