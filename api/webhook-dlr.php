<?php
// This is a public endpoint for receiving delivery reports (DLR) from Kudisms.
// It should not include the standard user authentication bootstrap.

// Include only the necessary configuration and database connection.
require_once __DIR__ . '/../app/config.php';

// --- Database Connection ---
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    error_log("DLR Webhook: Database Connection Failed: " . $conn->connect_error);
    exit("Database connection error.");
}

// Get the raw POST data
$json_payload = file_get_contents('php://input');

// For debugging, log the raw payload
$log_dir = __DIR__ . '/../logs/';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . 'dlr_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " --- Payload: " . $json_payload . "\n\n", FILE_APPEND);

if (empty($json_payload)) {
    http_response_code(400);
    exit("Empty payload.");
}

$data = json_decode($json_payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit("Invalid JSON payload.");
}

// --- Process the DLR data ---
$api_message_id = $data['id'] ?? null;
$status = strtolower($data['status'] ?? ''); // e.g., delivered, failed, sent
$failure_reason = $data['reason'] ?? null;

if (!$api_message_id || !$status) {
    http_response_code(400);
    exit("Missing required parameters 'id' or 'status'.");
}

$conn->begin_transaction();
try {
    // 1. Find the recipient by the API message ID
    $stmt_find = $conn->prepare("SELECT id, message_id, status FROM message_recipients WHERE api_message_id = ?");
    $stmt_find->bind_param("s", $api_message_id);
    $stmt_find->execute();
    $recipient = $stmt_find->get_result()->fetch_assoc();
    $stmt_find->close();

    if (!$recipient) {
        throw new Exception("No matching message found for ID: " . htmlspecialchars($api_message_id), 404);
    }

    // Prevent processing the same DLR twice (e.g., from 'sent' to 'delivered')
    if ($recipient['status'] === 'Delivered' || $recipient['status'] === 'Failed') {
         throw new Exception("DLR for message ID " . htmlspecialchars($api_message_id) . " has already been processed.", 200);
    }

    // 2. Update the recipient's status
    $stmt_update = $conn->prepare("UPDATE message_recipients SET status = ?, failure_reason = ? WHERE id = ?");
    $new_status_display = ucfirst($status); // 'Failed', 'Delivered', etc.
    $stmt_update->bind_param("ssi", $new_status_display, $failure_reason, $recipient['id']);
    $stmt_update->execute();
    $stmt_update->close();

    // 3. If failed, and not already refunded, process a refund
    if ($status === 'failed') {
        // Get message details to find user and cost
        $stmt_msg = $conn->prepare(
            "SELECT m.user_id, m.cost, (SELECT COUNT(*) FROM message_recipients WHERE message_id = m.id) as recipient_count
             FROM messages m WHERE m.id = ?"
        );
        $stmt_msg->bind_param("i", $recipient['message_id']);
        $stmt_msg->execute();
        $message_details = $stmt_msg->get_result()->fetch_assoc();
        $stmt_msg->close();

        if ($message_details && $message_details['recipient_count'] > 0) {
            $user_id = $message_details['user_id'];
            $cost_per_recipient = (float)$message_details['cost'] / (int)$message_details['recipient_count'];
            $refund_amount = round($cost_per_recipient, 2);

            if ($refund_amount > 0) {
                // Add balance back to user
                $stmt_refund = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt_refund->bind_param("di", $refund_amount, $user_id);
                $stmt_refund->execute();
                $stmt_refund->close();

                // Log the refund as a transaction for auditing
                $stmt_trans = $conn->prepare(
                    "INSERT INTO transactions (user_id, type, amount, total_amount, status, gateway, description)
                     VALUES (?, 'refund', ?, ?, 'completed', 'system', ?)"
                );
                $description = "Refund for failed message to recipient in batch #" . $recipient['message_id'];
                $stmt_trans->bind_param("idds", $user_id, $refund_amount, $refund_amount, $description);
                $stmt_trans->execute();
                $stmt_trans->close();
            }
        }
    }

    $conn->commit();
    http_response_code(200);
    echo "DLR processed successfully.";

} catch (Exception $e) {
    $conn->rollback();
    $error_code = $e->getCode() ?: 500;
    http_response_code($error_code);
    error_log("DLR Webhook Error: " . $e->getMessage());
    echo $e->getMessage();
}

$conn->close();

?>
