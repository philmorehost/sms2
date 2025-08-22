<?php
// This script should be run by a cron job once per day.
// Example cron job: 0 9 * * * /usr/bin/php /path/to/your/project/cron/send_birthday_wishes.php
// This would run at 9:00 AM every day.

// Set base path and include bootstrap
require_once __DIR__ . '/../app/bootstrap.php';

echo "Starting birthday scheduler script...\n";

// Get today's month and day
$today_month_day = date('m-d');

// Find all contacts whose birthday is today and whose owners have an active schedule
$sql = "
    SELECT
        pc.first_name, pc.last_name, pc.phone_number,
        bs.message_template, bs.sender_id,
        u.id as user_id, u.balance, u.api_key
    FROM phonebook_contacts pc
    JOIN users u ON pc.user_id = u.id
    JOIN birthday_schedules bs ON u.id = bs.user_id
    WHERE
        DATE_FORMAT(pc.birthday, '%m-%d') = ?
        AND bs.is_active = 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today_month_day);
$stmt->execute();
$results = $stmt->get_result();

if ($results->num_rows === 0) {
    echo "No birthdays found for today. Exiting.\n";
    exit;
}

echo "Found " . $results->num_rows . " birthday(s) to process.\n";

// Fetch SMS rates for billing
$rates_query = $conn->query("SELECT network_prefix, rate FROM sms_rates");
$rates = [];
while ($row = $rates_query->fetch_assoc()) {
    $rates[$row['network_prefix']] = (float)$row['rate'];
}
$default_rate = $rates['default'] ?? 0.02;

// Fetch the SMS API URL from settings
$settings = get_settings(); // Assuming get_settings() is available via bootstrap
$sms_api_url = $settings['kudisms_api_url_sms'] ?? '';
if (empty($sms_api_url)) {
    echo "SMS API URL is not configured. Exiting.\n";
    exit;
}

while ($contact = $results->fetch_assoc()) {
    $user_id = $contact['user_id'];
    $phone_number = $contact['phone_number'];

    // Calculate cost
    $prefix = substr($phone_number, 3, 3);
    $cost = $rates[$prefix] ?? $default_rate;

    // Check if user has enough balance
    if ($contact['balance'] < $cost) {
        echo "User #$user_id has insufficient balance to send birthday SMS to $phone_number. Skipping.\n";
        continue;
    }

    // Personalize the message
    $message = str_replace('[first_name]', $contact['first_name'], $contact['message_template']);
    $message = str_replace('[last_name]', $contact['last_name'], $message);

    // Prepare data for API
    $post_data = [
        'token'      => $contact['api_key'],
        'senderID'   => $contact['sender_id'],
        'recipients' => $phone_number,
        'message'    => $message,
        'gateway'    => 2, // Assuming promotional route for now
    ];

    // Send via API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sms_api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response !== false && $http_code == 200) {
        $api_result = json_decode($response, true);
        if (isset($api_result['status']) && $api_result['status'] == 'success') {
            // If API call is successful, debit user and log message
            $conn->begin_transaction();
            try {
                $conn->query("UPDATE users SET balance = balance - $cost WHERE id = $user_id");

                $log_stmt = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, api_response, type) VALUES (?, ?, ?, ?, ?, 'completed', ?, 'sms_debit')");
                $log_stmt->bind_param("isssds", $user_id, $contact['sender_id'], $phone_number, $message, $cost, $response);
                $log_stmt->execute();

                $conn->commit();
                echo "Successfully sent birthday message to $phone_number for user #$user_id.\n";
            } catch (Exception $e) {
                $conn->rollback();
                echo "Database error for user #$user_id sending to $phone_number: " . $e->getMessage() . "\n";
            }
        } else {
             echo "API Error for user #$user_id sending to $phone_number: " . ($api_result['msg'] ?? 'Unknown API error') . "\n";
        }
    } else {
        echo "cURL Error for user #$user_id sending to $phone_number.\n";
    }
}

echo "Birthday scheduler script finished.\n";
?>
