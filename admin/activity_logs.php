<?php
/**
 * Admin - Activity Logs
 * Shows timestamps for user logins, orders, and product activities
 */

$pageTitle = 'Activity Logs';
$currentPage = 'activity_logs';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get recent user logins (users who have logged in recently)
$recentLogins = $conn->query("
    SELECT u.User_id, u.First_name, u.Last_name, u.Email, u.User_type, u.last_login
    FROM user u
    WHERE u.last_login IS NOT NULL
    ORDER BY u.last_login DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Get recent orders with all timestamps
$recentOrders = $conn->query("
    SELECT co.*,
           CONCAT(u.First_name, ' ', u.Last_name) as customer_name,
           CONCAT(updater.First_name, ' ', updater.Last_name) as updated_by_name
    FROM customer_order co
    JOIN user u ON co.customer_id = u.User_id
    LEFT JOIN user updater ON co.updated_by = updater.User_id
    ORDER BY co.order_date DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Get recent product submissions with approval timestamps
$recentProducts = $conn->query("
    SELECT fp.*,
           c.category_name,
           CONCAT(farmer.First_name, ' ', farmer.Last_name) as farmer_name,
           CONCAT(approver.First_name, ' ', approver.Last_name) as approved_by_name
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    JOIN user farmer ON fp.farmer_id = farmer.User_id
    LEFT JOIN user approver ON fp.approved_by = approver.User_id
    ORDER BY fp.submitted_at DESC, fp.product_id DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Activity Logs</h1>
    <p class="page-subtitle">Track user logins, orders, and product activities with timestamps</p>
</div>

<!-- Filter Tabs -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px;">
        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline'; ?>">
            <i class="fas fa-list"></i> All Activity
        </a>
        <a href="?filter=logins" class="btn <?php echo $filter === 'logins' ? 'btn-primary' : 'btn-outline'; ?>">
            <i class="fas fa-sign-in-alt"></i> User Logins
        </a>
        <a href="?filter=orders" class="btn <?php echo $filter === 'orders' ? 'btn-primary' : 'btn-outline'; ?>">
            <i class="fas fa-shopping-cart"></i> Order Activity
        </a>
        <a href="?filter=products" class="btn <?php echo $filter === 'products' ? 'btn-primary' : 'btn-outline'; ?>">
            <i class="fas fa-box"></i> Product Activity
        </a>
    </div>
</div>

<?php if ($filter === 'all' || $filter === 'logins'): ?>
<!-- User Login Activity -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sign-in-alt"></i> Recent User Logins</h3>
        <span class="badge badge-approved"><?php echo count($recentLogins); ?> records</span>
    </div>
    <?php if (empty($recentLogins)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <i class="fas fa-user-clock text-muted"></i>
                <p>No login records yet</p>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Time Ago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogins as $login): ?>
                        <tr>
                            <td><strong>#<?php echo $login['User_id']; ?></strong></td>
                            <td><?php echo sanitize($login['First_name'] . ' ' . $login['Last_name']); ?></td>
                            <td><?php echo sanitize($login['Email']); ?></td>
                            <td>
                                <?php
                                $roleClass = $login['User_type'] === 'Admin' ? 'badge-pending' :
                                            ($login['User_type'] === 'Farmer' ? 'badge-approved' : 'badge-processing');
                                ?>
                                <span class="badge <?php echo $roleClass; ?>"><?php echo $login['User_type']; ?></span>
                            </td>
                            <td><?php echo formatDateTime($login['last_login']); ?></td>
                            <td>
                                <span class="text-muted"><?php echo timeAgo($login['last_login']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($filter === 'all' || $filter === 'orders'): ?>
<!-- Order Activity -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-cart"></i> Order Activity & Timestamps</h3>
        <span class="badge badge-processing"><?php echo count($recentOrders); ?> orders</span>
    </div>
    <?php if (empty($recentOrders)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <i class="fas fa-shopping-bag text-muted"></i>
                <p>No orders yet</p>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Status Timestamps</th>
                        <th>Updated By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                            <td><?php echo sanitize($order['customer_name']); ?></td>
                            <td><?php echo formatCurrency($order['total_amount']); ?></td>
                            <td><?php echo getOrderStatusBadge($order['status']); ?></td>
                            <td>
                                <div><?php echo formatDateTime($order['order_date']); ?></div>
                                <small class="text-muted"><?php echo timeAgo($order['order_date']); ?></small>
                            </td>
                            <td>
                                <div class="timestamp-list">
                                    <?php if ($order['processed_at']): ?>
                                        <div class="timestamp-item">
                                            <i class="fas fa-cog text-warning"></i>
                                            <span>Processed: <?php echo formatDateTime($order['processed_at']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order['shipped_at']): ?>
                                        <div class="timestamp-item">
                                            <i class="fas fa-truck text-info"></i>
                                            <span>Shipped: <?php echo formatDateTime($order['shipped_at']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order['delivered_at']): ?>
                                        <div class="timestamp-item">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span>Delivered: <?php echo formatDateTime($order['delivered_at']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order['cancelled_at']): ?>
                                        <div class="timestamp-item">
                                            <i class="fas fa-times-circle text-danger"></i>
                                            <span>Cancelled: <?php echo formatDateTime($order['cancelled_at']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!$order['processed_at'] && !$order['shipped_at'] && !$order['delivered_at'] && !$order['cancelled_at']): ?>
                                        <span class="text-muted">Pending...</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo $order['updated_by_name'] ? sanitize($order['updated_by_name']) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($filter === 'all' || $filter === 'products'): ?>
<!-- Product Activity -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-box"></i> Product Submissions & Approvals</h3>
        <span class="badge badge-approved"><?php echo count($recentProducts); ?> products</span>
    </div>
    <?php if (empty($recentProducts)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <i class="fas fa-box text-muted"></i>
                <p>No products yet</p>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product</th>
                        <th>Farmer</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Approved At</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProducts as $product): ?>
                        <tr>
                            <td><strong>#<?php echo $product['product_id']; ?></strong></td>
                            <td>
                                <div><?php echo sanitize($product['product_name']); ?></div>
                                <small class="text-muted"><?php echo sanitize($product['category_name']); ?></small>
                            </td>
                            <td><?php echo sanitize($product['farmer_name']); ?></td>
                            <td><?php echo formatCurrency($product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?></td>
                            <td><?php echo getStatusBadge($product['status']); ?></td>
                            <td>
                                <?php if ($product['submitted_at']): ?>
                                    <div><?php echo formatDateTime($product['submitted_at']); ?></div>
                                    <small class="text-muted"><?php echo timeAgo($product['submitted_at']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['approved_at']): ?>
                                    <div><?php echo formatDateTime($product['approved_at']); ?></div>
                                    <small class="text-muted"><?php echo timeAgo($product['approved_at']); ?></small>
                                <?php elseif ($product['status'] === 'approved'): ?>
                                    <span class="text-muted">Before tracking</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['approved_by_name']): ?>
                                    <?php echo sanitize($product['approved_by_name']); ?>
                                <?php elseif ($product['status'] === 'approved'): ?>
                                    <span class="text-muted">System</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
.timestamp-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.75rem;
}
.timestamp-item {
    display: flex;
    align-items: center;
    gap: 6px;
}
.timestamp-item i {
    font-size: 0.875rem;
}
.text-warning { color: #D97706; }
.text-info { color: #0EA5E9; }
.text-success { color: #059669; }
.text-danger { color: #DC2626; }
.text-muted { color: var(--gray-400); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
