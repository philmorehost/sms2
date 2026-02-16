<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/helpers.php';

header('Content-Type: application/json');

// Must be a logged-in user or an admin to proceed
if (!isset($_SESSION['user_id']) && !is_admin()) {
    api_error("User not authenticated.", 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error("Invalid request method.", 405);
}

// Use the appropriate session ID
$user_id = is_admin() ? $_SESSION['admin_id'] : $_SESSION['user_id'];
$ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
$message = trim($_POST['message'] ?? '');
$is_admin_reply = is_admin() ? 1 : 0;

if (empty($ticket_id) || empty($message)) {
    api_error("Missing required parameters.", 400);
}

// Verify ticket ownership/existence (or admin status)
if ($is_admin_reply) {
    $stmt = $conn->prepare("SELECT id, status FROM support_tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
} else {
    $stmt = $conn->prepare("SELECT id, status FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $ticket_id, $user_id);
}
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket || $ticket['status'] === 'closed') {
    api_error("Ticket not found, does not belong to you, or is closed.", 403);
}

// All good, insert the message and update the ticket
$conn->begin_transaction();
try {
    // 1. Add the new message
    $stmt_msg = $conn->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin_reply, message) VALUES (?, ?, ?, ?)");
    $stmt_msg->bind_param("iiis", $ticket['id'], $user_id, $is_admin_reply, $message);
    $stmt_msg->execute();
    $new_message_id = $stmt_msg->insert_id;
    $stmt_msg->close();

    // 2. Update the ticket status
    $new_status = $is_admin_reply ? 'admin_reply' : 'user_reply';
    $stmt_ticket = $conn->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt_ticket->bind_param("si", $new_status, $ticket['id']);
    $stmt_ticket->execute();
    $stmt_ticket->close();

    $conn->commit();

    // Fetch the message we just inserted to return it
    $new_msg_stmt = $conn->prepare("SELECT tm.*, u.username FROM ticket_messages tm LEFT JOIN users u ON tm.user_id = u.id WHERE tm.id = ?");
    $new_msg_stmt->bind_param("i", $new_message_id);
    $new_msg_stmt->execute();
    $new_message_data = $new_msg_stmt->get_result()->fetch_assoc();

    echo json_encode(['success' => true, 'message' => $new_message_data]);

} catch (Exception $e) {
    $conn->rollback();
    api_error("An error occurred while sending your reply.", 500);
}
?>
