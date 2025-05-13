<?php
// Start session
session_start();

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.html");
        exit();
    }
}

// Redirect to admin panel if admin
function require_admin() {
    require_login();
    
    if ($_SESSION['is_admin'] != 1) {
        header("Location: profile.html");
        exit();
    }
}
?> 