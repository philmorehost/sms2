<?php
// Suppress any stray warnings from outputting and breaking the JSON response.
error_reporting(0);
@ini_set('display_errors', 0);

require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

// Check if user is an admin
if (!isset($current_user) || !$current_user['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin_email = get_admin_email();
$subject = "SMTP Test Email from " . SITE_NAME;
$message = "<p>This is a test email to confirm your SMTP settings are working correctly.</p>" .
           "<p>If you have received this, congratulations!</p>";

// The send_email function now uses the settings from the database automatically.
$result = send_email($admin_email, $subject, $message);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Test email sent successfully to ' . $admin_email]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send test email. Reason: ' . ($result['message'] ?? 'Unknown error.')]);
}
?>
