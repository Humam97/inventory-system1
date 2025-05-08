<?php
// modules/customers/index.php
require_once '../../config/database.php';
require_once '../../classes/Customer.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$customer = new Customer();
$customers = $customer->getAllCustomers();

// Get total customers and total revenue
$total_customers = 0;
$total_revenue = 0;
$active_customers = 0; // customers with orders

// Clone the result set for counting
$customers_for_count = $customer->getAllCustomers();
while ($row = $customers_for_count->fetch_assoc()) {
    $total_customers++;
    $total_revenue += $row['total_spent'] ?? 0;
    if ($row['total_orders'] > 0) $active_customers++;
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Customers Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                Add New Customer
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

    <!-- Customer Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Customers</h5>
                    <h3 class="card-text"><?= $total_customers ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h3 class="card-text">$<?= number_format($total_revenue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Customers</h5>
                    <h3 class="card-text"><?= $active_customers ?></h3>
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
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $customers->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= $row['total_orders'] ?? 0 ?></td>
                                <td>$<?= number_format($row['total_spent'] ?? 0, 2) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view.php?id=<?= $row['customer_id'] ?>" 
                                           class="btn btn-sm btn-info">View</a>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCustomerModal<?= $row['customer_id'] ?>">
                                            Edit
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteCustomerModal<?= $row['customer_id'] ?>"
                                                <?= $row['total_orders'] > 0 ? 'disabled' : '' ?>>
                                            Delete
                                        </button>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editCustomerModal<?= $row['customer_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="edit.php" method="POST">
                                                    <input type="hidden" name="customer_id" value="<?= $row['customer_id'] ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Customer</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="first_name" class="form-label">First Name</label>
                                                            <input type="text" class="form-control" name="first_name" 
                                                                   value="<?= htmlspecialchars($row['first_name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="last_name" class="form-label">Last Name</label>
                                                            <input type="text" class="form-control" name="last_name" 
                                                                   value="<?= htmlspecialchars($row['last_name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="email" class="form-label">Email</label>
                                                            <input type="email" class="form-control" name="email" 
                                                                   value="<?= htmlspecialchars($row['email']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="phone" class="form-label">Phone</label>
                                                            <input type="tel" class="form-control" name="phone" 
                                                                   value="<?= htmlspecialchars($row['phone']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="address" class="form-label">Address</label>
                                                            <textarea class="form-control" name="address" rows="3" required><?= htmlspecialchars($row['address']) ?></textarea>
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

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteCustomerModal<?= $row['customer_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete "<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="delete.php?id=<?= $row['customer_id'] ?>" class="btn btn-danger">Delete</a>
                                                </div>
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

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="add.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>