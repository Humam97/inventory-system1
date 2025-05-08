<?php
// save_dashboard_config.php
session_start(); // Ensure session is started

// Include necessary configurations
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure only POST requests are accepted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Check user authentication (adjust based on your authentication method)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get raw POST data
$rawInput = file_get_contents('php://input');

// Log raw input for debugging
file_put_contents('widget_config_debug.log', 
    date('[Y-m-d H:i:s] ') . 
    "Raw Input: " . $rawInput . "\n", 
    FILE_APPEND
);

// Decode JSON input
$data = json_decode($rawInput, true);

// Validate input
if ($data === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid JSON: ' . json_last_error_msg(),
        'raw_input' => $rawInput
    ]);
    exit;
}

// Validate enabled_widgets
if (!isset($data['enabled_widgets']) || !is_array($data['enabled_widgets'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid input: enabled_widgets must be an array',
        'received_data' => $data
    ]);
    exit;
}

// Available widget keys (to prevent arbitrary widget additions)
$availableWidgets = [
    'revenue_stats', 
    'inventory_alerts', 
    'recent_orders', 
    'top_products', 
    'customer_stats', 
    'active_campaigns'
];

// Filter and validate input widgets
$validWidgets = array_intersect($data['enabled_widgets'], $availableWidgets);

try {
    // Get database connection
    $db = Database::getInstance();
    
    // Prepare SQL statements
    $deleteStmt = $db->prepare("DELETE FROM user_dashboard_widgets WHERE user_id = ?");
    $deleteStmt->bind_param("i", $_SESSION['user_id']);
    
    // Execute delete
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete existing widgets: " . $deleteStmt->error);
    }
    
    // Prepare insert statement
    $insertStmt = $db->prepare("
        INSERT INTO user_dashboard_widgets (user_id, widget_id, is_enabled) 
        VALUES (?, ?, 1)
    ");

    // Insert each valid widget
    foreach ($validWidgets as $widget_id) {
        $insertStmt->bind_param("is", $_SESSION['user_id'], $widget_id);
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert widget: " . $insertStmt->error);
        }
    }

    // Log successful configuration
    file_put_contents('widget_config_debug.log', 
        date('[Y-m-d H:i:s] ') . 
        "Successfully saved widgets for user {$_SESSION['user_id']}: " . 
        implode(', ', $validWidgets) . "\n", 
        FILE_APPEND
    );

    // Respond with success
    echo json_encode([
        'success' => true, 
        'message' => 'Dashboard configuration updated successfully',
        'saved_widgets' => $validWidgets
    ]);

} catch (Exception $e) {
    // Log detailed error
    file_put_contents('widget_config_debug.log', 
        date('[Y-m-d H:i:s] ') . 
        "Error: " . $e->getMessage() . "\n", 
        FILE_APPEND
    );

    // Respond with error
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} finally {
    // Close statements
    if (isset($deleteStmt)) $deleteStmt->close();
    if (isset($insertStmt)) $insertStmt->close();
}
exit;