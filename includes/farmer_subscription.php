<?php
/**
 * AgriSphere - Farmer subscription helpers
 *
 * A farmer pays BDT 1000 for one year before they can use the farmer pages.
 * Everything about that fee lives in this one file plus
 * farmer/subscription.php - no existing design pattern code is involved.
 *
 * The payment is simulated, exactly like the rest of this academic project:
 * the account details are validated and a reference code is generated, but
 * no money moves and no payment gateway is contacted.
 */

if (!defined('AGRISPHERE')) {
    define('AGRISPHERE', true);
}

// The fee and the period. Change these two lines to change the plan.
define('FARMER_SUBSCRIPTION_FEE', 1000.00);
define('FARMER_SUBSCRIPTION_MONTHS', 12);

/**
 * Current subscription state for one farmer.
 *
 * Returns:
 *   active      bool    an APPROVED subscription that has not expired
 *   pending     bool    a payment is waiting for admin approval
 *   rejected    bool    the most recent request was rejected
 *   ever_paid   bool    the farmer has paid at least once before
 *   expires_at  string  expiry of the newest approved subscription ('' when none)
 *   pending_at  string  when the pending payment was made ('' when none)
 *   missing     bool    the table does not exist yet (migration not run)
 *
 * A pending payment does NOT grant access - only an admin approval does.
 */
function farmerSubscriptionStatus($conn, $farmerId)
{
    $state = [
        'active'     => false,
        'pending'    => false,
        'rejected'   => false,
        'ever_paid'  => false,
        'expires_at' => '',
        'pending_at' => '',
        'missing'    => false,
    ];

    if (!$farmerId) {
        return $state;
    }

    try {
        $stmt = $conn->prepare(
            "SELECT
                MAX(CASE WHEN status = 'Approved' THEN expires_at END) AS approved_expiry,
                MAX(CASE WHEN status = 'Pending'  THEN paid_at     END) AS pending_at,
                SUM(status = 'Pending')  AS pending_count,
                SUM(status = 'Rejected') AS rejected_count,
                COUNT(*)                 AS total
               FROM farmer_subscription
              WHERE farmer_id = ?"
        );
        if (!$stmt) {
            $state['missing'] = true;
            return $state;
        }
        $stmt->bind_param("i", $farmerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && (int)$row['total'] > 0) {
            $state['ever_paid']  = true;
            $state['expires_at'] = $row['approved_expiry'] ?? '';
            $state['pending_at'] = $row['pending_at'] ?? '';
            $state['pending']    = ((int)$row['pending_count'] > 0);
            $state['rejected']   = ((int)$row['rejected_count'] > 0);
            $state['active']     = ($state['expires_at'] !== ''
                                    && strtotime($state['expires_at']) > time());
        }
    } catch (Throwable $e) {
        // farmer_subscription does not exist yet - run
        // database/farmer_subscription.sql
        $state['missing'] = true;
    }

    return $state;
}

/**
 * True when the farmer may use the farmer pages.
 *
 * While the table is missing the gate stays open, so an un-migrated
 * install is not locked out of its own site.
 */
function farmerHasActiveSubscription($conn, $farmerId)
{
    $state = farmerSubscriptionStatus($conn, $farmerId);
    return $state['active'] || $state['missing'];
}

/**
 * Records one subscription payment as a PENDING request.
 *
 * The farmer does not gain access here. expires_at stays NULL until an
 * administrator approves it in admin/farmer_subscriptions.php.
 *
 * Returns the new subscription_id, or false on failure.
 */
function recordFarmerSubscriptionPayment($conn, $farmerId, $method, $account, $reference)
{
    $amount = FARMER_SUBSCRIPTION_FEE;

    $stmt = $conn->prepare(
        "INSERT INTO farmer_subscription
            (farmer_id, amount, payment_method, payment_account, payment_reference, status, paid_at)
         VALUES (?, ?, ?, ?, ?, 'Pending', NOW())"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("idsss", $farmerId, $amount, $method, $account, $reference);

    return $stmt->execute() ? $conn->insert_id : false;
}

/**
 * Admin approves one pending request: the twelve months start now.
 *
 * Renewing early does not waste time already paid for - if the farmer still
 * has an approved subscription running, the new period is added on top of
 * that expiry instead of restarting from today.
 *
 * Returns the new expiry date, or false if the row was not pending.
 */
function approveFarmerSubscription($conn, $subscriptionId, $adminId)
{
    $stmt = $conn->prepare(
        "SELECT farmer_id FROM farmer_subscription WHERE subscription_id = ? AND status = 'Pending'"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $subscriptionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return false;
    }

    $state = farmerSubscriptionStatus($conn, $row['farmer_id']);
    $startFrom = ($state['active'] && $state['expires_at'] !== '')
        ? strtotime($state['expires_at'])
        : time();

    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . FARMER_SUBSCRIPTION_MONTHS . ' months', $startFrom));

    $stmt = $conn->prepare(
        "UPDATE farmer_subscription
            SET status = 'Approved', reviewed_at = NOW(), reviewed_by = ?, expires_at = ?
          WHERE subscription_id = ? AND status = 'Pending'"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("isi", $adminId, $expiresAt, $subscriptionId);
    $stmt->execute();

    return $stmt->affected_rows > 0 ? $expiresAt : false;
}

/**
 * Admin rejects one pending request. expires_at stays NULL, so the farmer
 * gains nothing and may submit a new payment.
 */
function rejectFarmerSubscription($conn, $subscriptionId, $adminId)
{
    $stmt = $conn->prepare(
        "UPDATE farmer_subscription
            SET status = 'Rejected', reviewed_at = NOW(), reviewed_by = ?
          WHERE subscription_id = ? AND status = 'Pending'"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ii", $adminId, $subscriptionId);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

/**
 * Validates the simulated payment details and returns
 * ['ok' => bool, 'error' => string, 'account' => string, 'reference' => string].
 *
 * The rules mirror the ones the checkout already uses: an 11 digit mobile
 * number starting 01 for the wallets, and a 13-19 digit card number with
 * MM/YY expiry and a 3-4 digit security code.
 */
function validateFarmerSubscriptionPayment($method, $post)
{
    $fail = function ($message) {
        return ['ok' => false, 'error' => $message, 'account' => '', 'reference' => ''];
    };

    if ($method === 'bkash' || $method === 'nagad') {
        $number = trim($post['wallet_number'] ?? '');
        if (!preg_match('/^01[0-9]{9}$/', $number)) {
            return $fail('Enter a valid 11-digit number starting with 01.');
        }
        $prefix = ($method === 'bkash') ? 'BKS' : 'NGD';
        return [
            'ok'        => true,
            'error'     => '',
            'account'   => $number,
            'reference' => $prefix . '-' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ];
    }

    if ($method === 'card') {
        $number = preg_replace('/\s+/', '', $post['card_number'] ?? '');
        $expiry = trim($post['card_expiry'] ?? '');
        $cvc    = trim($post['card_cvc'] ?? '');

        if (!preg_match('/^[0-9]{13,19}$/', $number)) {
            return $fail('Enter a valid card number.');
        }
        if (!preg_match('#^(0[1-9]|1[0-2])/[0-9]{2}$#', $expiry)) {
            return $fail('Enter the card expiry as MM/YY.');
        }
        if (!preg_match('/^[0-9]{3,4}$/', $cvc)) {
            return $fail('Enter a valid security code.');
        }

        return [
            'ok'        => true,
            'error'     => '',
            'account'   => '**** **** **** ' . substr($number, -4),
            'reference' => 'AUTH-' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ];
    }

    return $fail('Please choose a payment method.');
}
