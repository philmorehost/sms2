<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit();
}

$draft_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$draft_id) {
    $response['message'] = 'Invalid Draft ID.';
    echo json_encode($response);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM sms_drafts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $draft_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$draft = $result->fetch_assoc();
$stmt->close();

if ($draft) {
    $response['success'] = true;
    $response['message'] = 'Draft loaded successfully.';
    $response['draft'] = $draft;
} else {
    $response['message'] = 'Draft not found or you do not have permission to access it.';
}

echo json_encode($response);
exit();
?>
