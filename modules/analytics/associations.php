<?php
// modules/analytics/associations.php
require_once '../../config/database.php';
require_once '../../classes/Analytics.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$analytics = new Analytics();

// Get product associations with configurable support and confidence thresholds
$minSupport = isset($_GET['support']) ? (float)$_GET['support'] : 0.01;  // Default 1%
$minConfidence = isset($_GET['confidence']) ? (float)$_GET['confidence'] : 0.1;  // Default 10%

$associations = $analytics->getProductAssociations($minSupport, $minConfidence);
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Product Associations Analysis</h2>
            <p class="text-muted">Discover which products are frequently purchased together</p>
        </div>
        <div class="col-md-6 text-end">
            <form action="associations.php" method="GET" class="row g-3 justify-content-end">
                <div class="col-auto">
                    <label class="col-form-label">Min Support:</label>
                </div>
                <div class="col-auto">
                    <input type="number" class="form-control form-control-sm" name="support" 
                           value="<?= $minSupport ?>" min="0.01" max="1" step="0.01">
                </div>
                <div class="col-auto">
                    <label class="col-form-label">Min Confidence:</label>
                </div>
                <div class="col-auto">
                    <input type="number" class="form-control form-control-sm" name="confidence" 
                           value="<?= $minConfidence ?>" min="0.1" max="1" step="0.1">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Explanation Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5>Understanding the Metrics</h5>
                    <ul class="mb-0">
                        <li><strong>Support:</strong> The percentage of orders that contain both products together</li>
                        <li><strong>Confidence A → B:</strong> The likelihood of buying product B when product A is purchased</li>
                        <li><strong>Confidence B → A:</strong> The likelihood of buying product A when product B is purchased</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Associations Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($associations)): ?>
                <div class="alert alert-info">
                    No product associations found with the current thresholds. Try lowering the support or confidence values.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Product Pair</th>
                                <th>Support</th>
                                <th>Confidence A → B</th>
                                <th>Confidence B → A</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($associations as $association): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <strong><?= htmlspecialchars($association['product1_name']) ?></strong>
                                                <br>+<br>
                                                <strong><?= htmlspecialchars($association['product2_name']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= number_format($association['support'] * 100, 1) ?>%
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= $association['support'] * 100 ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= number_format($association['confidence1'] * 100, 1) ?>%
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= $association['confidence1'] * 100 ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= number_format($association['confidence2'] * 100, 1) ?>%
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?= $association['confidence2'] * 100 ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#recommendationModal<?= $association['product1_id'] . '_' . $association['product2_id'] ?>">
                                            View Recommendations
                                        </button>
                                    </td>
                                </tr>

                                <!-- Recommendations Modal -->
                                <div class="modal fade" id="recommendationModal<?= $association['product1_id'] . '_' . $association['product2_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Marketing Recommendations</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h6>Product Pair</h6>
                                                <p>
                                                    <strong><?= htmlspecialchars($association['product1_name']) ?></strong>
                                                    <br>+<br>
                                                    <strong><?= htmlspecialchars($association['product2_name']) ?></strong>
                                                </p>

                                                <h6>Insights</h6>
                                                <ul>
                                                    <li>These products are purchased together in <?= number_format($association['support'] * 100, 1) ?>% of all orders.</li>
                                                    <li><?= number_format($association['confidence1'] * 100, 1) ?>% of customers who buy <?= htmlspecialchars($association['product1_name']) ?> also buy <?= htmlspecialchars($association['product2_name']) ?>.</li>
                                                    <li><?= number_format($association['confidence2'] * 100, 1) ?>% of customers who buy <?= htmlspecialchars($association['product2_name']) ?> also buy <?= htmlspecialchars($association['product1_name']) ?>.</li>
                                                </ul>

                                                <h6>Recommendations</h6>
                                                <ul>
                                                    <?php if ($association['confidence1'] > 0.5 || $association['confidence2'] > 0.5): ?>
                                                        <li>Create a bundle offer for these products</li>
                                                        <li>Display them together in the store layout</li>
                                                        <li>Cross-promote in product pages</li>
                                                    <?php else: ?>
                                                        <li>Consider cross-promoting these items</li>
                                                        <li>Test promotional bundles</li>
                                                    <?php endif; ?>
                                                    <?php if ($association['support'] > 0.2): ?>
                                                        <li>High-priority pair for marketing campaigns</li>
                                                        <li>Consider creating a permanent bundle</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
                        <a href="customers.php" class="btn btn-outline-primary">Customer Segments</a>
                        <a href="products.php" class="btn btn-outline-primary">Product Analysis</a>
                        <a href="campaigns.php" class="btn btn-outline-primary">Campaign Analysis</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>