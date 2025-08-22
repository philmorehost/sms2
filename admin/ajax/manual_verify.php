<?php
require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_POST['user_id'] ?? 0;
if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit();
}

// Update the user
$stmt = $conn->prepare("UPDATE users SET is_email_verified = 1, email_otp = NULL, otp_expires_at = NULL WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User manually verified.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
}
$stmt->close();
?>
