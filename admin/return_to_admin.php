<?php
require_once __DIR__ . '/../app/bootstrap.php';

// This script is for returning to the admin account after impersonating a user.

if (isset($_SESSION['original_admin_id'])) {
    // Restore the original admin's session
    $_SESSION['admin_id'] = $_SESSION['original_admin_id'];
    $_SESSION['admin_username'] = $_SESSION['original_admin_username'];

    // Clean up the session variables
    unset($_SESSION['original_admin_id']);
    unset($_SESSION['original_admin_username']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);

    // Redirect to the admin dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // If somehow accessed directly without being in impersonation mode,
    // just redirect to the regular user dashboard or login.
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit();
}
?>
