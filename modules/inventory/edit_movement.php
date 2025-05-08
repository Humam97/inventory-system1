<?php
// modules/inventory/edit_movement.php
require_once '../../config/database.php';
require_once '../../classes/Inventory.php';
require_once '../../classes/Product.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['movement_id'])) {
        $_SESSION['message'] = 'No movement specified!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }

    // Validate required fields
    $required_fields = ['product_id', 'movement_type', 'quantity', 'reference_id'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $_SESSION['message'] = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }

    $inventory = new Inventory();
    
    if ($inventory->updateMovement($_POST['movement_id'], $_POST)) {
        $_SESSION['message'] = 'Movement updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating movement!';
        $_SESSION['message_type'] = 'danger';
    }
}

header('Location: index.php');
exit();
?>