<?php
/**
 * Farmer - My Inventory
 * Professional inventory management with product cards
 */

$pageTitle = 'My Inventory';
$currentPage = 'manage_inventory';

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

// Handle updates (C++ backend: /api/farmer/inventory/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && isset($_POST['product_id'])) {
    try {
        $updateResult = AgriSphereBackend::call('/api/farmer/inventory/update', [
            'farmer_id'          => (int)$farmerId,
            'product_id'         => (int)$_POST['product_id'],
            'price_per_unit'     => (float)$_POST['price_per_unit'],
            'quantity_available' => (int)$_POST['quantity_available'],
        ]);
        setFlashMessage(
            !empty($updateResult['success']) ? 'success' : 'error',
            $updateResult['message'] ?? ($updateResult['error'] ?? 'Update failed')
        );
    } catch (BackendUnavailableException $e) {
        AgriSphereBackend::fail($e);
    }
    redirect(BASE_URL . 'farmer/manage_inventory.php');
}

// Get all products for this farmer (C++ backend: /api/farmer/inventory)
$inventoryResponse = AgriSphereBackend::tryCall('/api/farmer/inventory', [
    'farmer_id' => (int)$farmerId,
]);
$products = $inventoryResponse['products'] ?? [];



// Calculate stats
$totalProducts = count($products);
$approvedProducts = 0;
$pendingProducts = 0;
$rejectedProducts = 0;
$totalStock = 0;
$totalValue = 0;

foreach ($products as $product) {
    if ($product['status'] === 'approved') $approvedProducts++;
    elseif ($product['status'] === 'pending') $pendingProducts++;
    else $rejectedProducts++;

    $totalStock += $product['quantity_available'];
    $totalValue += $product['price_per_unit'] * $product['quantity_available'];
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">My Inventory</h1>
        <p class="page-subtitle">Manage your products, prices, and stock levels</p>
    </div>
    <a href="sell_products.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Product
    </a>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalProducts; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #D1FAE5; color: #059669;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $approvedProducts; ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $pendingProducts; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-cubes"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalStock); ?></div>
            <div class="stat-label">Total Stock</div>
        </div>
    </div>
</div>

<!-- Inventory Value Card -->
<div class="card inventory-value-card" style="margin-top: 24px; margin-bottom: 24px;">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div style="font-size: 0.875rem; color: var(--gray-500); margin-bottom: 4px;">Total Inventory Value</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);"><?php echo formatCurrency($totalValue); ?></div>
        </div>
        <div class="inventory-chart">
            <div class="chart-item">
                <span class="chart-dot approved"></span>
                <span><?php echo $approvedProducts; ?> Approved</span>
            </div>
            <div class="chart-item">
                <span class="chart-dot pending"></span>
                <span><?php echo $pendingProducts; ?> Pending</span>
            </div>
            <?php if ($rejectedProducts > 0): ?>
            <div class="chart-item">
                <span class="chart-dot rejected"></span>
                <span><?php echo $rejectedProducts; ?> Rejected</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Products Grid -->
<?php if (empty($products)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h4 class="empty-state-title">No products yet</h4>
                <p class="empty-state-text">Start by adding your first product to the marketplace.</p>
                <a href="sell_products.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Product
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="inventory-grid">
        <?php foreach ($products as $product):
            $status = $product['status'] ?? 'pending';
            $statusClass = $status === 'approved' ? 'approved' : ($status === 'rejected' ? 'rejected' : 'pending');
            $stockLevel = $product['quantity_available'];
            $stockClass = $stockLevel <= 0 ? 'out' : ($stockLevel < 10 ? 'low' : 'good');
        ?>
        <div class="inventory-card <?php echo $statusClass; ?>">
            <!-- Product Image -->
            <div class="inventory-card-image">
                <?php if ($product['product_image'] && file_exists(__DIR__ . '/../uploads/products/' . $product['product_image'])): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $product['product_image']; ?>" alt="<?php echo sanitize($product['product_name']); ?>">
                <?php else: ?>
                    <div class="product-placeholder">
                        <i class="fas fa-seedling"></i>
                    </div>
                <?php endif; ?>
                <div class="status-ribbon <?php echo $statusClass; ?>">
                    <?php echo ucfirst($status); ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="inventory-card-body">
                <div class="product-header">
                    <h3 class="product-name"><?php echo sanitize($product['product_name']); ?></h3>
                    <span class="category-badge"><?php echo sanitize($product['category_name']); ?></span>
                </div>

                <!-- Price & Stock Info -->
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label">Your Price</span>
                        <span class="meta-value price"><?php echo formatCurrency($product['price_per_unit']); ?></span>
                        <span class="meta-unit">per <?php echo sanitize($product['unit']); ?></span>
                    </div>
                    <?php if ($product['govt_price']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Govt. Price</span>
                        <span class="meta-value govt"><?php echo formatCurrency($product['govt_price']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span class="meta-label">Stock</span>
                        <span class="meta-value stock <?php echo $stockClass; ?>"><?php echo number_format($stockLevel); ?></span>
                        <span class="meta-unit"><?php echo sanitize($product['unit']); ?></span>
                    </div>
                </div>

                <!-- Quick Edit Form -->
                <form method="POST" class="quick-edit-form">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (<?php echo CURRENCY; ?>)</label>
                            <input type="number" step="0.01" name="price_per_unit" value="<?php echo $product['price_per_unit']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity_available" value="<?php echo $product['quantity_available']; ?>" class="form-control">
                        </div>
                        <button type="submit" name="save" class="btn btn-sm btn-primary">
                            <i class="fas fa-save"></i>
                        </button>
                    </div>
                </form>

                <!-- Product Photo -->
                <form method="POST" action="update_product_image.php" enctype="multipart/form-data" class="photo-edit-form">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <label>
                        <i class="fas fa-camera"></i>
                        <?php echo $product['product_image'] ? 'Change photo' : 'Add photo'; ?>
                    </label>
                    <div class="form-row">
                        <input type="file" name="product_image" class="form-control"
                               accept="image/jpeg,image/png,image/gif,image/webp" required>
                        <button type="submit" class="btn btn-sm btn-outline">
                            <i class="fas fa-upload"></i>
                        </button>
                    </div>
                </form>

                <!-- Action Buttons -->
                <div class="inventory-card-actions">
                    <?php if ($status === 'approved'): ?>
                        <a href="product_invoice.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline" target="_blank">
                            <i class="fas fa-file-invoice"></i> Listing Invoice
                        </a>
                    <?php elseif ($status === 'pending'): ?>
                        <span class="pending-text">
                            <i class="fas fa-hourglass-half"></i> Awaiting admin approval
                        </span>
                    <?php else: ?>
                        <span class="rejected-text">
                            <i class="fas fa-times-circle"></i>
                            <?php echo $product['rejection_reason'] ? sanitize($product['rejection_reason']) : 'Product was rejected'; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
/* Inventory Value Card */
.inventory-value-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid var(--primary);
}

.inventory-chart {
    display: flex;
    gap: 20px;
}

.chart-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.chart-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.chart-dot.approved { background: #059669; }
.chart-dot.pending { background: #D97706; }
.chart-dot.rejected { background: #DC2626; }

/* Inventory Grid */
.inventory-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}

/* Inventory Card */
.inventory-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--gray-200);
    transition: all 0.3s ease;
}

.inventory-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.inventory-card.approved {
    border-left: 4px solid #059669;
}

.inventory-card.pending {
    border-left: 4px solid #D97706;
}

.inventory-card.rejected {
    border-left: 4px solid #DC2626;
}

/* Card Image */
.inventory-card-image {
    position: relative;
    height: 160px;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.inventory-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-placeholder {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, #3d8b68 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

.status-ribbon {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-ribbon.approved {
    background: #059669;
    color: white;
}

.status-ribbon.pending {
    background: #D97706;
    color: white;
}

.status-ribbon.rejected {
    background: #DC2626;
    color: white;
}

/* Card Body */
.inventory-card-body {
    padding: 20px;
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.product-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
    flex: 1;
}

.category-badge {
    background: #E0E7FF;
    color: #4338CA;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    margin-left: 8px;
}

/* Product Meta */
.product-meta {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 16px;
    background: var(--gray-50);
    border-radius: 12px;
    margin-bottom: 16px;
}

.meta-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.meta-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-500);
    margin-bottom: 4px;
}

.meta-value {
    font-size: 1.125rem;
    font-weight: 700;
}

.meta-value.price {
    color: var(--primary);
}

.meta-value.govt {
    color: var(--gray-500);
    font-size: 0.875rem;
}

.meta-value.stock.good {
    color: #059669;
}

.meta-value.stock.low {
    color: #D97706;
}

.meta-value.stock.out {
    color: #DC2626;
}

.meta-unit {
    font-size: 11px;
    color: var(--gray-400);
}

/* Quick Edit Form */
.photo-edit-form {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed var(--gray-200);
}

.photo-edit-form label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    margin-bottom: 6px;
}

.photo-edit-form .form-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.photo-edit-form input[type="file"] {
    flex: 1;
    min-width: 0;
    font-size: 0.75rem;
    padding: 6px;
}

.quick-edit-form {
    margin-bottom: 16px;
}

.quick-edit-form .form-row {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

.quick-edit-form .form-group {
    flex: 1;
}

.quick-edit-form label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--gray-500);
    margin-bottom: 4px;
    text-transform: uppercase;
}

.quick-edit-form .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.quick-edit-form .form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(47, 107, 79, 0.1);
}

.quick-edit-form button {
    height: 38px;
    padding: 0 16px;
}

/* Action Buttons */
.inventory-card-actions {
    padding-top: 16px;
    border-top: 1px solid var(--gray-100);
    display: flex;
    justify-content: center;
}

.pending-text {
    font-size: 0.75rem;
    color: #D97706;
    display: flex;
    align-items: center;
    gap: 6px;
}

.rejected-text {
    font-size: 0.75rem;
    color: #DC2626;
    display: flex;
    align-items: center;
    gap: 6px;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .inventory-grid {
        grid-template-columns: 1fr;
    }

    .product-meta {
        grid-template-columns: repeat(3, 1fr);
    }

    .inventory-chart {
        flex-direction: column;
        gap: 8px;
    }

    .inventory-value-card .card-body {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>