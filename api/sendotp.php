<?php
// api/sendotp.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Collect all parameters
$params = [
    'token' => $user['api_key'], // We use our key for the external request
    'senderID' => $_POST['senderID'] ?? '',
    'recipients' => $_POST['recipients'] ?? '',
    'appnamecode' => $_POST['appnamecode'] ?? '',
    'templatecode' => $_POST['templatecode'] ?? '',
    'otp_type' => $_POST['otp_type'] ?? 'NUMERIC',
    'otp_length' => $_POST['otp_length'] ?? '6',
    'otp_duration' => $_POST['otp_duration'] ?? '5',
    'otp_attempts' => $_POST['otp_attempts'] ?? '1',
    'channel' => $_POST['channel'] ?? 'sms',
];

// Basic validation
if (empty($params['senderID']) || empty($params['recipients']) || empty($params['appnamecode']) || empty($params['templatecode'])) {
    api_error("Missing required parameters.", 400, '400');
}

// In a real app, you would add more validation here:
// - Check if the user's balance is sufficient (cost is returned by API, so this is tricky. Maybe a pre-flight check?)
// - Check if the templatecode and appnamecode belong to the user.

// Call external API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://my.kudisms.net/api/sendotp');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    api_error("cURL Error: " . curl_error($ch), 500, '500');
}

$api_result = json_decode($response, true);

if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
    $cost = $api_result['cost'] ?? 0;

    // Debit user and log transaction
    $conn->begin_transaction();
    try {
        $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_balance->bind_param("di", $cost, $user['id']);
        $stmt_balance->execute();

        // We need a table to store OTP verification details, including the verification_id.
        // For now, we'll log it in the generic messages table.
        $message_summary = "OTP sent via API. Verification ID: " . ($api_result['verification_id'] ?? 'N/A');
        $stmt_log = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, type, api_response) VALUES (?, ?, ?, ?, ?, 'success', 'sms_debit', ?)");
        $stmt_log->bind_param("isssds", $user['id'], $params['senderID'], $params['recipients'], $message_summary, $cost, $response);
        $stmt_log->execute();

        $conn->commit();
        http_response_code(200);
        echo $response; // Return the exact response from the gateway
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        api_error("Internal server error during transaction.", 500, '500');
    }
} else {
    $error_msg = $api_result['msg'] ?? 'An unknown error occurred with the OTP gateway.';
    api_error($error_msg, 400, '400');
}
?>
