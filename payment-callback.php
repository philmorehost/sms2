<?php
require_once 'app/bootstrap.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the reference from the query string
$reference = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_STRING);

if (!$reference) {
    // Redirect if no reference is provided
    header("Location: add-funds.php?error=no_reference");
    exit();
}

// Get settings from the database
$settings = get_settings();
$paystack_secret_key = $settings['paystack_secret_key'] ?? '';

if (empty($paystack_secret_key)) {
    die("Payment gateway not configured.");
}

// Verify the transaction with Paystack
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $paystack_secret_key,
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    // cURL error
    header("Location: add-funds.php?error=verification_failed");
    exit();
}

$result = json_decode($response, true);

if ($result['status'] == true && $result['data']['status'] == 'success') {
    // Payment was successful
    $amount = $result['data']['amount'] / 100; // Amount is in kobo
    $db_reference = $result['data']['reference'];

    // Check if we've already processed this transaction
    $stmt_check = $conn->prepare("SELECT status FROM transactions WHERE reference = ?");
    $stmt_check->bind_param("s", $db_reference);
    $stmt_check->execute();
    $transaction = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($transaction && $transaction['status'] == 'pending') {
        // This is a new, valid transaction. Update our database.
        $conn->begin_transaction();
        try {
            // 1. Update user's balance
            $stmt_user = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt_user->bind_param("di", $amount, $_SESSION['user_id']);
            $stmt_user->execute();
            $stmt_user->close();

            // 2. Update transaction status
            $stmt_status = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE reference = ?");
            $stmt_status->bind_param("s", $db_reference);
            $stmt_status->execute();
            $stmt_status->close();

            $conn->commit();

            // --- Check for Referral Commission ---
            // Check if it's the user's first deposit and if they were referred
            $deposits_count_stmt = $conn->prepare("SELECT COUNT(id) as count FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'completed'");
            $deposits_count_stmt->bind_param("i", $_SESSION['user_id']);
            $deposits_count_stmt->execute();
            $deposits_count = $deposits_count_stmt->get_result()->fetch_assoc()['count'];

            if ($deposits_count == 1 && $user['referred_by'] !== null) {
                $referrer_id = $user['referred_by'];
                $commission_rate = 0.10; // 10%
                $commission_amount = $amount * $commission_rate;

                // Award commission to referrer
                $ref_update_stmt = $conn->prepare("UPDATE users SET referral_balance = referral_balance + ? WHERE id = ?");
                $ref_update_stmt->bind_param("di", $commission_amount, $referrer_id);
                $ref_update_stmt->execute();

                // Log the referral earning
                $ref_log_stmt = $conn->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, transaction_id, amount, commission_rate) VALUES (?, ?, ?, ?, ?)");
                $ref_log_stmt->bind_param("iiidd", $referrer_id, $_SESSION['user_id'], $transaction['id'], $commission_amount, $commission_rate);
                $ref_log_stmt->execute();
            }
            // --- End Referral Check ---

            // Redirect to dashboard with success message
            header("Location: dashboard.php?payment=success&amount=" . $amount);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            // Log this error properly in a real application
            header("Location: add-funds.php?error=db_update_failed");
            exit();
        }
    } else {
        // This transaction was already processed or is invalid
        header("Location: dashboard.php?payment=already_processed");
        exit();
    }
} else {
    // Payment was not successful
    $error_message = urlencode($result['data']['gateway_response'] ?? 'Payment was not successful.');
    // Optionally update the local transaction to 'failed'
    $stmt_fail = $conn->prepare("UPDATE transactions SET status = 'failed' WHERE reference = ?");
    $stmt_fail->bind_param("s", $reference);
    $stmt_fail->execute();
    $stmt_fail->close();

    header("Location: add-funds.php?error=" . $error_message);
    exit();
}
?>
