<?php
// web/api/support.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $tickets = [];
    $stmt = $conn->prepare("SELECT ticket_id, subject, status, updated_at FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    mobile_api_success(['tickets' => $tickets]);
} elseif ($action === 'view') {
    $ticket_id_str = $_GET['id'] ?? '';
    if (empty($ticket_id_str)) mobile_api_error('Ticket ID required');

    $stmt = $conn->prepare("SELECT id, subject, status FROM support_tickets WHERE ticket_id = ? AND user_id = ?");
    $stmt->bind_param("si", $ticket_id_str, $user['id']);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ticket) mobile_api_error('Ticket not found');

    $messages = [];
    $msg_stmt = $conn->prepare("SELECT message, is_admin_reply, created_at FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
    $msg_stmt->bind_param("i", $ticket['id']);
    $msg_stmt->execute();
    $result = $msg_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    mobile_api_success(['ticket' => $ticket, 'messages' => $messages]);
} elseif ($action === 'create') {
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($subject) || empty($message)) mobile_api_error('Subject and message are required');

    $ticket_id_str = 'TICKET-' . strtoupper(bin2hex(random_bytes(4)));
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, ticket_id, subject, status) VALUES (?, ?, ?, 'user_reply')");
        $stmt->bind_param("iss", $user['id'], $ticket_id_str, $subject);
        $stmt->execute();
        $db_ticket_id = $conn->insert_id;

        $stmt_msg = $conn->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin_reply, message) VALUES (?, ?, 0, ?)");
        $stmt_msg->bind_param("iis", $db_ticket_id, $user['id'], $message);
        $stmt_msg->execute();

        $conn->commit();
        mobile_api_success(['ticket_id' => $ticket_id_str], 'Ticket created successfully');
    } catch (Exception $e) {
        $conn->rollback();
        mobile_api_error('Failed to create ticket');
    }
} elseif ($action === 'reply') {
    $ticket_id_str = $_POST['id'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($ticket_id_str) || empty($message)) mobile_api_error('ID and message required');

    $stmt = $conn->prepare("SELECT id FROM support_tickets WHERE ticket_id = ? AND user_id = ?");
    $stmt->bind_param("si", $ticket_id_str, $user['id']);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    if (!$ticket) mobile_api_error('Ticket not found');

    $stmt_msg = $conn->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin_reply, message) VALUES (?, ?, 0, ?)");
    $stmt_msg->bind_param("iis", $ticket['id'], $user['id'], $message);

    if ($stmt_msg->execute()) {
        $stmt_update = $conn->prepare("UPDATE support_tickets SET status = 'user_reply', updated_at = NOW() WHERE id = ?");
        $stmt_update->bind_param("i", $ticket['id']);
        $stmt_update->execute();
        mobile_api_success([], 'Reply sent');
    } else {
        mobile_api_error('Failed to send reply');
    }
}
?>
