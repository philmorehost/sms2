<?php
$page_title = 'Transaction Management';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

// Handle status updates for manual deposits
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaction'])) {
    $transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    // Fetch the transaction details
    $stmt_trans = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'pending'");
    $stmt_trans->bind_param("i", $transaction_id);
    $stmt_trans->execute();
    $transaction = $stmt_trans->get_result()->fetch_assoc();
    $stmt_trans->close();

    if ($transaction) {
        $conn->begin_transaction();
        try {
            if ($action == 'approve') {
                // 1. Update user's balance
                $stmt_user = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt_user->bind_param("di", $transaction['amount'], $transaction['user_id']);
                $stmt_user->execute();
                $stmt_user->close();

                // 2. Update transaction status
                $stmt_status = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
                $stmt_status->bind_param("i", $transaction_id);
                $stmt_status->execute();
                $stmt_status->close();

                $success = "Transaction approved. User's balance has been updated.";

                // --- Check for Referral Commission ---
                $settings = get_settings();
                $bonus_percentage = (float)($settings['referral_bonus_percentage'] ?? 0);

                if ($bonus_percentage > 0) {
                    $user_id_for_commission = $transaction['user_id'];
                    $deposit_amount = $transaction['amount'];

                    $user_stmt = $conn->prepare("SELECT referred_by FROM users WHERE id = ?");
                    $user_stmt->bind_param("i", $user_id_for_commission);
                    $user_stmt->execute();
                    $referred_user = $user_stmt->get_result()->fetch_assoc();
                    $user_stmt->close();

                    // We check that this is the user's first completed deposit.
                    $deposits_count_stmt = $conn->prepare("SELECT COUNT(id) as count FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'completed'");
                    $deposits_count_stmt->bind_param("i", $user_id_for_commission);
                    $deposits_count_stmt->execute();
                    $deposits_count = $deposits_count_stmt->get_result()->fetch_assoc()['count'];
                    $deposits_count_stmt->close();

                    if ($deposits_count == 1 && !empty($referred_user['referred_by'])) {
                        $referrer_id = $referred_user['referred_by'];
                        $commission_rate = $bonus_percentage / 100;
                        $commission_amount = $deposit_amount * $commission_rate;

                        $ref_update_stmt = $conn->prepare("UPDATE users SET referral_balance = referral_balance + ? WHERE id = ?");
                        $ref_update_stmt->bind_param("di", $commission_amount, $referrer_id);
                        $ref_update_stmt->execute();
                        $ref_update_stmt->close();

                        // Log the earning for history
                        $ref_log_stmt = $conn->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, transaction_id, amount, commission_rate) VALUES (?, ?, ?, ?, ?)");
                        $ref_log_stmt->bind_param("iiidd", $referrer_id, $user_id_for_commission, $transaction_id, $commission_amount, $commission_rate);
                        $ref_log_stmt->execute();
                        $ref_log_stmt->close();

                        $success .= " Referral commission of " . get_currency_symbol() . number_format($commission_amount, 2) . " awarded.";
                    }
                }
                // --- End Referral Check ---

            } elseif ($action == 'reject') {
                // Just update transaction status
                $stmt_status = $conn->prepare("UPDATE transactions SET status = 'failed' WHERE id = ?");
                $stmt_status->bind_param("i", $transaction_id);
                $stmt_status->execute();
                $stmt_status->close();

                $success = "Transaction has been rejected.";
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    } else {
        $errors[] = "Invalid or already processed transaction.";
    }
}


// --- Search and Filter Logic ---
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT t.*, u.username, i.id as invoice_id
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN invoices i ON t.invoice_id = i.id";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_term)) {
    $where_clauses[] = "(u.username LIKE ? OR t.reference LIKE ? OR t.type LIKE ?)";
    $search_param = "%{$search_term}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($status_filter)) {
    $where_clauses[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY t.created_at DESC";

// Fetch all transactions to display
$transactions = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
} else {
    // Handle potential SQL error
    $errors[] = "Error fetching transactions: " . $conn->error;
}

function get_transaction_status_badge($status) {
    $status = strtolower($status);
    $badge_class = 'bg-secondary'; // Default for other statuses
    if (in_array($status, ['completed', 'success', 'approved'])) {
        $badge_class = 'bg-success';
    } elseif (in_array($status, ['failed', 'cancelled', 'rejected'])) {
        $badge_class = 'bg-danger';
    } elseif ($status === 'pending') {
        $badge_class = 'bg-warning';
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

include 'includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success">
        <p><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title m-0">All Transactions</h3>
        <div class="card-tools">
             <!-- Search and Filter Form -->
            <form action="" method="GET" class="form-inline">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search by User, Ref, or Type..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <select name="status" class="form-select form-select-sm" style="max-width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="completed" <?php if(($_GET['status'] ?? '') == 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="pending" <?php if(($_GET['status'] ?? '') == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="failed" <?php if(($_GET['status'] ?? '') == 'failed') echo 'selected'; ?>>Failed</option>
                    </select>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Username</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Gateway</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="9" class="text-center">No transactions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?php echo $trans['id']; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($trans['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($trans['username']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $trans['type'])); ?></td>
                        <td><?php echo get_currency_symbol(); ?><?php echo number_format($trans['amount'], 2); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $trans['gateway'])); ?></td>
                        <td><?php echo htmlspecialchars($trans['reference']); ?></td>
                        <td>
                            <?php echo get_transaction_status_badge($trans['status']); ?>
                        </td>
                        <td>
                            <?php if ($trans['type'] == 'manual_deposit' && $trans['status'] == 'pending'): ?>
                                <!-- Approve/Reject buttons for pending manual deposits -->
                                <form action="transactions.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to APPROVE this transaction and credit the user\\\'s account?');">
                                    <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" name="update_transaction" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form action="transactions.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to REJECT this transaction?');">
                                    <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" name="update_transaction" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php elseif (!empty($trans['invoice_id'])): ?>
                                <!-- View Invoice button for all other transactions that have an invoice -->
                                <a href="view-invoice.php?id=<?php echo $trans['invoice_id']; ?>" class="btn btn-info btn-sm">View Invoice</a>
                            <?php else: ?>
                                <!-- Fallback for transactions without an invoice -->
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
