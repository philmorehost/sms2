<?php
header('Content-Type: application/json');
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Voice API uses GET as per documentation
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error("Invalid request method. Please use GET for the Voice API.", 405);
}

// Get & Validate Parameters from GET
$caller_id = $_GET['callerID'] ?? '';
$recipients = $_GET['recipients'] ?? '';
$message = $_GET['message'] ?? '';

if (empty($caller_id) || empty($recipients) || empty($message)) {
    api_error('Missing required parameters: callerID, recipients, and message are required.', 400);
}

// Call the helper function to send the voice message
$result = send_voice_tts($user, $caller_id, $recipients, $message, $conn);

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
