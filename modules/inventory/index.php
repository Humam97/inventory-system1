<?php
// modules/inventory/index.php
require_once '../../config/database.php';
require_once '../../classes/Inventory.php';
require_once '../../classes/Product.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Initialize classes
$inventory = new Inventory();
$productObj = new Product();

// Get inventory statistics
$inventoryValue = $inventory->getInventoryValue();
$movementStats = $inventory->getMovementStats();
$lowStockProducts = $inventory->getLowStockProducts();
$movements = $inventory->getAllMovements();

// Calculate 30-day movement totals
$inward = 0;
$outward = 0;
if ($movementStats) {
    while ($stat = $movementStats->fetch_assoc()) {
        if ($stat['movement_type'] == 'in') {
            $inward = $stat['total_quantity'];
        } else {
            $outward = $stat['total_quantity'];
        }
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Inventory Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMovementModal">
                Add Movement
            </button>
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

    <!-- Inventory Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Products</h5>
                    <h3 class="card-text"><?= number_format($inventoryValue['total_products']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Inventory Value</h5>
                    <h3 class="card-text">$<?= number_format($inventoryValue['total_value'], 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">30-Day Inward</h5>
                    <h3 class="card-text"><?= number_format($inward) ?> units</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">30-Day Outward</h5>
                    <h3 class="card-text"><?= number_format($outward) ?> units</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Low Stock Alerts -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Low Stock Alerts</h5>
                </div>
                <div class="card-body">
                    <?php 
                    if ($lowStockProducts && $lowStockProducts->num_rows > 0): 
                    ?>
                        <div class="list-group">
                            <?php while ($lowStockItem = $lowStockProducts->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($lowStockItem['product_name']) ?></h6>
                                        <small class="text-danger fw-bold">
                                            <?= $lowStockItem['current_stock'] ?> / <?= $lowStockItem['reorder_level'] ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        SKU: <?= htmlspecialchars($lowStockItem['sku']) ?><br>
                                        Category: <?= htmlspecialchars($lowStockItem['category_name']) ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small>Ordered <?= $lowStockItem['times_ordered'] ?> times</small>
                                        <button type="button" 
                                                class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#addMovementModal"
                                                onclick="preSelectProduct(<?= $lowStockItem['product_id'] ?>)">
                                            Restock
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted mb-0">No low stock alerts</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Movements</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                    <th>Current Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($movements): while ($movement = $movements->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M d, Y H:i', strtotime($movement['movement_date'])) ?></td>
                                        <td>
                                            <?= htmlspecialchars($movement['product_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($movement['sku']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($movement['movement_type'] == 'in'): ?>
                                                <span class="badge bg-success">IN</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">OUT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $movement['quantity'] ?></td>
                                        <td><?= htmlspecialchars($movement['reference_id']) ?></td>
                                        <td><?= $movement['current_stock'] ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editMovementModal<?= $movement['movement_id'] ?>">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteMovementModal<?= $movement['movement_id'] ?>">
                                                    Delete
                                                </button>
                                            </div>

                                            <!-- Edit Movement Modal -->
                                            <div class="modal fade" id="editMovementModal<?= $movement['movement_id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="edit_movement.php" method="POST">
                                                            <input type="hidden" name="movement_id" value="<?= $movement['movement_id'] ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Movement</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="product_id" class="form-label">Product</label>
                                                                    
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="movement_type" class="form-label">Movement Type</label>
                                                                    <select class="form-select" name="movement_type" required>
                                                                        <option value="in" <?= ($movement['movement_type'] == 'in') ? 'selected' : '' ?>>Stock In</option>
                                                                        <option value="out" <?= ($movement['movement_type'] == 'out') ? 'selected' : '' ?>>Stock Out</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="quantity" class="form-label">Quantity</label>
                                                                    <input type="number" class="form-control" name="quantity" 
                                                                           value="<?= $movement['quantity'] ?>" min="1" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="reference_id" class="form-label">Reference ID</label>
                                                                    <input type="text" class="form-control" name="reference_id" 
                                                                           value="<?= htmlspecialchars($movement['reference_id']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="notes" class="form-label">Notes</label>
                                                                    <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($movement['notes'] ?? '') ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update Movement</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Delete Movement Modal -->
                                            <div class="modal fade" id="deleteMovementModal<?= $movement['movement_id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete this movement? This will update the stock quantity accordingly.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <a href="delete_movement.php?id=<?= $movement['movement_id'] ?>" 
                                                               class="btn btn-danger">Delete Movement</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Movement Modal -->
<div class="modal fade" id="addMovementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="add_movement.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Inventory Movement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        
                    </div>
                    <div class="mb-3">
                        <label for="movement_type" class="form-label">Movement Type</label>
                        <select class="form-select" name="movement_type" required>
                            <option value="in">Stock In</option>
                            <option value="out">Stock Out</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-
                        <input type="number" class="form-control" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="reference_id" class="form-label">Reference ID</label>
                        <input type="text" class="form-control" name="reference_id" 
                               placeholder="e.g., PO123, RET456" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Movement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for handling the Restock button -->
<script>
function preSelectProduct(productId) {
    document.querySelector('#addMovementModal select[name="product_id"]').value = productId;
    document.querySelector('#addMovementModal select[name="movement_type"]').value = 'in';
}
</script>

<?php require_once '../../includes/footer.php'; ?>