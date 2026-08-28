<?php
/**
 * AgriSphere Registration Action Handler
 *
 * Validation, duplicate checks, OTP generation and the pending_registration
 * row are handled by the C++ RegistrationService
 * (backend/src/services/RegistrationService.cpp).
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

// Get form data
$firstName = sanitize($_POST['first_name'] ?? '');
$lastName = sanitize($_POST['last_name'] ?? '');
$username = sanitize($_POST['username'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$role = sanitize($_POST['role'] ?? '');

try {
    $result = AgriSphereBackend::call('/api/auth/register', [
        'first_name'       => $firstName,
        'last_name'        => $lastName,
        'username'         => $username,
        'email'            => $email,
        'phone'            => $phone,
        'address'          => $address,
        'password'         => $password,
        'confirm_password' => $confirmPassword,
        'role'             => $role,
    ]);

    if (empty($result['success'])) {
        $errors = $result['errors'] ?? [$result['error'] ?? 'Registration failed. Please try again.'];
        setFlashMessage('error', implode('<br>', $errors));
        redirect(BASE_URL . 'auth/register.php?role=' . strtolower($role));
    }

    // Store email in session for verification page
    $_SESSION['pending_email'] = $result['email'];
    $_SESSION['pending_role'] = $result['role'];

    // In production, send OTP via email using mail() or a service like PHPMailer
    // For demo, we'll display the OTP on the verification page
    $_SESSION['demo_otp'] = $result['otp']; // Remove this in production!

    setFlashMessage('success', 'Please verify your email with the OTP sent to ' . $result['email']);
    redirect(BASE_URL . 'auth/verify_otp.php');

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}
