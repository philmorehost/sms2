<?php
// web/api/reports.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'messages';

if ($action === 'messages') {
    $type = $_GET['type'] ?? 'all';
    $messages = [];
    $sql = "SELECT id, sender_id, recipients, message, cost, status, type, created_at FROM messages WHERE user_id = ?";
    if ($type !== 'all') $sql .= " AND type = '" . $conn->real_escape_string($type) . "'";
    $sql .= " ORDER BY created_at DESC LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['cost'] = (float)$row['cost'];
        $messages[] = $row;
    }
    mobile_api_success(['messages' => $messages]);
} elseif ($action === 'transactions') {
    $transactions = [];
    $stmt = $conn->prepare("SELECT id, reference, type, amount, total_amount, status, gateway, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['amount'] = (float)$row['amount'];
        $row['total_amount'] = (float)$row['total_amount'];
        $transactions[] = $row;
    }
    mobile_api_success(['transactions' => $transactions]);
}
?>
