<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit();
}

// Check if user is logged in
if (!isset($current_user)) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$task_id = $_POST['task_id'] ?? null;

if (empty($task_id)) {
    echo json_encode(['success' => false, 'message' => 'Task ID is missing.']);
    exit();
}

// Admins can cancel any task, users can only cancel their own.
if (is_admin()) {
    $stmt = $conn->prepare("UPDATE scheduled_tasks SET status = 'cancelled' WHERE id = ? AND (status = 'pending' OR status = 'processing')");
    $stmt->bind_param("i", $task_id);
} else {
    $stmt = $conn->prepare("UPDATE scheduled_tasks SET status = 'cancelled' WHERE id = ? AND user_id = ? AND (status = 'pending' OR status = 'processing')");
    $stmt->bind_param("ii", $task_id, $current_user['id']);
}

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Task cancelled successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task could not be cancelled. It may have already been processed or does not exist.']);
    }
} else {
    // Log error for debugging
    error_log("Failed to cancel task: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'An error occurred while cancelling the task.']);
}

$stmt->close();
$conn->close();
?>
