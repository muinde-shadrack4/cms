<?php
// ============================================================
// auth.php — Session & Role Protection
// Include this at the top of every protected page
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect to login if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . get_login_path());
        exit();
    }
}

// Get the correct login path based on current location
function get_login_path() {
    $script = $_SERVER['SCRIPT_FILENAME'];
    if (strpos($script, DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR) !== false ||
        strpos($script, DIRECTORY_SEPARATOR . 'dispatch' . DIRECTORY_SEPARATOR) !== false ||
        strpos($script, DIRECTORY_SEPARATOR . 'warehouse' . DIRECTORY_SEPARATOR) !== false ||
        strpos($script, DIRECTORY_SEPARATOR . 'driver' . DIRECTORY_SEPARATOR) !== false ||
        strpos($script, DIRECTORY_SEPARATOR . 'customer_service' . DIRECTORY_SEPARATOR) !== false ||
        strpos($script, DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR) !== false) {
        return '../login.php';
    }
    return 'login.php';
}

// Require a specific role
function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Wrong role — redirect to their own dashboard
        redirect_to_dashboard();
    }
}

// Redirect user to their correct dashboard
function redirect_to_dashboard() {
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':            header('Location: /courier_cms/admin/dashboard.php'); break;
        case 'customer_service': header('Location: /courier_cms/customer_service/dashboard.php'); break;
        case 'dispatch':         header('Location: /courier_cms/dispatch/dashboard.php'); break;
        case 'warehouse':        header('Location: /courier_cms/warehouse/dashboard.php'); break;
        case 'driver':           header('Location: /courier_cms/driver/dashboard.php'); break;
        case 'customer':         header('Location: /courier_cms/customer/dashboard.php'); break;
        default:                 header('Location: /courier_cms/login.php'); break;
    }
    exit();
}
?>