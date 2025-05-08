<?php
// modules/campaigns/create.php
require_once '../../config/database.php';
require_once '../../classes/Campaign.php';
require_once '../../classes/Product.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$product = new Product();
$products = $product->getAllProducts();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $campaign = new Campaign();
    
    // Prepare campaign products data
    $campaign_products = [];
    if (!empty($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $key => $product_id) {
            if (!empty($_POST['discount'][$key])) {
                $campaign_products[] = [
                    'product_id' => $product_id,
                    'discount_percentage' => $_POST['discount'][$key]
                ];
            }
        }
    }
    
    // Add products to campaign data
    $_POST['products'] = $campaign_products;
    
    if ($campaign->addCampaign($_POST)) {
        $_SESSION['message'] = 'Campaign created successfully!';
        $_SESSION['message_type'] = 'success';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['message'] = 'Error creating campaign!';
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Create New Campaign</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">Back to Campaigns</a>
        </div>
    </div>

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

    <div class="card">
        <div class="card-body">
            <form action="create.php" method="POST" id="campaignForm">
                <div class="row">
                    <!-- Campaign Details -->
                    <div class="col-md-6">
                        <h4 class="mb-4">Campaign Details</h4>
                        <div class="mb-3">
                            <label for="campaign_name" class="form-label">Campaign Name</label>
                            <input type="text" class="form-control" name="campaign_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" 
                                           min="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" 
                                           min="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campaign Products -->
                    <div class="col-md-6">
                        <h4 class="mb-4">Campaign Products</h4>
                        <div id="productList">
                            <div class="product-item mb-3">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label">Product</label>
                                        <select class="form-select" name="product_id[]" required>
                                            <option value="">Select Product</option>
                                            <?php while ($row = $products->fetch_assoc()): ?>
                                                <option value="<?= $row['product_id'] ?>">
                                                    <?= htmlspecialchars($row['product_name']) ?> 
                                                    ($<?= number_format($row['unit_price'], 2) ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Discount %</label>
                                        <input type="number" class="form-control" name="discount[]" 
                                               min="0" max="100" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success" id="addProduct">
                            Add Another Product
                        </button>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary">Create Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productList = document.getElementById('productList');
    const addProductBtn = document.getElementById('addProduct');
    
    // Add new product fields
    addProductBtn.addEventListener('click', function() {
        const productItem = productList.children[0].cloneNode(true);
        productItem.querySelectorAll('select, input').forEach(input => input.value = '');
        productList.appendChild(productItem);
    });
    
    // Date validation
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
    });
    
    endDate.addEventListener('change', function() {
        if (startDate.value && this.value < startDate.value) {
            this.value = startDate.value;
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>