<?php
/**
 * Farmer - Update Product Image
 *
 * The upload itself stays in PHP (move_uploaded_file is a file-system concern)
 * and reuses the existing uploadProductImage() helper. The C++ FarmerService
 * (/api/farmer/products/image) checks that the product really belongs to this
 * farmer and stores the new file name.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'farmer/manage_inventory.php');
}

$productId = (int)($_POST['product_id'] ?? 0);

if ($productId <= 0 || !isset($_FILES['product_image']) ||
    $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
    setFlashMessage('error', 'Please choose an image file to upload.');
    redirect(BASE_URL . 'farmer/manage_inventory.php');
}

// Same validation and unique-name generation the "add product" flow uses.
$uploadResult = uploadProductImage($_FILES['product_image']);
if (empty($uploadResult['success'])) {
    setFlashMessage('error', 'Image upload failed: ' . $uploadResult['error']);
    redirect(BASE_URL . 'farmer/manage_inventory.php');
}

try {
    $result = AgriSphereBackend::call('/api/farmer/products/image', [
        'farmer_id'     => (int)getCurrentUserId(),
        'product_id'    => $productId,
        'product_image' => $uploadResult['filename'],
    ]);

    if (!empty($result['success'])) {
        setFlashMessage('success', $result['message'] ?? 'Photo updated');
    } else {
        // The backend refused it (not this farmer's product), so throw away
        // the file that was just uploaded instead of leaving it orphaned.
        discardUploadedProductImage($uploadResult['filename']);
        setFlashMessage('error', $result['error'] ?? 'Could not update the photo');
    }

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}

redirect(BASE_URL . 'farmer/manage_inventory.php');
