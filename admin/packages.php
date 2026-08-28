<?php


$pageTitle = 'Product Packages';
$currentPage = 'packages';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Existing packages, with their items (read directly - this is admin maintenance)
$packages = [];
$result = $conn->query("
    SELECT pp.package_id, pp.package_name, pp.description, pp.is_active,
           COUNT(pi.package_item_id) AS item_count,
           COALESCE(SUM(pi.quantity * fp.price_per_unit), 0) AS package_total
    FROM product_package pp
    LEFT JOIN package_item pi ON pi.package_id = pp.package_id
    LEFT JOIN farm_product fp ON fp.product_id = pi.product_id
    GROUP BY pp.package_id
    ORDER BY pp.package_id DESC
");
if ($result) {
    $packages = $result->fetch_all(MYSQLI_ASSOC);
}

// The items of every package, grouped so the table can list them
$packageItems = [];
$result = $conn->query("
    SELECT pi.package_id, pi.quantity, fp.product_name, fp.unit
    FROM package_item pi
    JOIN farm_product fp ON fp.product_id = pi.product_id
    ORDER BY pi.package_item_id
");
if ($result) {
    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        $packageItems[$row['package_id']][] = $row;
    }
}

// Approved products the admin can choose from
$availableProducts = [];
$result = $conn->query("
    SELECT fp.product_id, fp.product_name, fp.unit, fp.price_per_unit, fp.product_image,
           c.category_name,
           CONCAT(u.First_name, ' ', u.Last_name) AS farmer_name
    FROM farm_product fp
    JOIN category c ON c.category_id = fp.category_id
    JOIN user u ON u.User_id = fp.farmer_id
    WHERE fp.status = 'approved'
    ORDER BY c.category_name, fp.product_name
");
if ($result) {
    $availableProducts = $result->fetch_all(MYSQLI_ASSOC);
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Product Packages</h1>
    <p class="page-subtitle">
        Build a combo from existing products &mdash; customers add the whole package to their cart in one click
    </p>
</div>

<!-- Existing packages -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-box"></i> Existing Packages (<?php echo count($packages); ?>)</h3>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Package</th>
                    <th>Contents</th>
                    <th>Value</th>
                    <th>Visible</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($packages)): ?>
                    <tr>
                        <td colspan="6" class="text-muted" style="text-align: center; padding: 24px;">
                            No packages yet. Create one below.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?php echo (int)$package['package_id']; ?></td>
                        <td>
                            <strong><?php echo sanitize($package['package_name']); ?></strong>
                            <div class="text-muted text-sm"><?php echo sanitize($package['description']); ?></div>
                        </td>
                        <td class="text-sm">
                            <?php foreach ($packageItems[$package['package_id']] ?? [] as $item): ?>
                                <div>
                                    <?php echo sanitize($item['product_name']); ?>
                                    &mdash; <?php echo (int)$item['quantity']; ?> <?php echo sanitize($item['unit']); ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($packageItems[$package['package_id']])): ?>
                                <span class="text-muted">empty</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap;"><?php echo formatCurrency((float)$package['package_total']); ?></td>
                        <td>
                            <span class="badge <?php echo (int)$package['is_active'] === 1 ? 'badge-approved' : 'badge-pending'; ?>">
                                <?php echo (int)$package['is_active'] === 1 ? 'Visible' : 'Hidden'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <form method="POST" action="package_action.php" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="package_id" value="<?php echo (int)$package['package_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Show / hide">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </form>
                                <form method="POST" action="package_action.php" style="display: inline;"
                                      onsubmit="return confirm('Delete this package? The products themselves are not affected.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="package_id" value="<?php echo (int)$package['package_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create a new package -->
<form method="POST" action="package_action.php">
    <input type="hidden" name="action" value="create">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus"></i> Create a New Package</h3>
        </div>
        <div class="card-body">
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="package_name">Package Name <span style="color: var(--error);">*</span></label>
                    <input type="text" id="package_name" name="package_name" class="form-control"
                           placeholder="e.g. Ramadan Special" maxlength="120" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Short Description</label>
                    <input type="text" id="description" name="description" class="form-control"
                           placeholder="What is this package for?" maxlength="300">
                </div>
            </div>

            <p class="text-muted" style="margin: 8px 0 16px;">
                Tick the products to include and set how much of each. A package needs at least 2 products.
                The total is worked out live from the current prices.
            </p>

            <div class="package-picker">
                <?php foreach ($availableProducts as $product): ?>
                    <label class="picker-item">
                        <input type="checkbox" name="product_id[]"
                               value="<?php echo (int)$product['product_id']; ?>"
                               data-price="<?php echo (float)$product['price_per_unit']; ?>"
                               class="picker-check">
                        <img src="<?php echo getProductImageUrl($product['product_image']); ?>" alt="" class="picker-thumb">
                        <span class="picker-info">
                            <strong><?php echo sanitize($product['product_name']); ?></strong>
                            <span class="text-muted text-sm">
                                <?php echo sanitize($product['farmer_name']); ?> &middot;
                                <?php echo formatCurrency((float)$product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?>
                            </span>
                        </span>
                        <input type="number" min="1" value="1"
                               name="quantity[<?php echo (int)$product['product_id']; ?>]"
                               class="form-control picker-qty" aria-label="Quantity">
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 16px; border-top: 2px solid var(--gray-200);">
                <div>
                    <span class="text-muted">Selected:</span>
                    <strong id="pickerCount">0</strong> products &middot;
                    <span class="text-muted">Package value:</span>
                    <strong id="pickerTotal" style="color: var(--primary);"><?php echo formatCurrency(0); ?></strong>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Package
                </button>
            </div>
        </div>
    </div>
</form>

<style>
.package-picker {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 10px;
    max-height: 460px;
    overflow-y: auto;
    padding: 4px;
}

.picker-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.picker-item:has(.picker-check:checked) {
    border-color: var(--primary);
    background: var(--primary-50);
}

.picker-thumb {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    flex-shrink: 0;
}

.picker-info {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.picker-info strong {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.picker-qty {
    width: 68px;
    flex-shrink: 0;
    padding: 6px;
}
</style>

<script>
const CURRENCY_SYMBOL = '<?php echo CURRENCY; ?>';

function refreshPickerSummary() {
    let count = 0;
    let total = 0;

    document.querySelectorAll('.picker-item').forEach(function (item) {
        const check = item.querySelector('.picker-check');
        const qty = item.querySelector('.picker-qty');
        if (check.checked) {
            count += 1;
            total += parseFloat(check.dataset.price) * (parseInt(qty.value, 10) || 0);
        }
    });

    document.getElementById('pickerCount').textContent = count;
    document.getElementById('pickerTotal').textContent =
        CURRENCY_SYMBOL + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.querySelectorAll('.picker-check, .picker-qty').forEach(function (el) {
    el.addEventListener('change', refreshPickerSummary);
    el.addEventListener('input', refreshPickerSummary);
});

// Typing in the quantity box should not toggle the checkbox label.
document.querySelectorAll('.picker-qty').forEach(function (el) {
    el.addEventListener('click', function (event) { event.preventDefault(); event.stopPropagation(); });
});

refreshPickerSummary();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
