<?php
// modules/campaigns/view.php
require_once '../../config/database.php';
require_once '../../classes/Campaign.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No campaign specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$campaign = new Campaign();
$campaignData = $campaign->getCampaign($_GET['id']);
$campaignStats = $campaign->getCampaignStats($_GET['id']);

if (!$campaignData) {
    $_SESSION['message'] = 'Campaign not found!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Calculate campaign status
$now = time();
$start = strtotime($campaignData['start_date']);
$end = strtotime($campaignData['end_date']);

if ($now < $start) {
    $timeStatus = 'Starts in ' . ceil(($start - $now) / 86400) . ' days';
    $timeClass = 'text-info';
} elseif ($now > $end) {
    $timeStatus = 'Ended ' . ceil(($now - $end) / 86400) . ' days ago';
    $timeClass = 'text-secondary';
} else {
    $timeStatus = 'Currently Active';
    $timeClass = 'text-success';
}

// Initialize stats with default values if null
$totalOrders = isset($campaignStats['total_orders']) ? (int)$campaignStats['total_orders'] : 0;
$totalRevenue = isset($campaignStats['total_revenue']) ? (float)$campaignStats['total_revenue'] : 0.00;
$averageOrderValue = isset($campaignStats['average_order_value']) ? (float)$campaignStats['average_order_value'] : 0.00;
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Campaign Details</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">Back to Campaigns</a>
            <a href="edit.php?id=<?= $_GET['id'] ?>" class="btn btn-primary">Edit Campaign</a>
        </div>
    </div>

    <div class="row">
        <!-- Campaign Information -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Campaign Information</h5>
                </div>
                <div class="card-body">
                    <h4><?= htmlspecialchars($campaignData['campaign_name']) ?></h4>
                    <p class="text-muted"><?= htmlspecialchars($campaignData['description']) ?></p>
                    
                    <div class="mb-3">
                        <strong>Duration:</strong><br>
                        <?= date('M d, Y', strtotime($campaignData['start_date'])) ?> - 
                        <?= date('M d, Y', strtotime($campaignData['end_date'])) ?>
                        <br>
                        <span class="<?= $timeClass ?>"><?= $timeStatus ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Status:</strong><br>
                        <?php
                        $status_class = [
                            'draft' => 'secondary',
                            'active' => 'success',
                            'completed' => 'info',
                            'cancelled' => 'danger'
                        ];
                        ?>
                        <span class="badge bg-<?= $status_class[$campaignData['status']] ?>">
                            <?= ucfirst($campaignData['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Campaign Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5>Campaign Performance</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Orders:</span>
                        <strong><?= $totalOrders ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Revenue:</span>
                        <strong>$<?= number_format($totalRevenue, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Average Order Value:</span>
                        <strong>$<?= number_format($averageOrderValue, 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Products -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Campaign Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Regular Price</th>
                                    <th>Discount</th>
                                    <th>Sale Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($campaignData['products'])): ?>
                                    <?php foreach ($campaignData['products'] as $product): ?>
                                        <?php
                                        $regularPrice = (float)$product['unit_price'];
                                        $discountPercentage = (float)$product['discount_percentage'];
                                        $discountedPrice = $regularPrice * (1 - $discountPercentage / 100);
                                        $savings = $regularPrice - $discountedPrice;
                                        ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($product['product_name']) ?><br>
                                                <small class="text-muted">SKU: <?= htmlspecialchars($product['sku']) ?></small>
                                            </td>
                                            <td>$<?= number_format($regularPrice, 2) ?></td>
                                            <td><?= number_format($discountPercentage, 1) ?>%</td>
                                            <td>
                                                <strong>$<?= number_format($discountedPrice, 2) ?></strong>
                                                <br>
                                                <small class="text-success">
                                                    Save $<?= number_format($savings, 2) ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No products in this campaign</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>