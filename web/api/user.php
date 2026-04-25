<?php
// web/api/user.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);

$action = $_GET['action'] ?? 'view';

if ($action === 'view') {
    mobile_api_success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone_number'],
            'balance' => (float)$user['balance'],
            'referral_code' => $user['referral_code'],
            'created_at' => $user['created_at']
        ]
    ]);
} elseif ($action === 'update') {
    $email = trim($_POST['email'] ?? $user['email']);
    $phone = trim($_POST['phone'] ?? $user['phone_number']);
    $password = trim($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) mobile_api_error('Invalid email address');

    $sql = "UPDATE users SET email = ?, phone_number = ? WHERE id = ?";
    $params = [$email, $phone, $user['id']];
    $types = "ssi";

    if (!empty($password)) {
        $sql = "UPDATE users SET email = ?, phone_number = ?, password = ? WHERE id = ?";
        $params = [$email, $phone, password_hash($password, PASSWORD_DEFAULT), $user['id']];
        $types = "sssi";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        mobile_api_success([], 'Profile updated successfully');
    } else {
        mobile_api_error('Failed to update profile');
    }
}
?>
