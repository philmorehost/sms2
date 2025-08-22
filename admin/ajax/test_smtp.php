<?php
require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$settings = get_settings();
$admin_email = $settings['admin_email'] ?? null;

if (!$admin_email) {
    echo json_encode(['success' => false, 'message' => 'Admin email is not set in General Settings.']);
    exit();
}

$subject = "SMTP Test Email from " . SITE_NAME;
$body = "<p>This is a test email to confirm your SMTP settings are configured correctly.</p><p>If you have received this, your email system is working!</p>";

$result = send_email($admin_email, $subject, $body);

echo json_encode($result);
?>
