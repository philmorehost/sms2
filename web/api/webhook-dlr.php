<?php
/**
 * PhilmoreSMS Delivery Report (DLR) Webhook
 * Handles delivery status updates from SMS gateways (KudiSMS, RouteMobile, etc.)
 * and implements automatic refunds for failed messages.
 */

require_once __DIR__ . '/bootstrap.php';

// Log the incoming DLR for debugging
$raw_data = file_get_contents('php://input');
$log_entry = date('Y-m-d H:i:s') . " | DLR Received | GET: " . json_encode($_GET) . " | POST: " . json_encode($_POST) . " | RAW: " . $raw_data . "\n";
file_put_contents(__DIR__ . '/../../app/logs/dlr.log', $log_entry, FILE_APPEND);

// Normalize input
$data = array_merge($_GET, $_POST);

// Extract fields (Map common gateway field names)
$status = $data['status'] ?? $data['dlv'] ?? '';
$api_msg_id = $data['msgid'] ?? $data['msg_id'] ?? $data['id'] ?? '';
$recipient = $data['recipient'] ?? $data['dest'] ?? $data['destination'] ?? $data['to'] ?? '';
$ref = $data['ref'] ?? $data['reference'] ?? '';

if (empty($api_msg_id) && empty($ref) && empty($recipient)) {
    http_response_code(400);
    die("Invalid DLR payload");
}

// 1. Identify the recipient record
// We prioritize matching by api_message_id and recipient_number
$recipient_number = filter_phone_numbers($recipient)[0] ?? '';

$query = "SELECT mr.*, m.user_id, m.cost, m.recipients as all_recipients 
          FROM message_recipients mr 
          JOIN messages m ON mr.message_id = m.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($api_msg_id)) {
    $query .= " AND mr.api_message_id = ?";
    $params[] = $api_msg_id;
    $types .= "s";
}

if (!empty($recipient_number)) {
    $query .= " AND mr.recipient_number LIKE ?";
    $params[] = "%" . substr($recipient_number, -10); // Match last 10 digits to be safe with prefixes
    $types .= "s";
}

if (!empty($ref)) {
    $query .= " AND mr.message_id = ?";
    $params[] = $ref;
    $types .= "i";
}

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$recipient_record = $res->fetch_assoc();
$stmt->close();

if (!$recipient_record) {
    error_log("DLR Error: Could not find recipient record for ID: $api_msg_id, Ref: $ref, Number: $recipient");
    mobile_api_error("Record not found", 404);
}

$message_id = $recipient_record['message_id'];
$user_id = $recipient_record['user_id'];
$total_recipients = count(explode(',', $recipient_record['all_recipients']));
$cost_per_recipient = $recipient_record['cost'] / ($total_recipients ?: 1);

// 2. Update the status
$normalized_status = strtolower($status);
$db_status = 'Delivered';
$is_failed = false;

// Common failure statuses across gateways
$failed_statuses = ['failed', 'rejected', 'undeliverable', 'expired', 'deleted', 'undeliv', 'reject', '2', 'rejected_by_operator'];
$success_statuses = ['delivered', 'delivrd', 'success', '1', 'delivered_to_terminal'];

if (in_array($normalized_status, $failed_statuses) || (is_numeric($status) && $status > 1 && $status != 1701)) {
    $db_status = 'Failed';
    $is_failed = true;
} elseif (in_array($normalized_status, $success_statuses)) {
    $db_status = 'Delivered';
} else {
    $db_status = 'Sent'; // Keep as sent if unknown
}

// Update recipient status
$stmt_update = $conn->prepare("UPDATE message_recipients SET status = ?, failure_reason = ? WHERE id = ?");
$reason = $is_failed ? "Gateway status: " . $status : null;
$stmt_update->bind_param("ssi", $db_status, $reason, $recipient_record['id']);
$stmt_update->execute();
$stmt_update->close();

// 3. Process Refund if failed
if ($is_failed && $recipient_record['status'] !== 'Failed') { // Prevent double refund
    $conn->begin_transaction();
    try {
        // Credit the user
        $stmt_refund = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt_refund->bind_param("di", $cost_per_recipient, $user_id);
        $stmt_refund->execute();
        $stmt_refund->close();

        // Log transaction
        $ref_str = "REFUND-MSG-" . $message_id . "-" . $recipient_record['id'];
        $desc = "Refund for failed SMS to " . $recipient_record['recipient_number'];
        $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, reference, type, amount, status, description) VALUES (?, ?, 'refund', ?, 'completed', ?)");
        $stmt_trans->bind_param("isds", $user_id, $ref_str, $cost_per_recipient, $desc);
        $stmt_trans->execute();
        $stmt_trans->close();

        $conn->commit();
        error_log("DLR Refund: Processed " . get_currency_symbol() . "$cost_per_recipient refund for User ID: $user_id (Message ID: $message_id)");
    } catch (Exception $e) {
        $conn->rollback();
        error_log("DLR Refund Error: " . $e->getMessage());
    }
}

mobile_api_success(['dlr_status' => $db_status], "DLR processed");
