<?php
// modules/orders/update_status.php
require_once '../../config/database.php';
require_once '../../classes/Order.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
        $_SESSION['message'] = 'Missing required information!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }

    $order = new Order();
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // Validate status value
    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $_SESSION['message'] = 'Invalid status value!';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit();
    }

    if ($order->updateOrderStatus($order_id, $status)) {
        $_SESSION['message'] = 'Order status updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating order status!';
        $_SESSION['message_type'] = 'danger';
    }
}

header('Location: index.php');
exit();
?>