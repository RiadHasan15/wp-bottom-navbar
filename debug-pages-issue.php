<?php
/**
 * Debug Pages Issue - Diagnostic Script
 * 
 * This script helps diagnose why pages are not showing in the 
 * "Specific Pages" dropdown in the Page Targeting feature.
 */

// Ensure we're in WordPress context
if (!function_exists('get_pages')) {
    die('This script must be run in WordPress context. Add this code to a WordPress page or plugin.');
}

echo "<h1>🔍 WP Bottom Navigation Pro - Pages Issue Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 5px; }
    .error { background: #ffebee; border-left: 4px solid #f44336; }
    .success { background: #e8f5e8; border-left: 4px solid #4caf50; }
    .warning { background: #fff3e0; border-left: 4px solid #ff9800; }
    .code { background: #f5f5f5; padding: 10px; font-family: monospace; border-radius: 3px; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
    .page-list { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
</style>";

// Test 1: Check get_pages() function
echo "<div class='section'>";
echo "<h2>📄 Test 1: get_pages() Function Check</h2>";

$pages_method1 = get_pages(array(
    'sort_order' => 'ASC',
    'sort_column' => 'post_title',
    'post_status' => 'publish',
    'number' => 0,
    'hierarchical' => 1
));

echo "<div class='test-result'>";
echo "<strong>get_pages() result:</strong> " . count($pages_method1) . " pages found";
echo "</div>";

if (!empty($pages_method1)) {
    echo "<div class='test-result success'>✅ get_pages() working correctly</div>";
    echo "<div class='page-list'>";
    echo "<strong>Pages found:</strong><br>";
    foreach ($pages_method1 as $page) {
        echo "- ID: {$page->ID}, Title: \"{$page->post_title}\", Status: {$page->post_status}<br>";
    }
    echo "</div>";
} else {
    echo "<div class='test-result warning'>⚠️ get_pages() returned no results</div>";
}
echo "</div>";

// Test 2: Check get_posts() fallback
echo "<div class='section'>";
echo "<h2>📄 Test 2: get_posts() Fallback Check</h2>";

$pages_method2 = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

echo "<div class='test-result'>";
echo "<strong>get_posts() result:</strong> " . count($pages_method2) . " pages found";
echo "</div>";

if (!empty($pages_method2)) {
    echo "<div class='test-result success'>✅ get_posts() fallback working</div>";
    echo "<div class='page-list'>";
    echo "<strong>Pages found via get_posts():</strong><br>";
    foreach ($pages_method2 as $page) {
        echo "- ID: {$page->ID}, Title: \"{$page->post_title}\", Status: {$page->post_status}, Type: {$page->post_type}<br>";
    }
    echo "</div>";
} else {
    echo "<div class='test-result warning'>⚠️ get_posts() fallback also returned no results</div>";
}
echo "</div>";

// Test 3: Check all content (pages + posts)
echo "<div class='section'>";
echo "<h2>📄 Test 3: All Content Check (Pages + Posts)</h2>";

$all_content = get_posts(array(
    'post_type' => array('page', 'post'),
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

echo "<div class='test-result'>";
echo "<strong>All content result:</strong> " . count($all_content) . " items found";
echo "</div>";

if (!empty($all_content)) {
    echo "<div class='test-result success'>✅ Found content (pages and/or posts)</div>";
    echo "<div class='page-list'>";
    echo "<strong>All content found:</strong><br>";
    $page_count = 0;
    $post_count = 0;
    foreach ($all_content as $item) {
        if ($item->post_type === 'page') $page_count++;
        if ($item->post_type === 'post') $post_count++;
        echo "- ID: {$item->ID}, Title: \"{$item->post_title}\", Type: {$item->post_type}, Status: {$item->post_status}<br>";
    }
    echo "</div>";
    echo "<div class='test-result'>";
    echo "<strong>Summary:</strong> {$page_count} pages, {$post_count} posts";
    echo "</div>";
} else {
    echo "<div class='test-result error'>❌ No content found at all!</div>";
}
echo "</div>";

// Test 4: Database direct check
echo "<div class='section'>";
echo "<h2>🗄️ Test 4: Direct Database Check</h2>";

global $wpdb;
$db_pages = $wpdb->get_results("
    SELECT ID, post_title, post_status, post_type 
    FROM {$wpdb->posts} 
    WHERE post_type = 'page' 
    AND post_status = 'publish' 
    ORDER BY post_title ASC
");

echo "<div class='test-result'>";
echo "<strong>Direct database query:</strong> " . count($db_pages) . " pages found";
echo "</div>";

if (!empty($db_pages)) {
    echo "<div class='test-result success'>✅ Database contains published pages</div>";
    echo "<div class='page-list'>";
    echo "<strong>Pages in database:</strong><br>";
    foreach ($db_pages as $page) {
        echo "- ID: {$page->ID}, Title: \"{$page->post_title}\", Status: {$page->post_status}<br>";
    }
    echo "</div>";
} else {
    echo "<div class='test-result error'>❌ No pages found in database</div>";
}
echo "</div>";

// Test 5: Check WordPress installation state
echo "<div class='section'>";
echo "<h2>🔧 Test 5: WordPress Installation State</h2>";

$sample_page = get_page_by_title('Sample Page');
$hello_world = get_post(1); // Usually the first post

echo "<div class='test-result'>";
echo "<strong>Sample Page exists:</strong> " . ($sample_page ? 'Yes (ID: ' . $sample_page->ID . ')' : 'No');
echo "</div>";

echo "<div class='test-result'>";
echo "<strong>Hello World post exists:</strong> " . ($hello_world ? 'Yes (ID: ' . $hello_world->ID . ')' : 'No');
echo "</div>";

// Check if this is a fresh WordPress installation
$total_posts = wp_count_posts('post');
$total_pages = wp_count_posts('page');

echo "<div class='test-result'>";
echo "<strong>Total posts:</strong> " . $total_posts->publish . " published";
echo "</div>";

echo "<div class='test-result'>";
echo "<strong>Total pages:</strong> " . $total_pages->publish . " published";
echo "</div>";

if ($total_pages->publish == 0 && $total_posts->publish <= 1) {
    echo "<div class='test-result warning'>⚠️ This appears to be a fresh WordPress installation with minimal content</div>";
}
echo "</div>";

// Test 6: Recommendations
echo "<div class='section'>";
echo "<h2>💡 Test 6: Diagnostic Results & Recommendations</h2>";

$total_found = max(count($pages_method1), count($pages_method2), count($all_content));

if ($total_found > 0) {
    echo "<div class='test-result success'>";
    echo "<strong>✅ GOOD NEWS:</strong> Pages/content found! The issue might be in the admin interface rendering.";
    echo "</div>";
    
    echo "<div class='test-result'>";
    echo "<strong>Recommended actions:</strong>";
    echo "<ul>";
    echo "<li>Clear any caching plugins</li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Verify you're looking at the correct dropdown</li>";
    echo "<li>Try refreshing the admin page</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='test-result error'>";
    echo "<strong>❌ ISSUE FOUND:</strong> No pages or content available in WordPress.";
    echo "</div>";
    
    echo "<div class='test-result'>";
    echo "<strong>Required actions:</strong>";
    echo "<ul>";
    echo "<li><strong>Create pages:</strong> Go to Pages → Add New in WordPress admin</li>";
    echo "<li><strong>Publish content:</strong> Make sure pages are published, not drafts</li>";
    echo "<li><strong>Check permissions:</strong> Ensure you have permission to view pages</li>";
    echo "</ul>";
    echo "</div>";
    
    // Offer to create sample pages
    if (current_user_can('edit_pages')) {
        echo "<div class='test-result warning'>";
        echo "<strong>🔧 QUICK FIX:</strong> I can create some sample pages for testing.";
        echo "<br><br>";
        echo "<strong>Sample pages to create:</strong>";
        echo "<ul>";
        echo "<li>Home - Welcome page</li>";
        echo "<li>About - About us page</li>";
        echo "<li>Contact - Contact information</li>";
        echo "<li>Services - Our services</li>";
        echo "</ul>";
        
        echo "<p><strong>To create these pages, add this code to your functions.php temporarily:</strong></p>";
        echo "<div class='code'>";
        echo htmlspecialchars("
function wpbnp_create_sample_pages() {
    \$sample_pages = array(
        'Home' => 'Welcome to our homepage. This is a sample page created for testing the WP Bottom Navigation Pro plugin.',
        'About' => 'Learn more about us. This page contains information about our company and mission.',
        'Contact' => 'Get in touch with us. You can reach us through the contact information provided here.',
        'Services' => 'Our services and offerings. Discover what we can do for you and your business.'
    );
    
    foreach (\$sample_pages as \$title => \$content) {
        \$existing = get_page_by_title(\$title);
        if (!\$existing) {
            \$page_id = wp_insert_post(array(
                'post_title' => \$title,
                'post_content' => \$content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id()
            ));
            if (\$page_id && !is_wp_error(\$page_id)) {
                echo 'Created page: ' . \$title . ' (ID: ' . \$page_id . ')<br>';
            }
        }
    }
}

// Run once, then remove this code
add_action('admin_init', 'wpbnp_create_sample_pages');
        ");
        echo "</div>";
        echo "</div>";
    }
}
echo "</div>";

// Test 7: JavaScript Debug Code
echo "<div class='section'>";
echo "<h2>🔬 Test 7: Browser Console Debug Code</h2>";
echo "<p>If pages exist but still don't show in the dropdown, run this in your browser console:</p>";
echo "<div class='code'>";
echo htmlspecialchars("
// Check if the dropdown element exists
console.log('Page selectors found:', $('.wpbnp-multiselect').length);

// Check dropdown content
$('.wpbnp-multiselect').each(function(index) {
    const \$select = $(this);
    const name = \$select.attr('name');
    if (name && name.includes('pages')) {
        console.log('Page selector ' + index + ':', {
            name: name,
            options: \$select.find('option').length,
            content: \$select.html()
        });
    }
});

// Check if we're on the right tab
console.log('Current URL:', window.location.href);
console.log('Page targeting tab active:', window.location.href.includes('page_targeting'));
");
echo "</div>";
echo "</div>";

echo "<div class='section success'>";
echo "<h2>✅ Diagnostic Complete</h2>";
echo "<p>Review the results above to identify why pages are not showing in the dropdown.</p>";
if ($total_found > 0) {
    echo "<p><strong>Summary:</strong> Content exists, likely an interface rendering issue.</p>";
} else {
    echo "<p><strong>Summary:</strong> No content found, need to create pages first.</p>";
}
echo "</div>";
?>