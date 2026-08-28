<?php
/**
 * Customer - Premium Membership Action Handler
 *
 * Posts the application to the C++ backend:
 *
 *   /api/membership/apply
 *      -> MembershipService validates the payment details with the existing
 *         Factory Method payment classes
 *      -> stores a premium_membership row with status = 'Pending'
 *
 * The customer does NOT become Premium here. Only an admin approval does that.
 * The ৳300 amount is set by the backend, never by this form.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'customer/premium.php');
}

try {
    $result = AgriSphereBackend::call('/api/membership/apply', [
        'customer_id'    => (int)getCurrentUserId(),
        'payment_method' => sanitize($_POST['payment_method'] ?? ''),
        'bkash_number'   => sanitize($_POST['bkash_number'] ?? ''),
        'card_number'    => sanitize($_POST['card_number'] ?? ''),
        'card_expiry'    => sanitize($_POST['card_expiry'] ?? ''),
        'card_cvc'       => sanitize($_POST['card_cvc'] ?? ''),
    ]);

    setFlashMessage(
        !empty($result['success']) ? 'success' : 'error',
        $result['message'] ?? ($result['error'] ?? 'Something went wrong')
    );

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

redirect(BASE_URL . 'customer/premium.php');
