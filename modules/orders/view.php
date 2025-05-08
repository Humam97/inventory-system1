<?php
// modules/orders/view.php
require_once '../../config/database.php';
require_once '../../classes/Order.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No order specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$order = new Order();
$orderData = $order->getOrder($_GET['id']);

if (!$orderData) {
    $_SESSION['message'] = 'Order not found!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$status_class = [
    'pending' => 'warning',
    'processing' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Order Details #<?= str_pad($orderData['order_id'], 5, '0', STR_PAD_LEFT) ?></h2>
        </div>
        <div class="col text-end">
            <a href="index.php" class="btn btn-secondary">Back to Orders</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">Print Order</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderData['details'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                        <td>$<?= number_format($item['subtotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <th>$<?= number_format($orderData['total_amount'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Order Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Order Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Current Status:</strong>
                        <span class="badge bg-<?= $status_class[$orderData['status']] ?> ms-2">
                            <?= ucfirst($orderData['status']) ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Order Date:</strong>
                        <span class="ms-2">
                            <?= date('M d, Y H:i', strtotime($orderData['order_date'])) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="card">
                <div class="card-header">
                    <h5>Customer Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($orderData['first_name'] . ' ' . $orderData['last_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($orderData['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($orderData['phone']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, footer {
        display: none !important;
    }
    .card {
        border: none !important;
    }
    .card-header {
        background: none !important;
        border: none !important;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>