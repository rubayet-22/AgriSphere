<?php
/**
 * Farmer - Subscription (BDT 1000 / year)
 *
 * Shows the farmer's subscription state and takes the payment. This is the
 * only farmer page that is NOT behind the subscription gate, otherwise an
 * unpaid farmer could never reach it.
 */

$pageTitle = 'Subscription';
$currentPage = 'subscription';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/farmer_subscription.php';

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
$state = farmerSubscriptionStatus($conn, $farmerId);

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Farmer Subscription</h1>
    <p class="page-subtitle">
        <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?> for
        <?php echo FARMER_SUBSCRIPTION_MONTHS; ?> months of selling on AgriSphere
    </p>
</div>

<?php if (!empty($state['missing'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-database"></i>
        The <code>farmer_subscription</code> table does not exist yet. Run
        <code>database/farmer_subscription.sql</code> first.
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
    <div>
        <!-- Current status -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-id-card"></i> Your Subscription</h3>
            </div>
            <div class="card-body">
                <?php if ($state['active']): ?>
                    <p>
                        <span class="badge badge-approved" style="font-size: 1rem; padding: 6px 14px;">Active</span>
                    </p>
                    <p style="margin-top: 12px;">
                        Valid until <strong><?php echo formatDateTime($state['expires_at']); ?></strong>.
                        You can sell products, manage inventory and set discounts as normal.
                    </p>
                    <p class="text-muted text-sm" style="margin-top: 8px;">
                        Renewing early does not waste the time you already paid for &mdash; once the admin
                        approves it, another <?php echo FARMER_SUBSCRIPTION_MONTHS; ?> months is added on
                        top of the date above.
                    </p>
                <?php elseif ($state['expires_at'] !== ''): ?>
                    <p>
                        <span class="badge badge-rejected" style="font-size: 1rem; padding: 6px 14px;">Expired</span>
                    </p>
                    <p style="margin-top: 12px;">
                        Your subscription expired on <strong><?php echo formatDateTime($state['expires_at']); ?></strong>.
                        Renew below to start selling again.
                    </p>
                <?php elseif ($state['rejected'] && !$state['pending']): ?>
                    <p>
                        <span class="badge badge-rejected" style="font-size: 1rem; padding: 6px 14px;">Rejected</span>
                    </p>
                    <p style="margin-top: 12px;">
                        Your last payment was not approved by the admin. You can submit a new payment below.
                    </p>
                <?php else: ?>
                    <p>
                        <span class="badge badge-pending" style="font-size: 1rem; padding: 6px 14px;">Not subscribed</span>
                    </p>
                    <p style="margin-top: 12px;">
                        Pay the <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?> fee to register as a
                        selling farmer on AgriSphere. An admin reviews the payment before your account is activated.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($state['pending']): ?>
            <!-- A payment is already waiting for the admin -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hourglass-half"></i> Waiting for Admin Approval</h3>
                </div>
                <div class="card-body">
                    <p>
                        Your <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?> payment was submitted on
                        <strong><?php echo formatDateTime($state['pending_at']); ?></strong> and is waiting for an
                        administrator to review it.
                    </p>
                    <div class="alert alert-warning" style="margin-top: 16px;">
                        <i class="fas fa-info-circle"></i>
                        <?php if ($state['active']): ?>
                            Your current subscription keeps working in the meantime. The extra
                            <?php echo FARMER_SUBSCRIPTION_MONTHS; ?> months are added once the admin approves.
                        <?php else: ?>
                            You cannot sell yet. Selling unlocks as soon as the admin approves this payment.
                        <?php endif; ?>
                    </div>
                    <p class="text-muted text-sm">
                        Only one payment can be pending at a time, so there is nothing more to do right now.
                    </p>
                </div>
            </div>
        <?php else: ?>
        <!-- Payment -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-credit-card"></i>
                    <?php echo $state['active'] ? 'Renew Early' : ($state['expires_at'] !== '' ? 'Renew Subscription' : 'Pay Subscription Fee'); ?>
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="subscription_action.php">
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bkash" checked onchange="updateSubscriptionPaymentUI()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #E2136E;"><i class="fas fa-mobile-alt"></i></div>
                                <div><strong>bKash</strong></div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="nagad" onchange="updateSubscriptionPaymentUI()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #F6921E;"><i class="fas fa-mobile-alt"></i></div>
                                <div><strong>Nagad</strong></div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="card" onchange="updateSubscriptionPaymentUI()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #1A1F71;"><i class="fas fa-credit-card"></i></div>
                                <div><strong>Credit/Debit Card</strong></div>
                            </div>
                        </label>
                    </div>

                    <div id="subWalletField" class="form-group" style="margin-top: 16px;">
                        <label class="form-label" for="wallet_number">Mobile Number</label>
                        <input type="tel" id="wallet_number" name="wallet_number" class="form-control"
                               placeholder="01XXXXXXXXX" maxlength="11" required>
                    </div>

                    <div id="subCardField" class="form-group" style="margin-top: 16px; display: none;">
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
                        <i class="fas fa-info-circle"></i>
                        This is a simulated payment for demonstration purposes. The fee is fixed at
                        <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?> and cannot be changed.
                        <strong>An admin must approve the payment before your subscription starts.</strong>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                        <i class="fas fa-paper-plane"></i>
                        Submit <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?> Payment
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- What it covers -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">What the fee covers</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span>Fee</span>
                    <strong><?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span>Valid for</span>
                    <strong><?php echo FARMER_SUBSCRIPTION_MONTHS; ?> months</strong>
                </div>
                <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--gray-200);">
                <p class="text-muted text-sm">A subscribed farmer can:</p>
                <ul class="text-muted text-sm" style="margin: 8px 0 0 18px;">
                    <li>List products for sale</li>
                    <li>Manage inventory, prices and photos</li>
                    <li>Offer product discounts to members</li>
                    <li>See sales history and invoices</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.payment-options { display: flex; flex-direction: column; gap: 12px; }
.payment-option { cursor: pointer; }
.payment-option input { display: none; }
.payment-option-content {
    display: flex; align-items: center; gap: 16px; padding: 16px;
    border: 2px solid var(--gray-200); border-radius: var(--radius);
    transition: all var(--transition-fast);
}
.payment-option input:checked + .payment-option-content {
    border-color: var(--primary); background: var(--primary-50);
}
.payment-icon {
    width: 48px; height: 48px; border-radius: var(--radius); display: flex;
    align-items: center; justify-content: center; color: white; font-size: 1.25rem;
}
</style>

<script>
function updateSubscriptionPaymentUI() {
    var method = document.querySelector('input[name="payment_method"]:checked').value;
    var isCard = (method === 'card');

    document.getElementById('subWalletField').style.display = isCard ? 'none' : 'block';
    document.getElementById('wallet_number').required = !isCard;

    document.getElementById('subCardField').style.display = isCard ? 'block' : 'none';
    document.getElementById('card_number').required = isCard;
    document.getElementById('card_expiry').required = isCard;
    document.getElementById('card_cvc').required = isCard;
}
updateSubscriptionPaymentUI();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
