<?php
/**
 * Customer - Premium Membership
 *
 * Shows the customer's membership state and lets them apply. Submitting the
 * form does NOT make anyone Premium: the C++ MembershipService stores the
 * application as 'Pending' and an admin has to approve it
 * (admin/premium.php) before the checkout starts using
 * PremiumDiscountStrategy.
 */

$pageTitle = 'Premium Membership';
$currentPage = 'premium';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('error', 'Please login as a customer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

$customerId = getCurrentUserId();

// C++ backend: /api/membership/status
$status = AgriSphereBackend::tryCall('/api/membership/status', [
    'customer_id' => (int)$customerId,
]);

$isPremium       = !empty($status['is_premium']);
$monthlyFee      = (float)($status['monthly_fee'] ?? 300);
$premiumPercent  = (float)($status['premium_discount_percent'] ?? 5);
$bulkThreshold   = (int)($status['bulk_threshold'] ?? 10);
$hasApplication  = !empty($status['has_application']);
$applicationStatus = $status['application_status'] ?? '';
$isPending       = ($applicationStatus === 'Pending');

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Premium Membership</h1>
    <p class="page-subtitle"><?php echo formatCurrency($monthlyFee); ?> per month</p>
</div>

<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
    <div>
        <?php if ($isPremium): ?>
            <!-- Active membership -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-crown" style="color: #D97706;"></i> You are a Premium member</h3>
                </div>
                <div class="card-body">
                    <p>Your membership is active until
                        <strong><?php echo formatDateTime($status['expires_at'] ?? ''); ?></strong>.</p>
                    <p style="margin-top: 12px;">Every order you place now gets a
                        <strong><?php echo rtrim(rtrim(number_format($premiumPercent, 2), '0'), '.'); ?>%
                        Premium discount</strong> at checkout.</p>
                    <div class="alert alert-warning" style="margin-top: 16px;">
                        <i class="fas fa-info-circle"></i>
                        Place a bulk order (<?php echo $bulkThreshold; ?> items or more) as a
                        Premium member and you get <strong>15%</strong> &mdash; the best rate
                        AgriSphere offers.
                    </div>
                </div>
            </div>

        <?php elseif ($isPending): ?>
            <!-- Waiting for the admin -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hourglass-half"></i> Request pending</h3>
                </div>
                <div class="card-body">
                    <p>Your <?php echo formatCurrency((float)($status['amount'] ?? $monthlyFee)); ?>
                       membership request was submitted on
                       <strong><?php echo formatDateTime($status['applied_at'] ?? ''); ?></strong>
                       and is waiting for admin approval.</p>
                    <?php if (!empty($status['payment_reference'])): ?>
                        <p style="margin-top: 8px;" class="text-muted">
                            Payment reference: <?php echo sanitize($status['payment_reference']); ?>
                        </p>
                    <?php endif; ?>
                    <div class="alert alert-warning" style="margin-top: 16px;">
                        <i class="fas fa-info-circle"></i>
                        You are still a <strong>Regular</strong> customer. The 5% Premium discount
                        starts only after an admin approves this request.
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Application form -->
            <?php if ($applicationStatus === 'Rejected'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-times-circle"></i>
                    Your previous request was rejected on
                    <?php echo formatDateTime($status['reviewed_at'] ?? ''); ?>.
                    You are a Regular customer. You may apply again below.
                </div>
            <?php elseif ($applicationStatus === 'Approved'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    Your previous membership expired on
                    <?php echo formatDateTime($status['expires_at'] ?? ''); ?>.
                    Renew it below.
                </div>
            <?php endif; ?>

            <form method="POST" action="premium_action.php">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-credit-card"></i> Pay the membership fee</h3>
                    </div>
                    <div class="card-body">
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bkash" checked onchange="updateMembershipPaymentUI()">
                                <div class="payment-option-content">
                                    <div class="payment-icon" style="background: #E2136E;">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div><strong>bKash</strong></div>
                                </div>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="nagad" onchange="updateMembershipPaymentUI()">
                                <div class="payment-option-content">
                                    <div class="payment-icon" style="background: #F6921E;">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div><strong>Nagad</strong></div>
                                </div>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="card" onchange="updateMembershipPaymentUI()">
                                <div class="payment-option-content">
                                    <div class="payment-icon" style="background: #1A1F71;">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div><strong>Credit/Debit Card</strong></div>
                                </div>
                            </label>
                        </div>

                        <div id="membershipWalletField" class="form-group" style="margin-top: 16px;">
                            <label class="form-label" for="bkash_number">Mobile Number</label>
                            <input type="tel" id="bkash_number" name="bkash_number" class="form-control"
                                   placeholder="01XXXXXXXXX" maxlength="11" required>
                        </div>

                        <div id="membershipCardField" class="form-group" style="margin-top: 16px; display: none;">
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
                            <i class="fas fa-info-circle"></i> This is a simulated payment for
                            demonstration purposes. The fee is fixed at
                            <?php echo formatCurrency($monthlyFee); ?> per month and cannot be changed.
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                            <i class="fas fa-crown"></i> Submit Premium Request
                            (<?php echo formatCurrency($monthlyFee); ?>/month)
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- What you get -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Customer discount rules</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span>Regular customer</span><strong>0%</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span>Premium member</span>
                    <strong style="color: var(--primary);">
                        <?php echo rtrim(rtrim(number_format($premiumPercent, 2), '0'), '.'); ?>%
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span>Bulk order (<?php echo $bulkThreshold; ?>+ items)</span>
                    <strong style="color: var(--primary);">10%</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span>Premium <em>and</em> bulk order</span>
                    <strong style="color: var(--primary);">15%</strong>
                </div>
                <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--gray-200);">
                <p class="text-muted text-sm">
                    One rate is applied per order. A Premium member placing a bulk order gets
                    the best rate of 15%.
                </p>
                <p class="text-muted text-sm" style="margin-top: 8px;">
                    Farmer product discounts are separate and are already included in the
                    product price you see.
                </p>
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
function updateMembershipPaymentUI() {
    var method = document.querySelector('input[name="payment_method"]:checked').value;
    var isCard = (method === 'card');

    document.getElementById('membershipWalletField').style.display = isCard ? 'none' : 'block';
    document.getElementById('bkash_number').required = !isCard;

    document.getElementById('membershipCardField').style.display = isCard ? 'block' : 'none';
    document.getElementById('card_number').required = isCard;
    document.getElementById('card_expiry').required = isCard;
    document.getElementById('card_cvc').required = isCard;
}

if (document.querySelector('input[name="payment_method"]')) {
    updateMembershipPaymentUI();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
