<?php
/**
 * Admin - Farmer Subscription Action Handler
 *
 * approve -> status = 'Approved', expires_at = (current expiry or now)
 *            + FARMER_SUBSCRIPTION_MONTHS, farmer pages unlock
 * reject  -> status = 'Rejected', the farmer stays locked out and may
 *            submit a new payment
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/farmer_subscription.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/farmer_subscriptions.php');
}

$action         = sanitize($_POST['action'] ?? '');
$subscriptionId = (int)($_POST['subscription_id'] ?? 0);
$adminId        = (int)getCurrentUserId();

if ($subscriptionId <= 0) {
    setFlashMessage('error', 'Invalid request');
    redirect(BASE_URL . 'admin/farmer_subscriptions.php');
}

if ($action === 'approve') {
    $expiresAt = approveFarmerSubscription($conn, $subscriptionId, $adminId);
    if ($expiresAt === false) {
        setFlashMessage('error', 'That payment was already reviewed.');
    } else {
        setFlashMessage(
            'success',
            'Subscription #' . $subscriptionId . ' approved. The farmer can sell until ' .
            formatDateTime($expiresAt) . '.'
        );
    }
} elseif ($action === 'reject') {
    if (rejectFarmerSubscription($conn, $subscriptionId, $adminId)) {
        setFlashMessage('success', 'Subscription #' . $subscriptionId . ' rejected. The farmer stays locked out.');
    } else {
        setFlashMessage('error', 'That payment was already reviewed.');
    }
} else {
    setFlashMessage('error', 'Invalid action');
}

redirect(BASE_URL . 'admin/farmer_subscriptions.php');
