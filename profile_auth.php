<?php
// Include authentication check
require_once 'check_auth.php';

// Require login to access profile
require_login();

// Get user data from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$fullname = $_SESSION['fullname'];
$is_admin = $_SESSION['is_admin'];

// Redirect to appropriate page
if ($_SERVER['REQUEST_URI'] == '/profile.html' && $is_admin == 1) {
    header("Location: admin_panel.php");
    exit();
}
?> 