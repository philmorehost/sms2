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
        if ($user['is_email_verified'] == 0) {
            mobile_api_error('Account not verified. Please verify your email.');
        }

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

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone_number, api_key, referral_code, is_email_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssssss", $username, $email, $hashed_password, $phone, $api_key, $ref_code);

    if ($stmt->execute()) {
        mobile_api_success([
            'token' => $api_key,
            'username' => $username
        ], 'Registration successful');
    } else {
        mobile_api_error('Registration failed');
    }
} else {
    mobile_api_error('Invalid action');
}
?>
