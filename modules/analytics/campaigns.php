<?php
// modules/analytics/campaigns.php
require_once '../../config/database.php';
require_once '../../classes/Analytics.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$analytics = new Analytics();
$campaignEffectiveness = $analytics->getCampaignEffectiveness();

// Initialize totals
$totalRevenue = 0;
$totalOrders = 0;
$activeCount = 0;
$completedCount = 0;

// Process campaign data
$campaigns = [];
if ($campaignEffectiveness) {
    while ($row = $campaignEffectiveness->fetch_assoc()) {
        $campaigns[] = $row;
        $totalRevenue += floatval($row['revenue'] ?? 0);
        $totalOrders += intval($row['order_count'] ?? 0);
        
        if (isset($row['start_date']) && isset($row['end_date'])) {
            $now = new DateTime();
            $start = new DateTime($row['start_date']);
            $end = new DateTime($row['end_date']);
            
            if ($now >= $start && $now <= $end) {
                $activeCount++;
            } elseif ($now > $end) {
                $completedCount++;
            }
        }
    }
}

$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Campaign Performance Analysis</h2>
            <p class="text-muted">Track and analyze marketing campaign effectiveness</p>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Campaign Revenue</h5>
                    <h3 class="card-text">$<?= number_format($totalRevenue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Campaign Orders</h5>
                    <h3 class="card-text"><?= number_format($totalOrders) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Campaigns</h5>
                    <h3 class="card-text"><?= number_format($activeCount) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Avg Order Value</h5>
                    <h3 class="card-text">$<?= number_format($avgOrderValue, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Revenue by Campaign Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Campaign Revenue Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Orders by Campaign Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Campaign Orders Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Performance Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Campaign Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Avg Order Value</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <?php
                            $status = 'Draft';
                            $statusClass = 'secondary';

                            if (isset($campaign['start_date']) && isset($campaign['end_date'])) {
                                $now = new DateTime();
                                $start = new DateTime($campaign['start_date']);
                                $end = new DateTime($campaign['end_date']);
                                
                                if ($now < $start) {
                                    $status = 'Upcoming';
                                    $statusClass = 'info';
                                } elseif ($now >= $start && $now <= $end) {
                                    $status = 'Active';
                                    $statusClass = 'success';
                                } else {
                                    $status = 'Completed';
                                    $statusClass = 'secondary';
                                }
                            }

                            // Calculate performance percentage
                            $performance = $totalRevenue > 0 ? (floatval($campaign['revenue'] ?? 0) / $totalRevenue) * 100 : 0;
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($campaign['campaign_name']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($campaign['description'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?php if (isset($campaign['start_date']) && isset($campaign['end_date'])): 
                                        $start = new DateTime($campaign['start_date']);
                                        $end = new DateTime($campaign['end_date']);
                                        $duration = $start->diff($end)->days;
                                    ?>
                                        <?= $start->format('M d, Y') ?> - 
                                        <?= $end->format('M d, Y') ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= $duration ?> days
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Date not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td><?= number_format(intval($campaign['order_count'] ?? 0)) ?></td>
                                <td>
                                    $<?= number_format(floatval($campaign['revenue'] ?? 0), 2) ?>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= $performance ?>%"></div>
                                    </div>
                                </td>
                                <td>$<?= number_format(floatval($campaign['avg_order_value'] ?? 0), 2) ?></td>
                                <td>
                                    <?php if ($performance >= 25): ?>
                                        <span class="badge bg-success">High Impact</span>
                                    <?php elseif ($performance >= 10): ?>
                                        <span class="badge bg-info">Good Impact</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Low Impact</span>
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
                        <a href="products.php" class="btn btn-outline-primary">Product Analysis</a>
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
    const chartsData = <?php 
        $labels = [];
        $revenues = [];
        $orders = [];
        foreach ($campaigns as $campaign) {
            $labels[] = $campaign['campaign_name'];
            $revenues[] = floatval($campaign['revenue'] ?? 0);
            $orders[] = intval($campaign['order_count'] ?? 0);
        }
        echo json_encode([
            'labels' => $labels,
            'revenues' => $revenues,
            'orders' => $orders
        ]);
    ?>;

    // Revenue Chart
    if (document.getElementById('revenueChart')) {
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: chartsData.labels,
                datasets: [{
                    label: 'Revenue',
                    data: chartsData.revenues,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    }
                }
            }
        });
    }

    // Orders Chart
    if (document.getElementById('ordersChart')) {
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: chartsData.labels,
                datasets: [{
                    label: 'Orders',
                    data: chartsData.orders,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>