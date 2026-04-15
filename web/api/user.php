<?php
// web/api/user.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);

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
?>
