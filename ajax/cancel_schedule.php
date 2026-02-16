<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$task_ids = [];

if (isset($_POST['task_ids'])) {
    $decoded_ids = json_decode($_POST['task_ids'], true);
    if (is_array($decoded_ids)) {
        foreach ($decoded_ids as $id) {
            $task_ids[] = (int)$id;
        }
    }
} elseif (isset($_POST['task_id'])) {
    // Handle single cancellation for backward compatibility or other uses
    $task_ids[] = (int)$_POST['task_id'];
}

if (empty($task_ids)) {
    echo json_encode(['success' => false, 'message' => 'No task IDs provided.']);
    exit();
}

// Create placeholders for the IN clause
$placeholders = implode(',', array_fill(0, count($task_ids), '?'));
$types = str_repeat('i', count($task_ids));

// We also check that the task belongs to the current user for security
$sql = "UPDATE scheduled_tasks SET status = 'cancelled' WHERE id IN ($placeholders) AND user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);

// Bind parameters
$params = array_merge($task_ids, [$user_id]);
$stmt->bind_param($types . 'i', ...$params);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    if ($affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => "$affected_rows task(s) cancelled successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not cancel tasks. They might have already been processed or do not belong to you.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update tasks. Please try again.']);
}

$stmt->close();
?>
