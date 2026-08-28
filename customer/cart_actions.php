<?php
/**
 * Customer - Cart Actions Handler
 *
 * All cart mutations are performed by the C++ CartService
 * (backend/src/services/CartService.cpp). This file handles the session check,
 * the AJAX/redirect distinction and the flash messages.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Please login first']);
        exit;
    }
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

$customerId = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle AJAX requests
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

/**
 * Sends the backend outcome back the way the caller expects it.
 */
function respond($result, $isAjax, $redirectOnError, $redirectOnSuccess)
{
    if (empty($result['success'])) {
        $message = $result['error'] ?? 'Request failed';
        if ($isAjax) {
            echo json_encode(['error' => $message]);
            exit;
        }
        setFlashMessage('error', $message);
        redirect($redirectOnError);
    }

    if ($isAjax) {
        echo json_encode([
            'success'   => true,
            'message'   => $result['message'] ?? '',
            'cartCount' => $result['cartCount'] ?? 0,
        ]);
        exit;
    }

    setFlashMessage('success', $result['message'] ?? 'Done');
    redirect($redirectOnSuccess);
}

try {
    switch ($action) {
        case 'add':
            $result = AgriSphereBackend::call('/api/cart/add', [
                'customer_id' => (int)$customerId,
                'product_id'  => (int)($_POST['product_id'] ?? 0),
                'quantity'    => (int)($_POST['quantity'] ?? 1),
            ]);
            respond($result, $isAjax,
                BASE_URL . 'customer/products.php',
                BASE_URL . 'customer/cart.php');
            break;

        case 'update':
            $result = AgriSphereBackend::call('/api/cart/update', [
                'customer_id' => (int)$customerId,
                'cart_id'     => (int)($_POST['cart_id'] ?? 0),
                'quantity'    => (int)($_POST['quantity'] ?? 1),
            ]);
            respond($result, $isAjax,
                BASE_URL . 'customer/cart.php',
                BASE_URL . 'customer/cart.php');
            break;

        case 'remove':
            $result = AgriSphereBackend::call('/api/cart/remove', [
                'customer_id' => (int)$customerId,
                'cart_id'     => (int)($_POST['cart_id'] ?? 0),
            ]);
            respond($result, $isAjax,
                BASE_URL . 'customer/cart.php',
                BASE_URL . 'customer/cart.php');
            break;

        case 'clear':
            $result = AgriSphereBackend::call('/api/cart/clear', [
                'customer_id' => (int)$customerId,
            ]);
            respond($result, $isAjax,
                BASE_URL . 'customer/cart.php',
                BASE_URL . 'customer/cart.php');
            break;

        default:
            redirect(BASE_URL . 'customer/cart.php');
    }
} catch (BackendUnavailableException $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    AgriSphereBackend::fail($e);
}
