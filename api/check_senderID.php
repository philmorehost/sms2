<?php
// api/check_senderID.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Get parameters from either POST or GET
$sender_id = $_REQUEST['senderID'] ?? '';

if (empty($sender_id)) {
    api_error("Missing required parameter: senderID.", 400, '400');
}

// Query the database for the status
$stmt = $conn->prepare("SELECT status FROM sender_ids WHERE user_id = ? AND sender_id = ?");
$stmt->bind_param("is", $user['id'], $sender_id);
$stmt->execute();
$result = $stmt->get_result();
$sender_id_data = $result->fetch_assoc();
$stmt->close();

if ($sender_id_data) {
    $status = $sender_id_data['status'];
    $message = "The sender ID is currently " . $status . ".";
    if ($status === 'approved') {
        $message = "The sender ID has been approved";
    } elseif ($status === 'rejected') {
        $message = "The sender ID has been rejected. Please contact support.";
    } elseif ($status === 'pending') {
        $message = "The sender ID is pending review.";
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "error_code" => "000",
        "msg" => $message
    ]);
} else {
    api_error("The specified Sender ID was not found for your account.", 404, '404');
}
?>
