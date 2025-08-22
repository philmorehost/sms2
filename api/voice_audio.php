<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/helpers.php';

header('Content-Type: application/json');

// Get POST data
$token = $_POST['token'] ?? '';
$caller_id = $_POST['callerID'] ?? '';
$recipients = $_POST['recipients'] ?? '';
$audio_url = $_POST['audio'] ?? '';

// Find user by API key
$stmt = $conn->prepare("SELECT * FROM users WHERE api_key = ? AND api_access_status = 'approved'");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid API key or access denied.']);
    exit();
}

// Call the helper function
$result = send_voice_audio_api($user, $caller_id, $recipients, $audio_url, $conn);

echo json_encode($result);
?>
