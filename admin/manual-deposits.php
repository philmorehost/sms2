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

// Processed deposits are now fetched via AJAX
$processed_deposits = [];

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
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-12 col-md-8 mb-2 mb-md-0">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Username or Reference ID...">
            </div>
            <div class="col-12 col-md-4">
                <select id="statusFilter" class="form-select">
                    <option value="">All Processed</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table id="processedDepositsTable" class="table table-bordered table-hover">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const processedTableBody = document.querySelector('#processedDepositsTable tbody');
    const paginationContainer = document.querySelector('.pagination-container');
    let currentPage = 1;
    let searchTimeout;

    function fetchProcessedDeposits() {
        const searchTerm = searchInput.value;
        const status = statusFilter.value;

        processedTableBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('status', status);
        formData.append('page', currentPage);

        fetch('ajax/search_manual_deposits.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                processedTableBody.innerHTML = data.html;
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination;
                }
            } else {
                processedTableBody.innerHTML = '<tr><td colspan="6" class="text-center">An error occurred.</td></tr>';
            }
        })
        .catch(error => {
            processedTableBody.innerHTML = '<tr><td colspan="6" class="text-center">An error occurred. Please try again.</td></tr>';
            console.error('Error:', error);
        });
    }

    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchProcessedDeposits();
        }, 300);
    });

    statusFilter.addEventListener('change', () => {
        currentPage = 1;
        fetchProcessedDeposits();
    });

    if (paginationContainer) {
        paginationContainer.addEventListener('click', function(e) {
            e.preventDefault();
            const link = e.target.closest('a.page-link');
            if (link) {
                const page = link.dataset.page;
                if (page) {
                    currentPage = parseInt(page, 10);
                    fetchProcessedDeposits();
                }
            }
        });
    }

    fetchProcessedDeposits();
});
</script>
<?php include 'includes/footer.php'; ?>
