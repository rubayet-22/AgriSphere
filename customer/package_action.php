<?php
/**
 * Customer - Package Action Handler (Facade Pattern)
 *
 * Makes exactly ONE call to the backend:
 *
 *   /api/packages/add
 *      -> PackageFacade::addPackageToCart()
 *         -> PackageRepository   (what is in the package)
 *         -> CatalogRepository   (does each product still exist / approved)
 *         -> stock check         (is there enough of each)
 *         -> CartRepository      (add or merge every cart line)
 *         all inside one db::Transaction
 *
 * This file never calls /api/cart/add. If any product in the package cannot
 * be added, the backend adds none of them and returns the reason.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Please login first']);
        exit;
    }
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'customer/packages.php');
}

try {
    $result = AgriSphereBackend::call('/api/packages/add', [
        'customer_id' => (int)getCurrentUserId(),
        'package_id'  => (int)($_POST['package_id'] ?? 0),
    ]);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    if (!empty($result['success'])) {
        setFlashMessage('success', $result['message'] ?? 'Package added to your cart');
        redirect(BASE_URL . 'customer/cart.php');
    }

    setFlashMessage('error', $result['error'] ?? 'Could not add the package');
    redirect(BASE_URL . 'customer/packages.php');

} catch (BackendUnavailableException $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    AgriSphereBackend::fail($e);
}
