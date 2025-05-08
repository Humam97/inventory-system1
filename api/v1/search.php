<?php
// api/v1/search.php
require_once '../../config/database.php';
require_once '../../classes/Search.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode(['error' => 'Search query is required']);
    exit;
}

$search = new Search();
$results = $search->globalSearch($_GET['q']);

echo json_encode($results);
?>