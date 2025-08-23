<?php
$page_title = 'Manual Deposit Verification';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle deposit approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_deposit'])) {
    $deposit_id = (int)$_POST['deposit_id'];
    $action = $_POST['action'];

    // Fetch deposit details
    $dep_stmt = $conn->prepare("SELECT * FROM manual_deposits WHERE id = ? AND status = 'pending'");
    $dep_stmt->bind_param("i", $deposit_id);
    $dep_stmt->execute();
    $deposit = $dep_stmt->get_result()->fetch_assoc();

    if ($deposit) {
        $conn->begin_transaction();
        try {
            if ($action == 'approve') {
                // 1. Update user's balance
                $stmt_user = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt_user->bind_param("di", $deposit['amount'], $deposit['user_id']);
                $stmt_user->execute();

                // 2. Update transaction status
                $stmt_trans = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
                $stmt_trans->bind_param("i", $deposit['transaction_id']);
                $stmt_trans->execute();

                // 3. Update invoice status
                $stmt_invoice = $conn->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
                $stmt_invoice->bind_param("i", $deposit['invoice_id']);
                $stmt_invoice->execute();

                // 4. Update manual_deposits status
                $stmt_dep = $conn->prepare("UPDATE manual_deposits SET status = 'approved' WHERE id = ?");
                $stmt_dep->bind_param("i", $deposit_id);
                $stmt_dep->execute();

                $success = "Deposit approved and user's wallet funded successfully.";

            } elseif ($action == 'reject') {
                // 1. Update transaction status
                $stmt_trans = $conn->prepare("UPDATE transactions SET status = 'failed' WHERE id = ?");
                $stmt_trans->bind_param("i", $deposit['transaction_id']);
                $stmt_trans->execute();

                // 2. Update invoice status
                $stmt_invoice = $conn->prepare("UPDATE invoices SET status = 'cancelled' WHERE id = ?");
                $stmt_invoice->bind_param("i", $deposit['invoice_id']);
                $stmt_invoice->execute();

                // 3. Update manual_deposits status
                $stmt_dep = $conn->prepare("UPDATE manual_deposits SET status = 'rejected' WHERE id = ?");
                $stmt_dep->bind_param("i", $deposit_id);
                $stmt_dep->execute();

                $success = "Deposit submission has been rejected.";
            }
            $conn->commit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $errors[] = "Database transaction failed: " . $exception->getMessage();
        }
    } else {
        $errors[] = "Invalid deposit or action already taken.";
    }
}


// Fetch all pending manual deposits
$pending_deposits = [];
$sql = "SELECT md.*, u.username
        FROM manual_deposits md
        JOIN users u ON md.user_id = u.id
        WHERE md.status = 'pending'
        ORDER BY md.created_at ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pending_deposits[] = $row;
    }
    $stmt->close();
}

// Fetch all processed manual deposits (approved or rejected)
$processed_deposits = [];
$sql_processed = "SELECT md.*, u.username
                  FROM manual_deposits md
                  JOIN users u ON md.user_id = u.id
                  WHERE md.status != 'pending'
                  ORDER BY md.created_at DESC
                  LIMIT 50"; // Limit to last 50 for performance
$stmt_processed = $conn->prepare($sql_processed);
if ($stmt_processed) {
    $stmt_processed->execute();
    $result_processed = $stmt_processed->get_result();
    while ($row = $result_processed->fetch_assoc()) {
        $processed_deposits[] = $row;
    }
    $stmt_processed->close();
}

function get_deposit_status_badge($status) {
    $status = strtolower($status);
    $badge_class = 'bg-secondary';
    if ($status === 'approved') {
        $badge_class = 'bg-success';
    } elseif ($status === 'rejected') {
        $badge_class = 'bg-danger';
    } elseif ($status === 'pending') {
        $badge_class = 'bg-warning';
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}
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

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Pending Manual Deposit Submissions</h3>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Username</th>
                    <th>Amount (<?php echo get_currency_symbol(); ?>)</th>
                    <th>User Reference</th>
                    <th>Payment Date</th>
                    <th>Submitted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pending_deposits)): ?>
                    <tr><td colspan="6" class="text-center">No pending deposits found.</td></tr>
                <?php else: ?>
                    <?php foreach ($pending_deposits as $deposit): ?>
                    <tr>
                        <td><a href="users.php?search=<?php echo htmlspecialchars($deposit['username']); ?>"><?php echo htmlspecialchars($deposit['username']); ?></a></td>
                        <td><?php echo get_currency_symbol(); ?><?php echo number_format($deposit['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($deposit['reference_id']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($deposit['payment_date'])); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($deposit['created_at'])); ?></td>
                        <td>
                            <form action="manual-deposits.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to APPROVE this deposit?');">
                                <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" name="update_deposit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form action="manual-deposits.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to REJECT this deposit?');">
                                <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" name="update_deposit" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Processed Deposit History</h3>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Username</th>
                    <th>Amount (<?php echo get_currency_symbol(); ?>)</th>
                    <th>User Reference</th>
                    <th>Payment Date</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($processed_deposits)): ?>
                    <tr><td colspan="6" class="text-center">No processed deposits found.</td></tr>
                <?php else: ?>
                    <?php foreach ($processed_deposits as $deposit): ?>
                    <tr>
                        <td><a href="users.php?search=<?php echo htmlspecialchars($deposit['username']); ?>"><?php echo htmlspecialchars($deposit['username']); ?></a></td>
                        <td><?php echo get_currency_symbol(); ?><?php echo number_format($deposit['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($deposit['reference_id']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($deposit['payment_date'])); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($deposit['created_at'])); ?></td>
                        <td><?php echo get_deposit_status_badge($deposit['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
