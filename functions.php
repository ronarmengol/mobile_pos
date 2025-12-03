<?php
require_once 'config.php';

// Set timezone to Africa/Lusaka (CAT - Central Africa Time, UTC+2)
date_default_timezone_set('Africa/Lusaka');

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die("Access Denied. Admins only.");
    }
}

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        die("Access Denied. Superadmins only.");
    }
}

function sanitize($conn, $input) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}

function formatPrice($amount) {
    return 'K' . number_format($amount, 2);
}

// Session Timeout (5 minutes = 300 seconds)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();
?>
