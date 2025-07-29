<?php
/**
 * Merge Conflict Resolution Helper
 * Run this script after resolving merge conflicts to ensure consistency
 */

// Define the workspace directory
$workspace = __DIR__;

echo "🔧 WP Bottom Navigation Pro - Merge Conflict Resolution Helper\n";
echo "================================================================\n\n";

// Check for common conflict patterns
$files_to_check = [
    'wp-bottom-navigation-pro.php',
    'includes/functions.php',
    'admin/settings-ui.php',
    'assets/js/admin.js',
    'assets/css/admin.css'
];

$conflicts_found = false;

foreach ($files_to_check as $file) {
    $filepath = $workspace . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Check for unresolved conflict markers
    if (strpos($content, '<<<<<<<') !== false || 
        strpos($content, '=======') !== false || 
        strpos($content, '>>>>>>>') !== false) {
        echo "🔴 UNRESOLVED CONFLICTS in $file\n";
        $conflicts_found = true;
    } else {
        echo "✅ $file - No conflicts\n";
    }
}

if ($conflicts_found) {
    echo "\n🚨 CONFLICTS DETECTED!\n";
    echo "Please resolve all conflict markers before proceeding.\n\n";
    exit(1);
}

echo "\n🎉 All files checked - No unresolved conflicts found!\n\n";

// Perform consistency checks
echo "🔍 Performing consistency checks...\n";

// Check version consistency
$main_file = file_get_contents($workspace . '/wp-bottom-navigation-pro.php');
if (preg_match("/define\('WPBNP_VERSION', '([^']+)'\);/", $main_file, $matches)) {
    $version = $matches[1];
    echo "📌 Current version: $version\n";
    
    // Suggest version bump if needed
    if (version_compare($version, '1.2.0', '<')) {
        echo "💡 Consider bumping to 1.2.0 for pro branch merge\n";
    }
}

// Check for duplicate function definitions
$functions_file = $workspace . '/includes/functions.php';
if (file_exists($functions_file)) {
    $content = file_get_contents($functions_file);
    $functions = [];
    
    if (preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches)) {
        foreach ($matches[1] as $func_name) {
            if (isset($functions[$func_name])) {
                echo "🔴 DUPLICATE FUNCTION: $func_name\n";
                $conflicts_found = true;
            } else {
                $functions[$func_name] = true;
            }
        }
    }
    
    if (!$conflicts_found) {
        echo "✅ No duplicate functions found\n";
    }
}

// Check for JavaScript syntax
$js_file = $workspace . '/assets/js/admin.js';
if (file_exists($js_file)) {
    $content = file_get_contents($js_file);
    
    // Basic syntax checks
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    
    if ($open_braces !== $close_braces) {
        echo "🔴 JAVASCRIPT SYNTAX ERROR: Mismatched braces in admin.js\n";
        echo "   Open braces: $open_braces, Close braces: $close_braces\n";
        $conflicts_found = true;
    } else {
        echo "✅ JavaScript syntax looks good\n";
    }
}

if ($conflicts_found) {
    echo "\n🚨 ISSUES DETECTED!\n";
    echo "Please fix the issues above before proceeding.\n\n";
    exit(1);
}

echo "\n✅ ALL CHECKS PASSED!\n";
echo "The code is ready for the pro branch merge.\n\n";

echo "📋 POST-MERGE CHECKLIST:\n";
echo "- [ ] Test plugin activation/deactivation\n";
echo "- [ ] Verify license activation works\n";
echo "- [ ] Check page targeting functionality\n";
echo "- [ ] Test all existing features\n";
echo "- [ ] Check browser console for JS errors\n";
echo "- [ ] Verify PHP error log is clean\n\n";

echo "🎯 Merge completed successfully!\n";
?>