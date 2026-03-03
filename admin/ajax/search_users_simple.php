<?php
require_once '../../app/bootstrap.php';

// Authorize administrator
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$query = $_GET['query'] ?? '';
$users = [];

if (strlen($query) >= 2) {
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username LIKE ? OR email LIKE ? LIMIT 10");
    $searchTerm = "%$query%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'users' => $users]);
