<?php
// web/api/payment.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'settings';

if ($action === 'settings') {
    $settings = get_settings();
    mobile_api_success([
        'manual_payment' => [
            'enabled' => !empty($settings['manual_bank_name']),
            'bank_name' => $settings['manual_bank_name'] ?? '',
            'account_name' => $settings['manual_account_name'] ?? '',
            'account_number' => $settings['manual_account_number'] ?? '',
            'instructions' => $settings['manual_payment_instructions'] ?? ''
        ],
        'vat_percentage' => (float)($settings['vat_percentage'] ?? 0),
        'currency' => get_currency_code(),
        'currency_symbol' => get_currency_symbol()
    ]);
} elseif ($action === 'submit_manual') {
    $amount = (float)$_POST['amount'];
    $reference = $_POST['reference'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($amount <= 0 || empty($reference)) mobile_api_error('Valid amount and reference required');

    $settings = get_settings();
    $vat_percentage = (float)($settings['vat_percentage'] ?? 0);
    $vat_amount = $amount * ($vat_percentage / 100);
    $credit_amount = $amount - $vat_amount;

    $conn->begin_transaction();
    try {
        $stmt_inv = $conn->prepare("INSERT INTO invoices (user_id, status, subtotal, vat_percentage, vat_amount, total_amount) VALUES (?, 'unpaid', ?, ?, ?, ?)");
        $stmt_inv->bind_param("idddd", $user['id'], $credit_amount, $vat_percentage, $vat_amount, $amount);
        $stmt_inv->execute();
        $invoice_id = $conn->insert_id;

        $desc = "Manual Deposit Submission. Ref: " . $reference;
        $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, invoice_id, reference, type, amount, total_amount, status, gateway, description) VALUES (?, ?, ?, 'deposit', ?, ?, 'pending', 'manual', ?)");
        $stmt_trans->bind_param("iisdds", $user['id'], $invoice_id, $reference, $credit_amount, $amount, $desc);
        $stmt_trans->execute();
        $transaction_id = $conn->insert_id;

        $stmt_update = $conn->prepare("UPDATE invoices SET transaction_id = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $transaction_id, $invoice_id);
        $stmt_update->execute();

        $stmt_dep = $conn->prepare("INSERT INTO manual_deposits (user_id, transaction_id, invoice_id, amount, reference_id, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_dep->bind_param("iiidss", $user['id'], $transaction_id, $invoice_id, $credit_amount, $reference, $date);
        $stmt_dep->execute();

        $conn->commit();
        mobile_api_success([], 'Payment proof submitted successfully');
    } catch (Exception $e) {
        $conn->rollback();
        mobile_api_error('Failed to submit payment proof');
    }
} elseif ($action === 'global_settings') {
    $settings = get_settings();
    $global_wallet_currency = $settings['global_wallet_currency'] ?? 'EUR';
    $conversion_rate = (float)($settings['global_wallet_conversion_rate'] ?? 0);

    $crypto_methods = [];
    $res = $conn->query("SELECT id, name, address, network, instructions FROM crypto_payment_methods WHERE is_active = 1");
    while($row = $res->fetch_assoc()) $crypto_methods[] = $row;

    $stmt = $conn->prepare("SELECT balance FROM global_wallets WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $gw_res = $stmt->get_result()->fetch_assoc();
    $global_balance = (float)($gw_res['balance'] ?? 0);

    mobile_api_success([
        'global_balance' => $global_balance,
        'currency' => $global_wallet_currency,
        'conversion_rate' => $conversion_rate,
        'crypto_methods' => $crypto_methods,
        'main_balance' => (float)$user['balance']
    ]);
} elseif ($action === 'convert') {
    $amount = (float)$_POST['amount'];
    $settings = get_settings();
    $conversion_rate = (float)($settings['global_wallet_conversion_rate'] ?? 0);
    $global_wallet_currency = $settings['global_wallet_currency'] ?? 'EUR';

    if ($amount <= 0 || $user['balance'] < $amount || $conversion_rate <= 0) {
        mobile_api_error('Invalid amount or insufficient balance');
    }

    $converted_amount = $amount * $conversion_rate;
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE users SET balance = balance - $amount WHERE id = {$user['id']}");
        $conn->query("INSERT INTO global_wallets (user_id, balance) VALUES ({$user['id']}, $converted_amount) ON DUPLICATE KEY UPDATE balance = balance + $converted_amount");
        $conn->query("INSERT INTO global_manual_deposits (user_id, amount, currency, crypto_type, proof_of_payment, status) VALUES ({$user['id']}, $converted_amount, '$global_wallet_currency', 'conversion', 'conversion', 'approved')");
        $conn->commit();
        mobile_api_success([], 'Conversion successful');
    } catch (Exception $e) {
        $conn->rollback();
        mobile_api_error('Conversion failed');
    }
} elseif ($action === 'init_paystack') {
    $total_amount = (float)$_POST['amount'];
    if ($total_amount <= 0) mobile_api_error('Invalid amount');

    $email = $user['email'];
    $settings = get_settings();
    $vat_percentage = (float)($settings['vat_percentage'] ?? 0);
    $vat_amount = $total_amount * ($vat_percentage / 100);
    $subtotal = $total_amount - $vat_amount;
    $amount_in_kobo = round($total_amount * 100);
    $reference = 'psk_m_' . bin2hex(random_bytes(10));

    $conn->begin_transaction();
    try {
        $stmt_inv = $conn->prepare("INSERT INTO invoices (user_id, status, subtotal, vat_percentage, vat_amount, total_amount) VALUES (?, 'unpaid', ?, ?, ?, ?)");
        $stmt_inv->bind_param("idddd", $user['id'], $subtotal, $vat_percentage, $vat_amount, $total_amount);
        $stmt_inv->execute();
        $invoice_id = $conn->insert_id;

        $desc = "Paystack Mobile Deposit. Ref: " . $reference;
        $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, invoice_id, reference, type, amount, total_amount, status, gateway, description) VALUES (?, ?, ?, 'deposit', ?, ?, 'pending', 'paystack', ?)");
        $stmt_trans->bind_param("iisdds", $user['id'], $invoice_id, $reference, $subtotal, $total_amount, $desc);
        $stmt_trans->execute();
        $transaction_id = $conn->insert_id;

        $conn->query("UPDATE invoices SET transaction_id = $transaction_id WHERE id = $invoice_id");
        $conn->commit();

        $paystack_secret_key = $settings['paystack_secret_key'] ?? '';
        if (empty($paystack_secret_key)) mobile_api_error('Payment gateway not configured');

        $post_data = [
            'email' => $email,
            'amount' => $amount_in_kobo,
            'reference' => $reference,
            'callback_url' => SITE_URL . '/payment-callback.php',
            'metadata' => ['user_id' => $user['id'], 'transaction_id' => $transaction_id]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $paystack_secret_key, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        $result = json_decode($response, true);

        if ($result['status'] == true) {
            mobile_api_success(['authorization_url' => $result['data']['authorization_url'], 'reference' => $reference]);
        } else {
            mobile_api_error($result['message'] ?? 'Paystack initialization failed');
        }
    } catch (Exception $e) {
        $conn->rollback();
        mobile_api_error('Payment initialization failed');
    }
}
?>
