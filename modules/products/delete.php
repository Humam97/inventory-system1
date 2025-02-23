<?php
// modules/products/delete.php
require_once '../../config/database.php';
require_once '../../classes/Product.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if id is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No product specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$product = new Product();

// Get product data to check if it exists
$productData = $product->getProduct($_GET['id']);
if (!$productData) {
    $_SESSION['message'] = 'Product not found!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Try to delete the product
if ($product->deleteProduct($_GET['id'])) {
    $_SESSION['message'] = 'Product deleted successfully!';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Error deleting product!';
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to products page
header('Location: index.php');
exit();
?>