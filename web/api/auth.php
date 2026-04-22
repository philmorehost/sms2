<?php
// web/api/auth.php
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        mobile_api_error('Login and password are required');
    }

    $stmt = $conn->prepare("SELECT id, username, password, api_key, is_email_verified FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        // Bypass email verification check for mobile users to allow smooth usage
        /*
        if ($user['is_email_verified'] == 0) {
            mobile_api_error('Account not verified. Please verify your email.');
        }
        */

        mobile_api_success([
            'token' => $user['api_key'],
            'username' => $user['username'],
            'user_id' => $user['id']
        ], 'Login successful');
    } else {
        mobile_api_error('Invalid login credentials');
    }
} elseif ($action === 'register') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        mobile_api_error('Username, email and password are required');
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        mobile_api_error('Username or email already exists');
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $api_key = 'sk_' . bin2hex(random_bytes(16));
    $ref_code = strtoupper(substr($username, 0, 3)) . bin2hex(random_bytes(2));

    // Set is_email_verified = 1 by default for mobile registrations
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone_number, api_key, referral_code, is_email_verified) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssssss", $username, $email, $hashed_password, $phone, $api_key, $ref_code);

    if ($stmt->execute()) {
        mobile_api_success([
            'token' => $api_key,
            'username' => $username
        ], 'Registration successful');
    } else {
        mobile_api_error('Registration failed');
    }
} elseif ($action === 'forgot_password') {
    $email = $_POST['email'] ?? '';
    if (empty($email)) mobile_api_error('Email is required');

    // Reuse existing logic from ajax/send_password_reset_otp.php if possible,
    // but here we implement a standard mobile-friendly response.
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        mobile_api_error('No account found with that email');
    }
    $stmt->close();

    $otp = sprintf("%06d", mt_rand(0, 999999));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $otp, $expires_at);

    if ($stmt->execute()) {
        $subject = "Password Reset Code";
        $message = "Your password reset code is: <b>$otp</b>. It expires in 1 hour.";
        send_email($email, $subject, $message);
        mobile_api_success([], 'Reset code sent to your email');
    } else {
        mobile_api_error('Failed to send reset code');
    }
} elseif ($action === 'reset_password') {
    $email = $_POST['email'] ?? '';
    $otp = $_POST['otp'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($otp) || empty($password)) {
        mobile_api_error('Email, OTP and new password are required');
    }

    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        mobile_api_error('Invalid or expired OTP');
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute()) {
        // Clear OTPs
        $stmt_clear = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt_clear->bind_param("s", $email);
        $stmt_clear->execute();

        mobile_api_success([], 'Password reset successful');
    } else {
        mobile_api_error('Failed to reset password');
    }
} else {
    mobile_api_error('Invalid action');
}
?>
