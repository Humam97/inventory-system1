<?php
// modules/categories/delete.php
require_once '../../config/database.php';
require_once '../../classes/Category.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if id is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No category specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$category = new Category();

// Try to delete the category
if ($category->deleteCategory($_GET['id'])) {
    $_SESSION['message'] = 'Category deleted successfully!';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Cannot delete category! Make sure it has no products.';
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to categories page
header('Location: index.php');
exit();
?>