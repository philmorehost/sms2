<?php
// api/balance.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// If we get here, the user is authenticated.
http_response_code(200);
echo json_encode([
    "status" => "success",
    "error_code" => "000",
    "balance" => number_format($user['balance'], 2, '.', '') // Return as a string without thousands separator
]);
?>
