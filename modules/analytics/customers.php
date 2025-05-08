<?php
// modules/analytics/customers.php
require_once '../../config/database.php';
require_once '../../classes/Analytics.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$analytics = new Analytics();
$customerSegments = $analytics->getCustomerSegments();

// Initialize segment counters
$segments = [
    'new' => 0,
    'active' => 0,
    'loyal' => 0,
    'lost' => 0
];

$totalRevenue = 0;
$totalCustomers = 0;

// Process customer data for segmentation
$customers = [];
if ($customerSegments) {
    while ($row = $customerSegments->fetch_assoc()) {
        $totalRevenue += $row['total_spent'];
        $totalCustomers++;
        
        // Calculate days since last order
        $lastOrder = strtotime($row['last_order_date']);
        $daysSinceOrder = floor((time() - $lastOrder) / (60 * 60 * 24));
        
        // Determine customer segment
        if ($daysSinceOrder <= 30) {
            $segment = 'active';
        } elseif ($row['order_count'] >= 3) {
            $segment = 'loyal';
        } elseif ($daysSinceOrder <= 90) {
            $segment = 'new';
        } else {
            $segment = 'lost';
        }
        
        $segments[$segment]++;
        
        // Store customer data
        $customers[] = [
            'id' => $row['customer_id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'orders' => $row['order_count'],
            'total_spent' => $row['total_spent'],
            'avg_order' => $row['avg_order_value'],
            'last_order' => $row['last_order_date'],
            'days_since_first' => $row['days_since_first_order'],
            'segment' => $segment
        ];
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Customer Segmentation Analysis</h2>
            <p class="text-muted">Analyze customer behavior and segment performance</p>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Customers</h5>
                    <h3 class="card-text">
                        <?= number_format($segments['active']) ?>
                        <small class="text-white-50">
                            (<?= $totalCustomers > 0 ? number_format(($segments['active'] / $totalCustomers) * 100, 1) : 0 ?>%)
                        </small>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Loyal Customers</h5>
                    <h3 class="card-text">
                        <?= number_format($segments['loyal']) ?>
                        <small class="text-white-50">
                            (<?= $totalCustomers > 0 ? number_format(($segments['loyal'] / $totalCustomers) * 100, 1) : 0 ?>%)
                        </small>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">New Customers</h5>
                    <h3 class="card-text">
                        <?= number_format($segments['new']) ?>
                        <small class="text-white-50">
                            (<?= $totalCustomers > 0 ? number_format(($segments['new'] / $totalCustomers) * 100, 1) : 0 ?>%)
                        </small>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">At Risk Customers</h5>
                    <h3 class="card-text">
                        <?= number_format($segments['lost']) ?>
                        <small class="text-black-50">
                            (<?= $totalCustomers > 0 ? number_format(($segments['lost'] / $totalCustomers) * 100, 1) : 0 ?>%)
                        </small>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Segment Performance -->
    <div class="row">
        <!-- Customer Segments Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Customer Segments Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="segmentsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Customer Value Distribution -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Average Order Value by Segment</h5>
                </div>
                <div class="card-body">
                    <canvas id="valueChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer List -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Customer Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="customersTable">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Segment</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Avg Order Value</th>
                            <th>Last Order</th>
                            <th>Customer Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['name']) ?></td>
                                <td>
                                    <?php
                                    $segmentClass = [
                                        'active' => 'success',
                                        'loyal' => 'primary',
                                        'new' => 'info',
                                        'lost' => 'warning'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $segmentClass[$customer['segment']] ?>">
                                        <?= ucfirst($customer['segment']) ?>
                                    </span>
                                </td>
                                <td><?= number_format($customer['orders']) ?></td>
                                <td>$<?= number_format($customer['total_spent'], 2) ?></td>
                                <td>$<?= number_format($customer['avg_order'], 2) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($customer['last_order'])) ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php
                                        $days = floor((time() - strtotime($customer['last_order'])) / (60 * 60 * 24));
                                        echo $days . ' days ago';
                                        ?>
                                    </small>
                                </td>
                                <td><?= number_format($customer['days_since_first']) ?> days</td>
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
    // Segments Chart
    const segmentsCtx = document.getElementById('segmentsChart').getContext('2d');
    new Chart(segmentsCtx, {
        type: 'pie',
        data: {
            labels: ['Active', 'Loyal', 'New', 'At Risk'],
            datasets: [{
                data: [
                    <?= $segments['active'] ?>,
                    <?= $segments['loyal'] ?>,
                    <?= $segments['new'] ?>,
                    <?= $segments['lost'] ?>
                ],
                backgroundColor: [
                    'rgb(40, 167, 69)',
                    'rgb(0, 123, 255)',
                    'rgb(23, 162, 184)',
                    'rgb(255, 193, 7)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Value Chart
    const valueCtx = document.getElementById('valueChart').getContext('2d');
    const segmentValues = {
        'active': [],
        'loyal': [],
        'new': [],
        'lost': []
    };

    <?php foreach ($customers as $customer): ?>
        segmentValues['<?= $customer['segment'] ?>'].push(<?= $customer['avg_order'] ?>);
    <?php endforeach; ?>

    const avgValues = Object.keys(segmentValues).map(segment => {
        const values = segmentValues[segment];
        return values.length > 0 ? values.reduce((a, b) => a + b) / values.length : 0;
    });

    new Chart(valueCtx, {
        type: 'bar',
        data: {
            labels: ['Active', 'Loyal', 'New', 'At Risk'],
            datasets: [{
                label: 'Average Order Value',
                data: avgValues,
                backgroundColor: [
                    'rgba(40, 167, 69, 0.2)',
                    'rgba(0, 123, 255, 0.2)',
                    'rgba(23, 162, 184, 0.2)',
                    'rgba(255, 193, 7, 0.2)'
                ],
                borderColor: [
                    'rgb(40, 167, 69)',
                    'rgb(0, 123, 255)',
                    'rgb(23, 162, 184)',
                    'rgb(255, 193, 7)'
                ],
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
                        text: 'Average Order Value ($)'
                    }
                }
            }
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>