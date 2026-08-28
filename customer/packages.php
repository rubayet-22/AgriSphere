<?php
/**
 * Customer - Product Packages
 *
 * Each package is added with ONE request to the C++ PackageFacade
 * (/api/packages/add). This page never calls /api/cart/add per product -
 * finding the products, checking stock, reading prices, adding every cart
 * line and rolling back on failure all happen inside the Facade.
 */

$pageTitle = 'Product Packages';
$currentPage = 'packages';

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

// C++ backend: /api/packages/list (Facade read side)
$response = AgriSphereBackend::tryCall('/api/packages/list');
$packages = $response['packages'] ?? [];

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Product Packages</h1>
    <p class="page-subtitle">Add a whole week of shopping to your cart in one click</p>
</div>

<?php if (!empty($response['backend_down'])): ?>
    <div class="alert alert-error"><?php echo sanitize($response['error']); ?></div>
<?php endif; ?>

<?php if (empty($packages)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
                <h4 class="empty-state-title">No packages available</h4>
                <p class="empty-state-text">
                    Run <code>database/facade_migration.sql</code> to create the sample packages.
                </p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-seedling"></i> Browse Products
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
        <?php foreach ($packages as $package): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-box" style="color: var(--primary);"></i>
                        <?php echo sanitize($package['package_name']); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted" style="margin-bottom: 16px;">
                        <?php echo sanitize($package['description']); ?>
                    </p>

                    <table class="table" style="margin-bottom: 16px;">
                        <tbody>
                            <?php foreach ($package['items'] as $item): ?>
                                <tr>
                                    <td style="width: 56px; padding-right: 0;">
                                        <?php if (!empty($item['product_image'])): ?>
                                            <img src="<?php echo getProductImageUrl($item['product_image']); ?>"
                                                 alt="<?php echo sanitize($item['product_name']); ?>"
                                                 class="package-thumb">
                                        <?php else: ?>
                                            <div class="package-thumb package-thumb-empty">
                                                <i class="fas fa-leaf"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo sanitize($item['product_name']); ?></strong>
                                        <?php if (empty($item['in_stock'])): ?>
                                            <span class="badge badge-rejected" style="margin-left: 6px;">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted" style="white-space: nowrap;">
                                        <?php echo (int)$item['quantity']; ?> <?php echo sanitize($item['unit']); ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <?php echo formatCurrency((float)$item['line_total']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.15rem; font-weight: 700; padding-top: 12px; border-top: 2px solid var(--gray-200);">
                        <span>Total</span>
                        <span style="color: var(--primary);"><?php echo formatCurrency((float)$package['total']); ?></span>
                    </div>
                    <div class="text-muted text-sm" style="margin-top: 4px;">
                        <?php echo (int)$package['item_count']; ?> products &middot;
                        <?php echo (int)$package['total_units']; ?> units
                    </div>

                    <?php if (!empty($package['available'])): ?>
                        <form action="package_action.php" method="POST" style="margin-top: 16px;">
                            <input type="hidden" name="package_id" value="<?php echo (int)$package['package_id']; ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                                <i class="fas fa-cart-plus"></i>
                                Add <?php echo sanitize($package['package_name']); ?> to Cart
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-outline" style="width: 100%; padding: 12px; margin-top: 16px;" disabled>
                            <i class="fas fa-ban"></i> Currently unavailable
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-warning" style="margin-top: 24px;">
        <i class="fas fa-info-circle"></i>
        Prices already include any discount the farmer is offering. A package is added to
        your normal cart, so you can still change quantities or remove items afterwards.
    </div>

    <style>
    .package-thumb {
        width: 44px;
        height: 44px;
        object-fit: cover;
        border-radius: var(--radius-sm);
        display: block;
    }
    .package-thumb-empty {
        background: var(--gray-100);
        color: var(--gray-400);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    </style>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
