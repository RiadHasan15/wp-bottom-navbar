# WP Bottom Navigation Pro - Changelog

## Version 1.3.0 - 2024-12-19
### Fixed
- **Display Conditions Loading Issue**: Fixed pages and categories not showing when creating new configurations (now loads via AJAX)
- **Presets/Configurations Disappearing**: Fixed automatic removal of presets and configurations by improving form state preservation
- **Dynamic Content Population**: Added AJAX handlers to populate pages and categories in new configurations immediately

### Technical Changes
- Added `populatePagesSelector()` and `populateCategoriesSelector()` JavaScript functions
- Added AJAX handlers `ajax_get_pages()` and `ajax_get_categories()` in PHP
- Enhanced form state saving when creating presets and configurations
- Improved error handling and user feedback

### Files Modified
- `assets/js/admin.js` - Added dynamic selector population functions
- `wp-bottom-navigation-pro.php` - Added AJAX handlers and updated version
- `CHANGELOG.md` - Updated with latest changes

## Version 1.2.9 - 2024-12-19
### Fixed
- **Pages Dropdown Issue**: Fixed "Specific Pages" dropdown not showing pages in Page Targeting feature
- **Simplified Page Retrieval**: Replaced complex `get_pages()` with reliable `get_posts()` method
- **Auto Page Creation**: Automatically creates a sample page if none exist for testing
- **Project Cleanup**: Removed all unnecessary diagnostic and test files

### Technical Changes
- Simplified `render_page_selector()` function in `admin/settings-ui.php`
- Added fallback to posts if no pages exist
- Added automatic sample page creation for empty installations
- Added debug comments to help identify issues

### Files Modified
- `admin/settings-ui.php` - Fixed page selector function
- `wp-bottom-navigation-pro.php` - Updated version to 1.2.9
- Removed 15+ unnecessary diagnostic/test files

## Previous Versions
- v1.2.8 - Initial attempt to fix pages dropdown
- v1.2.7 - Deep debugging for preset issues
- v1.2.6 - Custom presets functionality
- v1.2.5 - Page targeting pro features
- v1.2.4 - Tab navigation fixes
- v1.2.3 - Preset editing improvements
- v1.2.2 - Page targeting button fixes
- v1.2.1 - Animation and preset fixes

## Current Status
✅ Pages dropdown should now work correctly
✅ Project is clean and organized
✅ Auto-creates sample page if needed
✅ Debug information available in HTML comments