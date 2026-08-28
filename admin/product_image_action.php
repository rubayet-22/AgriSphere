<?php
/**
 * Admin - Update Product Image
 *
 * Same flow as the farmer version, but unscoped: an administrator may replace
 * the photo of any product, whichever farmer owns it. The upload stays in PHP
 * and reuses uploadProductImage(); the C++ AdminService
 * (/api/admin/products/image) stores the resulting file name.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/products.php');
}

$productId = (int)($_POST['product_id'] ?? 0);
$returnTo  = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/products.php');

if ($productId <= 0 || !isset($_FILES['product_image']) ||
    $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
    setFlashMessage('error', 'Please choose an image file to upload.');
    redirect($returnTo);
}

$uploadResult = uploadProductImage($_FILES['product_image']);
if (empty($uploadResult['success'])) {
    setFlashMessage('error', 'Image upload failed: ' . $uploadResult['error']);
    redirect($returnTo);
}

try {
    $result = AgriSphereBackend::call('/api/admin/products/image', [
        'product_id'    => $productId,
        'product_image' => $uploadResult['filename'],
    ]);

    if (!empty($result['success'])) {
        setFlashMessage('success', $result['message'] ?? 'Photo updated');
    } else {
        discardUploadedProductImage($uploadResult['filename']);
        setFlashMessage('error', $result['error'] ?? 'Could not update the photo');
    }

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

redirect($returnTo);
