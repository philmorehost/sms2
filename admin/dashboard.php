<?php
$page_title = 'Admin Dashboard';
include 'includes/header.php';

$csrf_token = generate_csrf_token();

// Fetch stats for the dashboard
function get_count($conn, $sql) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        return $count;
    }
    return 0;
}

// Total Users
$total_users = get_count($conn, "SELECT COUNT(id) as count FROM users WHERE is_admin = 0");

// Total Users Wallet Balance (Local + Global)
$stmt_wallet = $conn->prepare("SELECT
    (SELECT SUM(balance) FROM users WHERE is_admin = 0) as total_local,
    (SELECT SUM(balance) FROM global_wallets) as total_global");
$total_local_balance = 0;
$total_global_balance = 0;
if ($stmt_wallet) {
    $stmt_wallet->execute();
    $balances = $stmt_wallet->get_result()->fetch_assoc();
    $total_local_balance = $balances['total_local'] ?? 0;
    $total_global_balance = $balances['total_global'] ?? 0;
    $stmt_wallet->close();
}

// Total Groups
$total_groups = get_count($conn, "SELECT COUNT(id) as count FROM phonebook_groups");
// Total Contacts
$total_contacts = get_count($conn, "SELECT COUNT(id) as count FROM phonebook_contacts");

// Pending Manual Payments
$pending_payments = get_count($conn, "SELECT COUNT(id) as count FROM manual_deposits WHERE status = 'pending'");

?>

<div class="mb-4">
    <h4 class="mb-3">Quick Actions</h4>
    <div class="d-flex flex-wrap gap-2">
        <a href="manual-deposits.php?status=pending" class="btn btn-warning position-relative">
            <i class="fas fa-money-bill-wave me-1"></i> Pending Manual Payments
            <?php if ($pending_payments > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $pending_payments; ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="sender-ids.php?status=pending" class="btn btn-info position-relative">
            <i class="fas fa-id-card me-1"></i> Pending Sender IDs
        </a>
        <a href="support-tickets.php?status=open" class="btn btn-primary">
            <i class="fas fa-headset me-1"></i> Open Support Tickets
        </a>
        <a href="settings.php" class="btn btn-secondary">
            <i class="fas fa-cog me-1"></i> Platform Settings
        </a>
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#creditDebitModal">
            <i class="fas fa-exchange-alt me-1"></i> Credit/Debit User
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-primary">
            <div class="inner">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="users.php" class="stat-box-footer">Manage Users <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-success">
            <div class="inner">
                <h3><?php echo get_currency_symbol() . number_format($total_local_balance, 2); ?></h3>
                <p>Local Wallets Total</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
            <a href="users.php" class="stat-box-footer">View Users <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($total_global_balance, 2); ?></h3>
                <p>Global Wallets Total</p>
            </div>
            <div class="icon">
                <i class="fas fa-globe"></i>
            </div>
            <a href="users.php" class="stat-box-footer">View Users <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-warning">
            <div class="inner">
                <h3><?php echo $total_groups; ?></h3>
                <p>Contact Groups</p>
            </div>
            <div class="icon">
                <i class="fas fa-address-book"></i>
            </div>
            <a href="#" class="stat-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-secondary">
            <div class="inner">
                <h3><?php echo $total_contacts; ?></h3>
                <p>Total Contacts</p>
            </div>
            <div class="icon">
                <i class="fas fa-book"></i>
            </div>
            <a href="#" class="stat-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Recent SMS Report</h3>
                <a href="reports.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Sender</th>
                                <th>Route</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC LIMIT 5");
                            if ($stmt) {
                                $stmt->execute();
                                $recent_messages = $stmt->get_result();
                                while ($msg = $recent_messages->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['username']); ?></td>
                                <td><?php echo htmlspecialchars($msg['sender_id']); ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst($msg['type']); ?></span></td>
                                <td><?php echo get_currency_symbol() . number_format($msg['cost'], 2); ?></td>
                                <td>
                                    <?php
                                    $status = strtolower($msg['status']);
                                    $badge = 'bg-warning';
                                    if ($status == 'success' || $status == 'sent') $badge = 'bg-success';
                                    if ($status == 'failed') $badge = 'bg-danger';
                                    echo "<span class='badge $badge'>" . ucfirst($status) . "</span>";
                                    ?>
                                </td>
                                <td class="small"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; $stmt->close(); } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Recent User Registrations</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Username</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT username, email, phone_number, created_at FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5");
                            if ($stmt) {
                                $stmt->execute();
                                $recent_users_result = $stmt->get_result();
                                while ($row = $recent_users_result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo $row['created_at']; ?></td>
                            </tr>
                            <?php
                                endwhile;
                                $stmt->close();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Credit/Debit Modal -->
<div class="modal fade" id="creditDebitModal" tabindex="-1" aria-labelledby="creditDebitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="creditDebitForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="creditDebitModalLabel">Credit/Debit User Wallet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="creditDebitMessage"></div>
                    <div class="mb-3">
                        <label for="user_search" class="form-label">Search User (Username or Email)</label>
                        <input type="text" class="form-control" id="user_search" autocomplete="off" placeholder="Start typing...">
                        <div id="user_results" class="list-group mt-1" style="position: absolute; z-index: 1000; width: 93%;"></div>
                        <input type="hidden" name="user_id" id="target_user_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Wallet</label>
                        <select class="form-select" name="wallet_type" id="wallet_type" required>
                            <option value="local">Local Wallet (<?php echo get_currency_symbol(); ?>)</option>
                            <option value="global">Global Wallet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select class="form-select" name="action_type" id="action_type" required>
                            <option value="credit">Credit (+)</option>
                            <option value="debit">Debit (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="credit_amount" class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="credit_amount" required min="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" name="description" id="description" rows="2" placeholder="e.g., Manual correction"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="confirmCreditDebitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSearch = document.getElementById('user_search');
    const userResults = document.getElementById('user_results');
    const targetUserId = document.getElementById('target_user_id');
    const creditDebitForm = document.getElementById('creditDebitForm');
    const creditDebitMessage = document.getElementById('creditDebitMessage');

    let searchTimeout;

    userSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        if (query.length < 2) {
            userResults.innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`ajax/search_users_simple.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        data.users.forEach(u => {
                            html += `<a href="#" class="list-group-item list-group-item-action select-user" data-id="${u.id}" data-username="${u.username}">
                                        ${u.username} (${u.email})
                                     </a>`;
                        });
                        userResults.innerHTML = html;
                    }
                });
        }, 300);
    });

    userResults.addEventListener('click', function(e) {
        const item = e.target.closest('.select-user');
        if (item) {
            e.preventDefault();
            targetUserId.value = item.dataset.id;
            userSearch.value = item.dataset.username;
            userResults.innerHTML = '';
        }
    });

    creditDebitForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('confirmCreditDebitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const formData = new FormData(this);
        fetch('ajax/credit_debit_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                creditDebitMessage.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                creditDebitForm.reset();
                targetUserId.value = '';
                // Optional: Refresh dashboard stats
                setTimeout(() => location.reload(), 1500);
            } else {
                creditDebitMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                btn.disabled = false;
                btn.innerHTML = 'Submit';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            creditDebitMessage.innerHTML = '<div class="alert alert-danger">A server error occurred.</div>';
            btn.disabled = false;
            btn.innerHTML = 'Submit';
        });
    });

    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!userSearch.contains(e.target) && !userResults.contains(e.target)) {
            userResults.innerHTML = '';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
