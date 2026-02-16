<?php
$page_title = 'Global Wallet';
include 'includes/header.php';

// Fetch user's global wallet balance
$global_wallet_stmt = $conn->prepare("SELECT balance FROM global_wallets WHERE user_id = ?");
$global_wallet_stmt->bind_param("i", $current_user['id']);
$global_wallet_stmt->execute();
$global_wallet_result = $global_wallet_stmt->get_result();
$global_wallet = $global_wallet_result->fetch_assoc();
$global_wallet_balance = $global_wallet['balance'] ?? 0.00;
$global_wallet_stmt->close();

$settings = get_settings();
$global_wallet_currency = $settings['global_wallet_currency'] ?? 'EUR';
$conversion_rate = (float)($settings['global_wallet_conversion_rate'] ?? 0);

$errors = [];
$success = '';

// Handle Fund Conversion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['convert_funds'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $amount_to_convert = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if ($amount_to_convert <= 0) {
        $errors[] = "Please enter a valid amount to convert.";
    } elseif ($current_user['balance'] < $amount_to_convert) {
        $errors[] = "Insufficient balance in your general wallet.";
    } elseif ($conversion_rate <= 0) {
        $errors[] = "Fund conversion is currently disabled by the administrator.";
    } else {
        $converted_amount = $amount_to_convert * $conversion_rate;

        $conn->begin_transaction();
        try {
            // 1. Debit from general wallet
            $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt_debit->bind_param("di", $amount_to_convert, $current_user['id']);
            $stmt_debit->execute();
            $stmt_debit->close();

            // 2. Ensure global wallet exists
            $stmt_wallet_check = $conn->prepare("INSERT INTO global_wallets (user_id, balance) VALUES (?, 0) ON DUPLICATE KEY UPDATE balance = balance");
            $stmt_wallet_check->bind_param("i", $current_user['id']);
            $stmt_wallet_check->execute();
            $stmt_wallet_check->close();

            // 3. Credit global wallet
            $stmt_credit = $conn->prepare("UPDATE global_wallets SET balance = balance + ? WHERE user_id = ?");
            $stmt_credit->bind_param("di", $converted_amount, $current_user['id']);
            $stmt_credit->execute();
            $stmt_credit->close();

            // 4. Log the conversion as a special type of deposit for tracking
            $stmt_log = $conn->prepare("INSERT INTO global_manual_deposits (user_id, amount, currency, crypto_type, proof_of_payment, status) VALUES (?, ?, ?, 'conversion', 'conversion', 'approved')");
            $stmt_log->bind_param("ids", $current_user['id'], $converted_amount, $global_wallet_currency);
            $stmt_log->execute();
            $stmt_log->close();

            $conn->commit();
            $success = "Successfully converted " . get_currency_symbol() . number_format($amount_to_convert, 2) . " to " . number_format($converted_amount, 2) . " " . $global_wallet_currency . ".";

            // Refresh balances
            $current_user['balance'] -= $amount_to_convert;
            $global_wallet_balance += $converted_amount;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "An error occurred during conversion: " . $e->getMessage();
        }
    }
}

// Handle Manual Deposit Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_deposit'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $crypto_type = trim($_POST['crypto_type']);
    $transaction_hash = trim($_POST['transaction_hash']);

    if ($amount <= 0) {
        $errors[] = "Invalid amount entered.";
    }
    if (empty($_FILES['proof_of_payment']['name'])) {
        $errors[] = "Proof of payment is required.";
    }

    if (empty($errors)) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $proof_ext = pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION);
        $proof_filename = 'proof_' . $current_user['id'] . '_' . time() . '.' . $proof_ext;
        $proof_path = $upload_dir . $proof_filename;

        if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $proof_path)) {
            $stmt = $conn->prepare("INSERT INTO global_manual_deposits (user_id, amount, currency, crypto_type, transaction_hash, proof_of_payment, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("idssss", $current_user['id'], $amount, $global_wallet_currency, $crypto_type, $transaction_hash, $proof_filename);
            if ($stmt->execute()) {
                $success = "Your deposit request has been submitted and is pending review.";
            } else {
                $errors[] = "Failed to submit your request. Please try again.";
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to upload your proof of payment.";
        }
    }
}

// Fetch transaction history
$transactions_result = $conn->query("SELECT * FROM global_manual_deposits WHERE user_id = {$current_user['id']} ORDER BY created_at DESC");

// Fetch active crypto methods
$crypto_methods_result = $conn->query("SELECT * FROM crypto_payment_methods WHERE is_active = 1");
?>

<div class="row">
    <!-- Left Column: Balance & Conversion -->
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Global Wallet Balance</h5>
            </div>
            <div class="card-body text-center">
                <h1 class="display-4"><?php echo number_format($global_wallet_balance, 2); ?> <small class="text-muted"><?php echo $global_wallet_currency; ?></small></h1>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Convert Funds</h5>
            </div>
            <div class="card-body">
                <?php if ($conversion_rate > 0): ?>
                    <p>Convert funds from your main wallet to your global wallet.</p>
                    <p>Current balance: <strong><?php echo get_currency_symbol() . number_format($current_user['balance'], 2); ?></strong></p>
                    <p>Conversion Rate: <strong>1 <?php echo get_currency_code(); ?> = <?php echo $conversion_rate; ?> <?php echo $global_wallet_currency; ?></strong></p>
                    <form action="global-wallet.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="input-group">
                            <input type="number" name="amount" class="form-control" placeholder="Amount in <?php echo get_currency_code(); ?>" required>
                            <button type="submit" name="convert_funds" class="btn btn-primary">Convert</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Fund conversion is currently disabled.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Manual Deposit -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Fund with Cryptocurrency</h5>
            </div>
            <div class="card-body">
                <?php if ($crypto_methods_result->num_rows > 0): ?>
                    <div class="accordion" id="cryptoAccordion">
                        <?php while($method = $crypto_methods_result->fetch_assoc()): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-<?php echo $method['id']; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $method['id']; ?>">
                                        <?php echo htmlspecialchars($method['name']); ?>
                                    </button>
                                </h2>
                                <div id="collapse-<?php echo $method['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#cryptoAccordion">
                                    <div class="accordion-body">
                                        <p><strong>Address:</strong> <code><?php echo htmlspecialchars($method['address']); ?></code></p>
                                        <?php if($method['network']): ?><p><strong>Network:</strong> <?php echo htmlspecialchars($method['network']); ?></p><?php endif; ?>
                                        <?php if($method['instructions']): ?><p><strong>Instructions:</strong> <?php echo nl2br(htmlspecialchars($method['instructions'])); ?></p><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <hr>
                    <h5>Submit Your Deposit Details</h5>
                    <form action="global-wallet.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                         <div class="mb-3">
                            <label for="amount" class="form-label">Amount Sent (in <?php echo $global_wallet_currency; ?>)</label>
                            <input type="number" step="any" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="crypto_type" class="form-label">Cryptocurrency Used</label>
                            <input type="text" name="crypto_type" class="form-control" placeholder="e.g., Bitcoin, USDT" required>
                        </div>
                         <div class="mb-3">
                            <label for="transaction_hash" class="form-label">Transaction ID / Hash (Optional)</label>
                            <input type="text" name="transaction_hash" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="proof_of_payment" class="form-label">Proof of Payment</label>
                            <input type="file" name="proof_of_payment" class="form-control" required accept="image/*,.pdf">
                            <div class="form-text">Upload a screenshot or document of the completed transaction.</div>
                        </div>
                        <button type="submit" name="submit_deposit" class="btn btn-success">Submit for Verification</button>
                    </form>
                <?php else: ?>
                     <p class="text-muted">Manual deposit via cryptocurrency is currently unavailable.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Transaction History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions_result->num_rows > 0): ?>
                        <?php while($tx = $transactions_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo format_date_for_display($tx['created_at']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($tx['crypto_type'])); ?></td>
                                <td><?php echo number_format($tx['amount'], 2); ?> <?php echo htmlspecialchars($tx['currency']); ?></td>
                                <td>
                                    <?php
                                        $status_badge = 'secondary';
                                        if ($tx['status'] == 'approved') $status_badge = 'success';
                                        if ($tx['status'] == 'rejected') $status_badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $status_badge; ?>"><?php echo ucfirst($tx['status']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No transactions yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>