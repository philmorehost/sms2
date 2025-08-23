<?php
// This script is intended to be run from the command line via a cron job.

// Bootstrap the application
require_once __DIR__ . '/../app/bootstrap.php';

echo "Cron Job: Checking status of pending Sender IDs...\n";

// 1. Fetch all pending sender IDs
$pending_ids = [];
$stmt = $conn->prepare("SELECT id, sender_id FROM sender_ids WHERE status = 'pending'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pending_ids[] = $row;
    }
    $stmt->close();
}

if (empty($pending_ids)) {
    echo "No pending sender IDs found.\n";
    exit;
}

echo "Found " . count($pending_ids) . " pending Sender ID(s) to check.\n";

// 2. Loop through them and check the status via API
foreach ($pending_ids as $item) {
    $record_id = $item['id'];
    $sender_id = $item['sender_id'];

    echo "Checking: $sender_id (ID: $record_id)... ";

    $api_result = check_sender_id_api($sender_id);

    if ($api_result['success']) {
        // API call was successful, now check the message
        $message = strtolower($api_result['message']);
        $new_status = 'pending'; // Default to no change

        // This is a bit fragile, but based on the API docs provided.
        if (strpos($message, 'approved') !== false) {
            $new_status = 'approved';
        } elseif (strpos($message, 'rejected') !== false || strpos($message, 'not found') !== false) {
            // Assuming if the API says "not found", it was rejected or never existed.
            $new_status = 'rejected';
        }

        if ($new_status !== 'pending') {
            // 3. Update the status in our database
            $stmt = $conn->prepare("UPDATE sender_ids SET status = ?, api_response = ? WHERE id = ?");
            $api_response_str = json_encode($api_result['data']);
            $stmt->bind_param("ssi", $new_status, $api_response_str, $record_id);
            if ($stmt->execute()) {
                echo "Status updated to '$new_status'.\n";
            } else {
                echo "Failed to update status in DB.\n";
            }
            $stmt->close();
        } else {
            echo "Status is still pending according to API.\n";
        }
    } else {
        // API call failed
        echo "API call failed. Reason: " . $api_result['message'] . "\n";
        // We can also update the api_response field with the error for debugging
        $stmt = $conn->prepare("UPDATE sender_ids SET api_response = ? WHERE id = ?");
        $error_response = json_encode($api_result);
        $stmt->bind_param("si", $error_response, $record_id);
        $stmt->execute();
        $stmt->close();
    }
}

echo "Cron job finished.\n";
?>
