<?php
// web/api/reports.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'messages';

if ($action === 'messages') {
    $messages = [];
    $stmt = $conn->prepare("SELECT id, sender_id, recipients, message, cost, status, type, created_at FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['cost'] = (float)$row['cost'];
        $messages[] = $row;
    }
    mobile_api_success(['messages' => $messages]);
}
?>
