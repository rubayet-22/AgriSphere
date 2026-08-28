<?php
/**
 * Get User Details API
 * Returns detailed user data as JSON for modal display
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Get user details
$stmt = $conn->prepare("
    SELECT u.*,
           COALESCE(ub.is_blocked, 0) as is_blocked,
           ub.blocked_at
    FROM user u
    LEFT JOIN user_block ub ON u.User_id = ub.user_id
    WHERE u.User_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Remove sensitive data
unset($user['Password']);

// Get user-specific detailed data
$stats = [];
$products = [];
$orders = [];

if ($user['User_type'] === 'Farmer') {
    // Get farmer's address
    $stmt = $conn->prepare("SELECT address, bank_name, bank_account_number FROM farmer WHERE farmer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $farmerDetails = $stmt->get_result()->fetch_assoc();
    $user['address'] = $farmerDetails['address'] ?? '';
    $user['bank_name'] = $farmerDetails['bank_name'] ?? '';
    $user['bank_account_number'] = $farmerDetails['bank_account_number'] ?? '';

    // Farmer stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM farm_product WHERE farmer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_products'] = $stmt->get_result()->fetch_assoc()['total_products'];

    $stmt = $conn->prepare("SELECT COUNT(*) as approved_products FROM farm_product WHERE farmer_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['approved_products'] = $stmt->get_result()->fetch_assoc()['approved_products'];

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(oi.quantity * oi.price_per_unit), 0) as total_revenue
        FROM order_items oi
        JOIN farm_product fp ON oi.product_id = fp.product_id
        JOIN customer_order co ON oi.order_id = co.order_id
        WHERE fp.farmer_id = ? AND co.status = 'Delivered'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_revenue'] = $stmt->get_result()->fetch_assoc()['total_revenue'];

    // Get all farmer products with details
    $stmt = $conn->prepare("
        SELECT fp.product_id, fp.product_name, fp.price_per_unit, fp.unit, fp.quantity,
               fp.status, fp.description, c.category_name,
               COALESCE(fi.quantity_available, 0) as stock,
               (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
                JOIN customer_order co ON oi.order_id = co.order_id
                WHERE oi.product_id = fp.product_id AND co.status != 'Cancelled') as total_sold,
               (SELECT COALESCE(SUM(oi.quantity * oi.price_per_unit), 0) FROM order_items oi
                JOIN customer_order co ON oi.order_id = co.order_id
                WHERE oi.product_id = fp.product_id AND co.status != 'Cancelled') as total_earnings
        FROM farm_product fp
        JOIN category c ON fp.category_id = c.category_id
        LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id
        WHERE fp.farmer_id = ?
        ORDER BY fp.product_id DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} elseif ($user['User_type'] === 'Customer') {
    // Get customer's address
    $stmt = $conn->prepare("SELECT address FROM customer WHERE customer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $customerDetails = $stmt->get_result()->fetch_assoc();
    $user['address'] = $customerDetails['address'] ?? '';

    // Customer stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM customer_order WHERE customer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_orders'] = $stmt->get_result()->fetch_assoc()['total_orders'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM customer_order WHERE customer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_spent'] = $stmt->get_result()->fetch_assoc()['total_spent'];

    // Get all customer orders with items
    $stmt = $conn->prepare("
        SELECT co.order_id, co.order_date, co.total_amount, co.status
        FROM customer_order co
        WHERE co.customer_id = ?
        ORDER BY co.order_date DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $ordersResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get items for each order
    foreach ($ordersResult as $order) {
        $stmt = $conn->prepare("
            SELECT oi.quantity, oi.price_per_unit, fp.product_name, fp.unit,
                   CONCAT(u.First_name, ' ', u.Last_name) as farmer_name
            FROM order_items oi
            JOIN farm_product fp ON oi.product_id = fp.product_id
            JOIN user u ON fp.farmer_id = u.User_id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order['order_id']);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $order['items'] = $items;
        $orders[] = $order;
    }
}

$user['stats'] = $stats;
$user['products'] = $products;
$user['orders'] = $orders;

echo json_encode(['success' => true, 'user' => $user]);
