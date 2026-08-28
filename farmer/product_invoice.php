<?php
/**
 * Farmer - Product Approval Invoice
 * Generates a printable invoice for an approved product listing
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    setFlashMessage('error', 'Please login as a farmer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

$farmerId = getCurrentUserId();
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    redirect(BASE_URL . 'farmer/manage_inventory.php');
}

// Get product details (only if it belongs to this farmer and is approved)
$stmt = $conn->prepare("
    SELECT fp.*, c.category_name,
           COALESCE(fi.quantity_available, 0) as stock_quantity,
           gp.price_per_unit as govt_price,
           CONCAT(approver.First_name, ' ', approver.Last_name) as approved_by_name
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id
    LEFT JOIN government_prices gp ON LOWER(fp.product_name) = LOWER(gp.product_name)
    LEFT JOIN user approver ON fp.approved_by = approver.User_id
    WHERE fp.product_id = ? AND fp.farmer_id = ? AND fp.status = 'approved'
");
if ($stmt === false) {
    setFlashMessage('error', 'Database error: ' . $conn->error);
    redirect(BASE_URL . 'farmer/manage_inventory.php');
}
$stmt->bind_param("ii", $productId, $farmerId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    setFlashMessage('error', 'Product not found or not approved yet');
    redirect(BASE_URL . 'farmer/manage_inventory.php');
}

// Get farmer info
$stmt = $conn->prepare("SELECT u.*, f.address FROM user u LEFT JOIN farmer f ON u.User_id = f.farmer_id WHERE u.User_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

// Generate invoice number (Product Listing Invoice)
$invoiceNumber = 'PL-' . date('Ymd') . '-' . str_pad($productId, 4, '0', STR_PAD_LEFT);
$submittedAt = $product['submitted_at'] ?? $product['created_at'] ?? date('Y-m-d H:i:s');
$approvedAt = $product['approved_at'] ?? $product['updated_at'] ?? date('Y-m-d H:i:s');
$approvedByName = $product['approved_by_name'] ?? 'System Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Listing Invoice #<?php echo $invoiceNumber; ?> | AgriSphere</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #1f2937;
            background: #f3f4f6;
            padding: 20px;
        }

        .invoice {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 48px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 48px;
            padding-bottom: 24px;
            border-bottom: 2px solid #2F6B4F;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #2F6B4F 0%, #3d8b68 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: #2F6B4F;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: 700;
            color: #2F6B4F;
            margin-bottom: 8px;
        }

        .invoice-badge {
            display: inline-block;
            background: #d1fae5;
            color: #059669;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .invoice-number {
            font-size: 16px;
            color: #6b7280;
        }

        .invoice-date {
            font-size: 14px;
            color: #9ca3af;
        }

        .section {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .farmer-info {
            display: flex;
            justify-content: space-between;
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        .farmer-info h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .farmer-info p {
            color: #374151;
        }

        .farmer-info strong {
            font-size: 16px;
            color: #1f2937;
        }

        .product-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .product-name {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .product-category {
            display: inline-block;
            background: #e0e7ff;
            color: #4338ca;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-id {
            text-align: right;
            color: #6b7280;
        }

        .product-id strong {
            font-size: 18px;
            color: #2F6B4F;
        }

        .product-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .detail-box {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }

        .detail-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }

        .detail-value.price {
            color: #2F6B4F;
        }

        .detail-sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .approval-section {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 24px;
        }

        .approval-icon {
            width: 64px;
            height: 64px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 32px;
            color: #059669;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .approval-title {
            font-size: 20px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 8px;
        }

        .approval-text {
            color: #047857;
            font-size: 14px;
        }

        .description-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .description-section h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .description-section p {
            color: #374151;
        }

        .footer {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }

        .footer p {
            margin-bottom: 4px;
        }

        .actions {
            margin-bottom: 20px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #2F6B4F;
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            margin-left: 8px;
        }

        .terms {
            margin-top: 32px;
            padding: 20px;
            background: #fffbeb;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
        }

        .terms h4 {
            font-size: 14px;
            color: #92400e;
            margin-bottom: 8px;
        }

        .terms ul {
            margin: 0;
            padding-left: 20px;
            color: #92400e;
            font-size: 13px;
        }

        .terms li {
            margin-bottom: 4px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice {
                box-shadow: none;
                padding: 0;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-primary" onclick="window.print()">
            Print Invoice
        </button>
        <a href="manage_inventory.php" class="btn btn-secondary">
            Back to Products
        </a>
    </div>

    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <span>&#127807;</span>
                </div>
                <span class="logo-text">AgriSphere</span>
            </div>
            <div class="invoice-info">
                <div class="invoice-badge">Product Approved</div>
                <div class="invoice-title">LISTING INVOICE</div>
                <div class="invoice-number">#<?php echo $invoiceNumber; ?></div>
                <div class="invoice-date">Submitted: <?php echo formatDate($submittedAt, 'd M Y, h:i A'); ?></div>
                <div class="invoice-date">Approved: <?php echo formatDate($approvedAt, 'd M Y, h:i A'); ?></div>
            </div>
        </div>

        <!-- Farmer Info -->
        <div class="farmer-info">
            <div>
                <h4>Seller (Farmer)</h4>
                <p><strong><?php echo sanitize($farmer['First_name'] . ' ' . $farmer['Last_name']); ?></strong></p>
                <p><?php echo sanitize($farmer['Email']); ?></p>
                <p><?php echo sanitize($farmer['Phone'] ?? ''); ?></p>
            </div>
            <div style="text-align: right;">
                <h4>Farmer ID</h4>
                <p><strong>#<?php echo $farmerId; ?></strong></p>
                <p><?php echo nl2br(sanitize($farmer['address'] ?? '')); ?></p>
            </div>
        </div>

        <!-- Approval Badge -->
        <div class="approval-section">
            <div class="approval-icon">&#10003;</div>
            <div class="approval-title">Product Successfully Approved!</div>
            <div class="approval-text">Your product is now live on AgriSphere marketplace and visible to all customers.</div>
            <div class="approval-text" style="margin-top: 8px; font-size: 12px;">Approved by: <strong><?php echo sanitize($approvedByName); ?></strong></div>
        </div>

        <!-- Product Details -->
        <div class="product-card">
            <div class="product-header">
                <div>
                    <div class="product-name"><?php echo sanitize($product['product_name']); ?></div>
                    <span class="product-category"><?php echo sanitize($product['category_name']); ?></span>
                </div>
                <div class="product-id">
                    Product ID<br>
                    <strong>#<?php echo $product['product_id']; ?></strong>
                </div>
            </div>

            <?php
            // Use government price as the standard price (selling price = govt price)
            $standardPrice = $product['govt_price'] ?? $product['price_per_unit'];
            ?>
            <div class="product-details">
                <div class="detail-box">
                    <div class="detail-label">Selling Price</div>
                    <div class="detail-value price"><?php echo formatCurrency($standardPrice); ?></div>
                    <div class="detail-sub">per <?php echo sanitize($product['unit']); ?></div>
                </div>
                <div class="detail-box">
                    <div class="detail-label">Available Stock</div>
                    <div class="detail-value"><?php echo number_format($product['stock_quantity']); ?></div>
                    <div class="detail-sub"><?php echo sanitize($product['unit']); ?></div>
                </div>
                <div class="detail-box">
                    <div class="detail-label">Government Price</div>
                    <div class="detail-value price"><?php echo formatCurrency($standardPrice); ?></div>
                    <div class="detail-sub">regulated rate</div>
                </div>
            </div>
        </div>

        <?php if ($product['description']): ?>
        <div class="description-section">
            <h4>Product Description</h4>
            <p><?php echo nl2br(sanitize($product['description'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Terms -->
        <div class="terms">
            <h4>Seller Terms & Conditions</h4>
            <ul>
                <li>Maintain accurate stock levels to avoid order cancellations</li>
                <li>Ensure product quality matches the description provided</li>
                <li>Respond to customer inquiries within 24 hours</li>
                <li>Pack products securely for safe delivery</li>
                <li>AgriSphere charges 0% platform fee on all sales</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Congratulations on your product listing!</strong></p>
            <p>AgriSphere - From Farm to Table, Simplified</p>
            <p style="margin-top: 16px;">This is a computer-generated invoice. No signature required.</p>
            <p style="margin-top: 8px;">Product ID: #<?php echo $productId; ?> | Generated on: <?php echo date('d M Y, h:i A'); ?></p>
        </div>
    </div>
</body>
</html>
