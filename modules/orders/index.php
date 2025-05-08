<?php
// modules/orders/index.php
require_once '../../config/database.php';
require_once '../../classes/Order.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$order = new Order();
$orders = $order->getAllOrders();

// Get status counts for summary
$status_counts = [
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$total_orders = 0;
$total_revenue = 0;

// Clone the result set for counting
$orders_for_count = $order->getAllOrders();
while ($row = $orders_for_count->fetch_assoc()) {
    $status_counts[$row['status']]++;
    $total_orders++;
    $total_revenue += $row['total_amount'];
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Orders Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="create.php" class="btn btn-primary">Create New Order</a>
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

    <!-- Orders Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h3 class="card-text"><?= $total_orders ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h3 class="card-text">$<?= number_format($total_revenue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Pending Orders</h5>
                    <h3 class="card-text"><?= $status_counts['pending'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Processing Orders</h5>
                    <h3 class="card-text"><?= $status_counts['processing'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= str_pad($row['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($row['order_date'])) ?></td>
                                <td>
                                    <span class="badge bg-info"><?= $row['total_items'] ?> items</span>
                                    <span class="badge bg-secondary"><?= $row['total_quantity'] ?> units</span>
                                </td>
                                <td>$<?= number_format($row['total_amount'], 2) ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $status_class[$row['status']] ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view.php?id=<?= $row['order_id'] ?>" 
                                           class="btn btn-sm btn-info">View</a>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateStatusModal<?= $row['order_id'] ?>">
                                            Update Status
                                        </button>
                                    </div>

                                    <!-- Update Status Modal -->
                                    <div class="modal fade" id="updateStatusModal<?= $row['order_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="update_status.php" method="POST">
                                                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Order Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Status</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="processing" <?= $row['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                                                <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                                                <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>