<?php
/**
 * Test AJAX handler for WP Bottom Navigation Pro
 */

header('Content-Type: application/json');

// Simulate WordPress AJAX response
$response = array(
    'success' => true,
    'data' => array(
        'message' => 'Settings saved successfully!',
        'settings' => isset($_POST['settings']) ? $_POST['settings'] : array()
    )
);

echo json_encode($response);
?>