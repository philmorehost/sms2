<?php
// web/api/messaging.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);

$action = $_GET['action'] ?? '';

if ($action === 'send_sms') {
    $sender_id = $_POST['senderID'] ?? '';
    $recipients = $_POST['recipients'] ?? '';
    $message = $_POST['message'] ?? '';
    $route = $_POST['route'] ?? 'promotional'; // promotional, corporate, global

    if (empty($sender_id) || empty($recipients) || empty($message)) {
        mobile_api_error('Missing required parameters');
    }

    $result = send_bulk_sms($user, $sender_id, $recipients, $message, $route, $conn);

    if ($result['success']) {
        mobile_api_success(['result' => $result], $result['message']);
    } else {
        mobile_api_error($result['message']);
    }
} elseif ($action === 'send_voice_audio') {
    $caller_id = $_POST['callerID'] ?? '';
    $recipients = $_POST['recipients'] ?? '';
    $audio_url = $_POST['audio'] ?? '';

    if (empty($caller_id) || empty($recipients) || empty($audio_url)) {
        mobile_api_error('Missing required parameters');
    }

    $result = send_voice_audio_api($user, $caller_id, $recipients, $audio_url, $conn);

    if ($result['success']) {
        mobile_api_success(['result' => $result], $result['message']);
    } else {
        mobile_api_error($result['message']);
    }
} elseif ($action === 'send_voice') {
    $caller_id = $_POST['callerID'] ?? '';
    $recipients = $_POST['recipients'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($caller_id) || empty($recipients) || empty($message)) {
        mobile_api_error('Missing required parameters');
    }

    $result = send_voice_tts($user, $caller_id, $recipients, $message, $conn);

    if ($result['success']) {
        mobile_api_success(['result' => $result], $result['message']);
    } else {
        mobile_api_error($result['message']);
    }
} else {
    mobile_api_error('Invalid action');
}
?>
