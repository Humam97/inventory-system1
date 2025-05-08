<?php
// modules/customers/edit.php
require_once '../../config/database.php';
require_once '../../classes/Customer.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['customer_id'])) {
        $_SESSION['message'] = 'No customer specified!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address'];
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
    
    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Invalid email format!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }

    $customer = new Customer();
    
    // Debug: Log the update attempt
    error_log("Updating customer ID " . $_POST['customer_id'] . ": " . print_r($_POST, true));
    
    if ($customer->updateCustomer($_POST['customer_id'], $_POST)) {
        $_SESSION['message'] = 'Customer updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        // Check if email already exists
        if (strpos(mysqli_error($Database::getInstance()->getConnection()), 'Duplicate entry') !== false) {
            $_SESSION['message'] = 'A customer with this email already exists!';
        } else {
            $_SESSION['message'] = 'Error updating customer!';
        }
        $_SESSION['message_type'] = 'danger';
    }
}

header('Location: index.php');
exit();
?>