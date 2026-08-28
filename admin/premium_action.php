<?php
/**
 * Admin - Premium Membership Action Handler
 *
 * Approve or reject one membership application. The decision is made by the
 * C++ MembershipService (/api/membership/review):
 *
 *   approve -> status = 'Approved', expires_at = now + 1 month
 *              the customer is Premium and the checkout starts using
 *              PremiumDiscountStrategy (5%)
 *   reject  -> status = 'Rejected', the customer stays Regular (0%)
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/backend_client.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/premium.php');
}

try {
    $result = AgriSphereBackend::call('/api/membership/review', [
        'admin_id'      => (int)getCurrentUserId(),
        'membership_id' => (int)($_POST['membership_id'] ?? 0),
        'action'        => sanitize($_POST['action'] ?? ''),
    ]);

    setFlashMessage(
        !empty($result['success']) ? 'success' : 'error',
        $result['message'] ?? ($result['error'] ?? 'Something went wrong')
    );

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

redirect(BASE_URL . 'admin/premium.php');
