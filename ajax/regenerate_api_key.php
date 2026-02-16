<?php
require_once '../app/bootstrap.php';

header('Content-Type: application/json');

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is approved for API access
$stmt = $conn->prepare("SELECT api_access_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user['api_access_status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'API access is not approved.']);
    exit();
}

// Generate a new API key
$new_api_key = bin2hex(random_bytes(32));

// Update the user's API key in the database
$stmt = $conn->prepare("UPDATE users SET api_key = ? WHERE id = ?");
$stmt->bind_param("si", $new_api_key, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'api_key' => $new_api_key]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update API key in the database.']);
}

$stmt->close();
$conn->close();
?>
