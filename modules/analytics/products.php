<?php
// modules/analytics/products.php
require_once '../../config/database.php';
require_once '../../classes/Analytics.php';
require_once '../../classes/Product.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$analytics = new Analytics();
$productObj = new Product();

// Get all products with their performance metrics
$topProducts = $analytics->getTopProducts(100); // Get top 100 products
$products = [];
$totalRevenue = 0;
$totalUnitsSold = 0;
$totalOrders = 0;

if ($topProducts) {
    while ($row = $topProducts->fetch_assoc()) {
        $products[] = $row;
        $totalRevenue += $row['revenue'];
        $totalUnitsSold += $row['units_sold'];
        $totalOrders += $row['order_count'];
    }
}

// Calculate product performance metrics
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
$avgUnitsPerOrder = $totalOrders > 0 ? $totalUnitsSold / $totalOrders : 0;
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Product Performance Analysis</h2>
            <p class="text-muted">Analyze product sales, trends, and inventory insights</p>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h3 class="card-text">$<?= number_format($totalRevenue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Units Sold</h5>
                    <h3 class="card-text"><?= number_format($totalUnitsSold) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Avg Order Value</h5>
                    <h3 class="card-text">$<?= number_format($avgOrderValue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Avg Units/Order</h5>
                    <h3 class="card-text"><?= number_format($avgUnitsPerOrder, 1) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top 10 Products Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Top 10 Products by Revenue</h5>
                </div>
                <div class="card-body">
                    <canvas id="topProductsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Units Sold Distribution -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Units Sold by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Performance Table -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Product Performance Details</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="sortTable('revenue')">Sort by Revenue</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="sortTable('units')">Sort by Units</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="sortTable('orders')">Sort by Orders</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="productsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Revenue</th>
                            <th>Units Sold</th>
                            <th>Orders</th>
                            <th>Avg Price</th>
                            <th>Performance</th>
                            <th>Stock Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $avgPrice = $product['units_sold'] > 0 ? $product['revenue'] / $product['units_sold'] : 0;
                            $performance = $totalRevenue > 0 ? ($product['revenue'] / $totalRevenue) * 100 : 0;
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($product['product_name']) ?>
                                    <br>
                                    <small class="text-muted">SKU: <?= htmlspecialchars($product['sku']) ?></small>
                                </td>
                                <td>
                                    $<?= number_format($product['revenue'], 2) ?>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= $performance ?>%"></div>
                                    </div>
                                </td>
                                <td><?= number_format($product['units_sold']) ?></td>
                                <td><?= number_format($product['order_count']) ?></td>
                                <td>$<?= number_format($avgPrice, 2) ?></td>
                                <td>
                                    <?php if ($performance >= 10): ?>
                                        <span class="badge bg-success">High Performer</span>
                                    <?php elseif ($performance >= 5): ?>
                                        <span class="badge bg-info">Good Performer</span>
                                    <?php elseif ($performance >= 1): ?>
                                        <span class="badge bg-warning">Average</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Low Performer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
    <?php
    $stockStatus = 'In Stock';
    $stockClass = 'success';
    
    // Make sure we're using the correct array key
    if (isset($product['stock_quantity'])) {
        if ($product['stock_quantity'] <= 0) {
            $stockStatus = 'Out of Stock';
            $stockClass = 'danger';
        } elseif (isset($product['reorder_level']) && $product['stock_quantity'] <= $product['reorder_level']) {
            $stockStatus = 'Low Stock';
            $stockClass = 'warning';
        }
    } else {
        $stockStatus = 'Unknown';
        $stockClass = 'secondary';
    }
    ?>
    <span class="badge bg-<?= $stockClass ?>"><?= $stockStatus ?></span>
    <?php if (isset($product['stock_quantity'])): ?>
        <br>
        <small class="text-muted">
            Stock: <?= number_format($product['stock_quantity']) ?>
            <?php if (isset($product['reorder_level'])): ?>
                (Reorder at: <?= number_format($product['reorder_level']) ?>)
            <?php endif; ?>
        </small>
    <?php endif; ?>
</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5>Other Analytics</h5>
                    <div class="btn-group">
                        <a href="index.php" class="btn btn-outline-primary">Dashboard</a>
                        <a href="associations.php" class="btn btn-outline-primary">Product Associations</a>
                        <a href="customers.php" class="btn btn-outline-primary">Customer Segments</a>
                        <a href="campaigns.php" class="btn btn-outline-primary">Campaign Analysis</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Top 10 Products Chart
    const top10Ctx = document.getElementById('topProductsChart').getContext('2d');
    const top10Data = <?php 
        $top10 = array_slice($products, 0, 10);
        echo json_encode([
            'labels' => array_map(function($p) { return $p['product_name']; }, $top10),
            'revenue' => array_map(function($p) { return $p['revenue']; }, $top10),
            'units' => array_map(function($p) { return $p['units_sold']; }, $top10)
        ]);
    ?>;

    new Chart(top10Ctx, {
        type: 'bar',
        data: {
            labels: top10Data.labels,
            datasets: [{
                label: 'Revenue ($)',
                data: top10Data.revenue,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: 'Units Sold',
                data: top10Data.units,
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderColor: 'rgb(153, 102, 255)',
                borderWidth: 1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Units Sold'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Table Sorting Functions
    window.sortTable = function(criteria) {
        const table = document.getElementById('productsTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let valueA, valueB;
            
            switch(criteria) {
                case 'revenue':
                    valueA = parseFloat(a.cells[1].textContent.replace('$', '').replace(',', ''));
                    valueB = parseFloat(b.cells[1].textContent.replace('$', '').replace(',', ''));
                    break;
                case 'units':
                    valueA = parseInt(a.cells[2].textContent.replace(',', ''));
                    valueB = parseInt(b.cells[2].textContent.replace(',', ''));
                    break;
                case 'orders':
                    valueA = parseInt(a.cells[3].textContent.replace(',', ''));
                    valueB = parseInt(b.cells[3].textContent.replace(',', ''));
                    break;
            }

            return valueB - valueA;
        });

        rows.forEach(row => tbody.appendChild(row));
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>