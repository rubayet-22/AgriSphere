<?php
/**
 * AgriSphere Resend OTP
 *
 * Handled by the C++ RegistrationService.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

// Check if there's a pending registration
if (!isset($_SESSION['pending_email'])) {
    redirect(BASE_URL . 'auth/register.php');
}

$email = $_SESSION['pending_email'];

try {
    $result = AgriSphereBackend::call('/api/auth/register/resend', ['email' => $email]);

    if (empty($result['success'])) {
        setFlashMessage('error', $result['error'] ?? 'Failed to resend OTP. Please try again.');
        if (($result['error_kind'] ?? '') === 'session_expired') {
            unset($_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['demo_otp']);
            redirect(BASE_URL . 'auth/register.php');
        }
    } else {
        // In production, send OTP via email
        // For demo, store in session
        $_SESSION['demo_otp'] = $result['otp'];
        setFlashMessage('success', 'A new OTP has been sent to your email.');
    }

    redirect(BASE_URL . 'auth/verify_otp.php');

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}
