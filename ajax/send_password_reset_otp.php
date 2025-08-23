<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

// Get email from POST request
$email = $_POST['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    // We don't want to reveal if an email exists or not for security reasons.
    // So we send a generic success message, but don't actually send an email.
    echo json_encode(['success' => true, 'message' => 'If an account with this email exists, a password reset code has been sent.']);
    exit();
}
$user = $user_result->fetch_assoc();
$stmt->close();

// Generate a secure 6-digit OTP
$otp = random_int(100000, 999999);
$otp_hashed = password_hash((string)$otp, PASSWORD_DEFAULT);

// OTP is valid for 10 minutes
$expiry_time = date('Y-m-d H:i:s', time() + (10 * 60));

$conn->begin_transaction();
try {
    // Delete any old OTPs for this email
    $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->bind_param("s", $email);
    $stmt_delete->execute();

    // Store the new hashed OTP
    // The 'password_resets' table should have an 'expiry_time' column (DATETIME).
    $stmt_insert = $conn->prepare("INSERT INTO password_resets (email, otp_code, expiry_time) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("sss", $email, $otp_hashed, $expiry_time);
    $stmt_insert->execute();

    // Send the email to the user
    $subject = "Your Password Reset Code";
    $email_body = "<p>Hello " . htmlspecialchars($user['username']) . ",</p>";
    $email_body .= "<p>You requested to reset your password. Use the following code to proceed:</p>";
    $email_body .= "<h2>" . $otp . "</h2>";
    $email_body .= "<p>This code is valid for 10 minutes. If you did not request this, please ignore this email.</p>";
    $email_body .= "<p>Thank you,<br>The " . SITE_NAME . " Team</p>";

    send_email($email, $subject, $email_body);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'If an account with this email exists, a password reset code has been sent.']);

} catch (Exception $e) {
    $conn->rollback();
    // Log the actual error but return a generic message to the user
    error_log("Password reset OTP error for $email: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.']);
}

exit();
?>
