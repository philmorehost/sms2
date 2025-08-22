<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!isset($current_user)) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['recipients']) || !isset($_POST['route'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$recipients = trim($_POST['recipients']);
$route = $_POST['route'];

if (empty($recipients)) {
    echo json_encode(['success' => true, 'cost' => '0.00', 'recipient_count' => 0]);
    exit();
}

// Get the correct price from settings based on the route
$settings = get_settings();
$price_per_sms = 0.0;
if ($route === 'corporate') {
    $price_per_sms = (float)($settings['price_sms_corp'] ?? 0.0);
} else {
    // Default to promotional
    $price_per_sms = (float)($settings['price_sms_promo'] ?? 0.0);
}


// Split recipients by commas, spaces, or newlines
$recipient_numbers = preg_split('/[\s,;\n]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY);
$recipient_count = count($recipient_numbers);

$total_cost = $recipient_count * $price_per_sms;

echo json_encode(['success' => true, 'cost' => number_format($total_cost, 2), 'recipient_count' => $recipient_count]);
?>
