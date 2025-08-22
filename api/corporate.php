<?php
// api/corporate.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Corporate route uses POST method as per documentation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error("Invalid request method. Please use POST for the corporate route.", 405, '405');
}

// Get parameters from POST
$sender_id = $_POST['senderID'] ?? '';
$recipients = $_POST['recipients'] ?? '';
$message = $_POST['message'] ?? '';

// Basic validation
if (empty($sender_id) || empty($recipients) || empty($message)) {
    api_error("Missing required parameters: senderID, recipients, and message are required.", 400, '400');
}

// Call the centralized sending function with the 'corporate' route
$result = send_bulk_sms($user, $sender_id, $recipients, $message, 'corporate', $conn);

if ($result['success']) {
    // Re-fetch user to get the updated balance
    $user_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user['id']);
    $user_stmt->execute();
    $updated_user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => $result['message'], // e.g., "Message sent successfully! Cost: ₦10.00"
        "balance" => number_format($updated_user['balance'], 2, '.', '')
    ]);
} else {
    // Map our internal error to the API error format
    $error_code = '401';
    if (strpos($result['message'], 'Insufficient balance') !== false) {
        $error_code = '107';
    }
    api_error($result['message'], 400, $error_code);
}
?>
