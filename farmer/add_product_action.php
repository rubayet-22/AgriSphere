<?php
/**
 * Farmer - Add Product Action Handler
 *
 * The image upload stays in PHP (it is a web/file-system concern); validation,
 * the farm_product insert and the farm_inventory record are handled by the C++
 * FarmerService (/api/farmer/products).
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
    redirect(BASE_URL . 'farmer/sell_products.php');
}

$farmerId = getCurrentUserId();

// Get form data
$productName = sanitize($_POST['product_name'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$description = sanitize($_POST['description'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 0);
$unit = sanitize($_POST['unit'] ?? '');
$pricePerUnit = (float)($_POST['price_per_unit'] ?? 0);

// Handle image upload (PHP keeps ownership of the file system)
$productImage = null;
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $uploadResult = uploadProductImage($_FILES['product_image']);
    if ($uploadResult['success']) {
        $productImage = $uploadResult['filename'];
    } else {
        setFlashMessage('error', 'Image upload failed: ' . $uploadResult['error']);
        redirect(BASE_URL . 'farmer/sell_products.php');
    }
}

try {
    $result = AgriSphereBackend::call('/api/farmer/products', [
        'farmer_id'      => (int)$farmerId,
        'product_name'   => $productName,
        'category_id'    => $categoryId,
        'description'    => $description,
        'quantity'       => $quantity,
        'unit'           => $unit,
        'price_per_unit' => $pricePerUnit,
        'product_image'  => $productImage,
    ]);

    if (empty($result['success'])) {
        $errors = $result['errors'] ?? [$result['error'] ?? 'Failed to add product. Please try again.'];
        setFlashMessage('error', implode('<br>', $errors));
        redirect(BASE_URL . 'farmer/sell_products.php');
    }

    setFlashMessage('success', $result['message']);
    redirect(BASE_URL . 'farmer/sales_history.php');

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}
