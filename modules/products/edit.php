<?php
// modules/products/edit.php
require_once '../../config/database.php';
require_once '../../classes/Product.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$product = new Product();

// Check if id is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No product specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Get product data
$productData = $product->getProduct($_GET['id']);
if (!$productData) {
    $_SESSION['message'] = 'Product not found!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($product->updateProduct($_GET['id'], $_POST)) {
        $_SESSION['message'] = 'Product updated successfully!';
        $_SESSION['message_type'] = 'success';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['message'] = 'Error updating product!';
        $_SESSION['message_type'] = 'danger';
    }
}

// Get categories for dropdown
$sql = "SELECT * FROM categories ORDER BY category_name";
$categories = Database::getInstance()->query($sql);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Product</h4>
                </div>
                <div class="card-body">
                    <form action="edit.php?id=<?= $_GET['id'] ?>" method="POST">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <option value="<?= $category['category_id'] ?>" 
                                            <?= ($category['category_id'] == $productData['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="product_name" 
                                   value="<?= htmlspecialchars($productData['product_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="sku" class="form-label">SKU</label>
                            <input type="text" class="form-control" name="sku" 
                                   value="<?= htmlspecialchars($productData['sku']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($productData['description']) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="unit_price" class="form-label">Unit Price</label>
                                    <input type="number" class="form-control" name="unit_price" step="0.01" 
                                           value="<?= htmlspecialchars($productData['unit_price']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" name="stock_quantity" 
                                           value="<?= htmlspecialchars($productData['stock_quantity']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="reorder_level" class="form-label">Reorder Level</label>
                                    <input type="number" class="form-control" name="reorder_level" 
                                           value="<?= htmlspecialchars($productData['reorder_level']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>