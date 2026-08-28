<?php
/**
 * Farmer - Discounts
 *
 * Activating a discount here is what triggers the Observer pattern in the
 * C++ backend: DiscountService saves the discount, then the DiscountSubject
 * notifies one observer per registered member, and each observer stores a
 * notification for its member.
 */

$pageTitle = 'Discounts';
$currentPage = 'discounts';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    setFlashMessage('error', 'Please login as a farmer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

// Check if user is blocked
if (isUserBlocked($conn, getCurrentUserId())) {
    session_destroy();
    setFlashMessage('error', 'Your account has been blocked. Please contact support.');
    redirect(BASE_URL . 'auth/login.php');
}

$farmerId = getCurrentUserId();

// Products with their current discount (C++ backend: /api/farmer/discounts)
$discountResponse = AgriSphereBackend::tryCall('/api/farmer/discounts', [
    'farmer_id' => (int)$farmerId,
]);
$products = $discountResponse['products'] ?? [];

// Only approved products can be discounted, because only those are visible
// to customers.
$approvedProducts = [];
$discountedCount = 0;
foreach ($products as $product) {
    if ($product['status'] === 'approved') {
        $approvedProducts[] = $product;
    }
    if ((int)$product['has_discount'] === 1) {
        $discountedCount++;
    }
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Discounts</h1>
        <p class="page-subtitle">Offer a discount and every member is notified automatically</p>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $discountedCount; ?></div>
            <div class="stat-label">Active Discounts</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($approvedProducts); ?></div>
            <div class="stat-label">Products You Can Discount</div>
        </div>
    </div>
</div>

<!-- Products -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title">My Products</h3>
    </div>

    <?php if (empty($approvedProducts)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h4 class="empty-state-title">Nothing to discount yet</h4>
                <p class="empty-state-text">
                    Only approved products can be discounted. Add a product and wait for
                    the admin to approve it.
                </p>
                <a href="sell_products.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="discount-list">
            <?php foreach ($approvedProducts as $product):
                $hasDiscount   = (int)$product['has_discount'] === 1;
                $originalPrice = (float)$product['price_per_unit'];
                $effective     = (float)$product['effective_price'];
                $percent       = (float)$product['discount_percent'];
            ?>
                <div class="discount-row">
                    <div class="discount-product">
                        <div class="discount-thumb">
                            <?php if ($product['product_image']): ?>
                                <img src="<?php echo getProductImageUrl($product['product_image']); ?>" alt="">
                            <?php else: ?>
                                <div class="discount-thumb-placeholder"><i class="fas fa-leaf"></i></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="discount-name"><?php echo sanitize($product['product_name']); ?></div>
                            <div class="discount-meta">
                                <?php echo sanitize($product['category_name']); ?>
                                &middot; <?php echo (int)$product['stock']; ?> in stock
                            </div>
                        </div>
                    </div>

                    <div class="discount-price">
                        <?php if ($hasDiscount): ?>
                            <span class="price-old"><?php echo formatCurrency($originalPrice); ?></span>
                            <span class="price-new"><?php echo formatCurrency($effective); ?></span>
                            <span class="badge badge-discount"><?php echo rtrim(rtrim(number_format($percent, 2), '0'), '.'); ?>% OFF</span>
                        <?php else: ?>
                            <span class="price-new"><?php echo formatCurrency($originalPrice); ?></span>
                            <span class="discount-meta">/ <?php echo sanitize($product['unit']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="discount-actions">
                        <form action="discount_action.php" method="POST" class="discount-form">
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                            <div class="percent-input">
                                <input type="number" name="discount_percent" min="1" max="90" step="1"
                                       value="<?php echo $hasDiscount ? (int)$percent : 10; ?>"
                                       class="form-control" required>
                                <span class="percent-sign">%</span>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-bullhorn"></i>
                                <?php echo $hasDiscount ? 'Update' : 'Apply'; ?>
                            </button>
                        </form>

                        <?php if ($hasDiscount): ?>
                            <form action="discount_action.php" method="POST"
                                  onsubmit="return confirm('Remove the discount from this product?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                                <button type="submit" class="btn btn-outline btn-sm">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.discount-list {
    display: flex;
    flex-direction: column;
}

.discount-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-200);
    flex-wrap: wrap;
}

.discount-row:last-child {
    border-bottom: none;
}

.discount-product {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 240px;
    flex: 1;
}

.discount-thumb {
    width: 52px;
    height: 52px;
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--gray-100);
    flex-shrink: 0;
}

.discount-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.discount-thumb-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-400);
}

.discount-name {
    font-weight: 600;
    color: var(--gray-800);
}

.discount-meta {
    font-size: 0.8125rem;
    color: var(--gray-500);
}

.discount-price {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 200px;
}

.price-old {
    text-decoration: line-through;
    color: var(--gray-400);
    font-size: 0.875rem;
}

.price-new {
    font-weight: 700;
    color: var(--primary);
}

.badge-discount {
    background: #FEE2E2;
    color: #B91C1C;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: var(--radius-full);
}

.discount-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.discount-form {
    display: flex;
    align-items: center;
    gap: 8px;
}

.percent-input {
    position: relative;
}

.percent-input .form-control {
    width: 90px;
    padding-right: 28px;
}

.percent-sign {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
    font-size: 0.875rem;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
