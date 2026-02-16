<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// --- Validation ---
if (empty($email) || empty($otp) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit();
}
if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit();
}
if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit();
}

// --- OTP Verification ---
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$reset_request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reset_request) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP or email. Please try again.']);
    exit();
}

// Check expiry
$expiry_time = strtotime($reset_request['expiry_time']);
$current_time = time();

if ($current_time > $expiry_time) {
    echo json_encode(['success' => false, 'message' => 'Your OTP has expired. Please request a new one.']);
    exit();
}

// Verify the OTP against the hashed version
if (!password_verify($otp, $reset_request['otp_code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please check the code and try again.']);
    exit();
}

// --- All checks passed, update the password ---
$conn->begin_transaction();
try {
    // 1. Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 2. Update the user's password
    $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt_update->bind_param("ss", $hashed_password, $email);
    $stmt_update->execute();
    $stmt_update->close();

    // 3. Delete the used OTP request
    $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->bind_param("s", $email);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 4. Log the user in
    $stmt_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    $_SESSION['user_id'] = $user['id'];

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Password reset successfully!', 'redirect_url' => 'dashboard.php']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Password reset failed for $email: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred while updating your password.']);
}

exit();
?>
