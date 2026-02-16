<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['results']) || !isset($_POST['tool_name'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$results_content = $_POST['results'];
$tool_name = $_POST['tool_name'];
$user_id = $_SESSION['user_id'];

// Get user email
$user_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_email = $user_stmt->get_result()->fetch_assoc()['email'];
$user_stmt->close();

if (!$user_email) {
    echo json_encode(['success' => false, 'message' => 'Could not find your email address.']);
    exit();
}

// Get admin email
$admin_email = get_admin_email();

// Prepare email details
$subject = "Results from " . $tool_name;
$filename = strtolower(str_replace(' ', '_', $tool_name)) . '_results.txt';
$from_name = SITE_NAME;
// In a real app, the from_email should be a no-reply address from your domain
$from_email = 'noreply@' . strtolower(str_replace(' ', '', SITE_NAME)) . '.com';

// Send to user
$user_message = "<p>Hello,</p><p>Attached are the results you requested from the " . $tool_name . " tool.</p><p>Thank you for using our service.</p>";
$user_sent = send_email_with_attachment($user_email, $subject, $user_message, $from_email, $from_name, $results_content, $filename);

// Send to admin
$admin_message = "<p>Hello Admin,</p><p>A user (" . $user_email . ") has used the " . $tool_name . " tool. A copy of their results is attached for your records.</p>";
$admin_sent = send_email_with_attachment($admin_email, $subject, $admin_message, $from_email, $from_name, $results_content, $filename);


if ($user_sent) {
    echo json_encode(['success' => true, 'message' => 'Email sent successfully.']);
} else {
    // Don't expose server-side email failure details to the client.
    // Log this error in a real application.
    echo json_encode(['success' => false, 'message' => 'There was an issue sending the email.']);
}
?>
