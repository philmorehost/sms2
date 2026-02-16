<?php
// api/send_otp.php
header('Content-Type: application/json');
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// API uses POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error("Invalid request method. Please use POST for sending OTP.", 405);
}

// Get & Validate Parameters from POST
$sender_id = $_POST['senderID'] ?? '';
$recipients = $_POST['recipients'] ?? '';
$otp = $_POST['otp'] ?? '';
$template_code = $_POST['templatecode'] ?? '';

if (empty($sender_id) || empty($recipients) || empty($otp) || empty($template_code)) {
    api_error('Missing required parameters: senderID, recipients, otp, and templatecode are required.', 400);
}

// Call the helper function to send the OTP
$result = send_otp($user, $sender_id, $recipients, $otp, $template_code, $conn);

// Return the response from the helper function
if ($result['success']) {
    // Re-fetch user to get the updated balance
    $user_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user['id']);
    $user_stmt->execute();
    $updated_user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => $result['message'],
        'balance' => number_format($updated_user['balance'], 2, '.', '')
    ]);
} else {
    api_error($result['message'], 400);
}
?>
