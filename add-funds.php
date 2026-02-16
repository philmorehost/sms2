<?php
$page_title = 'Fund Your Wallet';
// Include header, which also handles bootstrap and user session
include 'includes/header.php'; 

// --- Start of logic moved from top of file ---

// Fetch payment settings early
$settings = get_settings();
$manual_payment_enabled = !empty($settings['manual_bank_name']) && !empty($settings['manual_account_number']);
$vat_percentage = (float)($settings['vat_percentage'] ?? 0);

$invoice_to_pay = null;
if (isset($_GET['invoice_id'])) {
    $invoice_id_to_pay = (int)$_GET['invoice_id'];
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ? AND status = 'unpaid'");
    // Use $user instead of $current_user
    $stmt->bind_param("ii", $invoice_id_to_pay, $user['id']);
    $stmt->execute();
    $invoice_to_pay = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch active pricing plans
$plans_result = $conn->query("SELECT * FROM pricing_plans WHERE is_active = 1 ORDER BY price ASC");
$pricing_plans = [];
while($plan = $plans_result->fetch_assoc()) {
    $pricing_plans[] = $plan;
}

$errors = [];
$success = '';

// This logic is for the manual transfer form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_manual_payment'])) {
    $subtotal = (float)$_POST['amount'];
    $reference_id = trim($_POST['reference_id']);
    $payment_date = trim($_POST['payment_date']);
    $existing_invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);

    if ($subtotal > 0 && !empty($reference_id) && !empty($payment_date)) {
        if ($existing_invoice_id) {
            // Paying for an existing invoice
            // Find the associated transaction
            $trans_stmt = $conn->prepare("SELECT id FROM transactions WHERE invoice_id = ? AND user_id = ?");
            // Use $user instead of $current_user
            $trans_stmt->bind_param("ii", $existing_invoice_id, $user['id']);
            $trans_stmt->execute();
            $transaction = $trans_stmt->get_result()->fetch_assoc();

            if($transaction) {
                // Log the manual deposit request for admin review
                $manual_stmt = $conn->prepare("INSERT INTO manual_deposits (user_id, transaction_id, invoice_id, amount, reference_id, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                // Use $user instead of $current_user
                $manual_stmt->bind_param("iiidss", $user['id'], $transaction['id'], $existing_invoice_id, $subtotal, $reference_id, $payment_date);
                $manual_stmt->execute();

                $success = "Your payment proof has been submitted for Invoice #$existing_invoice_id and is pending verification.";
                // Optionally, re-notify admin
                $admin_email = get_admin_email();
                $subject = "Payment Proof Submitted for Invoice #$existing_invoice_id";
                // Use $user instead of $current_user
                $message = "User " . htmlspecialchars($user['username']) . " has submitted new payment proof for Invoice #$existing_invoice_id.<br>Please log in to review.";
                send_email($admin_email, $subject, $message);
            } else {
                $errors[] = "Could not find the invoice you are trying to pay for.";
            }

        } else {
            // Creating a new deposit request from scratch
            $vat_amount = $subtotal * ($vat_percentage / 100);
            $total_amount = $subtotal + $vat_amount;

            $conn->begin_transaction();
            try {
                // 1. Create an invoice with VAT details
                $invoice_stmt = $conn->prepare("INSERT INTO invoices (user_id, status, subtotal, vat_percentage, vat_amount, total_amount) VALUES (?, 'unpaid', ?, ?, ?, ?)");
                // Use $user instead of $current_user
                $invoice_stmt->bind_param("idddd", $user['id'], $subtotal, $vat_percentage, $vat_amount, $total_amount);
                $invoice_stmt->execute();
                $invoice_id = $conn->insert_id;

                // 2. Create a transaction record
                $desc = "Manual Deposit Submission. Ref: " . $reference_id;
                $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, invoice_id, reference, type, amount, total_amount, status, gateway, description) VALUES (?, ?, ?, 'deposit', ?, ?, 'pending', 'manual', ?)");
                // Use $user instead of $current_user
                $trans_stmt->bind_param("iisdds", $user['id'], $invoice_id, $reference_id, $subtotal, $total_amount, $desc);
                $trans_stmt->execute();
                $transaction_id = $conn->insert_id;

                // Update invoice with transaction_id
                $update_invoice_stmt = $conn->prepare("UPDATE invoices SET transaction_id = ? WHERE id = ?");
                $update_invoice_stmt->bind_param("ii", $transaction_id, $invoice_id);
                $update_invoice_stmt->execute();

                // 3. Log the manual deposit request for admin review
                $manual_stmt = $conn->prepare("INSERT INTO manual_deposits (user_id, transaction_id, invoice_id, amount, reference_id, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                // Use $user instead of $current_user
                $manual_stmt->bind_param("iiidss", $user['id'], $transaction_id, $invoice_id, $subtotal, $reference_id, $payment_date);
                $manual_stmt->execute();

                $conn->commit();
                $success = "Your payment submission has been received and is pending verification. Your wallet will be credited once approved.";

                // Send email notification to admin
                $admin_email = get_admin_email();
                $subject = "New Manual Deposit Submission";
                // Use $user instead of $current_user
                $message = "A new manual deposit has been submitted by user " . htmlspecialchars($user['username']) . ".<br><br>";
                $message .= "Amount: " . get_currency_symbol() . number_format($total_amount, 2) . "<br>";
                $message .= "Reference: " . htmlspecialchars($reference_id) . "<br>";
                $message .= "Please log in to the admin panel to review and approve this deposit.<br>";
                $message .= "<a href='" . SITE_URL . "/admin/manual-deposits.php'>Click here to review</a>";
                send_email($admin_email, $subject, $message);

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $errors[] = "Failed to submit your payment details. Please try again. Error: " . $exception->getMessage();
            }
        }
    } else {
        $errors[] = "Please fill in all the details for the manual payment submission.";
    }
}

// Fetch user's deposit history
$deposit_history = [];
$history_stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? AND type = 'deposit' ORDER BY created_at DESC LIMIT 10");
// Use $user instead of $current_user
$history_stmt->bind_param("i", $user['id']);
$history_stmt->execute();
$result = $history_stmt->get_result();
while($row = $result->fetch_assoc()) {
    $deposit_history[] = $row;
}
$history_stmt->close();

// Fetch user's pending (unpaid) invoices
$pending_invoices = [];
$pending_stmt = $conn->prepare("
    SELECT i.id, i.created_at, i.total_amount, t.description 
    FROM invoices i
    LEFT JOIN transactions t ON i.transaction_id = t.id
    WHERE i.user_id = ? AND i.status = 'unpaid' 
    ORDER BY i.created_at DESC
");
// Use $user instead of $current_user
$pending_stmt->bind_param("i", $user['id']);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
while($row = $pending_result->fetch_assoc()) {
    $pending_invoices[] = $row;
}
$pending_stmt->close();

function get_deposit_status_badge($status) {
    $status = strtolower($status);
    $badge_class = 'bg-secondary';
    if (in_array($status, ['completed', 'approved'])) {
        $badge_class = 'bg-success';
    } elseif (in_array($status, ['failed', 'rejected', 'cancelled'])) {
        $badge_class = 'bg-danger';
    } elseif ($status === 'pending') {
        $badge_class = 'bg-warning';
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

// --- End of logic block ---
?>
<link rel="stylesheet" href="css/add-funds.css">

<div class="card mb-4" data-currency-symbol="<?php echo get_currency_symbol(); ?>" data-invoice-amount="<?php echo $invoice_to_pay ? htmlspecialchars($invoice_to_pay['subtotal']) : ''; ?>">
    <div class="card-header">
        <h3 class="card-title">Fund Your Wallet</h3>
    </div>
    <div class="card-body">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <p class="mb-0"><?php echo $success; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($pricing_plans)): ?>
        <div class="mb-4">
            <h5>Quick Plans</h5>
            <p>Select one of our popular plans for a quick deposit.</p>
            <div class="row">
                <?php foreach($pricing_plans as $plan): ?>
                <div class="col-lg-3 col-md-4 col-6 mb-3">
                    <button class="btn btn-outline-primary w-100 h-100 p-3 text-center pricing-plan-btn" data-price="<?php echo $plan['price']; ?>">
                        <h5 class="h6 mb-1"><?php echo htmlspecialchars($plan['plan_name']); ?></h5>
                        <p class="mb-1 text-muted"><?php echo number_format($plan['credits']); ?> Credits</p>
                        <p class="h5 m-0"><?php echo get_currency_symbol(); ?><?php echo number_format($plan['price'], 2); ?></p>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <hr>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="form-group mb-3">
                    <label for="amount" class="form-label"><h5>Amount to Deposit (<?php echo get_currency_symbol(); ?>)</h5></label>
                    <input type="number" class="form-control form-control-lg" id="amount" placeholder="Select a plan or enter a custom amount" min="1" step="0.01" required>
                </div>

                <?php if ($vat_percentage > 0): ?>
                <div id="vat-calculation" class="mb-3" style="display: none;" data-vat-rate="<?php echo $vat_percentage; ?>" data-currency-symbol="<?php echo get_currency_symbol(); ?>">
                    <p class="mb-1">Subtotal: <strong id="subtotal-display"></strong></p>
                    <p class="mb-1">VAT (<?php echo $vat_percentage; ?>%): <strong id="vat-display"></strong></p>
                    <hr class="my-1">
                    <p class="mb-0 h5">Total to Pay: <strong id="total-display"></strong></p>
                </div>
                <?php endif; ?>

                <!-- Payment Method Selection -->
                <div class="payment-method-tabs nav nav-tabs">
                    <div class="payment-method-tab nav-link active" data-target="#paystack-content">
                        <i class="fas fa-credit-card"></i> Pay with Paystack
                    </div>
                    <?php if ($manual_payment_enabled): ?>
                    <div class="payment-method-tab nav-link" data-target="#manual-content">
                        <i class="fas fa-university"></i> Manual Bank Transfer
                    </div>
                    <?php endif; ?>
                </div>

                <div class="tab-content pt-3">
                    <!-- Paystack Content -->
                    <div id="paystack-content" class="payment-method-content active">
                        <p>You will be redirected to Paystack to complete your payment securely.</p>
                        <form id="paystack-form" action="payment-gateway/paystack-init.php" method="POST">
                            <input type="hidden" name="amount" class="payment-amount-input">
                            <button type="submit" name="process_payment" class="btn btn-primary btn-lg" disabled>Pay with Paystack</button>
                        </form>
                    </div>

                    <?php if ($manual_payment_enabled): ?>
                    <!-- Manual Transfer Content -->
                    <div id="manual-content" class="payment-method-content">
                        <h4>Bank Account Details</h4>
                        <p>Please make your deposit to the account below and then submit your payment details for verification.</p>
                        <ul class="list-group mb-3">
                            <li class="list-group-item"><strong>Bank Name:</strong> <?php echo htmlspecialchars($settings['manual_bank_name']); ?></li>
                            <li class="list-group-item"><strong>Account Name:</strong> <?php echo htmlspecialchars($settings['manual_account_name']); ?></li>
                            <li class="list-group-item"><strong>Account Number:</strong> <?php echo htmlspecialchars($settings['manual_account_number']); ?></li>
                        </ul>
                        <?php if (!empty($settings['manual_payment_instructions'])): ?>
                            <div class="alert alert-info">
                                <strong>Instructions:</strong>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($settings['manual_payment_instructions'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <h5>Submit Your Payment Details</h5>
                         <form action="add-funds.php" method="POST">
                            <input type="hidden" name="invoice_id" value="<?php echo isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : ''; ?>">
                             <div class="form-group mb-3">
                                <label for="manual_amount">Amount Paid (<?php echo get_currency_symbol(); ?>)</label>
                                <input type="number" class="form-control" name="amount" id="manual_amount" required min="1" step="0.01" value="<?php echo $invoice_to_pay ? htmlspecialchars($invoice_to_pay['subtotal']) : ''; ?>" <?php echo $invoice_to_pay ? 'readonly' : ''; ?>>
                            </div>
                            <div class="form-group mb-3">
                                <label for="reference_id">Transaction ID / Reference</label>
                                <input type="text" class="form-control" name="reference_id" required>
                            </div>
                             <div class="form-group mb-3">
                                <label for="payment_date">Date of Payment</label>
                                <input type="date" class="form-control" name="payment_date" required>
                            </div>
                            <button type="submit" name="submit_manual_payment" class="btn btn-primary">Submit for Verification</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-secondary">
                    <h4>Instructions</h4>
                    <p>1. Select a pricing plan or enter a custom amount.</p>
                    <p>2. Choose your preferred payment method.</p>
                    <p>3. Follow the instructions to complete the payment.</p>
                    <p>4. Your wallet will be credited automatically for Paystack payments, or after verification for manual transfers.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/add-funds.js"></script>

<!-- Pending Invoices -->
<?php if (!empty($pending_invoices)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Your Pending Invoices</h3>
    </div>
    <div class="card-body table-responsive">
        <p>You have pending invoices. You can complete the payment for them below.</p>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_invoices as $invoice): ?>
                <tr>
                    <td>#<?php echo $invoice['id']; ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($invoice['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($invoice['description'] ?? 'Wallet Top-up'); ?></td>
                    <td><?php echo get_currency_symbol(); ?><?php echo number_format($invoice['total_amount'], 2); ?></td>
                    <td>
                        <a href="add-funds.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-primary btn-sm">Pay Now</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Your Deposit History</h3>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Gateway</th>
                    <th>Reference</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deposit_history)): ?>
                    <tr><td colspan="5" class="text-center">You have no deposit history.</td></tr>
                <?php else: ?>
                    <?php foreach ($deposit_history as $deposit): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($deposit['created_at'])); ?></td>
                        <td><?php echo get_currency_symbol(); ?><?php echo number_format($deposit['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($deposit['gateway'])); ?></td>
                        <td><?php echo htmlspecialchars($deposit['reference']); ?></td>
                        <td><?php echo get_deposit_status_badge($deposit['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
