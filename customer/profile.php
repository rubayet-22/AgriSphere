<?php
/**
 * Customer - Profile Page
 */

$pageTitle = 'My Profile';
$currentPage = 'profile';

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

// Get user and customer data
$stmt = $conn->prepare("
    SELECT u.*, c.address, c.phone as customer_phone
    FROM user u
    LEFT JOIN customer c ON u.User_id = c.customer_id
    WHERE u.User_id = ?
");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_order WHERE customer_id = ?");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as spent FROM customer_order WHERE customer_id = ?");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$totalSpent = $stmt->get_result()->fetch_assoc()['spent'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        // Handle password change
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($newPassword !== $confirmPassword) {
            setFlashMessage('error', 'New passwords do not match');
        } elseif (strlen($newPassword) < 6) {
            setFlashMessage('error', 'Password must be at least 6 characters');
        } elseif ($currentPassword !== $customer['Password']) {
            setFlashMessage('error', 'Current password is incorrect');
        } else {
            $stmt = $conn->prepare("UPDATE user SET Password = ? WHERE User_id = ?");
            $stmt->bind_param("si", $newPassword, $customerId);
            if ($stmt->execute()) {
                setFlashMessage('success', 'Password changed successfully');
            } else {
                setFlashMessage('error', 'Failed to change password');
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'notification_preferences') {
        // Handle notification preferences (Decorator pattern)
        // These three flags decide which decorators the C++ backend wraps the
        // notification with. In-App is not stored: it is always on, because it
        // IS the notification row itself (BaseNotification).
        $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
        $notifySms   = isset($_POST['notify_sms']) ? 1 : 0;
        $notifyPush  = isset($_POST['notify_push']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE user SET notify_email = ?, notify_sms = ?, notify_push = ? WHERE User_id = ?");
        if ($stmt) {
            $stmt->bind_param("iiii", $notifyEmail, $notifySms, $notifyPush, $customerId);
            $stmt->execute();
            setFlashMessage('success', 'Notification preferences saved');
        } else {
            setFlashMessage('error', 'Could not save preferences. Run database/decorator_migration.sql first.');
        }
        redirect(BASE_URL . 'customer/profile.php');
    } else {
        // Handle profile update
        $firstName = sanitize($_POST['first_name']);
        $lastName = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);

        // Update user table
        $stmt = $conn->prepare("UPDATE user SET First_name = ?, Last_name = ?, Email = ?, Phone = ? WHERE User_id = ?");
        $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $customerId);
        $stmt->execute();

        // Update or insert customer table
        $stmt = $conn->prepare("INSERT INTO customer (customer_id, address, phone) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE address = ?, phone = ?");
        $stmt->bind_param("issss", $customerId, $address, $phone, $address, $phone);
        $stmt->execute();

        // Update session
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;

        setFlashMessage('success', 'Profile updated successfully');
        redirect(BASE_URL . 'customer/profile.php');
    }
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">My Profile</h1>
    <p class="page-subtitle">View and manage your account information.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Profile Card -->
    <div class="card">
        <div class="card-body" style="text-align: center;">
            <div class="sidebar-avatar" style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto 16px; background: var(--info-light); color: var(--info);">
                <?php echo strtoupper(substr($customer['First_name'], 0, 1)); ?>
            </div>
            <h3 style="margin-bottom: 4px;"><?php echo sanitize($customer['First_name'] . ' ' . $customer['Last_name']); ?></h3>
            <p class="text-muted">@<?php echo sanitize($customer['Username']); ?></p>
            <span class="badge badge-shipped" style="margin-top: 8px;">Customer</span>

            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--gray-100);">
                <div style="display: flex; justify-content: space-around;">
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?php echo $totalOrders; ?></div>
                        <div class="text-muted" style="font-size: 0.8125rem;">Orders</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?php echo formatCurrency($totalSpent); ?></div>
                        <div class="text-muted" style="font-size: 0.8125rem;">Total Spent</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--gray-100);">
                <a href="orders.php" class="btn btn-outline btn-sm" style="width: 100%;">
                    <i class="fas fa-clipboard-list"></i> View Orders
                </a>
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Profile</h3>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control"
                               value="<?php echo sanitize($customer['First_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control"
                               value="<?php echo sanitize($customer['Last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" class="form-control"
                           value="<?php echo sanitize($customer['Username']); ?>" disabled style="background: var(--gray-100);">
                    <small class="text-muted">Username cannot be changed</small>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?php echo sanitize($customer['Email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               value="<?php echo sanitize($customer['Phone'] ?? $customer['customer_phone'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Delivery Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3" required><?php echo sanitize($customer['address'] ?? ''); ?></textarea>
                    <small class="text-muted">This will be used as your default delivery address</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Notification Preferences Section -->
<div class="card" style="margin-top: 24px; max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bell"></i> Notification Preferences</h3>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom: 16px;">
            Choose how you want to be told when a farmer discounts a product.
            In-App notifications are always on.
        </p>

        <form action="" method="POST">
            <input type="hidden" name="action" value="notification_preferences">

            <label style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gray-100);">
                <input type="checkbox" checked disabled>
                <span>
                    <strong>In-App</strong>
                    <div class="text-muted" style="font-size: 0.8125rem;">Always on &mdash; shown on your Notifications page</div>
                </span>
            </label>

            <label style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gray-100); cursor: pointer;">
                <input type="checkbox" name="notify_email" value="1"
                       <?php echo (int)($customer['notify_email'] ?? 1) === 1 ? 'checked' : ''; ?>>
                <span>
                    <strong>Email</strong>
                    <div class="text-muted" style="font-size: 0.8125rem;"><?php echo sanitize($customer['Email']); ?></div>
                </span>
            </label>

            <label style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gray-100); cursor: pointer;">
                <input type="checkbox" name="notify_sms" value="1"
                       <?php echo (int)($customer['notify_sms'] ?? 0) === 1 ? 'checked' : ''; ?>>
                <span>
                    <strong>SMS</strong>
                    <div class="text-muted" style="font-size: 0.8125rem;"><?php echo sanitize($customer['Phone'] ?? ''); ?></div>
                </span>
            </label>

            <label style="display: flex; align-items: center; gap: 12px; padding: 12px 0; cursor: pointer;">
                <input type="checkbox" name="notify_push" value="1"
                       <?php echo (int)($customer['notify_push'] ?? 0) === 1 ? 'checked' : ''; ?>>
                <span>
                    <strong>Push</strong>
                    <div class="text-muted" style="font-size: 0.8125rem;">Push notification to your device</div>
                </span>
            </label>

            <div class="alert alert-warning" style="margin-top: 16px;">
                <i class="fas fa-info-circle"></i>
                Email, SMS and Push delivery are simulated for this academic project
                and are recorded in the backend log.
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 8px;">
                <i class="fas fa-save"></i> Save Preferences
            </button>
        </form>
    </div>
</div>

<!-- Change Password Section -->
<div class="card" style="margin-top: 24px; max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">Change Password</h3>
    </div>
    <div class="card-body">
        <form action="" method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
