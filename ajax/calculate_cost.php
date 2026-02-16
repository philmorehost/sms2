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
$recipient_numbers = preg_split('/[\s,;\n]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY);
$recipient_count = count($recipient_numbers);
$total_cost = 0;

if ($route === 'global') {
    $global_profit_margin = (float)($settings['global_profit_percentage'] ?? 0.0);
    $user_profit_margin = (float)($current_user['profit_percentage'] ?? 0.0);

    foreach ($recipient_numbers as $number) {
        $base_price = get_global_sms_price_for_number($number, $conn);
        if ($base_price === null) {
            echo json_encode(['success' => false, 'message' => "Could not find a price for one of the numbers: {$number}."]);
            exit();
        }
        $price_after_global_margin = $base_price * (1 + $global_profit_margin / 100);
        $final_price = $price_after_global_margin * (1 + $user_profit_margin / 100);
        $total_cost += $final_price;
    }
} else {
    $price_per_sms = 0.0;
    if ($route === 'corporate') {
        $price_per_sms = (float)($settings['price_sms_corp'] ?? 0.0);
    } else {
        // Default to promotional
        $price_per_sms = (float)($settings['price_sms_promo'] ?? 0.0);
    }
    $total_cost = $recipient_count * $price_per_sms;
}

echo json_encode(['success' => true, 'cost' => number_format($total_cost, 2), 'recipient_count' => $recipient_count]);
?>
