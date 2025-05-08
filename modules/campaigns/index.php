<?php
// modules/campaigns/index.php
require_once '../../config/database.php';
require_once '../../classes/Campaign.php';
require_once '../../classes/Product.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$campaign = new Campaign();
$campaigns = $campaign->getAllCampaigns();

// Calculate campaign statistics
$active_campaigns = 0;
$total_revenue = 0;
$upcoming_campaigns = 0;

$campaigns_for_stats = $campaign->getAllCampaigns();
while ($row = $campaigns_for_stats->fetch_assoc()) {
    if ($row['status'] == 'active') {
        $active_campaigns++;
    } elseif ($row['status'] == 'draft' && strtotime($row['start_date']) > time()) {
        $upcoming_campaigns++;
    }
    $total_revenue += $row['revenue'] ?? 0;
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Marketing Campaigns</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="create.php" class="btn btn-primary">Create New Campaign</a>
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

    <!-- Campaign Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Campaigns</h5>
                    <h3 class="card-text"><?= $active_campaigns ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Campaign Revenue</h5>
                    <h3 class="card-text">$<?= number_format($total_revenue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Campaigns</h5>
                    <h3 class="card-text"><?= $upcoming_campaigns ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaigns List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Duration</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($campaigns): while ($row = $campaigns->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($row['campaign_name']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($row['description']) ?></small>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($row['start_date'])) ?> - 
                                    <?= date('M d, Y', strtotime($row['end_date'])) ?>
                                    <?php
                                    $now = time();
                                    $start = strtotime($row['start_date']);
                                    $end = strtotime($row['end_date']);
                                    if ($now < $start) {
                                        echo '<br><small class="text-muted">Starts in ' . ceil(($start - $now) / 86400) . ' days</small>';
                                    } elseif ($now > $end) {
                                        echo '<br><small class="text-muted">Ended ' . ceil(($now - $end) / 86400) . ' days ago</small>';
                                    } else {
                                        echo '<br><small class="text-success">Currently Active</small>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $row['total_products'] ?> products</span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'draft' => 'secondary',
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'cancelled' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $status_class[$row['status']] ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td>$<?= number_format($row['revenue'] ?? 0, 2) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view.php?id=<?= $row['campaign_id'] ?>" 
                                           class="btn btn-sm btn-info">View</a>
                                        <a href="edit.php?id=<?= $row['campaign_id'] ?>" 
                                           class="btn btn-sm btn-primary">Edit</a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteCampaignModal<?= $row['campaign_id'] ?>"
                                                <?= $row['status'] === 'active' ? 'disabled' : '' ?>>
                                            Delete
                                        </button>
                                    </div>

                                    <!-- Delete Campaign Modal -->
                                    <div class="modal fade" id="deleteCampaignModal<?= $row['campaign_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete the campaign "<?= htmlspecialchars($row['campaign_name']) ?>"?
                                                    This action cannot be undone.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="delete.php?id=<?= $row['campaign_id'] ?>" class="btn btn-danger">Delete Campaign</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>