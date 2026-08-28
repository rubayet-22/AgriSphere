<?php
/**
 * Customer - Process Order
 *
 * Alternative order-placement entry point (kept for compatibility). The
 * transaction itself is executed by the C++ OrderService
 * (/api/orders/place-direct).
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
    redirect(BASE_URL . 'customer/checkout.php');
}

$customerId = getCurrentUserId();

// Get form data
$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$instructions = sanitize($_POST['instructions'] ?? '');
$paymentMethod = sanitize($_POST['payment_method'] ?? '');
$bkashNumber = sanitize($_POST['bkash_number'] ?? '');

try {
    $result = AgriSphereBackend::call('/api/orders/place-direct', [
        'customer_id'    => (int)$customerId,
        'name'           => $name,
        'phone'          => $phone,
        'address'        => $address,
        'instructions'   => $instructions,
        'payment_method' => $paymentMethod,
        'bkash_number'   => $bkashNumber,
    ]);

    if (empty($result['success'])) {
        setFlashMessage('error', $result['error'] ?? 'Failed to place order. Please try again.');
        if (($result['error'] ?? '') === 'Your cart is empty') {
            redirect(BASE_URL . 'customer/products.php');
        }
        redirect(BASE_URL . 'customer/checkout.php');
    }

    // Store order ID in session for confirmation page
    $_SESSION['last_order_id'] = $result['order_id'];

    setFlashMessage('success', $result['message']);
    redirect(BASE_URL . 'customer/order_detail.php?id=' . $result['order_id']);

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}
