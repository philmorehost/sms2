<?php
// This script should be run by a cron job, e.g., every minute.
// * * * * * /usr/bin/php /path/to/your/project/cron/process_scheduled_tasks.php

// Set a long execution time
set_time_limit(0);

require_once __DIR__ . '/../app/bootstrap.php';

echo "Cron job started at " . date('Y-m-d H:i:s') . "\n";

// --- 1. Find due tasks and lock them ---
// NOTE: The application now stores all scheduled times in UTC.
// This cron job must therefore compare against the current UTC time.
$now_utc = gmdate('Y-m-d H:i:s');
$due_tasks = [];

// Begin transaction
$conn->begin_transaction();
try {
    // Find tasks that are due (in UTC) and lock the rows for update
    $stmt_select = $conn->prepare("SELECT * FROM scheduled_tasks WHERE scheduled_for <= ? AND status = 'pending' FOR UPDATE");
    $stmt_select->bind_param("s", $now_utc);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    while ($row = $result->fetch_assoc()) {
        $due_tasks[] = $row;
    }
    $stmt_select->close();


    if (!empty($due_tasks)) {
        $ids_to_process = array_column($due_tasks, 'id');
        $id_placeholders = implode(',', array_fill(0, count($ids_to_process), '?'));

        // Mark them as 'processing' to prevent other cron jobs from picking them up
        $update_stmt = $conn->prepare("UPDATE scheduled_tasks SET status = 'processing', processed_at = NOW() WHERE id IN ($id_placeholders)");
        $types = str_repeat('i', count($ids_to_process));
        $update_stmt->bind_param($types, ...$ids_to_process);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Commit the transaction
    $conn->commit();
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    die("Failed to lock tasks: " . $exception->getMessage());
}

if (empty($due_tasks)) {
    echo "No due tasks found. Exiting.\n";
    exit();
}

echo "Found " . count($due_tasks) . " tasks to process.\n";

// --- 2. Process each locked task ---
foreach ($due_tasks as $task) {
    echo "Processing task ID: " . $task['id'] . " of type " . $task['task_type'] . "\n";

    $payload = json_decode($task['payload'], true);
    $user_id = $task['user_id'];

    $settings = get_settings();
    $final_status = 'pending';
    $final_message = '';
    $is_post_request = false;
    $post_data = null;

    switch ($task['task_type']) {
        case 'sms':
            $api_key = $settings['kudisms_api_key_sms'] ?? '';
            if (empty($api_key)) {
                $final_status = 'failed';
                $final_message = 'SMS API key is not configured.';
                continue 2;
            }
            if ($payload['route'] === 'corporate') {
                $is_post_request = true;
                $url = "https://my.kudisms.net/api/corporate/sms";
                $post_data = ['token' => $api_key, 'senderID' => $payload['sender_id'], 'recipients' => $payload['recipients'], 'message' => $payload['message']];
            } else {
                $url = "https://my.kudisms.net/api/sms?" . http_build_query(['token' => $api_key, 'senderID' => $payload['sender_id'], 'recipients' => $payload['recipients'], 'message' => $payload['message'], 'gateway' => '2']);
            }
            break;

        case 'global_sms':
            $username = $settings['routemobile_username'] ?? '';
            $password = $settings['routemobile_password'] ?? '';
            if (empty($username) || empty($password)) {
                $final_status = 'failed';
                $final_message = 'Global SMS API is not configured.';
                continue 2;
            }
            $params = [
                'username' => $username,
                'password' => $password,
                'type' => 0,
                'dlr' => 1,
                'destination' => $payload['recipients'],
                'source' => $payload['sender_id'],
                'message' => $payload['message']
            ];
            $url = "https://rslr.connectbind.com:8443/bulksms/bulksms?" . http_build_query($params);
            break;

        case 'voice_tts':
            $api_key = $settings['kudisms_api_key_tts'] ?? '';
            if (empty($api_key)) {
                $final_status = 'failed';
                $final_message = 'TTS API key is not configured.';
                continue 2;
            }
            $url = "https://kudisms.vtudomain.com/api/texttospeech?" . http_build_query(['token' => $api_key, 'callerID' => $payload['caller_id'], 'recipients' => $payload['recipients'], 'message' => $payload['message']]);
            break;

        default:
            $final_status = 'failed';
            $final_message = 'Unknown task type: ' . $task['task_type'];
            continue 2;
    }

    if ($final_status !== 'failed') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($is_post_request) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $is_api_success = false;
        if ($task['task_type'] === 'sms') {
            $api_result = json_decode($response, true);
            $is_api_success = ($http_code == 200 && (($payload['route'] === 'promotional' && isset($api_result['error_code']) && $api_result['error_code'] == '000') || ($payload['route'] === 'corporate' && isset($api_result['status']) && $api_result['status'] == 'success')));
        } elseif ($task['task_type'] === 'global_sms') {
            $response_parts = explode(',', trim($response));
            $first_code = explode('|', $response_parts[0])[0] ?? null;
            $is_api_success = $first_code === '1701';
        } else { // For voice_tts and others
            $api_result = json_decode($response, true);
            $is_api_success = ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success');
        }

        // Get the original message_id from the payload
        $message_id = $payload['message_id'] ?? null;

        if ($is_api_success) {
            $final_status = 'completed';
            // If we have a message_id, update its status to success
            if ($message_id) {
                $update_msg_stmt = $conn->prepare("UPDATE messages SET status = 'success', api_response = ? WHERE id = ?");
                $update_msg_stmt->bind_param("si", $response, $message_id);
                $update_msg_stmt->execute();
                $update_msg_stmt->close();
            }
        } else {
            $final_status = 'failed';
            // If the send failed, we need to refund the user
            if ($message_id) {
                $conn->begin_transaction();
                try {
                    // 1. Get the original cost and wallet type from the messages table
                    $msg_stmt = $conn->prepare("SELECT cost, user_id, wallet_type FROM messages WHERE id = ?");
                    $msg_stmt->bind_param("i", $message_id);
                    $msg_stmt->execute();
                    $msg_res = $msg_stmt->get_result();

                    if ($msg_row = $msg_res->fetch_assoc()) {
                        $cost_to_refund = $msg_row['cost'];
                        $user_to_refund = $msg_row['user_id'];
                        $wallet_type = $msg_row['wallet_type'];

                        // 2. Refund the user to the correct wallet
                        if ($wallet_type === 'global') {
                            $refund_stmt = $conn->prepare("UPDATE global_wallets SET balance = balance + ? WHERE user_id = ?");
                        } else {
                            $refund_stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        }
                        $refund_stmt->bind_param("di", $cost_to_refund, $user_to_refund);
                        $refund_stmt->execute();
                        $refund_stmt->close();

                        // 3. Update the message status to 'failed'
                        $update_fail_stmt = $conn->prepare("UPDATE messages SET status = 'failed', api_response = ? WHERE id = ?");
                        $update_fail_stmt->bind_param("si", $response, $message_id);
                        $update_fail_stmt->execute();
                        $update_fail_stmt->close();
                    }
                    $msg_stmt->close();
                    $conn->commit();
                    echo "Refunded $cost_to_refund to user $user_to_refund for failed message $message_id.\n";
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "Error during refund transaction for message ID $message_id: " . $e->getMessage() . "\n";
                }
            }
        }
        $final_message = $response;
    }

    // --- 3. Update the task with the final result ---
    $update_final_stmt = $conn->prepare("UPDATE scheduled_tasks SET status = ?, result_message = ? WHERE id = ?");
    $update_final_stmt->bind_param("ssi", $final_status, $final_message, $task['id']);
    $update_final_stmt->execute();
    $update_final_stmt->close();

    echo "Finished processing task ID: " . $task['id'] . " with status: " . $final_status . "\n";
}

echo "Cron job finished at " . date('Y-m-d H:i:s') . "\n";
?>
