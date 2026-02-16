<?php
// cron/process_scheduled_emails.php
require_once __DIR__ . '/../app/bootstrap.php';

$log_file = __DIR__ . '/../logs/cron_email.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($log_file, "[{$timestamp}] --- Cron job for scheduled emails started ---\n", FILE_APPEND);

// Find tasks that are due
$now_utc = gmdate('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT * FROM scheduled_tasks WHERE task_type = 'email' AND status = 'pending' AND scheduled_for <= ?");
$stmt->bind_param("s", $now_utc);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($tasks)) {
    file_put_contents($log_file, "No pending email tasks to process.\n\n", FILE_APPEND);
    echo "No pending email tasks to process.\n";
    exit;
}

$log_message = "Found " . count($tasks) . " email task(s) to process.\n";
file_put_contents($log_file, $log_message, FILE_APPEND);
echo $log_message;

foreach ($tasks as $task) {
    $task_log_message = "Processing Task ID: " . $task['id'] . "\n";
    file_put_contents($log_file, $task_log_message, FILE_APPEND);
    echo $task_log_message;

    // Mark as processing to prevent duplicate sending
    $update_stmt = $conn->prepare("UPDATE scheduled_tasks SET status = 'processing', processed_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("i", $task['id']);
    $update_stmt->execute();
    $update_stmt->close();

    $payload = json_decode($task['payload'], true);
    $subject = $payload['subject'];
    $body = $payload['body'];
    $recipients = $payload['recipients'];

    $email_count = 0;
    $errors = [];

    foreach ($recipients as $recipient) {
        try {
            $personalized_body = $body;
            if (!empty($recipient['username'])) {
                $personalized_body = str_replace('[username]', htmlspecialchars($recipient['username']), $body);
                $personalized_body = "Dear " . htmlspecialchars($recipient['username']) . ",<br><br>" . $personalized_body;
            }
            send_email($recipient['email'], $subject, $personalized_body);
            $email_count++;
            if ($email_count % 10 == 0) {
                sleep(1);
            }
        } catch (Exception $e) {
            $errors[] = "Failed to send to " . $recipient['email'] . ": " . $e->getMessage();
        }
    }

    // Mark as completed or failed
    $final_status = empty($errors) ? 'completed' : 'failed';
    $result_message = empty($errors) ? "Sent to {$email_count} recipient(s)." : implode("\n", $errors);

    $update_stmt = $conn->prepare("UPDATE scheduled_tasks SET status = ?, result_message = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $final_status, $result_message, $task['id']);
    $update_stmt->execute();
    $update_stmt->close();

    $final_log_message = "Task ID " . $task['id'] . " processed. Status: " . $final_status . "\n";
    file_put_contents($log_file, $final_log_message, FILE_APPEND);
    echo $final_log_message;
}

$end_log_message = "[{$timestamp}] --- Cron job finished ---\n\n";
file_put_contents($log_file, $end_log_message, FILE_APPEND);
echo "All tasks processed.\n";
?>
