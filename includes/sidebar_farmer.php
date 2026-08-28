<?php
/**
 * Farmer Sidebar Navigation
 */
$userName = getCurrentUserName();
$userInitials = strtoupper(substr($userName, 0, 1));
?>
<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <i class="fas fa-leaf"></i>
            </div>
            <span class="sidebar-logo-text"><?php echo SITE_NAME; ?></span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><?php echo $userInitials; ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo sanitize($userName); ?></div>
            <div class="sidebar-user-role">Farmer</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>farmer/dashboard.php"
           class="sidebar-nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/sell_products.php"
           class="sidebar-nav-item <?php echo $currentPage === 'sell_products' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Sell Products</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/manage_inventory.php"
           class="sidebar-nav-item <?php echo $currentPage === 'manage_inventory' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span>My Inventory</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/discounts.php"
           class="sidebar-nav-item <?php echo $currentPage === 'discounts' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Discounts</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/my_sales.php"
           class="sidebar-nav-item <?php echo $currentPage === 'my_sales' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>My Sales</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/sales_history.php"
           class="sidebar-nav-item <?php echo $currentPage === 'sales_history' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Sales History</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/subscription.php"
           class="sidebar-nav-item <?php echo $currentPage === 'subscription' ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i>
            <span>Subscription</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/profile.php"
           class="sidebar-nav-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/ai_assistant.php"
           class="sidebar-nav-item <?php echo $currentPage === 'ai_assistant' ? 'active' : ''; ?>">
            <i class="fas fa-robot"></i>
            <span>AI Assistant</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="sidebar-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<!-- Top Bar -->
<header class="topbar">
    <div class="topbar-title"><?php echo sanitize($pageTitle); ?></div>
    <div class="topbar-actions">
        <div class="topbar-search">
            <i class="fas fa-search" style="color: var(--gray-400);"></i>
            <input type="text" placeholder="Search...">
        </div>
    </div>
</header>

<!-- Main Content Area -->
<main class="main-content">
    <?php displayFlashMessage(); ?>

<?php
// -------------------------------------------------------------------------
//  Subscription gate
// -------------------------------------------------------------------------
//  Every farmer page includes this sidebar, so this is the single place that
//  keeps an unpaid farmer out. The paywall is rendered here rather than
//  redirected to, because the page has already started sending output by the
//  time this file runs (output_buffering is off), which would make a
//  header() redirect fail.
//
//  farmer/subscription.php is deliberately exempt - it is where they pay.
// -------------------------------------------------------------------------
require_once __DIR__ . '/farmer_subscription.php';

if (($currentPage ?? '') !== 'subscription' && !farmerHasActiveSubscription($conn, getCurrentUserId())) {
    $subState = farmerSubscriptionStatus($conn, getCurrentUserId());
    ?>
    <div class="page-header">
        <h1 class="page-title">
            <?php echo $subState['pending'] ? 'Waiting for Admin Approval' : 'Subscription Required'; ?>
        </h1>
        <p class="page-subtitle">
            <?php
            if ($subState['pending']) {
                echo 'Your payment has been submitted and is being reviewed';
            } elseif ($subState['expires_at'] !== '') {
                echo 'Your farmer subscription has expired';
            } else {
                echo 'Pay once to start selling on AgriSphere';
            }
            ?>
        </p>
    </div>

    <div class="card" style="max-width: 640px;">
        <div class="card-body" style="text-align: center; padding: 40px 32px;">
            <div style="font-size: 3rem; color: <?php echo $subState['pending'] ? 'var(--warning)' : 'var(--primary)'; ?>; margin-bottom: 16px;">
                <i class="fas <?php echo $subState['pending'] ? 'fa-hourglass-half' : 'fa-id-card'; ?>"></i>
            </div>

            <?php if ($subState['pending']): ?>
                <h3 style="margin-bottom: 8px;">Payment Submitted</h3>
                <p class="text-muted" style="margin-bottom: 24px;">
                    Your <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?> payment of
                    <strong><?php echo formatDateTime($subState['pending_at']); ?></strong>
                    is waiting for an administrator to approve it. Selling unlocks as soon as it is approved.
                </p>
                <a href="<?php echo BASE_URL; ?>farmer/subscription.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-receipt"></i> View Subscription
                </a>
            <?php else: ?>
                <h3 style="margin-bottom: 8px;">
                    <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?>
                    <span class="text-muted" style="font-size: 1rem; font-weight: 400;">
                        / <?php echo FARMER_SUBSCRIPTION_MONTHS; ?> months
                    </span>
                </h3>

                <p class="text-muted" style="margin-bottom: 24px;">
                    <?php if ($subState['expires_at'] !== ''): ?>
                        Your subscription expired on
                        <strong><?php echo formatDateTime($subState['expires_at']); ?></strong>.
                        Renew it to get back to selling.
                    <?php elseif ($subState['rejected']): ?>
                        Your last payment was not approved by the admin. You can submit a new one.
                    <?php else: ?>
                        Farmer accounts pay a subscription fee before listing products.
                        An admin approves the payment, then your dashboard, inventory and discounts unlock.
                    <?php endif; ?>
                </p>

                <a href="<?php echo BASE_URL; ?>farmer/subscription.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-credit-card"></i>
                    <?php echo $subState['expires_at'] !== '' ? 'Renew Subscription' : 'Pay Subscription Fee'; ?>
                </a>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <a href="<?php echo BASE_URL; ?>auth/logout.php" class="text-muted text-sm">Log out</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/footer.php';
    exit;
}
?>
