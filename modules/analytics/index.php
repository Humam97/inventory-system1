<?php
// modules/analytics/index.php
require_once '../../config/database.php';
require_once '../../classes/Analytics.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$analytics = new Analytics();

// Get analytics data with error handling
$salesOverview = $analytics->getSalesOverview() ?? [
    'total_orders' => 0,
    'total_revenue' => 0,
    'average_order_value' => 0,
    'unique_customers' => 0
];

$monthlySales = $analytics->getMonthlySales(12);
$topProducts = $analytics->getTopProducts(5);
$categoryPerformance = $analytics->getCategoryPerformance();
$campaignEffectiveness = $analytics->getCampaignEffectiveness();
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Analytics Dashboard</h2>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h3 class="card-text"><?= number_format((int)$salesOverview['total_orders']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h3 class="card-text">$<?= number_format((float)$salesOverview['total_revenue'], 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Order Value</h5>
                    <h3 class="card-text">$<?= number_format((float)$salesOverview['average_order_value'], 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Unique Customers</h5>
                    <h3 class="card-text"><?= number_format((int)$salesOverview['unique_customers']) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Sales Chart -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Monthly Sales Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlySalesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Top Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Units</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($topProducts): while ($product = $topProducts->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($product['product_name']) ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($product['sku']) ?></small>
                                        </td>
                                        <td><?= number_format((int)$product['units_sold']) ?></td>
                                        <td>$<?= number_format((float)$product['revenue'], 2) ?></td>
                                    </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Category Performance -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Category Performance</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Campaign Performance -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Campaign Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg. Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($campaignEffectiveness): while ($campaign = $campaignEffectiveness->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($campaign['campaign_name']) ?></td>
                                        <td><?= number_format((int)$campaign['order_count']) ?></td>
                                        <td>$<?= number_format((float)$campaign['revenue'], 2) ?></td>
                                        <td>$<?= number_format((float)$campaign['avg_order_value'], 2) ?></td>
                                    </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5>Detailed Analysis</h5>
                    <div class="btn-group">
                        <a href="associations.php" class="btn btn-outline-primary">Product Associations</a>
                        <a href="customers.php" class="btn btn-outline-primary">Customer Segments</a>
                        <a href="products.php" class="btn btn-outline-primary">Product Analysis</a>
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
    // Monthly Sales Chart
    const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
    const monthlyData = <?php 
        $labels = [];
        $revenues = [];
        $orders = [];
        
        if ($monthlySales) {
            while ($row = $monthlySales->fetch_assoc()) {
                $labels[] = date('M Y', strtotime($row['month'] . '-01'));
                $revenues[] = (float)$row['revenue'] ?? 0;
                $orders[] = (int)$row['order_count'] ?? 0;
            }
        }
        
        echo json_encode([
            'labels' => $labels,
            'revenues' => $revenues,
            'orders' => $orders
        ], JSON_NUMERIC_CHECK);
    ?>;

    if (monthlyData.labels.length > 0) {
        new Chart(monthlySalesCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Revenue',
                    data: monthlyData.revenues,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    yAxisID: 'y'
                }, {
                    label: 'Orders',
                    data: monthlyData.orders,
                    borderColor: 'rgb(153, 102, 255)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
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
                            text: 'Number of Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    // Category Performance Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?php 
        $categories = [];
        $categoryRevenues = [];
        $categoryUnits = [];
        
        if ($categoryPerformance) {
            while ($row = $categoryPerformance->fetch_assoc()) {
                $categories[] = $row['category_name'];
                $categoryRevenues[] = (float)$row['revenue'] ?? 0;
                $categoryUnits[] = (int)$row['units_sold'] ?? 0;
            }
        }
        
        echo json_encode([
            'labels' => $categories,
            'revenues' => $categoryRevenues,
            'units' => $categoryUnits
        ], JSON_NUMERIC_CHECK);
    ?>;

    if (categoryData.labels.length > 0) {
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryData.labels,
                datasets: [{
                    label: 'Revenue',
                    data: categoryData.revenues,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }, {
                    label: 'Units Sold',
                    data: categoryData.units,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgb(153, 102, 255)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>