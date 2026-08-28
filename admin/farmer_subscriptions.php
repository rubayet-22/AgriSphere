<?php
/**
 * Admin - Farmer Subscriptions
 *
 * Approve or reject the BDT 1000 subscription payments farmers submit.
 * Approving is what actually activates the farmer: it sets expires_at and
 * unlocks the farmer pages.
 */

$pageTitle = 'Farmer Subscriptions';
$currentPage = 'farmer_subscriptions';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/farmer_subscription.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Filter: Pending (default), Approved, Rejected, or all
$filter = sanitize($_GET['status'] ?? 'Pending');
if (!in_array($filter, ['Pending', 'Approved', 'Rejected', 'all'], true)) {
    $filter = 'Pending';
}

$requests = [];
$pendingCount = 0;
$tableMissing = false;

try {
    $sql = "SELECT fs.*, CONCAT(u.First_name, ' ', u.Last_name) AS farmer_name, u.Username, u.Email,
                   (fs.status = 'Approved' AND fs.expires_at > NOW()) AS is_active
              FROM farmer_subscription fs
              JOIN user u ON u.User_id = fs.farmer_id";
    if ($filter !== 'all') {
        $sql .= " WHERE fs.status = ?";
    }
    $sql .= " ORDER BY fs.subscription_id DESC LIMIT 200";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($filter !== 'all') {
            $stmt->bind_param("s", $filter);
        }
        $stmt->execute();
        $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $result = $conn->query("SELECT COUNT(*) AS c FROM farmer_subscription WHERE status = 'Pending'");
    if ($result && $row = $result->fetch_assoc()) {
        $pendingCount = (int)$row['c'];
    }
} catch (Throwable $e) {
    $tableMissing = true;
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Farmer Subscriptions</h1>
    <p class="page-subtitle">
        <?php echo $pendingCount; ?> payment(s) waiting for review &middot;
        <?php echo formatCurrency(FARMER_SUBSCRIPTION_FEE); ?> per
        <?php echo FARMER_SUBSCRIPTION_MONTHS; ?> months
    </p>
</div>

<?php if ($tableMissing): ?>
    <div class="alert alert-error">
        <i class="fas fa-database"></i>
        Run <code>database/farmer_subscription.sql</code> and
        <code>database/farmer_subscription_approval.sql</code> first.
    </div>
<?php endif; ?>

<!-- Filter tabs -->
<div class="card mb-4">
    <div class="card-body" style="display: flex; gap: 12px;">
        <?php foreach (['Pending', 'Approved', 'Rejected', 'all'] as $tab): ?>
            <a href="farmer_subscriptions.php?status=<?php echo $tab; ?>"
               class="btn btn-sm <?php echo $filter === $tab ? 'btn-primary' : 'btn-outline'; ?>">
                <?php echo $tab === 'all' ? 'All' : $tab; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Subscription Payments</h3>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farmer</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Valid Until</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-muted" style="text-align: center; padding: 24px;">
                            No subscription payments found.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?php echo (int)$req['subscription_id']; ?></td>
                        <td>
                            <strong><?php echo sanitize($req['farmer_name']); ?></strong>
                            <div class="text-muted text-sm">
                                <?php echo sanitize($req['Username']); ?> &middot;
                                <?php echo sanitize($req['Email']); ?>
                            </div>
                        </td>
                        <td style="white-space: nowrap;"><?php echo formatCurrency((float)$req['amount']); ?></td>
                        <td>
                            <?php echo sanitize($req['payment_method']); ?>
                            <div class="text-muted text-sm">
                                <?php echo sanitize($req['payment_account'] ?? ''); ?><br>
                                <?php echo sanitize($req['payment_reference'] ?? ''); ?>
                            </div>
                        </td>
                        <td><?php echo formatDateTime($req['paid_at']); ?></td>
                        <td>
                            <?php
                            $status = $req['status'];
                            $badge = $status === 'Approved' ? 'badge-approved'
                                   : ($status === 'Rejected' ? 'badge-rejected' : 'badge-pending');
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span>
                            <?php if ($status === 'Approved' && (int)$req['is_active'] !== 1): ?>
                                <div class="text-muted text-sm">expired</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDateTime($req['expires_at']); ?></td>
                        <td>
                            <?php if ($status === 'Pending'): ?>
                                <div style="display: flex; gap: 8px;">
                                    <form method="POST" action="farmer_subscription_action.php" style="display: inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="subscription_id" value="<?php echo (int)$req['subscription_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="farmer_subscription_action.php" style="display: inline;"
                                          onsubmit="return confirm('Reject this subscription payment?');">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="subscription_id" value="<?php echo (int)$req['subscription_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-muted text-sm">
                                    Reviewed <?php echo formatDateTime($req['reviewed_at']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
