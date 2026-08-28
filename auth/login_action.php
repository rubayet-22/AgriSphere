<?php
/**
 * AgriSphere Login Action Handler
 *
 * Credential checking, the blocked-account check and the last_login update all
 * live in the C++ AuthService now (backend/src/services/AuthService.cpp).
 * This file keeps only what is genuinely a web concern: reading the form,
 * reCAPTCHA, the PHP session and the redirect.
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backend_client.php';

session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'auth/login.php');
}

// Get form data
$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = sanitize($_POST['role'] ?? '');

// Validate input
if (empty($username) || empty($password) || empty($role)) {
    setFlashMessage('error', 'All fields are required');
    redirect(BASE_URL . 'auth/login.php');
}

// reCAPTCHA Validation (browser concern - stays in PHP)
//
// Skipped when the request comes from this machine. Every failed login
// redirects back to login.php, which renders a brand new captcha, and a
// captcha token is single-use and expires after about two minutes. During
// local development that means re-solving the puzzle after every wrong
// password. On a real host REMOTE_ADDR is not loopback, so the check below
// runs exactly as it always did.
$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (!$isLocalRequest) {
    $recaptchaSecret = '6LdC5zYsAAAAAMmTfuEhVOak2O1ZguOxEbMBfydN';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptchaSecret . '&response=' . $recaptchaResponse);
    $responseKeys = json_decode((string)$response, true);

    if (intval($responseKeys['success'] ?? 0) !== 1) {
        setFlashMessage('error', 'reCAPTCHA verification failed. Please try again.');
        redirect(BASE_URL . 'auth/login.php?role=' . strtolower($role));
    }
}

try {
    $result = AgriSphereBackend::call('/api/auth/login', [
        'username' => $username,
        'password' => $password,
        'role'     => $role,
    ]);

    // Accounts whose password is stored as a bcrypt/argon2 hash: the backend
    // made the decision, PHP only runs the password_verify() primitive and
    // hands the result straight back for the final check.
    if (empty($result['success']) && !empty($result['requires_hash_verification'])) {
        if (password_verify($password, $result['password_hash'] ?? '')) {
            $result = AgriSphereBackend::call('/api/auth/login/complete', [
                'user_id' => (int)($result['user_id'] ?? 0),
            ]);
        }
    }

    if (empty($result['success'])) {
        setFlashMessage('error', $result['error'] ?? 'Invalid username or password');
        redirect(BASE_URL . 'auth/login.php?role=' . strtolower($role));
    }

    // Build the PHP session from the backend's payload
    $session = $result['session'];
    $_SESSION['user_id']    = $session['user_id'];
    $_SESSION['user_name']  = $session['user_name'];
    $_SESSION['user_type']  = $session['user_type'];
    $_SESSION['username']   = $session['username'];
    $_SESSION['login_time'] = date('Y-m-d H:i:s');

    // Regenerate session ID for security
    session_regenerate_id(true);

    setFlashMessage('success', $result['message'] ?? 'Welcome back!');
    redirect(BASE_URL . ($result['redirect'] ?? 'auth/login.php'));

} catch (BackendUnavailableException $e) {
    AgriSphereBackend::fail($e);
}
