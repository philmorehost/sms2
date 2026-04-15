<?php
// web/api/dashboard.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);

// 1. Messages Sent
$msg_sent_stmt = $conn->prepare("SELECT COUNT(id) as count FROM messages WHERE user_id = ? AND status = 'success'");
$msg_sent_stmt->bind_param("i", $user['id']);
$msg_sent_stmt->execute();
$messages_sent_count = $msg_sent_stmt->get_result()->fetch_assoc()['count'];
$msg_sent_stmt->close();

// 2. Recent Transactions
$recent_transactions = [];
$trans_stmt = $conn->prepare("SELECT id, created_at, description, amount, status FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$trans_stmt->bind_param("i", $user['id']);
$trans_stmt->execute();
$result = $trans_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['amount'] = (float)$row['amount'];
    $recent_transactions[] = $row;
}
$trans_stmt->close();

mobile_api_success([
    'stats' => [
        'messages_sent' => (int)$messages_sent_count,
        'balance' => (float)$user['balance']
    ],
    'recent_transactions' => $recent_transactions
]);
?>
