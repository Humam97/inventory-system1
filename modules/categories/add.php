<?php
// modules/categories/add.php
require_once '../../config/database.php';
require_once '../../classes/Category.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Print the POST data
    error_log("Adding category: " . print_r($_POST, true));
    
    $category = new Category();
    
    if ($category->addCategory($_POST)) {
        $_SESSION['message'] = 'Category added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        // Get the MySQL error if any
        $db = Database::getInstance();
        $error = mysqli_error($db->getConnection());
        $_SESSION['message'] = 'Error adding category: ' . $error;
        $_SESSION['message_type'] = 'danger';
        error_log("Error adding category: " . $error);
    }
}

header('Location: index.php');
exit();
?>