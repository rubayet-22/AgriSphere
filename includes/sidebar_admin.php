<?php
/**
 * Admin Sidebar Navigation
 */
$userName = getCurrentUserName();
$userInitials = strtoupper(substr($userName, 0, 1));

// Get pending counts for badges
$pendingProducts = 0;
$pendingOrders = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'pending'");
if ($row = $result->fetch_assoc()) {
    $pendingProducts = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customer_order WHERE status = 'Pending' OR status = 'Processing'");
if ($row = $result->fetch_assoc()) {
    $pendingOrders = $row['count'];
}

// Premium membership requests waiting for review. Wrapped in try/catch so the
// admin pages still work if database/strategy_migration.sql has not been run.
$pendingMemberships = 0;
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM premium_membership WHERE status = 'Pending'");
    if ($result && $row = $result->fetch_assoc()) {
        $pendingMemberships = (int)$row['count'];
    }
} catch (Throwable $e) {
    $pendingMemberships = 0;
}

// Farmer subscription payments waiting for review. Same try/catch guard for
// installs that have not run database/farmer_subscription.sql yet.
$pendingFarmerSubs = 0;
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM farmer_subscription WHERE status = 'Pending'");
    if ($result && $row = $result->fetch_assoc()) {
        $pendingFarmerSubs = (int)$row['count'];
    }
} catch (Throwable $e) {
    $pendingFarmerSubs = 0;
}
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
        <div class="sidebar-avatar" style="background: #FEE2E2; color: #991B1B;"><?php echo $userInitials; ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo sanitize($userName); ?></div>
            <div class="sidebar-user-role">Administrator</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php"
           class="sidebar-nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/users.php"
           class="sidebar-nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/products.php"
           class="sidebar-nav-item <?php echo $currentPage === 'products' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Products</span>
            <?php if ($pendingProducts > 0): ?>
                <span class="sidebar-nav-badge"><?php echo $pendingProducts; ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/categories.php"
           class="sidebar-nav-item <?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/inventory.php"
           class="sidebar-nav-item <?php echo $currentPage === 'inventory' ? 'active' : ''; ?>">
            <i class="fas fa-warehouse"></i>
            <span>Inventory</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/orders.php"
           class="sidebar-nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>Orders</span>
            <?php if ($pendingOrders > 0): ?>
                <span class="sidebar-nav-badge"><?php echo $pendingOrders; ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/farmer_subscriptions.php"
           class="sidebar-nav-item <?php echo $currentPage === 'farmer_subscriptions' ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i>
            <span>Farmer Subs</span>
            <?php if ($pendingFarmerSubs > 0): ?>
                <span class="sidebar-nav-badge"><?php echo $pendingFarmerSubs; ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/packages.php"
           class="sidebar-nav-item <?php echo $currentPage === 'packages' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Packages</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/premium.php"
           class="sidebar-nav-item <?php echo $currentPage === 'premium' ? 'active' : ''; ?>">
            <i class="fas fa-crown"></i>
            <span>Premium</span>
            <?php if ($pendingMemberships > 0): ?>
                <span class="sidebar-nav-badge"><?php echo $pendingMemberships; ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/activity_logs.php"
           class="sidebar-nav-item <?php echo $currentPage === 'activity_logs' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Activity Logs</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/design_pattern_tests.php"
           class="sidebar-nav-item <?php echo $currentPage === 'design_pattern_tests' ? 'active' : ''; ?>">
            <i class="fas fa-chess"></i>
            <span>Design Patterns Test</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/ai_assistant.php"
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
