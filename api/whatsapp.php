<?php
header('Content-Type: application/json');
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// WhatsApp API uses POST as per documentation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error("Invalid request method. Please use POST for the WhatsApp API.", 405);
}

// Get & Validate Parameters from POST
$recipient = $_POST['recipient'] ?? '';
$template_code = $_POST['template_code'] ?? '';
$parameters = $_POST['parameters'] ?? ''; // e.g., 'John,AB-123'
$button_parameters = $_POST['button_parameters'] ?? ''; // e.g., '12345'
$header_parameters = $_POST['header_parameters'] ?? ''; // e.g., 'image_url.jpg'


if (empty($recipient) || empty($template_code)) {
    api_error('Missing required parameters: recipient and template_code are required.', 400);
}

// Call the helper function to send the WhatsApp message
$result = send_whatsapp_message($user, $recipient, $template_code, $parameters, $button_parameters, $header_parameters, $conn);

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
