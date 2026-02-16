<?php
$page_title = 'Global Manual Deposits';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    // Handle status updates (Approve/Reject)
    if (isset($_POST['update_deposit_status'])) {
        $deposit_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $action = $_POST['update_deposit_status']; // 'approve' or 'reject'

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT * FROM global_manual_deposits WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("i", $deposit_id);
            $stmt->execute();
            $deposit = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$deposit) {
                throw new Exception("Deposit not found or already processed.");
            }

            if ($action === 'approve') {
                $stmt_update = $conn->prepare("UPDATE global_manual_deposits SET status = 'approved' WHERE id = ?");
                $stmt_update->bind_param("i", $deposit_id);
                $stmt_update->execute();
                $stmt_update->close();

                $stmt_wallet = $conn->prepare("INSERT INTO global_wallets (user_id, balance) VALUES (?, 0) ON DUPLICATE KEY UPDATE balance = balance");
                $stmt_wallet->bind_param("i", $deposit['user_id']);
                $stmt_wallet->execute();
                $stmt_wallet->close();

                $stmt_credit = $conn->prepare("UPDATE global_wallets SET balance = balance + ? WHERE user_id = ?");
                $stmt_credit->bind_param("di", $deposit['amount'], $deposit['user_id']);
                $stmt_credit->execute();
                $stmt_credit->close();

                $success = "Deposit approved and user's global wallet credited.";

            } elseif ($action === 'reject') {
                $stmt_update = $conn->prepare("UPDATE global_manual_deposits SET status = 'rejected' WHERE id = ?");
                $stmt_update->bind_param("i", $deposit_id);
                $stmt_update->execute();
                $stmt_update->close();
                $success = "Deposit rejected successfully.";
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }

    // Handle delete
    if (isset($_POST['delete_deposit'])) {
        $deposit_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $stmt = $conn->prepare("DELETE FROM global_manual_deposits WHERE id = ?");
        $stmt->bind_param("i", $deposit_id);
        if ($stmt->execute()) {
            $success = "Deposit record deleted successfully.";
        } else {
            $errors[] = "Failed to delete deposit record.";
        }
        $stmt->close();
    }
}


// --- Filtering Logic ---
$allowed_statuses = ['pending', 'approved', 'rejected'];
$status_filter = $_GET['status'] ?? ''; // Use null coalescing to prevent undefined key notice

if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = ''; // Default to all if invalid status is provided
}

// --- Database Query ---
$sql = "SELECT d.*, u.username FROM global_manual_deposits d JOIN users u ON d.user_id = u.id";
$params = [];
$types = '';

if ($status_filter) {
    $sql .= " WHERE d.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql .= " ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$deposits_result = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Global Manual Deposits</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="m-0">Deposit Requests</h5>
        <form action="global-deposits.php" method="GET" class="d-flex">
            <select name="status" class="form-select me-2" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?php if ($status_filter == 'pending') echo 'selected'; ?>>Pending</option>
                <option value="approved" <?php if ($status_filter == 'approved') echo 'selected'; ?>>Approved</option>
                <option value="rejected" <?php if ($status_filter == 'rejected') echo 'selected'; ?>>Rejected</option>
            </select>
            <noscript><button type="submit" class="btn btn-primary">Filter</button></noscript>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Crypto Type</th>
                        <th>Hash</th>
                        <th>Proof</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($deposit = $deposits_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($deposit['username']); ?></td>
                        <td><?php echo number_format($deposit['amount'], 2); ?> <?php echo htmlspecialchars($deposit['currency']); ?></td>
                        <td><?php echo htmlspecialchars($deposit['crypto_type']); ?></td>
                        <td><?php echo htmlspecialchars($deposit['transaction_hash'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($deposit['proof_of_payment']); ?>" target="_blank">View Proof</a>
                        </td>
                        <td><?php echo format_date_for_display($deposit['created_at']); ?></td>
                        <td>
                            <?php
                                $status_badge = 'secondary';
                                if ($deposit['status'] == 'approved') $status_badge = 'success';
                                if ($deposit['status'] == 'rejected') $status_badge = 'danger';
                            ?>
                            <span class="badge bg-<?php echo $status_badge; ?>"><?php echo ucfirst($deposit['status']); ?></span>
                        </td>
                        <td>
                            <?php if ($deposit['status'] == 'pending'): ?>
                                <form action="global-deposits.php" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="id" value="<?php echo $deposit['id']; ?>">
                                    <button type="submit" name="update_deposit_status" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this deposit?');">Approve</button>
                                    <button type="submit" name="update_deposit_status" value="reject" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to reject this deposit?');">Reject</button>
                                </form>
                            <?php endif; ?>
                            <form action="global-deposits.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this record?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="id" value="<?php echo $deposit['id']; ?>">
                                <button type="submit" name="delete_deposit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
