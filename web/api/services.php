<?php
// web/api/services.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? '';

if ($action === 'referrals') {
    $referrals = [];
    $stmt = $conn->prepare("SELECT username, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $referrals[] = $row;
    }
    mobile_api_success([
        'referral_code' => $user['referral_code'],
        'referral_balance' => (float)$user['referral_balance'],
        'referrals' => $referrals
    ]);
} elseif ($action === 'otp_templates') {
    $templates = [];
    $stmt = $conn->prepare("SELECT template_code, template_name, message_body, status FROM otp_templates WHERE user_id = ? OR is_public = 1");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    mobile_api_success(['templates' => $templates]);
}
?>
