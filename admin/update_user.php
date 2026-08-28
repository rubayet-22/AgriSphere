<?php
/**
 * Update User Details API
 * Handles user profile updates from admin
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Verify user exists
$stmt = $conn->prepare("SELECT User_id, User_type FROM user WHERE User_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Update user basic info
$firstName = isset($input['first_name']) ? sanitize($input['first_name']) : null;
$lastName = isset($input['last_name']) ? sanitize($input['last_name']) : null;
$email = isset($input['email']) ? sanitize($input['email']) : null;
$phone = isset($input['phone']) ? sanitize($input['phone']) : null;

if ($firstName && $lastName && $email) {
    $stmt = $conn->prepare("UPDATE user SET First_name = ?, Last_name = ?, Email = ?, Phone = ? WHERE User_id = ?");
    $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Failed to update user info']);
        exit;
    }
}

// Update type-specific info
if ($user['User_type'] === 'Farmer') {
    $address = isset($input['address']) ? sanitize($input['address']) : '';
    $bankName = isset($input['bank_name']) ? sanitize($input['bank_name']) : '';
    $bankAccount = isset($input['bank_account_number']) ? sanitize($input['bank_account_number']) : '';

    // Check if farmer record exists
    $stmt = $conn->prepare("SELECT farmer_id FROM farmer WHERE farmer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $farmerExists = $stmt->get_result()->num_rows > 0;

    if ($farmerExists) {
        $stmt = $conn->prepare("UPDATE farmer SET address = ?, bank_name = ?, bank_account_number = ? WHERE farmer_id = ?");
        $stmt->bind_param("sssi", $address, $bankName, $bankAccount, $userId);
    } else {
        $stmt = $conn->prepare("INSERT INTO farmer (farmer_id, address, bank_name, bank_account_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $address, $bankName, $bankAccount);
    }
    $stmt->execute();

} elseif ($user['User_type'] === 'Customer') {
    $address = isset($input['address']) ? sanitize($input['address']) : '';

    // Check if customer record exists
    $stmt = $conn->prepare("SELECT customer_id FROM customer WHERE customer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $customerExists = $stmt->get_result()->num_rows > 0;

    if ($customerExists) {
        $stmt = $conn->prepare("UPDATE customer SET address = ? WHERE customer_id = ?");
        $stmt->bind_param("si", $address, $userId);
    } else {
        $stmt = $conn->prepare("INSERT INTO customer (customer_id, address) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $address);
    }
    $stmt->execute();
}

echo json_encode(['success' => true, 'message' => 'User updated successfully']);
