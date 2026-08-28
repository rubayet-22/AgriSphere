<?php
/**
 * Get Government Price API
 *
 * Thin pass-through to the C++ CatalogService
 * (/api/catalog/government-prices). The JSON shape returned to the browser is
 * unchanged, so assets/js and the pages that consume it need no edits.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

header('Content-Type: application/json');

$productName = $_GET['product'] ?? '';
$categoryId = (int)($_GET['category'] ?? 0);

if (empty($productName) && $categoryId <= 0) {
    echo json_encode(['error' => 'Product name or category required']);
    exit;
}

try {
    $result = AgriSphereBackend::call('/api/catalog/government-prices', [
        'product'  => $productName,
        'category' => $categoryId,
    ]);
    echo json_encode($result);
} catch (BackendUnavailableException $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
