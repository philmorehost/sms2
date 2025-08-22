<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Ensure an admin is logged in
if (!is_admin()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$target_user_id = (int)$_GET['id'];
$current_admin_id = $current_user['id'];

// Prevent admin from switching to their own account
if ($target_user_id === $current_admin_id) {
    header("Location: users.php?error=self_switch");
    exit();
}

// Check if the target user exists and is not an admin
$stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ? AND is_admin = 0");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: users.php?error=invalid_user");
    exit();
}
$target_user = $result->fetch_assoc();
$stmt->close();

// Store the original admin details in the session
$_SESSION['original_admin_id'] = $current_admin_id;
$_SESSION['original_admin_username'] = $current_user['username'];

// Switch to the target user's session
$_SESSION['user_id'] = $target_user['id'];
$_SESSION['username'] = $target_user['username'];

// Unset admin-specific session variables to avoid conflicts
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);


// Redirect to the user's dashboard
header("Location: ../dashboard.php");
exit();
?>
