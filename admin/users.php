<?php
/**
 * Admin Users Management
 * View, filter, and manage all users
 */

$pageTitle = 'Users';
$currentPage = 'users';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Get filter parameters
$filterType = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$whereConditions = ["u.User_type != 'Admin'"];
$params = [];
$types = '';

if ($filterType && in_array($filterType, ['Farmer', 'Customer'])) {
    $whereConditions[] = "u.User_type = ?";
    $params[] = $filterType;
    $types .= 's';
}

if ($filterStatus === 'blocked') {
    $whereConditions[] = "ub.is_blocked = 1";
} elseif ($filterStatus === 'active') {
    $whereConditions[] = "(ub.is_blocked IS NULL OR ub.is_blocked = 0)";
}

if ($search) {
    $whereConditions[] = "(u.First_name LIKE ? OR u.Last_name LIKE ? OR u.Username LIKE ? OR u.Email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

$whereClause = implode(' AND ', $whereConditions);

$query = "
    SELECT u.*,
           COALESCE(ub.is_blocked, 0) as is_blocked,
           ub.blocked_at
    FROM user u
    LEFT JOIN user_block ub ON u.User_id = ub.user_id
    WHERE $whereClause
    ORDER BY u.User_id DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $users = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Get counts for filter badges
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type != 'Admin'")->fetch_assoc()['count'];
$totalFarmers = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type = 'Farmer'")->fetch_assoc()['count'];
$totalCustomers = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type = 'Customer'")->fetch_assoc()['count'];
$blockedUsers = $conn->query("SELECT COUNT(*) as count FROM user_block WHERE is_blocked = 1")->fetch_assoc()['count'];

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Manage and monitor all registered users</p>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card mb-4">
    <div class="card-body" style="padding: 12px 16px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="users.php" class="filter-tab <?php echo (!$filterType && !$filterStatus) ? 'active' : ''; ?>">
                All Users <span class="filter-count"><?php echo $totalUsers; ?></span>
            </a>
            <a href="users.php?type=Farmer" class="filter-tab <?php echo $filterType === 'Farmer' ? 'active' : ''; ?>">
                Farmers <span class="filter-count"><?php echo $totalFarmers; ?></span>
            </a>
            <a href="users.php?type=Customer" class="filter-tab <?php echo $filterType === 'Customer' ? 'active' : ''; ?>">
                Customers <span class="filter-count"><?php echo $totalCustomers; ?></span>
            </a>
            <a href="users.php?status=blocked" class="filter-tab <?php echo $filterStatus === 'blocked' ? 'active' : ''; ?>">
                Blocked <span class="filter-count"><?php echo $blockedUsers; ?></span>
            </a>

            <div style="margin-left: auto;">
                <form method="GET" action="users.php" style="display: flex; gap: 8px;">
                    <?php if ($filterType): ?>
                        <input type="hidden" name="type" value="<?php echo $filterType; ?>">
                    <?php endif; ?>
                    <?php if ($filterStatus): ?>
                        <input type="hidden" name="status" value="<?php echo $filterStatus; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo $search; ?>" class="form-control" style="width: 250px;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($search): ?>
                        <a href="users.php<?php echo $filterType ? '?type='.$filterType : ''; ?><?php echo $filterStatus ? ($filterType ? '&' : '?').'status='.$filterStatus : ''; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php
            if ($filterType) echo $filterType . 's';
            elseif ($filterStatus === 'blocked') echo 'Blocked Users';
            else echo 'All Users';
            ?>
            <?php if ($search): ?>
                <span class="text-muted"> - Search: "<?php echo $search; ?>"</span>
            <?php endif; ?>
        </h3>
        <span class="text-muted"><?php echo count($users); ?> users found</span>
    </div>

    <?php if (empty($users)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h4 class="empty-state-title">No users found</h4>
                <p class="empty-state-text">
                    <?php if ($search): ?>
                        No users match your search criteria.
                    <?php else: ?>
                        There are no users matching this filter.
                    <?php endif; ?>
                </p>
                <a href="users.php" class="btn btn-primary">View All Users</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Subscription</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar" style="background: <?php echo $user['User_type'] === 'Farmer' ? '#D1FAE5' : '#DBEAFE'; ?>; color: <?php echo $user['User_type'] === 'Farmer' ? '#059669' : '#2563EB'; ?>;">
                                        <?php echo strtoupper(substr($user['First_name'], 0, 1) . substr($user['Last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo sanitize($user['First_name'] . ' ' . $user['Last_name']); ?></strong>
                                        <div class="text-muted text-sm">@<?php echo sanitize($user['Username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo sanitize($user['Email']); ?></div>
                                <?php if ($user['Phone']): ?>
                                    <div class="text-muted text-sm"><?php echo sanitize($user['Phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['User_type'] === 'Farmer' ? 'badge-approved' : 'badge-shipped'; ?>">
                                    <?php echo $user['User_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['User_type'] === 'Farmer'): ?>
                                    <span class="badge badge-approved">৳1,000</span>
                                    <div class="text-muted text-sm">Subscribed</div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_blocked']): ?>
                                    <span class="badge badge-rejected">Blocked</span>
                                    <?php if ($user['blocked_at']): ?>
                                        <div class="text-muted text-sm"><?php echo formatDate($user['blocked_at']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-approved">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="viewUser(<?php echo $user['User_id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($user['is_blocked']): ?>
                                        <form method="POST" action="user_action.php" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['User_id']; ?>">
                                            <input type="hidden" name="action" value="unblock">
                                            <button type="submit" class="btn btn-sm btn-success" title="Unblock User">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="user_action.php" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['User_id']; ?>">
                                            <input type="hidden" name="action" value="block">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Block User" onclick="return confirm('Are you sure you want to block this user?')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- User Details Modal -->
<div id="userModal" class="user-modal-overlay" style="display: none;" onclick="if(event.target===this)closeModal()">
    <div class="user-modal-box">
        <div class="user-modal-header">
            <h3><i class="fas fa-user-circle"></i> User Details</h3>
            <button type="button" class="user-modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="user-modal-body">
            <!-- User Profile Section (Fixed) -->
            <div class="user-profile-section" id="userProfileSection">
                <div class="loading">Loading user details...</div>
            </div>

            <!-- Tabs Navigation (Fixed) -->
            <div class="modal-tabs-wrapper" id="modalTabsWrapper" style="display: none;">
                <div class="modal-tabs">
                    <button class="modal-tab active" data-tab="profile" onclick="switchTab('profile')">
                        <i class="fas fa-user"></i> Profile Info
                    </button>
                    <button class="modal-tab" data-tab="activity" onclick="switchTab('activity')">
                        <i class="fas fa-chart-line"></i> <span id="activityTabLabel">Activity</span>
                    </button>
                </div>
                <div class="modal-header-actions">
                    <button type="button" class="btn btn-sm btn-primary" id="editUserBtn" onclick="toggleEditMode()">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>

            <!-- Scrollable Content Area -->
            <div class="user-modal-scroll" id="modalScrollArea">
                <div class="tab-content active" id="tab-profile"></div>
                <div class="tab-content" id="tab-activity"></div>
            </div>
        </div>
    </div>
</div>

<style>
.filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--gray-100);
    border-radius: var(--radius-full);
    color: var(--gray-600);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all var(--transition-fast);
}

.filter-tab:hover {
    background: var(--gray-200);
    color: var(--gray-700);
}

.filter-tab.active {
    background: var(--primary);
    color: white;
}

.filter-tab.active .filter-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.filter-count {
    background: var(--gray-200);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.btn-success {
    background: var(--success);
    color: white;
    border: none;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: var(--error);
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #DC2626;
}

/* ============================================
   USER MODAL STYLES - Unique class names
   ============================================ */
.user-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.user-modal-box {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 900px;
    max-height: calc(100vh - 40px);
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
}

.user-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: linear-gradient(135deg, #2F6B4F 0%, #3d8b68 100%);
    color: white;
    flex-shrink: 0;
}

.user-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-modal-close {
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    transition: all 0.2s;
}

.user-modal-close:hover {
    background: rgba(255,255,255,0.4);
    transform: scale(1.1);
}

.user-modal-body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* User Profile Section - Fixed at top */
.user-profile-section {
    padding: 20px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.user-profile-card {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-profile-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.user-profile-details {
    flex: 1;
    min-width: 0;
}

.user-profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px;
    line-height: 1.2;
}

.user-profile-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
}

.user-profile-meta .badge {
    font-size: 0.75rem;
    padding: 4px 10px;
}

.user-profile-username {
    color: #64748b;
    font-size: 0.875rem;
}

.user-profile-stats {
    display: flex;
    gap: 16px;
    margin-left: auto;
    flex-shrink: 0;
}

.profile-stat {
    text-align: center;
    padding: 12px 16px;
    background: white;
    border-radius: 12px;
    min-width: 90px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.profile-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2F6B4F;
}

.profile-stat-label {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

/* Tabs Wrapper - Fixed below profile */
.modal-tabs-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.modal-tabs {
    display: flex;
    gap: 4px;
}

.modal-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 20px;
    background: none;
    border: none;
    cursor: pointer;
    color: #64748b;
    font-weight: 500;
    font-size: 0.9rem;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s;
}

.modal-tab:hover {
    color: #2F6B4F;
    background: #f0fdf4;
}

.modal-tab.active {
    color: #2F6B4F;
    border-bottom-color: #2F6B4F;
}

.modal-tab i {
    font-size: 1rem;
}

.modal-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Scrollable Content Area */
.user-modal-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    min-height: 0;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

/* Section Headers */
.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

.section-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #334155;
}

.section-header i {
    color: #2F6B4F;
}

/* Info Grid - Profile Tab */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.info-item {
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    transition: border-color 0.2s;
}

.info-item:hover {
    border-color: #2F6B4F;
}

.info-item.full-width {
    grid-column: span 2;
}

.info-item label {
    display: block;
    font-size: 0.7rem;
    color: #64748b;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.info-item .value {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
    word-break: break-word;
}

.info-item .value.not-provided {
    color: #94a3b8;
    font-style: italic;
}

.info-item input, .info-item textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.info-item input:focus, .info-item textarea:focus {
    outline: none;
    border-color: #2F6B4F;
    box-shadow: 0 0 0 3px rgba(47, 107, 79, 0.1);
}

/* Edit Actions */
.edit-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

/* Products & Orders Lists */
.items-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item-card {
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    transition: all 0.2s;
}

.item-card:hover {
    border-color: #2F6B4F;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.item-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.item-card-title {
    font-weight: 600;
    font-size: 1rem;
    color: #1e293b;
    margin: 0;
}

.item-card-subtitle {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 2px;
}

.item-card-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 12px;
    padding-top: 12px;
    border-top: 1px dashed #e2e8f0;
}

.item-stat {
    text-align: center;
    padding: 8px;
    background: #f8fafc;
    border-radius: 8px;
}

.item-stat-label {
    font-size: 0.65rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.item-stat-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    margin-top: 2px;
}

.item-stat-value.highlight {
    color: #2F6B4F;
}

/* Order Items Breakdown */
.order-items-breakdown {
    margin-top: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
}

.order-items-breakdown h5 {
    margin: 0 0 8px;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
}

.order-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px dashed #e2e8f0;
    font-size: 0.85rem;
}

.order-item-row:last-child {
    border-bottom: none;
}

.order-item-name {
    font-weight: 500;
    color: #334155;
}

.order-item-meta {
    color: #64748b;
    font-size: 0.8rem;
}

.order-item-price {
    font-weight: 600;
    color: #2F6B4F;
}

/* Empty State */
.empty-state-box {
    text-align: center;
    padding: 48px 24px;
    background: #f8fafc;
    border-radius: 12px;
    border: 2px dashed #e2e8f0;
}

.empty-state-box i {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 16px;
}

.empty-state-box h4 {
    margin: 0 0 8px;
    color: #64748b;
    font-weight: 600;
}

.empty-state-box p {
    margin: 0;
    color: #94a3b8;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .modal {
        padding: 10px;
    }

    .user-profile-card {
        flex-direction: column;
        text-align: center;
    }

    .user-profile-stats {
        margin: 16px 0 0;
        width: 100%;
        justify-content: center;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .info-item.full-width {
        grid-column: span 1;
    }

    .modal-tabs-wrapper {
        flex-direction: column;
        padding: 12px;
        gap: 12px;
    }

    .modal-tabs {
        width: 100%;
    }

    .modal-tab {
        flex: 1;
        justify-content: center;
    }
}
</style>

<script>
let currentUser = null;
let isEditMode = false;

function viewUser(userId) {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('userProfileSection').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading user details...</div>';
    document.getElementById('modalTabsWrapper').style.display = 'none';
    document.getElementById('tab-profile').innerHTML = '';
    document.getElementById('tab-activity').innerHTML = '';
    isEditMode = false;

    fetch('get_user.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.user;
                renderUserModal(currentUser);
            } else {
                document.getElementById('userProfileSection').innerHTML = '<div class="loading" style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Error loading user details</div>';
            }
        })
        .catch(error => {
            document.getElementById('userProfileSection').innerHTML = '<div class="loading" style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Error loading user details</div>';
        });
}

function renderUserModal(user) {
    const isFarmer = user.User_type === 'Farmer';
    const avatarBg = isFarmer ? '#D1FAE5' : '#DBEAFE';
    const avatarColor = isFarmer ? '#059669' : '#2563EB';

    // Update activity tab label
    document.getElementById('activityTabLabel').textContent = isFarmer ? 'Products' : 'Orders';

    // Render Profile Section
    let statsHtml = '';
    if (user.stats) {
        if (isFarmer) {
            statsHtml = `
                <div class="user-profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-value">${user.stats.total_products}</div>
                        <div class="profile-stat-label">Products</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">${user.stats.approved_products}</div>
                        <div class="profile-stat-label">Approved</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">৳${formatNumber(user.stats.total_revenue)}</div>
                        <div class="profile-stat-label">Revenue</div>
                    </div>
                </div>
            `;
        } else {
            statsHtml = `
                <div class="user-profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-value">${user.stats.total_orders}</div>
                        <div class="profile-stat-label">Orders</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">৳${formatNumber(user.stats.total_spent)}</div>
                        <div class="profile-stat-label">Spent</div>
                    </div>
                </div>
            `;
        }
    }

    document.getElementById('userProfileSection').innerHTML = `
        <div class="user-profile-card">
            <div class="user-profile-avatar" style="background: ${avatarBg}; color: ${avatarColor};">
                ${user.First_name[0]}${user.Last_name[0]}
            </div>
            <div class="user-profile-details">
                <h3 class="user-profile-name">${user.First_name} ${user.Last_name}</h3>
                <div class="user-profile-meta">
                    <span class="badge ${isFarmer ? 'badge-approved' : 'badge-shipped'}">${user.User_type}</span>
                    <span class="user-profile-username">@${user.Username}</span>
                    <span class="badge ${user.is_blocked ? 'badge-rejected' : 'badge-approved'}">
                        ${user.is_blocked ? 'Blocked' : 'Active'}
                    </span>
                </div>
            </div>
            ${statsHtml}
        </div>
    `;

    // Show tabs
    document.getElementById('modalTabsWrapper').style.display = 'flex';

    // Reset edit button
    const editBtn = document.getElementById('editUserBtn');
    editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
    editBtn.classList.add('btn-primary');
    editBtn.classList.remove('btn-secondary');

    // Render Profile Tab
    document.getElementById('tab-profile').innerHTML = renderProfileTab(user);

    // Render Activity Tab (Products or Orders)
    document.getElementById('tab-activity').innerHTML = isFarmer ? renderProductsTab(user) : renderOrdersTab(user);

    // Reset to profile tab
    switchTab('profile');
}

function renderProfileTab(user) {
    const isFarmer = user.User_type === 'Farmer';

    let html = `
        <div class="section-header">
            <i class="fas fa-id-card"></i>
            <h4>Personal Information</h4>
        </div>
        <div class="info-grid" id="infoGrid">
            <div class="info-item">
                <label>First Name</label>
                <div class="value ${!user.First_name ? 'not-provided' : ''}" data-field="first_name">${user.First_name || 'Not provided'}</div>
            </div>
            <div class="info-item">
                <label>Last Name</label>
                <div class="value ${!user.Last_name ? 'not-provided' : ''}" data-field="last_name">${user.Last_name || 'Not provided'}</div>
            </div>
            <div class="info-item">
                <label>Email Address</label>
                <div class="value ${!user.Email ? 'not-provided' : ''}" data-field="email">${user.Email || 'Not provided'}</div>
            </div>
            <div class="info-item">
                <label>Phone Number</label>
                <div class="value ${!user.Phone ? 'not-provided' : ''}" data-field="phone">${user.Phone || 'Not provided'}</div>
            </div>
            <div class="info-item">
                <label>National ID (NID)</label>
                <div class="value ${!user.NID ? 'not-provided' : ''}">${user.NID || 'Not provided'}</div>
            </div>
            <div class="info-item">
                <label>Last Login</label>
                <div class="value ${!user.last_login ? 'not-provided' : ''}">${user.last_login ? formatDate(user.last_login) : 'Never logged in'}</div>
            </div>
            <div class="info-item full-width">
                <label>Address</label>
                <div class="value ${!user.address ? 'not-provided' : ''}" data-field="address">${user.address || 'Not provided'}</div>
            </div>
        </div>
    `;

    if (isFarmer) {
        html += `
            <div class="section-header" style="margin-top: 24px;">
                <i class="fas fa-university"></i>
                <h4>Banking Information</h4>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Bank Name</label>
                    <div class="value ${!user.bank_name ? 'not-provided' : ''}" data-field="bank_name">${user.bank_name || 'Not provided'}</div>
                </div>
                <div class="info-item">
                    <label>Account Number</label>
                    <div class="value ${!user.bank_account_number ? 'not-provided' : ''}" data-field="bank_account_number">${user.bank_account_number || 'Not provided'}</div>
                </div>
                <div class="info-item">
                    <label>Subscription Status</label>
                    <div class="value"><span class="badge badge-approved">Active - ৳1,000</span></div>
                </div>
                <div class="info-item">
                    <label>Account Created</label>
                    <div class="value">${user.created_at ? formatDate(user.created_at) : 'N/A'}</div>
                </div>
            </div>
        `;
    }

    html += `
        <div class="edit-actions" id="editActions" style="display: none;">
            <button class="btn btn-secondary" onclick="cancelEdit()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn btn-primary" onclick="saveUser()">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    `;

    return html;
}

function renderProductsTab(user) {
    if (!user.products || user.products.length === 0) {
        return `
            <div class="empty-state-box">
                <i class="fas fa-box-open"></i>
                <h4>No Products Listed</h4>
                <p>This farmer hasn't listed any products yet.</p>
            </div>
        `;
    }

    let html = `
        <div class="section-header">
            <i class="fas fa-boxes"></i>
            <h4>All Products (${user.products.length})</h4>
        </div>
        <div class="items-list">
    `;

    user.products.forEach(product => {
        const statusClass = product.status === 'approved' ? 'badge-approved' :
                           (product.status === 'rejected' ? 'badge-rejected' : 'badge-pending');
        html += `
            <div class="item-card">
                <div class="item-card-header">
                    <div>
                        <h5 class="item-card-title">${product.product_name}</h5>
                        <div class="item-card-subtitle">${product.category_name}</div>
                    </div>
                    <span class="badge ${statusClass}">${product.status}</span>
                </div>
                <div class="item-card-stats">
                    <div class="item-stat">
                        <div class="item-stat-label">Price</div>
                        <div class="item-stat-value highlight">৳${formatNumber(product.price_per_unit)}</div>
                    </div>
                    <div class="item-stat">
                        <div class="item-stat-label">Unit</div>
                        <div class="item-stat-value">${product.unit}</div>
                    </div>
                    <div class="item-stat">
                        <div class="item-stat-label">Stock</div>
                        <div class="item-stat-value">${product.stock}</div>
                    </div>
                    <div class="item-stat">
                        <div class="item-stat-label">Total Sold</div>
                        <div class="item-stat-value">${product.total_sold}</div>
                    </div>
                    <div class="item-stat">
                        <div class="item-stat-label">Earnings</div>
                        <div class="item-stat-value highlight">৳${formatNumber(product.total_earnings)}</div>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    return html;
}

function renderOrdersTab(user) {
    if (!user.orders || user.orders.length === 0) {
        return `
            <div class="empty-state-box">
                <i class="fas fa-shopping-bag"></i>
                <h4>No Orders Yet</h4>
                <p>This customer hasn't placed any orders yet.</p>
            </div>
        `;
    }

    let html = `
        <div class="section-header">
            <i class="fas fa-shopping-cart"></i>
            <h4>All Orders (${user.orders.length})</h4>
        </div>
        <div class="items-list">
    `;

    user.orders.forEach(order => {
        const statusClass = order.status === 'Delivered' ? 'badge-approved' :
                           (order.status === 'Cancelled' ? 'badge-rejected' :
                           (order.status === 'Shipped' ? 'badge-shipped' : 'badge-pending'));

        let itemsHtml = '';
        if (order.items && order.items.length > 0) {
            itemsHtml = '<div class="order-items-breakdown"><h5>Items Ordered</h5>';
            order.items.forEach(item => {
                itemsHtml += `
                    <div class="order-item-row">
                        <div>
                            <div class="order-item-name">${item.product_name}</div>
                            <div class="order-item-meta">${item.quantity} ${item.unit} × ৳${formatNumber(item.price_per_unit)} • from ${item.farmer_name}</div>
                        </div>
                        <div class="order-item-price">৳${formatNumber(item.quantity * item.price_per_unit)}</div>
                    </div>
                `;
            });
            itemsHtml += '</div>';
        }

        html += `
            <div class="item-card">
                <div class="item-card-header">
                    <div>
                        <h5 class="item-card-title">Order #${order.order_id}</h5>
                        <div class="item-card-subtitle">${formatDate(order.order_date)}</div>
                    </div>
                    <span class="badge ${statusClass}">${order.status}</span>
                </div>
                <div class="item-card-stats">
                    <div class="item-stat">
                        <div class="item-stat-label">Items</div>
                        <div class="item-stat-value">${order.items ? order.items.length : 0}</div>
                    </div>
                    <div class="item-stat">
                        <div class="item-stat-label">Total Amount</div>
                        <div class="item-stat-value highlight">৳${formatNumber(order.total_amount)}</div>
                    </div>
                </div>
                ${itemsHtml}
            </div>
        `;
    });

    html += '</div>';
    return html;
}

function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.modal-tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.dataset.tab === tabName) {
            tab.classList.add('active');
        }
    });

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    if (tabName === 'profile') {
        document.getElementById('tab-profile').classList.add('active');
    } else {
        document.getElementById('tab-activity').classList.add('active');
    }

    // Reset edit mode when switching tabs
    if (isEditMode) {
        cancelEdit();
    }
}

function toggleEditMode() {
    isEditMode = !isEditMode;
    const editBtn = document.getElementById('editUserBtn');

    if (isEditMode) {
        // Switch to profile tab first
        switchTab('profile');

        editBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
        editBtn.classList.remove('btn-primary');
        editBtn.classList.add('btn-secondary');

        // Convert values to inputs
        document.querySelectorAll('#infoGrid .value[data-field]').forEach(el => {
            const field = el.dataset.field;
            let value = currentUser[field] || currentUser[field.charAt(0).toUpperCase() + field.slice(1)] || '';
            if (value === 'Not provided') value = '';
            el.innerHTML = `<input type="text" name="${field}" value="${escapeHtml(value)}" placeholder="Enter ${field.replace('_', ' ')}" />`;
        });

        document.getElementById('editActions').style.display = 'flex';
    } else {
        cancelEdit();
    }
}

function cancelEdit() {
    isEditMode = false;
    const editBtn = document.getElementById('editUserBtn');
    editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
    editBtn.classList.add('btn-primary');
    editBtn.classList.remove('btn-secondary');

    // Re-render profile tab
    document.getElementById('tab-profile').innerHTML = renderProfileTab(currentUser);
}

function saveUser() {
    const data = {
        user_id: currentUser.User_id
    };

    document.querySelectorAll('#infoGrid input[name]').forEach(input => {
        data[input.name] = input.value;
    });

    // Show loading state
    const saveBtn = document.querySelector('#editActions .btn-primary');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;

    fetch('update_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Update currentUser with new values
            Object.keys(data).forEach(key => {
                if (key !== 'user_id') {
                    const capitalizedKey = key.charAt(0).toUpperCase() + key.slice(1);
                    if (currentUser.hasOwnProperty(capitalizedKey)) {
                        currentUser[capitalizedKey] = data[key];
                    } else {
                        currentUser[key] = data[key];
                    }
                }
            });

            isEditMode = false;
            renderUserModal(currentUser);

            // Show success message
            showToast('User updated successfully!', 'success');

            // Refresh the page after a short delay
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Error: ' + (result.error || 'Failed to update user'), 'error');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        showToast('Error saving user data', 'error');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
    currentUser = null;
    isEditMode = false;
}

// Helper functions
function formatNumber(num) {
    return parseFloat(num || 0).toLocaleString('en-IN');
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
    toast.style.cssText = `
        background: ${bgColor};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        margin-top: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 8px;
        animation: slideIn 0.3s ease;
    `;
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
    toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('userModal').style.display === 'flex') {
        closeModal();
    }
});
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
