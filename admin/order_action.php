<?php
/**
 * Admin Order Actions
 * Handle order status and payment status updates
 *
 * Status transitions, the timestamp columns and the COD auto-payment rule are
 * implemented in the C++ AdminService (/api/admin/orders/*).
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/backend_client.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/orders.php');
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

if (!$orderId) {
    setFlashMessage('error', 'Invalid request');
    redirect(BASE_URL . 'admin/orders.php');
}

try {
    if ($action === 'update_status') {
        $result = AgriSphereBackend::call('/api/admin/orders/status', [
            'admin_id' => (int)getCurrentUserId(),
            'order_id' => $orderId,
            'status'   => isset($_POST['new_status']) ? sanitize($_POST['new_status']) : '',
        ]);
    } elseif ($action === 'update_payment') {
        $result = AgriSphereBackend::call('/api/admin/orders/payment', [
            'order_id'       => $orderId,
            'payment_status' => isset($_POST['payment_status']) ? sanitize($_POST['payment_status']) : '',
        ]);
    } else {
        setFlashMessage('error', 'Invalid action');
        $result = null;
    }

    if ($result !== null) {
        if (!empty($result['success'])) {
            setFlashMessage('success', $result['message']);
        } else {
            $error = $result['error'] ?? 'Update failed';
            setFlashMessage('error', $error);
            if ($error === 'Invalid status' || $error === 'Invalid payment status') {
                redirect(BASE_URL . 'admin/orders.php');
            }
        }
    }

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

// Redirect back to referring page or orders list
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'admin/orders.php';
redirect($referer);
