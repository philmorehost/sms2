<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['amount'])) {
    // Redirect back if accessed directly or without amount
    header("Location: ../add-funds.php?error=invalid_request");
    exit();
}

$total_amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$email = $current_user['email'];

if (!$total_amount || $total_amount <= 0) {
    header("Location: ../add-funds.php?error=invalid_amount");
    exit();
}

// Calculate subtotal and VAT from the total amount
$settings = get_settings();
$vat_percentage = (float)($settings['vat_percentage'] ?? 0);
$subtotal = $total_amount / (1 + ($vat_percentage / 100));
$vat_amount = $total_amount - $subtotal;


// Amount in Kobo (Paystack requires the amount in the lowest currency unit)
$amount_in_kobo = round($total_amount * 100);
// Generate a unique reference for this transaction
$reference = 'psk_' . bin2hex(random_bytes(10));

// --- Transaction and Invoice Creation ---
$conn->begin_transaction();
try {
    // 1. Create an invoice
    $invoice_stmt = $conn->prepare("INSERT INTO invoices (user_id, status, subtotal, vat_percentage, vat_amount, total_amount) VALUES (?, 'unpaid', ?, ?, ?, ?)");
    $invoice_stmt->bind_param("idddd", $current_user['id'], $subtotal, $vat_percentage, $vat_amount, $total_amount);
    $invoice_stmt->execute();
    $invoice_id = $conn->insert_id;

    // 2. Create a transaction record
    $desc = "Paystack Deposit. Ref: " . $reference;
    $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, invoice_id, reference, type, amount, total_amount, status, gateway, description) VALUES (?, ?, ?, 'deposit', ?, ?, 'pending', 'paystack', ?)");
    $trans_stmt->bind_param("iisdds", $current_user['id'], $invoice_id, $reference, $subtotal, $total_amount, $desc);
    $trans_stmt->execute();
    $transaction_id = $conn->insert_id;

    // 3. Update invoice with transaction_id
    $update_invoice_stmt = $conn->prepare("UPDATE invoices SET transaction_id = ? WHERE id = ?");
    $update_invoice_stmt->bind_param("ii", $transaction_id, $invoice_id);
    $update_invoice_stmt->execute();

    $conn->commit();
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    header("Location: ../add-funds.php?error=" . urlencode("Database error: " . $exception->getMessage()));
    exit();
}


// Get settings from the database
$settings = get_settings();
$paystack_secret_key = $settings['paystack_secret_key'] ?? '';

if (empty($paystack_secret_key)) {
    header("Location: ../add-funds.php?error=" . urlencode("Payment gateway is not configured."));
    exit();
}

// Prepare data for Paystack API
$post_data = [
    'email' => $email,
    'amount' => $amount_in_kobo,
    'reference' => $reference,
    'callback_url' => SITE_URL . '/payment-callback.php',
    'metadata' => [
        'user_id' => $current_user['id'],
        'transaction_id' => $transaction_id,
        'custom_fields' => [
            [
                'display_name' => "Payer Name",
                'variable_name' => "payer_name",
                'value' => $current_user['username']
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $paystack_secret_key,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    // Handle cURL error - redirect back with a generic error
    header("Location: ../add-funds.php?error=curl_error");
    exit();
}

$result = json_decode($response, true);

if ($result['status'] == true && isset($result['data']['authorization_url'])) {
    // Redirect user to Paystack payment page
    header('Location: ' . $result['data']['authorization_url']);
    exit();
} else {
    // Handle Paystack API error
    $error_message = urlencode($result['message'] ?? 'An unknown error occurred with Paystack.');
    header("Location: ../add-funds.php?error=" . $error_message);
    exit();
}
?>
