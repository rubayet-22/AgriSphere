<?php
/**
 * Farmer - Subscription Payment Handler
 *
 * Validates the (simulated) payment details, records the payment and extends
 * the farmer's subscription by FARMER_SUBSCRIPTION_MONTHS.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/farmer_subscription.php';

session_start();

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'farmer/subscription.php');
}

$farmerId = getCurrentUserId();
$method   = sanitize($_POST['payment_method'] ?? '');

// Only one payment may be awaiting review at a time.
$state = farmerSubscriptionStatus($conn, $farmerId);
if (!empty($state['pending'])) {
    setFlashMessage('error', 'You already have a payment waiting for admin approval.');
    redirect(BASE_URL . 'farmer/subscription.php');
}

// Same validation rules the product checkout uses.
$payment = validateFarmerSubscriptionPayment($method, $_POST);

if (empty($payment['ok'])) {
    setFlashMessage('error', $payment['error']);
    redirect(BASE_URL . 'farmer/subscription.php');
}

$subscriptionId = recordFarmerSubscriptionPayment(
    $conn,
    $farmerId,
    $method,
    $payment['account'],
    $payment['reference']
);

if ($subscriptionId === false) {
    setFlashMessage('error', 'Could not record the payment. Run database/farmer_subscription.sql and try again.');
    redirect(BASE_URL . 'farmer/subscription.php');
}

// The subscription is NOT active yet - an admin has to approve it.
setFlashMessage(
    'success',
    'Payment of ' . formatCurrency(FARMER_SUBSCRIPTION_FEE) . ' submitted (reference ' .
    $payment['reference'] . '). It is now waiting for admin approval.'
);
redirect(BASE_URL . 'farmer/subscription.php');
