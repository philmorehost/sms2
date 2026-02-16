<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    api_error("User not authenticated.", 401);
}

$user_id = $_SESSION['user_id'];
$ticket_id = filter_input(INPUT_GET, 'ticket_id', FILTER_VALIDATE_INT);
$last_message_id = filter_input(INPUT_GET, 'last_message_id', FILTER_VALIDATE_INT);

if (empty($ticket_id) || $last_message_id === false) {
    api_error("Missing required parameters.", 400);
}

// Verify ticket ownership/existence (or admin status)
if (is_admin()) {
    $stmt = $conn->prepare("SELECT id FROM support_tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
} else {
    $stmt = $conn->prepare("SELECT id FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $ticket_id, $user_id);
}
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    api_error("Ticket not found or you do not have permission to view it.", 403);
}

// Fetch new messages
$messages = [];
$stmt_msgs = $conn->prepare("
    SELECT tm.*, u.username
    FROM ticket_messages tm
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE tm.ticket_id = ? AND tm.id > ?
    ORDER BY tm.created_at ASC
");
$stmt_msgs->bind_param("ii", $ticket['id'], $last_message_id);
$stmt_msgs->execute();
$result_msgs = $stmt_msgs->get_result();
while($row = $result_msgs->fetch_assoc()) {
    $messages[] = $row;
}
$stmt_msgs->close();

echo json_encode(['success' => true, 'messages' => $messages]);
?>
