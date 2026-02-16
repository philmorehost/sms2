<?php
// api/senderID.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Get parameters from POST
$sender_id = $_POST['senderID'] ?? '';
$sample_message = $_POST['message'] ?? '';

// Validation
if (empty($sender_id) || empty($sample_message)) {
    api_error("Missing required parameters: senderID and message are required.", 400, '400');
}
if (strlen($sender_id) > 11) {
    api_error("Sender ID must not be more than 11 characters.", 400, '400');
}

// Check if this sender ID already exists for the user
$stmt_check = $conn->prepare("SELECT id FROM sender_ids WHERE user_id = ? AND sender_id = ?");
$stmt_check->bind_param("is", $user['id'], $sender_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    api_error("You have already registered this Sender ID.", 400, '400');
}
$stmt_check->close();

// Insert into the database
$stmt = $conn->prepare("INSERT INTO sender_ids (user_id, sender_id, sample_message, status) VALUES (?, ?, ?, 'pending')");
$stmt->bind_param("iss", $user['id'], $sender_id, $sample_message);

if ($stmt->execute()) {
    // Notify admin about the new sender ID request
    $admin_email = get_admin_email();
    $subject = "New Sender ID Submission for Review";
    $message = "<p>A new Sender ID has been submitted for approval:</p><ul>" .
               "<li>User: " . htmlspecialchars($user['username']) . "</li>" .
               "<li>Sender ID: " . htmlspecialchars($sender_id) . "</li>" .
               "<li>Sample Message: " . htmlspecialchars($sample_message) . "</li></ul>" .
               "<p>You can approve or deny it in the admin panel.</p>";
    send_email($admin_email, $subject, $message);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "error_code" => "000",
        "msg" => "Promotional sender ID submitted successfully"
    ]);
} else {
    api_error("Failed to submit Sender ID. Please try again.", 500, '500');
}

$stmt->close();
?>
