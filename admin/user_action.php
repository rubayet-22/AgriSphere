<?php
/**
 * Admin User Actions
 * Handle blocking/unblocking users
 *
 * The rules (user must exist, admins cannot be blocked) live in the C++
 * AdminService (/api/admin/users/block).
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/backend_client.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/users.php');
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

if (!$userId || !in_array($action, ['block', 'unblock'])) {
    setFlashMessage('error', 'Invalid request');
    redirect(BASE_URL . 'admin/users.php');
}

try {
    $result = AgriSphereBackend::call('/api/admin/users/block', [
        'user_id' => $userId,
        'action'  => $action,
    ]);

    if (!empty($result['success'])) {
        setFlashMessage('success', $result['message']);
    } else {
        $error = $result['error'] ?? 'Failed to update the user';
        setFlashMessage('error', $error);
        if ($error === 'User not found' || $error === 'Cannot block admin users') {
            redirect(BASE_URL . 'admin/users.php');
        }
    }

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

// Redirect back to referring page or users list
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'admin/users.php';
redirect($referer);
