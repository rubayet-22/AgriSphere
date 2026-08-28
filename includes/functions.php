<?php
/**
 * AgriSphere Helper Functions
 *
 * Data-access helpers in this file now go through the persistent C++ backend
 * (see includes/backend_client.php); the formatting/presentation helpers stay
 * pure PHP because they are a view concern.
 */

require_once __DIR__ . '/backend_client.php';

/**
 * Sanitize user input
 */
function sanitize($input) {
    // A missing field arrives as null, which trim()/strip_tags() no longer
    // accept in PHP 8.1+, so it is normalised to an empty string first.
    return htmlspecialchars(strip_tags(trim((string)($input ?? ''))), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency with Taka symbol
 */
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd M Y') {
    // Nullable columns (approved_at, delivered_at, last_login, ...) would make
    // strtotime() raise a deprecation and print "01 Jan 1970".
    if ($date === null || $date === '') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    if ($datetime === null || $datetime === '') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Generate a unique order number
 */
function generateOrderNumber() {
    return 'AG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_ADMIN;
}

/**
 * Check if user is farmer
 */
function isFarmer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_FARMER;
}

/**
 * Check if user is customer
 */
function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_CUSTOMER;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 */
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $typeClass = $flash['type'] === 'success' ? 'alert-success' :
                    ($flash['type'] === 'error' ? 'alert-error' : 'alert-warning');
        // Allow <br> tags for multi-line messages but escape other HTML
        $message = str_replace(['&lt;br&gt;', '&lt;br /&gt;'], '<br>', htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'));
        echo '<div class="alert ' . $typeClass . '">' . $message . '</div>';
    }
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $statusLower = strtolower($status);
    $badgeClass = 'badge-' . $statusLower;
    return '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
}

/**
 * Get order status badge HTML
 */
function getOrderStatusBadge($status) {
    $statusMap = [
        'Pending' => 'badge-pending',
        'Processing' => 'badge-processing',
        'Shipped' => 'badge-shipped',
        'Delivered' => 'badge-delivered',
        'Cancelled' => 'badge-rejected'
    ];
    $badgeClass = $statusMap[$status] ?? 'badge-pending';
    return '<span class="badge ' . $badgeClass . '">' . $status . '</span>';
}

/**
 * Upload product image
 */
function uploadProductImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }

    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    // Create directory if not exists
    if (!file_exists(PRODUCT_IMAGE_PATH)) {
        mkdir(PRODUCT_IMAGE_PATH, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . uniqid() . '.' . $extension;
    $filepath = PRODUCT_IMAGE_PATH . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Failed to save file'];
}

/**
 * Removes a product image that was just uploaded but could not be attached to
 * a product (for example the backend refused the update). Only files inside
 * uploads/products/ can be removed - basename() keeps a crafted name from
 * escaping that folder.
 *
 * Note: this is only used to clean up an upload from the current request. The
 * image being REPLACED is deliberately left on disk, because nothing
 * guarantees another product is not pointing at the same file name.
 */
function discardUploadedProductImage($filename) {
    if (empty($filename)) {
        return;
    }
    $path = PRODUCT_IMAGE_PATH . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Get product image URL or placeholder
 */
function getProductImageUrl($imageName) {
    if ($imageName && file_exists(PRODUCT_IMAGE_PATH . $imageName)) {
        return BASE_URL . 'uploads/products/' . $imageName;
    }
    return BASE_URL . 'assets/images/product-placeholder.svg';
}

/**
 * ---------------------------------------------------------------------
 * The four helpers below are used by almost every page. They now read
 * through the persistent C++ backend (and therefore through the Singleton
 * DatabaseManager) instead of issuing SQL from PHP.
 *
 * Their signatures are unchanged - the leading $conn argument is kept so
 * that no caller had to be touched - and they degrade to safe defaults if
 * the backend is not running, so a page still renders instead of blanking.
 * ---------------------------------------------------------------------
 */

/**
 * Check if user is blocked (C++ backend: /api/auth/block-status)
 */
function isUserBlocked($conn, $userId) {
    $response = AgriSphereBackend::tryCall('/api/auth/block-status', [
        'user_id' => (int)$userId,
    ]);
    return !empty($response['success']) && !empty($response['blocked']);
}

/**
 * Get category name by ID (C++ backend: /api/catalog/category-name)
 */
function getCategoryName($conn, $categoryId) {
    $response = AgriSphereBackend::tryCall('/api/catalog/category-name', [
        'category_id' => (int)$categoryId,
    ]);
    return $response['category_name'] ?? 'Unknown';
}

/**
 * Get all categories (C++ backend: /api/catalog/categories)
 */
function getAllCategories($conn) {
    $response = AgriSphereBackend::tryCall('/api/catalog/categories');
    return $response['categories'] ?? [];
}

/**
 * Get cart count for customer (C++ backend: /api/cart/count)
 */
function getCartCount($conn, $customerId) {
    $response = AgriSphereBackend::tryCall('/api/cart/count', [
        'customer_id' => (int)$customerId,
    ]);
    return $response['count'] ?? 0;
}

/**
 * Number of unread notifications for a member.
 * These rows are created by the Observer layer in the C++ backend whenever a
 * farmer activates a discount (C++ backend: /api/notifications/unread-count).
 */
function getUnreadNotificationCount($userId) {
    $response = AgriSphereBackend::tryCall('/api/notifications/unread-count', [
        'user_id' => (int)$userId,
    ]);
    return (int)($response['unread_count'] ?? 0);
}

/**
 * Truncate text to specified length
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user's full name
 */
function getUserFullName($conn, $userId) {
    $stmt = $conn->prepare("SELECT First_name, Last_name FROM user WHERE User_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['First_name'] . ' ' . $row['Last_name'];
    }
    return 'Unknown';
}

/**
 * Convert timestamp to human-readable "time ago" format
 */
function timeAgo($datetime) {
    if (!$datetime) return '';

    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}