<?php
/**
 * Farmer - Discount Action Handler
 *
 * Posts the farmer's request to the C++ backend. Everything that matters
 * happens there:
 *
 *   /api/farmer/discounts/activate
 *      -> DiscountService validates and saves the discount
 *      -> builds a DiscountEvent
 *      -> DiscountSubject notifies every registered member observer
 *      -> each observer stores a notification row for its member
 *
 * This file only reads the form, shows the result and redirects.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'farmer/discounts.php');
}

$farmerId  = getCurrentUserId();
$action    = sanitize($_POST['action'] ?? '');
$productId = (int)($_POST['product_id'] ?? 0);

try {
    if ($action === 'activate') {
        $result = AgriSphereBackend::call('/api/farmer/discounts/activate', [
            'farmer_id'        => (int)$farmerId,
            'product_id'       => $productId,
            'discount_percent' => (float)($_POST['discount_percent'] ?? 0),
        ]);
    } elseif ($action === 'remove') {
        $result = AgriSphereBackend::call('/api/farmer/discounts/remove', [
            'farmer_id'  => (int)$farmerId,
            'product_id' => $productId,
        ]);
    } else {
        setFlashMessage('error', 'Invalid request');
        redirect(BASE_URL . 'farmer/discounts.php');
    }

    setFlashMessage(
        !empty($result['success']) ? 'success' : 'error',
        $result['message'] ?? ($result['error'] ?? 'Something went wrong')
    );

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

redirect(BASE_URL . 'farmer/discounts.php');
