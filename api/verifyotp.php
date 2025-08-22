<?php
// api/verifyotp.php
header('Content-Type: application/json');
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// API uses POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error("Invalid request method. Please use POST for verifying OTP.", 405);
}

// Get & Validate Parameters from POST
$verification_id = $_POST['verification_id'] ?? '';
$otp = $_POST['otp'] ?? '';

if (empty($verification_id) || empty($otp)) {
    api_error('Missing required parameters: verification_id and otp are required.', 400);
}

// Call the helper function to verify the OTP
$result = verify_otp($user, $verification_id, $otp, $conn);

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
        'data' => $result['data'] ?? [],
        'balance' => number_format($updated_user['balance'], 2, '.', '')
    ]);
} else {
    api_error($result['message'], 400);
}
?>
