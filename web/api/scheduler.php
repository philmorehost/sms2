<?php
// web/api/scheduler.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'list_birthdays';

if ($action === 'list_birthdays') {
    $birthdays = [];
    $stmt = $conn->prepare("SELECT id, name, phone_number, date_of_birth FROM birthday_scheduler WHERE user_id = ? ORDER BY date_of_birth ASC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) $birthdays[] = $row;
    mobile_api_success(['birthdays' => $birthdays]);
} elseif ($action === 'list_schedules') {
    $schedules = [];
    $stmt = $conn->prepare("SELECT id, task_type, scheduled_for, status FROM scheduled_tasks WHERE user_id = ? AND status = 'pending' ORDER BY scheduled_for ASC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) $schedules[] = $row;
    mobile_api_success(['schedules' => $schedules]);
}
?>
