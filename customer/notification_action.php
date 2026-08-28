<?php
/**
 * Customer - Notification Action Handler
 *
 * Marks one notification, or all of them, as read.
 * The C++ NotificationService checks that the notification really belongs to
 * the logged-in member before changing anything.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'customer/notifications.php');
}

$customerId = getCurrentUserId();
$action = sanitize($_POST['action'] ?? '');

try {
    if ($action === 'read') {
        $result = AgriSphereBackend::call('/api/notifications/read', [
            'user_id'         => (int)$customerId,
            'notification_id' => (int)($_POST['notification_id'] ?? 0),
        ]);
    } elseif ($action === 'read_all') {
        $result = AgriSphereBackend::call('/api/notifications/read-all', [
            'user_id' => (int)$customerId,
        ]);
    } else {
        setFlashMessage('error', 'Invalid request');
        redirect(BASE_URL . 'customer/notifications.php');
    }

    if (empty($result['success'])) {
        setFlashMessage('error', $result['error'] ?? 'Could not update the notification');
    }

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

redirect(BASE_URL . 'customer/notifications.php');
