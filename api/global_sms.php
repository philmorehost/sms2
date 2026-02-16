<?php
// api/global_sms.php

// Bootstrap the API
require_once __DIR__ . '/bootstrap.php';

// --- Validation ---
if (!isset($_POST['sender_id']) || !isset($_POST['recipients']) || !isset($_POST['message'])) {
    json_response(false, 'Missing required parameters: sender_id, recipients, message.');
}

$sender_id = trim($_POST['sender_id']);
$recipients = trim($_POST['recipients']);
$message = trim($_POST['message']);
$route = 'global'; // This is the key difference

// --- API Logic ---
// The send_bulk_sms function from helpers.php already handles all the logic,
// including checking the global wallet, calculating costs, and sending the message.
$result = send_bulk_sms($user, $sender_id, $recipients, $message, $route, $conn);

if ($result['success']) {
    json_response(true, $result['message'], $result['data'] ?? []);
} else {
    json_response(false, $result['message'], $result['data'] ?? []);
}
