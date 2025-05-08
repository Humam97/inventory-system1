<?php
// modules/customers/view.php
require_once '../../config/database.php';
require_once '../../classes/Customer.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No customer specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$customer = new Customer();
$customerData = $customer->getCustomer($_GET['id']);
$customerStats = $customer->getCustomerStats($_GET['id']);

if (!$customerData) {
    $_SESSION['message'] = 'Customer not found!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Customer Details</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">Back to Customers</a>
            <button type="button" class="btn btn-primary" 
                    data-bs-toggle="modal" 
                    data-bs-target="#editCustomerModal<?= $customerData['customer_id'] ?>">
                Edit Customer
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Customer Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Customer Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($customerData['first_name'] . ' ' . $customerData['last_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($customerData['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($customerData['phone']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($customerData['address']) ?></p>
                    <p><strong>Customer Since:</strong> <?= date('M d, Y', strtotime($customerData['created_at'])) ?></p>
                </div>
            </div>

            <!-- Customer Statistics Card -->
            <div class="card">
                <div class="card-header">
                    <h5>Customer Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Orders:</span>
                        <strong><?= $customerStats['total_orders'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Spent:</span>
                        <strong>$<?= number_format($customerStats['total_spent'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Average Order Value:</span>
                        <strong>$<?= number_format($customerStats['average_order_value'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Last Order:</span>
                        <strong><?= $customerStats['last_order_date'] ? date('M d, Y', strtotime($customerStats['last_order_date'])) : 'Never' ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Order History -->
            <div class="card">
                <div class="card-header">
                    <h5>Order History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($customerData['orders'])): ?>
                        <p class="text-center">No orders found for this customer.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customerData['orders'] as $order): ?>
                                        <tr>
                                            <td>#<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $status_class[$order['status']] ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="../orders/view.php?id=<?= $order['order_id'] ?>" 
                                                   class="btn btn-sm btn-info">View Order</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal<?= $customerData['customer_id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="edit.php" method="POST">
                <input type="hidden" name="customer_id" value="<?= $customerData['customer_id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" 
                               value="<?= htmlspecialchars($customerData['first_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" 
                               value="<?= htmlspecialchars($customerData['last_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?= htmlspecialchars($customerData['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" 
                               value="<?= htmlspecialchars($customerData['phone']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" required><?= htmlspecialchars($customerData['address']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>