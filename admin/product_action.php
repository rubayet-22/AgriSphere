<?php
/**
 * Admin Product Actions
 * Handle approving/rejecting products
 *
 * The moderation rules live in the C++ AdminService
 * (/api/admin/products/moderate).
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/backend_client.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/products.php');
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
$rejectionReason = isset($_POST['rejection_reason']) ? sanitize($_POST['rejection_reason']) : '';
$redirectUrl = isset($_POST['redirect']) ? $_POST['redirect'] : 'products.php';

try {
    $result = AgriSphereBackend::call('/api/admin/products/moderate', [
        'admin_id'         => (int)getCurrentUserId(),
        'product_id'       => $productId,
        'action'           => $action,
        'rejection_reason' => $rejectionReason,
    ]);

    if (empty($result['success'])) {
        setFlashMessage('error', $result['error'] ?? 'Invalid request');

        // Rejecting without a reason sends the admin back to the review screen
        if (($result['error_kind'] ?? '') === 'needs_reason') {
            redirect(BASE_URL . 'admin/products.php?review=' . $productId);
        }
        redirect(BASE_URL . 'admin/products.php');
    }

    setFlashMessage('success', $result['message']);

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

// Redirect
redirect(BASE_URL . 'admin/' . $redirectUrl);
