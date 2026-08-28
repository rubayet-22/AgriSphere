<?php
/**
 * Admin - block/unblock from the dashboard.
 *
 * Note: unlike admin/user_action.php, unblocking here removes the user_block
 * row entirely. That original difference is preserved by using a separate
 * backend endpoint (/api/admin/users/block-record).
 */

require_once "../includes/auth.php";
require_once __DIR__ . '/../includes/backend_client.php';
require_role("Admin");

$uid = (int)$_POST['user_id'];

try {
    if (isset($_POST['block'])) {
        AgriSphereBackend::call('/api/admin/users/block-record', [
            'user_id' => $uid,
            'action'  => 'block',
        ]);
    }

    if (isset($_POST['unblock'])) {
        AgriSphereBackend::call('/api/admin/users/block-record', [
            'user_id' => $uid,
            'action'  => 'unblock',
        ]);
    }
} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

header("Location: dashboard.php");
exit();
