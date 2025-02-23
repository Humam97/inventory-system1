<?php
// modules/categories/edit.php
require_once '../../config/database.php';
require_once '../../classes/Category.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$category = new Category();

// Check if id is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No category specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Get category data
$categoryData = $category->getCategory($_GET['id']);
if (!$categoryData) {
    $_SESSION['message'] = 'Category not found!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Print the POST data
    error_log("Updating category: " . print_r($_POST, true));
    
    if ($category->updateCategory($_GET['id'], $_POST)) {
        $_SESSION['message'] = 'Category updated successfully!';
        $_SESSION['message_type'] = 'success';
        header('Location: index.php');
        exit();
    } else {
        // Get the MySQL error if any
        $db = Database::getInstance();
        $error = mysqli_error($db->getConnection());
        $_SESSION['message'] = 'Error updating category: ' . $error;
        $_SESSION['message_type'] = 'danger';
        error_log("Error updating category: " . $error);
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Category</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
                            <?= $_SESSION['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                        endif; 
                    ?>

                    <form action="edit.php?id=<?= $_GET['id'] ?>" method="POST">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" 
                                   value="<?= htmlspecialchars($categoryData['category_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($categoryData['description']) ?></textarea>
                        </div>

                        <div class="text-end">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>