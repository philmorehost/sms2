<?php
// This script should be run by a cron job, e.g., every 5 minutes.
require_once __DIR__ . '/../app/bootstrap.php';

echo "Cron Job: Requerying pending VTU transactions...\n";

// 1. Fetch all pending VTU transactions
$stmt = $conn->prepare("SELECT * FROM transactions WHERE status = 'pending' AND type = 'debit' AND vtu_service_type IS NOT NULL AND vtu_is_refunded = 0");
$stmt->execute();
$pending_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($pending_transactions)) {
    echo "No pending VTU transactions found to requery.\n";
    exit;
}

echo "Found " . count($pending_transactions) . " pending transaction(s).\n";

// 2. Fetch API credentials
$vtu_apis = [];
$vtu_apis_result = $conn->query("SELECT * FROM vtu_apis");
while ($row = $vtu_apis_result->fetch_assoc()) {
    $vtu_apis[$row['provider_name']] = $row;
}

foreach ($pending_transactions as $txn) {
    echo "Processing transaction ID: {$txn['id']}...\n";

    $provider = $txn['gateway'];
    $api_details = $vtu_apis[$provider] ?? null;

    if (!$api_details) {
        echo "  - Error: API provider '{$provider}' not configured. Skipping.\n";
        continue;
    }

    $api_response = json_decode($txn['api_response'], true);
    $request_id = null;
    if ($provider === 'VTPass') {
        $request_id = $api_response['requestId'] ?? null;
    } elseif ($provider === 'ClubKonnect') {
        $request_id = $api_response['orderid'] ?? null;
    } elseif ($provider === 'HDKDATA') {
        // Assuming our own transaction ID is the reference for HDKDATA
        $request_id = $txn['id'];
    }

    if (!$request_id) {
        echo "  - Error: Could not find request/order ID in the initial transaction data for provider {$provider}. Skipping.\n";
        continue;
    }

    // 3. Call the appropriate requery API
    $requery_result = null;
    if ($provider === 'VTPass') {
        $requery_result = requery_vtpass($request_id, $api_details);
    }
    // Add else if for other providers like ClubKonnect here in the future
    } else if ($provider === 'ClubKonnect') {
        $requery_result = requery_clubkonnect($request_id, $api_details);
    } else if ($provider === 'HDKDATA') {
        $requery_result = requery_hdkdata($request_id, $api_details);
    }

    if (!$requery_result) {
        echo "  - Error: Requery function for '{$provider}' failed or is not implemented. Skipping.\n";
        continue;
    }

    // 4. Process the requery response
    $final_status = 'pending'; // Default to no change
    if ($requery_result['success']) {
        $final_status = 'completed';
    } elseif ($requery_result['is_failed']) {
        $final_status = 'failed';
    }

    echo "  - Requery status: " . $final_status . "\n";

    if ($final_status === 'pending') {
        echo "  - Transaction is still pending on the provider's end. Will check again later.\n";
        continue;
    }

    // 5. Update the transaction status and handle refunds if necessary
    $conn->begin_transaction();
    try {
        if ($final_status === 'failed') {
            // Refund the user
            $stmt_refund = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt_refund->bind_param("di", $txn['total_amount'], $txn['user_id']);
            $stmt_refund->execute();
            $stmt_refund->close();

            // Update transaction status to failed and mark as refunded
            $stmt_update = $conn->prepare("UPDATE transactions SET status = 'failed', vtu_is_refunded = 1 WHERE id = ?");
            $stmt_update->bind_param("i", $txn['id']);
            $stmt_update->execute();
            $stmt_update->close();

            echo "  - User {$txn['user_id']} has been refunded " . $txn['total_amount'] . ".\n";

        } else if ($final_status === 'completed') {
            // Just update the status
            $stmt_update = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
            $stmt_update->bind_param("i", $txn['id']);
            $stmt_update->execute();
            $stmt_update->close();
        }
        $conn->commit();
        echo "  - Database updated successfully.\n";
    } catch (Exception $e) {
        $conn->rollback();
        echo "  - Database error during update/refund: " . $e->getMessage() . "\n";
    }
}

function requery_vtpass($request_id, $api_details) {
    $api_url = $api_details['is_sandbox'] ? 'https://sandbox.vtpass.com/api/requery' : 'https://vtpass.com/api/requery';

    $headers = [];
    if ($api_details['is_sandbox']) {
        $headers[] = 'Authorization: Basic ' . base64_encode($api_details['username'] . ':' . $api_details['secret_key']);
    } else {
        $headers[] = 'api-key: ' . $api_details['api_key'];
        $headers[] = 'secret-key: ' . $api_details['secret_key'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['request_id' => $request_id]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    $api_result = json_decode($response, true);

    // Check for definitive success or failure
    if (isset($api_result['content']['transactions']['status'])) {
        $status = strtolower($api_result['content']['transactions']['status']);
        if ($status === 'delivered' || $status === 'successful') {
            return ['success' => true, 'is_failed' => false, 'data' => $api_result];
        }
        if ($status === 'failed' || $status === 'reversed') {
            return ['success' => false, 'is_failed' => true, 'data' => $api_result];
        }
    }
    // Otherwise, assume it's still pending or an indeterminate state
    return ['success' => false, 'is_failed' => false, 'data' => $api_result];
}

function requery_clubkonnect($order_id, $api_details) {
    $api_url = "https://www.nellobytesystems.com/APIQueryV1.asp";

    $params = [
        'UserID' => $api_details['username'],
        'APIKey' => $api_details['api_key'],
        'OrderID' => $order_id,
    ];

    $url_with_params = $api_url . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_with_params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    $api_result = json_decode($response, true);

    // Check for definitive success or failure
    if (isset($api_result['status'])) {
        $status = strtoupper($api_result['status']);
        if ($status === 'ORDER_COMPLETED') {
            return ['success' => true, 'is_failed' => false, 'data' => $api_result];
        }
        // ClubKonnect doesn't seem to have a definitive 'failed' status on requery,
        // so we only look for completion. Anything else is treated as pending.
        // A more robust system might check for specific error codes if available.
    }

    // Assume pending if not explicitly completed
    return ['success' => false, 'is_failed' => false, 'data' => $api_result];
}

function requery_hdkdata($transaction_id, $api_details) {
    // TODO: Confirm the actual HDKDATA requery endpoint and parameters.
    // This is a placeholder implementation.
    $api_url = "https://hdkdata.com/api/requery";

    $post_data = [
        'transaction_id' => $transaction_id, // Assuming our DB transaction ID is the reference
    ];

    $headers = [
        'Authorization: Bearer ' . $api_details['api_key'],
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    $api_result = json_decode($response, true);

    // TODO: Adjust this logic based on the actual API response structure.
    if (isset($api_result['status'])) {
        $status = strtolower($api_result['status']);
        if ($status === 'delivered' || $status === 'success' || $status === 'completed') {
            return ['success' => true, 'is_failed' => false, 'data' => $api_result];
        }
        if ($status === 'failed' || $status === 'reversed') {
            return ['success' => false, 'is_failed' => true, 'data' => $api_result];
        }
    }

    // Assume pending if not explicitly successful or failed
    return ['success' => false, 'is_failed' => false, 'data' => $api_result];
}

echo "Cron job finished.\n";
?>
