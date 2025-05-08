<?php
// modules/orders/create.php
require_once '../../config/database.php';
require_once '../../classes/Order.php';
require_once '../../classes/Product.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$product = new Product();
$products = $product->getAllProducts();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order = new Order();
    
    // Prepare customer data
    $customerData = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address']
    ];
    
    // Prepare order items
    $orderItems = [];
    foreach ($_POST['product_id'] as $key => $product_id) {
        if (!empty($_POST['quantity'][$key]) && $_POST['quantity'][$key] > 0) {
            $orderItems[] = [
                'product_id' => $product_id,
                'quantity' => $_POST['quantity'][$key],
                'unit_price' => $_POST['unit_price'][$key]
            ];
        }
    }
    
    if ($order->createOrder($customerData, $orderItems)) {
        $_SESSION['message'] = 'Order created successfully!';
        $_SESSION['message_type'] = 'success';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['message'] = 'Error creating order!';
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Create New Order</h2>
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

    <form action="create.php" method="POST" id="orderForm">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Customer Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3" required></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Order Items</h5>
            </div>
            <div class="card-body">
                <div id="orderItems">
                    <div class="order-item mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Product</label>
                                <select class="form-select product-select" name="product_id[]" required>
                                    <option value="">Select Product</option>
                                    <?php while ($row = $products->fetch_assoc()): ?>
                                        <option value="<?= $row['product_id'] ?>" 
                                                data-price="<?= $row['unit_price'] ?>"
                                                data-stock="<?= $row['stock_quantity'] ?>">
                                            <?= htmlspecialchars($row['product_name']) ?> 
                                            (Stock: <?= $row['stock_quantity'] ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control quantity" name="quantity[]" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit Price</label>
                                <input type="number" class="form-control unit-price" name="unit_price[]" step="0.01" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Subtotal</label>
                                <input type="number" class="form-control subtotal" step="0.01" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col">
                        <button type="button" class="btn btn-success" id="addItem">Add Another Item</button>
                    </div>
                    <div class="col text-end">
                        <h4>Total: $<span id="orderTotal">0.00</span></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-4">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Order</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderItems = document.getElementById('orderItems');
    const addItemBtn = document.getElementById('addItem');
    const orderTotal = document.getElementById('orderTotal');
    
    // Add new item row
    addItemBtn.addEventListener('click', function() {
        const newItem = orderItems.children[0].cloneNode(true);
        newItem.querySelectorAll('input').forEach(input => input.value = '');
        newItem.querySelector('select').selectedIndex = 0;
        orderItems.appendChild(newItem);
        attachEventListeners();
    });
    
    function attachEventListeners() {
        document.querySelectorAll('.product-select').forEach(select => {
            select.addEventListener('change', updatePriceAndStock);
        });
        
        document.querySelectorAll('.quantity').forEach(input => {
            input.addEventListener('input', calculateSubtotal);
        });
    }
    
    function updatePriceAndStock(e) {
        const row = e.target.closest('.order-item');
        const option = e.target.options[e.target.selectedIndex];
        const price = option.dataset.price;
        const stock = option.dataset.stock;
        
        const quantityInput = row.querySelector('.quantity');
        quantityInput.max = stock;
        
        const priceInput = row.querySelector('.unit-price');
        priceInput.value = price;
        
        calculateSubtotal();
    }
    
    function calculateSubtotal() {
        let total = 0;
        
        document.querySelectorAll('.order-item').forEach(item => {
            const quantity = item.querySelector('.quantity').value || 0;
            const price = item.querySelector('.unit-price').value || 0;
            const subtotal = quantity * price;
            
            item.querySelector('.subtotal').value = subtotal.toFixed(2);
            total += subtotal;
        });
        
        orderTotal.textContent = total.toFixed(2);
    }
    
    attachEventListeners();
});
</script>

<?php require_once '../../includes/footer.php'; ?>