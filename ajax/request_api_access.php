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

// Update the user's status to 'requested'
$stmt = $conn->prepare("UPDATE users SET api_access_status = 'requested' WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Optionally, send an email to the admin here to notify them of the new request.
    echo json_encode(['success' => true, 'message' => 'Your request has been submitted for review.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit your request. Please try again.']);
}

$stmt->close();
?>
