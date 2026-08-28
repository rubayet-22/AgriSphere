<?php
/**
 * Customer - Checkout
 */

$pageTitle = 'Checkout';
$currentPage = 'cart';

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

// Get cart items (C++ backend: /api/orders/checkout-items)
$checkoutResponse = AgriSphereBackend::tryCall('/api/orders/checkout-items', [
    'customer_id' => (int)$customerId,
]);
if (empty($checkoutResponse['success'])) {
    setFlashMessage('error', $checkoutResponse['error'] ?? 'Database error. Please try again.');
    redirect(BASE_URL . 'customer/cart.php');
}
$cartItems = $checkoutResponse['items'] ?? [];

if (empty($cartItems)) {
    setFlashMessage('error', 'Your cart is empty');
    redirect(BASE_URL . 'customer/cart.php');
}

// Total is calculated alongside the listing
$subtotal = $checkoutResponse['subtotal'] ?? 0;

// Strategy pattern (C++ OrderService -> strategy::DiscountContext): exactly one
// customer discount rule is chosen - Regular 0%, Premium 5%, Bulk 10%. These
// values are only for display; /api/orders/place recalculates them itself and
// is the source of truth.
$customerDiscount = (float)($checkoutResponse['customer_discount'] ?? 0);
$discountStrategy = $checkoutResponse['discount_strategy'] ?? 'Regular';
$discountPercent  = (float)($checkoutResponse['discount_percent'] ?? 0);
$payable          = (float)($checkoutResponse['payable'] ?? $subtotal);

// Get customer info
$stmt = $conn->prepare("SELECT u.*, c.address, c.phone as customer_phone FROM user u LEFT JOIN customer c ON u.User_id = c.customer_id WHERE u.User_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
} else {
    $customer = ['First_name' => '', 'Last_name' => '', 'Phone' => '', 'address' => ''];
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $deliveryAddress = sanitize($_POST['delivery_address']);
    $deliveryPhone = sanitize($_POST['delivery_phone']);
    $paymentMethod = sanitize($_POST['payment_method']);
    $bkashNumber = isset($_POST['bkash_number']) ? sanitize($_POST['bkash_number']) : '';
    $cardNumber = isset($_POST['card_number']) ? sanitize($_POST['card_number']) : '';
    $cardExpiry = isset($_POST['card_expiry']) ? sanitize($_POST['card_expiry']) : '';
    $cardCvc    = isset($_POST['card_cvc']) ? sanitize($_POST['card_cvc']) : '';

    // Placing the order is one transaction across customer_order, order_items,
    // farm_product, farm_inventory, payment, customer and customer_cart. It is
    // executed by the C++ OrderService on a single connection leased from the
    // Singleton DatabaseManager (/api/orders/place). Payment validation, fee
    // and reference generation are handled there via the payment Factory Method.
    try {
        $orderResult = AgriSphereBackend::call('/api/orders/place', [
            'customer_id'      => (int)$customerId,
            'delivery_address' => $deliveryAddress,
            'delivery_phone'   => $deliveryPhone,
            'payment_method'   => $paymentMethod,
            'bkash_number'     => $bkashNumber,
            'card_number'      => $cardNumber,
            'card_expiry'      => $cardExpiry,
            'card_cvc'         => $cardCvc,
        ]);

        if (!empty($orderResult['success'])) {
            setFlashMessage('success', $orderResult['message']);
            redirect(BASE_URL . 'customer/order_detail.php?id=' . $orderResult['order_id']);
        }

        setFlashMessage('error', $orderResult['error'] ?? 'Checkout failed');
    } catch (BackendUnavailableException $e) {
        AgriSphereBackend::fail($e);
    }
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Checkout</h1>
    <p class="page-subtitle">Complete your order</p>
</div>

<form method="POST" action="">
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
        <!-- Left Column - Delivery & Payment -->
        <div>
            <!-- Delivery Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-truck"></i> Delivery Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="delivery_name">Full Name</label>
                        <input type="text" id="delivery_name" class="form-control"
                               value="<?php echo sanitize($customer['First_name'] . ' ' . $customer['Last_name']); ?>" disabled style="background: var(--gray-100);">
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="delivery_phone">Phone Number <span style="color: var(--error);">*</span></label>
                        <input type="tel" id="delivery_phone" name="delivery_phone" class="form-control"
                               value="<?php echo sanitize($customer['customer_phone'] ?? $customer['Phone'] ?? ''); ?>" required
                               placeholder="+880 1XXXXXXXXX">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="delivery_address">Delivery Address <span style="color: var(--error);">*</span></label>
                        <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" required
                                  placeholder="Enter your full delivery address"><?php echo sanitize($customer['address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-credit-card"></i> Payment Method</h3>
                </div>
                <div class="card-body">
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bkash" checked onchange="updatePaymentUI()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #E2136E;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <strong>bKash</strong>
                                    <div class="text-muted text-sm">Pay with your bKash account</div>
                                </div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="nagad" onchange="updatePaymentUI()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #F6921E;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <strong>Nagad</strong>
                                    <div class="text-muted text-sm">Pay with your Nagad account</div>
                                </div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="card" onchange="updatePaymentUI()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #1A1F71;">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div>
                                    <strong>Credit/Debit Card</strong>
                                    <div class="text-muted text-sm">Pay with your Visa/Mastercard</div>
                                </div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" onchange="updatePaymentUI()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: var(--success);">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <strong>Cash on Delivery</strong>
                                    <div class="text-muted text-sm">Pay when you receive your order</div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div id="bkashField" class="form-group" style="margin-top: 16px;">
                        <label class="form-label" for="bkash_number">Mobile Number</label>
                        <input type="tel" id="bkash_number" name="bkash_number" class="form-control"
                               placeholder="01XXXXXXXXX" maxlength="11">
                        <small class="text-muted">Enter the number linked to your payment account</small>
                    </div>

                    <div id="cardField" class="form-group" style="margin-top: 16px; display: none;">
                        <label class="form-label" for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" class="form-control"
                               placeholder="1234 5678 9012 3456" maxlength="19">
                        <div style="display: flex; gap: 12px; margin-top: 12px;">
                            <div style="flex: 1;">
                                <label class="form-label" for="card_expiry">Expiry (MM/YY)</label>
                                <input type="text" id="card_expiry" name="card_expiry" class="form-control"
                                       placeholder="MM/YY" maxlength="5">
                            </div>
                            <div style="flex: 1;">
                                <label class="form-label" for="card_cvc">CVC</label>
                                <input type="text" id="card_cvc" name="card_cvc" class="form-control"
                                       placeholder="123" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning" style="margin-top: 16px;">
                        <i class="fas fa-info-circle"></i> This is a simulated payment for demonstration purposes.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div>
            <div class="card" style="position: sticky; top: 100px;">
                <div class="card-header">
                    <h3 class="card-title">Order Summary</h3>
                </div>
                <div class="card-body">
                    <!-- Cart Items -->
                    <div class="checkout-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-item">
                                <div style="display: flex; gap: 12px;">
                                    <?php if ($item['product_image']): ?>
                                        <img src="<?php echo getProductImageUrl($item['product_image']); ?>" alt=""
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm);">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--gray-100); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                    // effective_price already includes any active discount
                                    $unitPrice = (float)($item['effective_price'] ?? $item['price_per_unit']);
                                    ?>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500;"><?php echo sanitize($item['product_name']); ?></div>
                                        <div class="text-muted text-sm">
                                            <?php echo $item['quantity']; ?> × <?php echo formatCurrency($unitPrice); ?>
                                            <?php if ((int)($item['has_discount'] ?? 0) === 1): ?>
                                                <span style="color: #B91C1C; font-weight: 600;">
                                                    (<?php echo rtrim(rtrim(number_format((float)$item['discount_percent'], 2), '0'), '.'); ?>% off)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo formatCurrency($unitPrice * $item['quantity']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--gray-200);">

                    <!-- Totals -->
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    <?php if ($customerDiscount > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--success);">
                            <span>
                                <?php echo sanitize($discountStrategy); ?> discount
                                (<?php echo rtrim(rtrim(number_format($discountPercent, 2), '0'), '.'); ?>%)
                            </span>
                            <span>- <?php echo formatCurrency($customerDiscount); ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--success);">
                        <span>Delivery</span>
                        <span>FREE</span>
                    </div>
                    <div id="paymentFeeRow" style="display: none; justify-content: space-between; margin-bottom: 8px; color: var(--gray-500);">
                        <span>Payment Fee</span>
                        <span id="paymentFeeAmount"></span>
                    </div>

                    <hr style="margin: 16px 0; border: none; border-top: 2px solid var(--gray-200);">

                    <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700;">
                        <span>Total</span>
                        <span id="orderTotalAmount" style="color: var(--primary);"><?php echo formatCurrency($payable); ?></span>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary" style="width: 100%; margin-top: 24px; padding: 14px;">
                        <i class="fas fa-check"></i> Place Order
                    </button>

                    <a href="cart.php" class="btn btn-outline" style="width: 100%; margin-top: 12px;">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.payment-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.payment-option {
    cursor: pointer;
}

.payment-option input {
    display: none;
}

.payment-option-content {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius);
    transition: all var(--transition-fast);
}

.payment-option input:checked + .payment-option-content {
    border-color: var(--primary);
    background: var(--primary-50);
}

.payment-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.checkout-items {
    max-height: 300px;
    overflow-y: auto;
}

.checkout-item {
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}

.checkout-item:last-child {
    border-bottom: none;
}
</style>

<script>
const CURRENCY_SYMBOL = '৳';
// Mirrors the fee model in backend/src/payment/ (WalletPaymentMethod::calculateFee,
// CardPayment::calculateFee) for a live preview only - the C++ backend recomputes
// the fee and total itself and is the source of truth.
const FEE_RATES = { bkash: 0.015, nagad: 0.015, card: 0.02, cod: 0 };
// The fee is charged on what the customer actually pays, i.e. after the
// customer Strategy discount - same order as OrderService::placeOrder().
const orderSubtotal = <?php echo json_encode((float)$payable); ?>;

function formatMoney(amount) {
    return CURRENCY_SYMBOL + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updatePaymentUI() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const bkashField = document.getElementById('bkashField');
    const bkashInput = document.getElementById('bkash_number');
    const cardField = document.getElementById('cardField');
    const cardNumberInput = document.getElementById('card_number');
    const cardExpiryInput = document.getElementById('card_expiry');
    const cardCvcInput = document.getElementById('card_cvc');

    const isWallet = (paymentMethod === 'bkash' || paymentMethod === 'nagad');
    const isCard = (paymentMethod === 'card');

    bkashField.style.display = isWallet ? 'block' : 'none';
    bkashInput.required = isWallet;

    cardField.style.display = isCard ? 'block' : 'none';
    cardNumberInput.required = isCard;
    cardExpiryInput.required = isCard;
    cardCvcInput.required = isCard;

    const rate = FEE_RATES[paymentMethod] || 0;
    const fee = orderSubtotal * rate;
    const feeRow = document.getElementById('paymentFeeRow');
    if (fee > 0) {
        feeRow.style.display = 'flex';
        document.getElementById('paymentFeeAmount').textContent = formatMoney(fee);
    } else {
        feeRow.style.display = 'none';
    }
    document.getElementById('orderTotalAmount').textContent = formatMoney(orderSubtotal + fee);
}

// Client-side mirror of WalletPaymentMethod::validate() / CardPayment::validate() -
// the C++ backend is still the source of truth; this only gives the customer
// instant feedback before the request is sent.
document.querySelector('form').addEventListener('submit', function (event) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    if (paymentMethod === 'bkash' || paymentMethod === 'nagad') {
        const number = document.getElementById('bkash_number').value.trim();
        if (!/^01[0-9]{9}$/.test(number)) {
            event.preventDefault();
            alert('Please enter a valid 11-digit mobile number starting with 01.');
        }
    } else if (paymentMethod === 'card') {
        const number = document.getElementById('card_number').value.replace(/\s+/g, '');
        const expiry = document.getElementById('card_expiry').value.trim();
        const cvc = document.getElementById('card_cvc').value.trim();
        if (!/^[0-9]{13,19}$/.test(number)) {
            event.preventDefault();
            alert('Please enter a valid card number.');
        } else if (!/^(0[1-9]|1[0-2])\/[0-9]{2}$/.test(expiry)) {
            event.preventDefault();
            alert('Please enter expiry as MM/YY.');
        } else if (!/^[0-9]{3,4}$/.test(cvc)) {
            event.preventDefault();
            alert('Please enter a valid security code.');
        }
    }
});

// Initialize on page load
updatePaymentUI();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
