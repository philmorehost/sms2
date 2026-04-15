<?php
// web/api/sender-ids.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $sender_ids = [];
    $stmt = $conn->prepare("SELECT id, sender_id, status, created_at FROM sender_ids WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sender_ids[] = $row;
    }
    $stmt->close();

    mobile_api_success(['sender_ids' => $sender_ids]);
} elseif ($action === 'request') {
    $sender_id = $_POST['senderID'] ?? '';
    $sample_message = $_POST['message'] ?? '';

    if (empty($sender_id) || empty($sample_message)) {
        mobile_api_error('Sender ID and sample message are required');
    }

    if (strlen($sender_id) > 11) {
        mobile_api_error('Sender ID must be 11 characters or less');
    }

    $stmt = $conn->prepare("INSERT INTO sender_ids (user_id, sender_id, sample_message, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iss", $user['id'], $sender_id, $sample_message);

    if ($stmt->execute()) {
        mobile_api_success([], 'Sender ID request submitted');
    } else {
        mobile_api_error('Failed to submit request');
    }
    $stmt->close();
} else {
    mobile_api_error('Invalid action');
}
?>
