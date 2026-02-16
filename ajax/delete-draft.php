<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit();
}

$draft_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$draft_id) {
    $response['message'] = 'Invalid Draft ID.';
    echo json_encode($response);
    exit();
}

$stmt = $conn->prepare("DELETE FROM sms_drafts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $draft_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Draft deleted successfully.';
    } else {
        $response['message'] = 'Draft not found or you do not have permission to delete it.';
    }
} else {
    $response['message'] = 'Database error during deletion.';
}

$stmt->close();
echo json_encode($response);
exit();
?>
