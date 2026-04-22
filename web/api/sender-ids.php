<?php
// web/api/sender-ids.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $sender_ids = [];
    $stmt = $conn->prepare("SELECT id, sender_id, type, status, created_at FROM sender_ids WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sender_ids[] = $row;
    }
    $stmt->close();
    mobile_api_success(['sender_ids' => $sender_ids]);

} elseif ($action === 'request') {
    $sender_id      = trim($_POST['senderID'] ?? '');
    $sample_message = trim($_POST['message'] ?? '');
    $type           = trim($_POST['type'] ?? 'promotional'); // promotional|corporate|airtel|caller
    $company_name   = trim($_POST['company_name'] ?? '');
    $nature_of_business = trim($_POST['nature_of_business'] ?? '');

    if (empty($sender_id) || empty($sample_message)) {
        mobile_api_error('Sender ID and sample message are required');
    }

    if ($type !== 'caller' && strlen($sender_id) > 11) {
        mobile_api_error('Sender ID must be 11 characters or less');
    }

    // Check if already exists for this user
    $check = $conn->prepare("SELECT id FROM sender_ids WHERE user_id = ? AND sender_id = ?");
    $check->bind_param("is", $user['id'], $sender_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        mobile_api_error('You have already submitted this Sender ID');
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO sender_ids (user_id, sender_id, type, sample_message, company_name, nature_of_business, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssss", $user['id'], $sender_id, $type, $sample_message, $company_name, $nature_of_business);

    if ($stmt->execute()) {
        mobile_api_success(['id' => $conn->insert_id], 'Sender ID request submitted for review');
    } else {
        mobile_api_error('Failed to submit request');
    }
    $stmt->close();

} else {
    mobile_api_error('Invalid action');
}
?>
