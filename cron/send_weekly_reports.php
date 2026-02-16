<?php
// This script should be run by a cron job once a week, e.g., every Monday at 2 AM.
// 0 2 * * 1 /usr/bin/php /path/to/your/project/cron/send_weekly_reports.php

set_time_limit(0);
require_once __DIR__ . '/../app/bootstrap.php';

echo "Weekly report job started at " . date('Y-m-d H:i:s') . "\n";

// Get all non-admin users who have an email address
$users_stmt = $conn->prepare("SELECT id, email, name, balance, last_login FROM users WHERE is_admin = 0 AND email IS NOT NULL AND email != ''");
$users_stmt->execute();
$users_result = $users_stmt->get_result();

if ($users_result->num_rows === 0) {
    echo "No users found to send reports to. Exiting.\n";
    exit();
}

$site_name = $settings['site_name'] ?? 'Your Site';
$start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
$end_date = date('Y-m-d H:i:s');

$sms_count_stmt = $conn->prepare("SELECT COUNT(id) as sms_count FROM messages WHERE user_id = ? AND created_at BETWEEN ? AND ?");

while ($user = $users_result->fetch_assoc()) {
    echo "Processing report for user ID: {$user['id']} ({$user['email']})\n";

    // 1. Get SMS sent in the last week
    $sms_count_stmt->bind_param("iss", $user['id'], $start_date, $end_date);
    $sms_count_stmt->execute();
    $sms_result = $sms_count_stmt->get_result()->fetch_assoc();
    $sms_sent_weekly = $sms_result['sms_count'] ?? 0;

    // 2. Get current credit balance
    $credit_balance = number_format($user['balance'], 2);

    // 3. Get last login date
    $last_login = $user['last_login'] ? format_date_for_display($user['last_login']) : 'N/A';

    // 4. Construct the email
    $subject = "Your Weekly Report from " . $site_name;
    $email_body = "
        <p>Hi " . htmlspecialchars($user['name']) . ",</p>
        <p>Here is your weekly activity summary from " . $site_name . ":</p>
        <table style='width: 100%; border-collapse: collapse;'>
            <tr style='background-color: #f2f2f2;'>
                <td style='padding: 8px; border: 1px solid #ddd;'>Metric</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>Value</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>SMS Sent (Last 7 Days)</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . number_format($sms_sent_weekly) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>Current Credit Balance</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . get_currency_symbol() . $credit_balance . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>Last Login</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . $last_login . "</td>
            </tr>
        </table>
        <p>Log in to your account to manage your services: <a href='" . SITE_URL . "/login.php'>Login Here</a></p>
        <p>Thank you for using our platform!</p>
    ";

    // 5. Send the email
    $send_result = send_email($user['email'], $subject, $email_body);

    if ($send_result['success']) {
        echo "Successfully sent report to {$user['email']}\n";
    } else {
        echo "Failed to send report to {$user['email']}. Reason: {$send_result['message']}\n";
    }

    // Optional: Add a small delay to avoid overwhelming the mail server
    sleep(1);
}

$sms_count_stmt->close();
$users_stmt->close();

echo "Weekly report job finished at " . date('Y-m-d H:i:s') . "\n";
?>
