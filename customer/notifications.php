<?php
/**
 * Customer - Notifications
 *
 * Shows the notification rows written by the Observer layer in the C++
 * backend. Because they are stored in the database, a member sees every
 * discount that was announced while they were logged out.
 */

$pageTitle = 'Notifications';
$currentPage = 'notifications';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('error', 'Please login as a customer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

// Check if user is blocked
if (isUserBlocked($conn, getCurrentUserId())) {
    session_destroy();
    setFlashMessage('error', 'Your account has been blocked. Please contact support.');
    redirect(BASE_URL . 'auth/login.php');
}

$customerId = getCurrentUserId();

// C++ backend: /api/notifications/list
$response = AgriSphereBackend::tryCall('/api/notifications/list', [
    'user_id' => (int)$customerId,
    'limit'   => 50,
]);
$notifications = $response['notifications'] ?? [];
$unreadCount   = (int)($response['unread_count'] ?? 0);

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">Notifications</h1>
            <p class="page-subtitle">
                <?php if ($unreadCount > 0): ?>
                    You have <?php echo $unreadCount; ?> unread notification<?php echo $unreadCount === 1 ? '' : 's'; ?>
                <?php else: ?>
                    You are all caught up
                <?php endif; ?>
            </p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form action="notification_action.php" method="POST">
                <input type="hidden" name="action" value="read_all">
                <button type="submit" class="btn btn-outline">
                    <i class="fas fa-check-double"></i> Mark all as read
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($notifications)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h4 class="empty-state-title">No notifications yet</h4>
                <p class="empty-state-text">
                    When a farmer discounts one of their products, you will be told here.
                </p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-seedling"></i> Browse Products
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="notification-list">
            <?php foreach ($notifications as $note):
                $isUnread = (int)$note['is_read'] === 0;
                // notification.type tells us which delivery channel wrote this
                // entry. The C++ Decorator layer stores one entry per channel
                // the customer enabled (In-App / SMS / Email / Push).
                $channelIcons = [
                    'discount_sms'   => 'fa-comment-sms',
                    'discount_email' => 'fa-envelope',
                    'discount_push'  => 'fa-bell',
                ];
                $noteIcon = $channelIcons[$note['type'] ?? ''] ?? 'fa-tags';
            ?>
                <div class="notification-item <?php echo $isUnread ? 'unread' : ''; ?>">
                    <div class="notification-icon">
                        <i class="fas <?php echo $noteIcon; ?>"></i>
                    </div>

                    <div class="notification-body">
                        <div class="notification-title">
                            <?php echo sanitize($note['title']); ?>
                            <?php if ($isUnread): ?>
                                <span class="badge-new">New</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-message">
                            <?php echo sanitize($note['message']); ?>
                        </div>
                        <div class="notification-time">
                            <i class="fas fa-clock"></i>
                            <?php echo formatDate($note['created_at']); ?>
                        </div>
                    </div>

                    <div class="notification-actions">
                        <?php if (!empty($note['product_id'])): ?>
                            <a href="products.php?search=<?php echo urlencode($note['product_name'] ?? ''); ?>"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-cart-plus"></i> View
                            </a>
                        <?php endif; ?>
                        <?php if ($isUnread): ?>
                            <form action="notification_action.php" method="POST">
                                <input type="hidden" name="action" value="read">
                                <input type="hidden" name="notification_id" value="<?php echo (int)$note['notification_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <i class="fas fa-check"></i> Mark read
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<style>
.notification-list {
    display: flex;
    flex-direction: column;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 18px 24px;
    border-bottom: 1px solid var(--gray-200);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: #F0FDF4;
    border-left: 3px solid var(--primary);
}

.notification-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #DCFCE7;
    color: #15803D;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-body {
    flex: 1;
    min-width: 200px;
}

.notification-title {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.badge-new {
    background: var(--primary);
    color: white;
    font-size: 0.6875rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: var(--radius-full);
    text-transform: uppercase;
}

.notification-message {
    color: var(--gray-600);
    font-size: 0.9375rem;
    line-height: 1.5;
}

.notification-time {
    margin-top: 6px;
    font-size: 0.8125rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 6px;
}

.notification-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

@media (max-width: 640px) {
    .notification-item {
        flex-wrap: wrap;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
