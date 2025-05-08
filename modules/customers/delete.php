<?php
// modules/customers/delete.php
require_once '../../config/database.php';
require_once '../../classes/Customer.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No customer specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$customer = new Customer();

// Debug: Log the deletion attempt
error_log("Attempting to delete customer ID: " . $_GET['id']);

if ($customer->deleteCustomer($_GET['id'])) {
    $_SESSION['message'] = 'Customer deleted successfully!';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Cannot delete customer with existing orders!';
    $_SESSION['message_type'] = 'danger';
}

header('Location: index.php');
exit();
?>