<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['invoice_id'])) {
    header("Location: ../transactions.php?error=invalid_request");
    exit();
}

$invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
if (!$invoice_id) {
    header("Location: ../transactions.php?error=invalid_invoice");
    exit();
}

// Fetch the invoice to ensure it belongs to the user and is unpaid
$stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ? AND status = 'unpaid'");
$stmt->bind_param("ii", $invoice_id, $current_user['id']);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    header("Location: ../transactions.php?error=invoice_not_found_or_paid");
    exit();
}

$total_amount = $invoice['total_amount'];
$email = $current_user['email'];
$transaction_id = $invoice['transaction_id'];

// Amount in Kobo
$amount_in_kobo = round($total_amount * 100);
// Generate a new, unique reference for this payment attempt
$reference = 'psk_inv_' . bin2hex(random_bytes(8)) . '_' . $invoice_id;

// Update the existing transaction with the new reference
$update_stmt = $conn->prepare("UPDATE transactions SET reference = ? WHERE id = ?");
$update_stmt->bind_param("si", $reference, $transaction_id);
$update_stmt->execute();
$update_stmt->close();


// Get Paystack secret key from settings
$settings = get_settings();
$paystack_secret_key = $settings['paystack_secret_key'] ?? '';

if (empty($paystack_secret_key)) {
    header("Location: ../view-invoice.php?id=$invoice_id&error=" . urlencode("Payment gateway not configured."));
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
        'invoice_id' => $invoice_id,
        'custom_fields' => [
            [
                'display_name' => "Payer Name",
                'variable_name' => "payer_name",
                'value' => $current_user['username']
            ],
            [
                'display_name' => "Invoice ID",
                'variable_name' => "invoice_id",
                'value' => "#" . $invoice_id
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
    header("Location: ../view-invoice.php?id=$invoice_id&error=curl_error");
    exit();
}

$result = json_decode($response, true);

if ($result['status'] == true && isset($result['data']['authorization_url'])) {
    header('Location: ' . $result['data']['authorization_url']);
    exit();
} else {
    $error_message = urlencode($result['message'] ?? 'An unknown error occurred.');
    header("Location: ../view-invoice.php?id=$invoice_id&error=" . $error_message);
    exit();
}
?>
