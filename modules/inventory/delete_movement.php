<?php
// modules/inventory/delete_movement.php
require_once '../../config/database.php';
require_once '../../classes/Inventory.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No movement specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$inventory = new Inventory();

if ($inventory->deleteMovement($_GET['id'])) {
    $_SESSION['message'] = 'Movement deleted successfully!';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Error deleting movement!';
    $_SESSION['message_type'] = 'danger';
}

header('Location: index.php');
exit();
?>