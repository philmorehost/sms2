<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Get the transaction reference from the URL
$reference = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_STRING);
if (!$reference) {
    die("No reference supplied");
}

// Get Paystack secret key from settings
$settings = get_settings();
$paystack_secret_key = $settings['paystack_secret_key'] ?? '';

if (empty($paystack_secret_key)) {
    // It's crucial to handle this case, maybe log it for the admin
    header("Location: ../add-funds.php?error=" . urlencode("Payment gateway not configured."));
    exit();
}

// Verify the transaction with Paystack
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . $reference);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $paystack_secret_key
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['status'] == true && $result['data']['status'] == 'success') {
    // Payment was successful
    $transaction_ref = $result['data']['reference'];
    $amount_paid = $result['data']['amount'] / 100; // Amount is in kobo

    // Find the corresponding transaction in our database
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ? AND status = 'pending'");
    $stmt->bind_param("s", $transaction_ref);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Security check: Ensure the amount paid matches the total amount in our records
    if ($transaction && (float)$amount_paid >= (float)$transaction['total_amount']) {
        // Transaction exists and the amount paid is sufficient
        $user_id = $transaction['user_id'];
        $invoice_id = $transaction['invoice_id'];
        $transaction_id = $transaction['id'];
        $amount_to_credit = $transaction['amount']; // This is the subtotal

        // Use a database transaction for atomicity
        $conn->begin_transaction();
        try {
            // 1. Update transaction status
            $update_trans = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
            $update_trans->bind_param("i", $transaction_id);
            $update_trans->execute();

            // 2. Update invoice status
            $update_invoice = $conn->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
            $update_invoice->bind_param("i", $invoice_id);
            $update_invoice->execute();

            // 3. Update user's balance with the subtotal amount
            $update_user = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $update_user->bind_param("di", $amount_to_credit, $user_id);
            $update_user->execute();

            // --- START of Referral Bonus Logic ---

            // Check if this was the user's first successful deposit
            $dep_count_stmt = $conn->prepare("SELECT COUNT(id) as deposit_count FROM transactions WHERE user_id = ? AND status = 'completed'");
            $dep_count_stmt->bind_param("i", $user_id);
            $dep_count_stmt->execute();
            $deposit_count = $dep_count_stmt->get_result()->fetch_assoc()['deposit_count'];
            $dep_count_stmt->close();

            if ($deposit_count === 1) {
                // This is the first deposit, check for a referrer
                $user_info_stmt = $conn->prepare("SELECT referred_by FROM users WHERE id = ?");
                $user_info_stmt->bind_param("i", $user_id);
                $user_info_stmt->execute();
                $referrer_id = $user_info_stmt->get_result()->fetch_assoc()['referred_by'];
                $user_info_stmt->close();

                if ($referrer_id) {
                    $bonus_percentage = (float)($settings['referral_bonus_percentage'] ?? 0);
                    if ($bonus_percentage > 0) {
                        $bonus_amount = ($amount_to_credit * $bonus_percentage) / 100;

                        // Award bonus to the referrer
                        $award_bonus_stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        $award_bonus_stmt->bind_param("di", $bonus_amount, $referrer_id);
                        $award_bonus_stmt->execute();
                        $award_bonus_stmt->close();

                        // Log the bonus transaction for the referrer
                        $log_bonus_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status) VALUES (?, 'referral_bonus', ?, ?, 'completed')");
                        $bonus_description = "Referral bonus from user #" . $user_id . " first deposit.";
                        $log_bonus_stmt->bind_param("ids", $referrer_id, $bonus_amount, $bonus_description);
                        $log_bonus_stmt->execute();
                        $log_bonus_stmt->close();
                    }
                }
            }
            // --- END of Referral Bonus Logic ---

            $conn->commit();

            // Redirect to a success page
            header("Location: ../transactions.php?payment=success");
            exit();

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            // Log this error for the admin
            header("Location: ../add-funds.php?error=" . urlencode("Database update failed after successful payment. Please contact support."));
            exit();
        }
    } else {
        // Discrepancy found (e.g., amount mismatch or transaction not found)
        // Log this for investigation
        header("Location: ../add-funds.php?error=" . urlencode("Payment verification failed."));
        exit();
    }
} else {
    // Payment was not successful on Paystack's end
    header("Location: ../add-funds.php?error=" . urlencode($result['data']['gateway_response'] ?? 'Payment failed.'));
    exit();
}
?>
