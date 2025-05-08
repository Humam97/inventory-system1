<?php
// modules/inventory/add_movement.php
require_once '../../config/database.php';
require_once '../../classes/Inventory.php';
require_once '../../classes/Product.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    
    // Validate movement type
    if (!in_array($_POST['movement_type'], ['in', 'out'])) {
        $_SESSION['message'] = 'Invalid movement type!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }
    
    // Validate quantity
    if (!is_numeric($_POST['quantity']) || $_POST['quantity'] <= 0) {
        $_SESSION['message'] = 'Quantity must be a positive number!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }
    
    $inventory = new Inventory();
    
    // Check if there's enough stock for outward movement
    if ($_POST['movement_type'] == 'out') {
        $product = new Product();
        $productData = $product->getProduct($_POST['product_id']);
        
        if ($productData['stock_quantity'] < $_POST['quantity']) {
            $_SESSION['message'] = 'Not enough stock available!';
            $_SESSION['message_type'] = 'danger';
            header('Location: index.php');
            exit();
        }
    }
    
    // Add the movement
    if ($inventory->addMovement($_POST)) {
        $_SESSION['message'] = 'Inventory movement recorded successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error recording inventory movement!';
        $_SESSION['message_type'] = 'danger';
    }
}

header('Location: index.php');
exit();
?>