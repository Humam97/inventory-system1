<?php
// modules/campaigns/delete.php
require_once '../../config/database.php';
require_once '../../classes/Campaign.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'No campaign specified!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$campaign = new Campaign();

// Get campaign data to check status
$campaignData = $campaign->getCampaign($_GET['id']);

if (!$campaignData) {
    $_SESSION['message'] = 'Campaign not found!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Prevent deletion of active campaigns
if ($campaignData['status'] === 'active') {
    $_SESSION['message'] = 'Cannot delete an active campaign!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Try to delete the campaign
if ($campaign->deleteCampaign($_GET['id'])) {
    $_SESSION['message'] = 'Campaign deleted successfully!';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Error deleting campaign!';
    $_SESSION['message_type'] = 'danger';
}

header('Location: index.php');
exit();
?>