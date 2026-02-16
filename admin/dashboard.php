<?php
$page_title = 'Admin Dashboard';
include 'includes/header.php';

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

// Total Users Wallet Balance
$stmt_wallet = $conn->prepare("SELECT SUM(balance) as total_balance FROM users WHERE is_admin = 0");
$total_wallet_balance = 0;
if ($stmt_wallet) {
    $stmt_wallet->execute();
    $total_wallet_balance = $stmt_wallet->get_result()->fetch_assoc()['total_balance'] ?? 0;
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
                <h3><?php echo get_currency_symbol() . number_format($total_wallet_balance, 2); ?></h3>
                <p>Users Wallet (Total Balance)</p>
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


<?php include 'includes/footer.php'; ?>
