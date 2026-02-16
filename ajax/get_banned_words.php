<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$banned_words = [];
$stmt = $conn->prepare("SELECT `value` FROM `banned` WHERE `type` = 'word'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Add the word to the list, converting to lowercase for case-insensitive matching on client-side
    $banned_words[] = strtolower($row['value']);
}
$stmt->close();

echo json_encode(['success' => true, 'words' => $banned_words]);
?>
