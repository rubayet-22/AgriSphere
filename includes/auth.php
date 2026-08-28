<?php
session_start();
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/init.php";
require_once __DIR__ . "/functions.php";

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: http://localhost/Agrisphere/auth/login.php");
        exit();
    }
}

function require_role($role) {
    require_login();
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== $role) {
        header("Location: http://localhost/Agrisphere/auth/login.php");
        exit();
    }
}

// Blocked-account check via the C++ backend (/api/auth/block-status)
function block_check_or_exit($conn) {
    if (!isset($_SESSION['user_id'])) return;

    if (isUserBlocked($conn, (int)$_SESSION['user_id'])) {
        session_unset();
        session_destroy();
        echo "Your account is blocked by admin.";
        exit();
    }
}
