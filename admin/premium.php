<?php
/**
 * Admin - Premium Membership Applications
 *
 * Approve or reject the ৳300/month membership requests submitted by
 * customers. Approving is what actually makes a customer Premium, which is
 * what makes the checkout select PremiumDiscountStrategy for them.
 */

$pageTitle = 'Premium Memberships';
$currentPage = 'premium';

require_once __DIR__ . '/../includes/header.php';

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

// C++ backend: /api/membership/applications
$response = AgriSphereBackend::tryCall('/api/membership/applications', [
    'status' => $filter === 'all' ? '' : $filter,
]);
$applications = $response['applications'] ?? [];
$pendingCount = (int)($response['pending_count'] ?? 0);

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Premium Memberships</h1>
    <p class="page-subtitle">
        <?php echo $pendingCount; ?> request(s) waiting for review &middot;
        <?php echo formatCurrency((float)($response['monthly_fee'] ?? 300)); ?> per month
    </p>
</div>

<?php if (!empty($response['backend_down'])): ?>
    <div class="alert alert-error"><?php echo sanitize($response['error']); ?></div>
<?php endif; ?>

<!-- Filter tabs -->
<div class="card mb-4">
    <div class="card-body" style="display: flex; gap: 12px;">
        <?php foreach (['Pending', 'Approved', 'Rejected', 'all'] as $tab): ?>
            <a href="premium.php?status=<?php echo $tab; ?>"
               class="btn btn-sm <?php echo $filter === $tab ? 'btn-primary' : 'btn-outline'; ?>">
                <?php echo $tab === 'all' ? 'All' : $tab; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Applications</h3>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Applied</th>
                    <th>Status</th>
                    <th>Valid Until</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="8" class="text-muted" style="text-align: center; padding: 24px;">
                            No membership applications found.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?php echo (int)$application['membership_id']; ?></td>
                        <td>
                            <strong><?php echo sanitize($application['customer_name']); ?></strong>
                            <div class="text-muted text-sm"><?php echo sanitize($application['customer_email']); ?></div>
                        </td>
                        <td><?php echo formatCurrency((float)$application['amount']); ?>/month</td>
                        <td>
                            <?php echo sanitize($application['payment_method']); ?>
                            <div class="text-muted text-sm">
                                <?php echo sanitize($application['payment_reference'] ?? ''); ?>
                            </div>
                        </td>
                        <td><?php echo formatDateTime($application['applied_at']); ?></td>
                        <td>
                            <?php
                            $status = $application['status'];
                            $badge = $status === 'Approved' ? 'badge-approved'
                                   : ($status === 'Rejected' ? 'badge-rejected' : 'badge-pending');
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span>
                            <?php if ($status === 'Approved' && (int)$application['is_active'] !== 1): ?>
                                <div class="text-muted text-sm">expired</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDateTime($application['expires_at']); ?></td>
                        <td>
                            <?php if ($status === 'Pending'): ?>
                                <form method="POST" action="premium_action.php" style="display: inline;">
                                    <input type="hidden" name="membership_id" value="<?php echo (int)$application['membership_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="premium_action.php" style="display: inline;"
                                      onsubmit="return confirm('Reject this Premium membership request?');">
                                    <input type="hidden" name="membership_id" value="<?php echo (int)$application['membership_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm btn-outline">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted text-sm">
                                    Reviewed <?php echo formatDateTime($application['reviewed_at']); ?>
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
