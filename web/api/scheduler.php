<?php
// web/api/scheduler.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'list_schedules';

if ($action === 'list_schedules') {
    $schedules = [];
    $stmt = $conn->prepare("SELECT id, task_type, scheduled_for, status FROM scheduled_tasks WHERE user_id = ? AND status = 'pending' ORDER BY scheduled_for ASC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    mobile_api_success(['schedules' => $schedules]);

} elseif ($action === 'schedule_sms') {
    $sender_id    = $_POST['senderID'] ?? '';
    $recipients   = $_POST['recipients'] ?? '';
    $message      = $_POST['message'] ?? '';
    $schedule_time = $_POST['schedule_time'] ?? '';
    $route        = $_POST['route'] ?? 'promotional';

    if (empty($sender_id) || empty($recipients) || empty($message) || empty($schedule_time)) {
        mobile_api_error('All fields are required');
    }

    // Validate date format
    $ts = strtotime($schedule_time);
    if (!$ts || $ts <= time()) {
        mobile_api_error('Schedule time must be a valid future date (YYYY-MM-DD HH:MM:SS)');
    }

    $task_data = json_encode([
        'senderID'   => $sender_id,
        'recipients' => $recipients,
        'message'    => $message,
        'route'      => $route
    ]);

    $stmt = $conn->prepare("INSERT INTO scheduled_tasks (user_id, task_type, task_data, scheduled_for, status) VALUES (?, 'sms', ?, ?, 'pending')");
    $stmt->bind_param("iss", $user['id'], $task_data, $schedule_time);
    if ($stmt->execute()) {
        mobile_api_success(['schedule_id' => $conn->insert_id], 'SMS scheduled successfully');
    } else {
        mobile_api_error('Failed to schedule SMS');
    }

} elseif ($action === 'cancel_schedule') {
    $schedule_id = (int)$_POST['id'];
    if ($schedule_id <= 0) mobile_api_error('Valid schedule ID required');

    $stmt = $conn->prepare("UPDATE scheduled_tasks SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $schedule_id, $user['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        mobile_api_success([], 'Schedule cancelled');
    } else {
        mobile_api_error('Schedule not found or already processed');
    }

} elseif ($action === 'list_birthdays') {
    $birthdays = [];
    $stmt = $conn->prepare("SELECT id, name, phone_number, date_of_birth FROM birthday_scheduler WHERE user_id = ? ORDER BY date_of_birth ASC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $birthdays[] = $row;
    }
    mobile_api_success(['birthdays' => $birthdays]);

} elseif ($action === 'add_birthday') {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob   = trim($_POST['dob'] ?? '');

    if (empty($name) || empty($phone) || empty($dob)) {
        mobile_api_error('Name, phone and date of birth are required');
    }

    $stmt = $conn->prepare("INSERT INTO birthday_scheduler (user_id, name, phone_number, date_of_birth) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user['id'], $name, $phone, $dob);
    if ($stmt->execute()) {
        mobile_api_success(['id' => $conn->insert_id], 'Birthday contact added');
    } else {
        mobile_api_error('Failed to add birthday contact');
    }

} elseif ($action === 'delete_birthday') {
    $id = (int)$_POST['id'];
    if ($id <= 0) mobile_api_error('Valid ID required');

    $stmt = $conn->prepare("DELETE FROM birthday_scheduler WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        mobile_api_success([], 'Birthday contact deleted');
    } else {
        mobile_api_error('Entry not found');
    }

} else {
    mobile_api_error('Invalid action');
}
?>
