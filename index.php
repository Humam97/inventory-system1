<?php
// index.php
session_start(); // Start session for user authentication

require_once 'config/database.php';
require_once 'classes/Dashboard.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

// Initialize dashboard with current user
$dashboard = new Dashboard();
$enabledWidgets = $dashboard->getEnabledWidgets();
?>

<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2>Dashboard</h2>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customizeModal">
                Customize Dashboard
            </button>
        </div>
    </div>

    <div class="row" id="dashboard-widgets">
        <?php foreach ($enabledWidgets as $widgetId => $widget): ?>
            <?php 
            $widgetContent = $dashboard->renderWidgetContent($widgetId);
            if ($widgetContent): 
            ?>
                <div class="<?= $widgetContent['size'] ?> mb-4" data-widget="<?= $widgetId ?>">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($widgetContent['title']) ?></h5>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <button class="dropdown-item widget-remove" data-widget="<?= $widgetId ?>">Remove</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Render widget content based on widget type
                            switch ($widgetId):
                                case 'revenue_stats':
                                    renderRevenueStats($widgetContent['data']);
                                    break;
                                case 'inventory_alerts':
                                    renderInventoryAlerts($widgetContent['data']);
                                    break;
                                case 'recent_orders':
                                    renderRecentOrders($widgetContent['data']);
                                    break;
                                case 'top_products':
                                    renderTopProducts($widgetContent['data']);
                                    break;
                                case 'customer_stats':
                                    renderCustomerStats($widgetContent['data']);
                                    break;
                                case 'active_campaigns':
                                    renderActiveCampaigns($widgetContent['data']);
                                    break;
                            endswitch;
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Customize Dashboard Modal -->
<div class="modal fade" id="customizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customize Dashboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php foreach ($dashboard->getAvailableWidgets() as $id => $widget): ?>
                        <div class="list-group-item">
                            <div class="form-check">
                                <input class="form-check-input widget-toggle" type="checkbox" 
                                       id="widget-<?= $id ?>" 
                                       value="<?= $id ?>"
                                       <?= isset($enabledWidgets[$id]) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="widget-<?= $id ?>">
                                    <?= htmlspecialchars($widget['title']) ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-widgets">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Widget Rendering Functions -->
<?php
function renderRevenueStats($data) {
    ?>
    <div class="row">
        <div class="col-md-4 text-center mb-3 ">
            <h6 class="text-muted">Total Revenue</h6>
            <h3>$<?= number_format($data['total_revenue'] ?? 0, 2) ?></h3>
        </div>
        <div class="col-md-4 text-center mb-3 ">
            <h6 class="text-muted">Total Orders</h6>
            <h3><?= number_format($data['total_orders'] ?? 0) ?></h3>
        </div>
        <div class="col-md-4 text-center mb-3">
            <h6 class="text-muted">Average Order</h6>
            <h3>$<?= number_format($data['average_order'] ?? 0, 2) ?></h3>
        </div>
    </div>
    <?php
}

function renderInventoryAlerts($alerts) {
    if (empty($alerts)): 
    ?>
        <p class="text-center text-muted">No low stock alerts</p>
    <?php 
    else: 
    ?>
        <div class="list-group list-group-flush">
            <?php foreach ($alerts as $alert): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($alert['product_name'] ?? 'Unknown Product') ?></h6>
                            <small class="text-muted">
                                <?= htmlspecialchars($alert['category_name'] ?? 'No Category') ?> - 
                                SKU: <?= htmlspecialchars($alert['sku'] ?? 'N/A') ?>
                            </small>
                        </div>
                        <span class="badge bg-warning">
                            <?= $alert['stock_quantity'] ?? 0 ?> left
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php 
    endif;
}

function renderRecentOrders($orders) {
    if (empty($orders)): 
    ?>
        <p class="text-center text-muted">No recent orders</p>
    <?php 
    else: 
    ?>
        <div class="list-group list-group-flush">
            <?php foreach ($orders as $order): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                Order #<?= str_pad($order['order_id'] ?? 0, 5, '0', STR_PAD_LEFT) ?>
                            </h6>
                            <small class="text-muted">
                                <?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?> -
                                <?= $order['items_count'] ?? 0 ?> items
                            </small>
                        </div>
                        <div class="text-end">
                            <div>$<?= number_format($order['total_amount'] ?? 0, 2) ?></div>
                            <small class="text-muted">
                                <?= date('M d, Y', strtotime($order['order_date'] ?? 'now')) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php 
    endif;
}

function renderTopProducts($products) {
    if (empty($products)): 
    ?>
        <p class="text-center text-muted">No product data available</p>
    <?php 
    else: 
    ?>
        <div class="list-group list-group-flush">
            <?php foreach ($products as $product): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($product['product_name'] ?? 'Unknown Product') ?></h6>
                            <small class="text-muted">
                                <?= htmlspecialchars($product['category_name'] ?? 'No Category') ?> - 
                                SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <div><?= number_format($product['units_sold'] ?? 0) ?> units</div>
                            <small class="text-muted">
                                <?= number_format($product['order_count'] ?? 0) ?> orders
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php 
    endif;
}

function renderCustomerStats($stats) {
    ?>
    <div class="row">
        <div class="col-md-4 text-center mb-3">
            <h6 class="text-muted">Total Customers</h6>
            <h3><?= number_format($stats['total_customers'] ?? 0) ?></h3>
        </div>
        <div class="col-md-4 text-center mb-3">
            <h6 class="text-muted">Active Customers</h6>
            <h3><?= number_format($stats['active_customers'] ?? 0) ?></h3>
        </div>
        <div class="col-md-4 text-center mb-3">
            <h6 class="text-muted">Avg. Customer Value</h6>
            <h3>$<?= number_format($stats['average_customer_value'] ?? 0, 2) ?></h3>
        </div>
    </div>
    <?php
}

function renderActiveCampaigns($campaigns) {
    if (empty($campaigns)): 
    ?>
        <p class="text-center text-muted">No active campaigns</p>
    <?php 
    else: 
    ?>
        <div class="list-group list-group-flush">
            <?php foreach ($campaigns as $campaign): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($campaign['name'] ?? 'Unnamed Campaign') ?></h6>
                            <small class="text-muted">
                                <?= date('M d, Y', strtotime($campaign['start_date'] ?? 'now')) ?> - 
                                <?= date('M d, Y', strtotime($campaign['end_date'] ?? 'now')) ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <div>$<?= number_format($campaign['revenue'] ?? 0, 2) ?></div>
                            <small class="text-muted">
                                <?= number_format($campaign['order_count'] ?? 0) ?> orders
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php 
    endif;
}
?>

<!-- JavaScript for Widget Management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Widget Remove Functionality
    document.querySelectorAll('.widget-remove').forEach(function(removeButton) {
        removeButton.addEventListener('click', function() {
            const widgetId = this.getAttribute('data-widget');
            const widgetElement = document.querySelector(`[data-widget="${widgetId}"]`);
            
            if (widgetElement) {
                widgetElement.remove();
                
                // Uncheck the corresponding checkbox in the customize modal
                const checkbox = document.getElementById(`widget-${widgetId}`);
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    });

    // Save Widgets Configuration
    document.getElementById('save-widgets').addEventListener('click', function() {
        // Collect enabled widgets
        const enabledWidgets = Array.from(
            document.querySelectorAll('#customizeModal .widget-toggle:checked')
        ).map(checkbox => checkbox.value);

        // Send AJAX request to save widget configuration
        fetch('save_dashboard_config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ enabled_widgets: enabledWidgets })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to reflect changes
                location.reload();
            } else {
                // Show error message
                alert(data.message || 'Failed to save widget configuration.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving widget configuration.');
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>