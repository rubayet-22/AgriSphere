<?php
/**
 * AgriSphere OTP Verification Action Handler
 *
 * OTP checking and account creation (user + farmer/customer profile) are done
 * by the C++ RegistrationService; this file maps the outcome onto the session
 * and the redirect.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'auth/register.php');
}

// Check if there's a pending registration
if (!isset($_SESSION['pending_email'])) {
    setFlashMessage('error', 'No pending registration found. Please register again.');
    redirect(BASE_URL . 'auth/register.php');
}

$email = $_SESSION['pending_email'];
$otp = sanitize($_POST['otp'] ?? '');

try {
    $result = AgriSphereBackend::call('/api/auth/register/verify', [
        'email' => $email,
        'otp'   => $otp,
    ]);

    if (empty($result['success'])) {
        setFlashMessage('error', $result['error'] ?? 'Invalid OTP. Please try again.');

        // The pending registration is gone for good - send the user back to
        // the registration form, exactly as before.
        if (($result['error_kind'] ?? '') === 'session_expired') {
            unset($_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['demo_otp']);
            redirect(BASE_URL . 'auth/register.php');
        }
        if (($result['error_kind'] ?? '') === 'insert_failed') {
            redirect(BASE_URL . 'auth/register.php');
        }
        redirect(BASE_URL . 'auth/verify_otp.php');
    }

    // Clear pending-registration session variables
    unset($_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['demo_otp']);

    // Auto-login the new user
    $session = $result['session'];
    $_SESSION['user_id']   = $session['user_id'];
    $_SESSION['user_name'] = $session['user_name'];
    $_SESSION['user_type'] = $session['user_type'];
    $_SESSION['username']  = $session['username'];

    // Regenerate session ID
    session_regenerate_id(true);

    setFlashMessage('success', 'Email verified successfully! Welcome to ' . SITE_NAME);
    redirect(BASE_URL . ($result['redirect'] ?? 'auth/login.php'));

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}
