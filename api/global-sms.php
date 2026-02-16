<?php
// api/global-sms.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// This endpoint uses POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error("Invalid request method. Please use POST for the Global SMS API.", 405, '405');
}

// Get parameters from POST
$sender_id = $_POST['senderID'] ?? '';
$recipients = $_POST['recipients'] ?? '';
$message = $_POST['message'] ?? '';

// Basic validation
if (empty($sender_id) || empty($recipients) || empty($message)) {
    api_error("Missing required parameters: senderID, recipients, and message are required.", 400, '400');
}

// Call the centralized sending function with the 'global' route
$result = send_bulk_sms($user, $sender_id, $recipients, $message, 'global', $conn);

if ($result['success']) {
    // Re-fetch user's global wallet to get the updated balance
    $wallet_stmt = $conn->prepare("SELECT balance FROM global_wallets WHERE user_id = ?");
    $wallet_stmt->bind_param("i", $user['id']);
    $wallet_stmt->execute();
    $updated_wallet = $wallet_stmt->get_result()->fetch_assoc();
    $wallet_stmt->close();

    $settings = get_settings();
    $global_wallet_currency = $settings['global_wallet_currency'] ?? 'EUR';

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "error_code" => "000",
        "message" => $result['message'],
        "balance" => number_format($updated_wallet['balance'], 2, '.', ''),
        "currency" => $global_wallet_currency
    ]);
} else {
    // Map our internal error to the API error format
    // Extract the error code from the message if possible, otherwise use a generic one
    $error_code = '401'; // Generic error
    if (strpos($result['message'], 'Insufficient global wallet balance') !== false) {
        $error_code = '108'; // New custom code for global wallet
    } else if (strpos($result['message'], 'Could not find a price') !== false) {
        $error_code = '109'; // New custom code for pricing not found
    } else if (isset($result['data']['error_code'])) {
        $error_code = $result['data']['error_code'];
    }

    api_error($result['message'], 400, $error_code);
}
?>
