<?php
$page_title = 'Dashboard';
include 'includes/header.php';

// Fetch stats for the dashboard
// 1. Messages Sent
// -- MODIFIED: Only count successful messages --
$msg_sent_stmt = $conn->prepare("SELECT COUNT(id) as count FROM messages WHERE user_id = ? AND status = 'success'");
$msg_sent_stmt->bind_param("i", $user['id']);
$msg_sent_stmt->execute();
$messages_sent_count = $msg_sent_stmt->get_result()->fetch_assoc()['count'];
$msg_sent_stmt->close();

// 2. Contacts
$contacts_stmt = $conn->prepare("SELECT COUNT(id) as count FROM phonebook_contacts WHERE user_id = ?");
$contacts_stmt->bind_param("i", $user['id']);
$contacts_stmt->execute();
$contacts_count = $contacts_stmt->get_result()->fetch_assoc()['count'];
$contacts_stmt->close();

// 3. Delivery Rate
// -- MODIFIED: Use 'success' status which is now being logged --
$total_sent_stmt = $conn->prepare("SELECT COUNT(id) as count FROM messages WHERE user_id = ? AND (status = 'success' OR status = 'failed')");
$total_sent_stmt->bind_param("i", $user['id']);
$total_sent_stmt->execute();
$total_sent_count = $total_sent_stmt->get_result()->fetch_assoc()['count'];
$total_sent_stmt->close();

$completed_sent_stmt = $conn->prepare("SELECT COUNT(id) as count FROM messages WHERE user_id = ? AND status = 'success'");
$completed_sent_stmt->bind_param("i", $user['id']);
$completed_sent_stmt->execute();
$completed_sent_count = $completed_sent_stmt->get_result()->fetch_assoc()['count'];
$completed_sent_stmt->close();

if ($total_sent_count > 0) {
    $delivery_rate = ($completed_sent_count / $total_sent_count) * 100;
} else {
    $delivery_rate = 0;
}

// 4. Recent Transactions
$recent_transactions = [];
$trans_stmt = $conn->prepare("SELECT created_at, description, amount, status FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$trans_stmt->bind_param("i", $user['id']);
$trans_stmt->execute();
$result = $trans_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
}
$trans_stmt->close();
?>

<div class="quick-links-container mb-4">
    <h5 class="mb-3">Quick Actions</h5>
    <div class="row">
        <div class="col-md-4 col-lg-2 mb-3">
            <a href="send-sms.php" class="quick-link-btn">
                <i class="fas fa-paper-plane"></i>
                <span>Send SMS</span>
            </a>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <a href="send-voice-sms.php" class="quick-link-btn">
                <i class="fas fa-voicemail"></i>
                <span>Voice SMS</span>
            </a>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <a href="otp-templates.php" class="quick-link-btn">
                <i class="fas fa-shield-alt"></i>
                <span>OTP Templates</span>
            </a>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <a href="send-whatsapp.php" class="quick-link-btn">
                <i class="fab fa-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <a href="referrals.php" class="quick-link-btn">
                <i class="fas fa-users"></i>
                <span>Referral Bonuses</span>
            </a>
        </div>
        <div class="col-md-4 col-lg-2 mb-3">
            <a href="support.php" class="quick-link-btn">
                <i class="fas fa-headset"></i>
                <span>Chat Admin</span>
            </a>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-6 col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-primary">
            <div class="inner">
                <h3><?php echo number_format($messages_sent_count); ?></h3>
                <p>Messages Sent</p>
            </div>
            <div class="icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <a href="reports.php" class="stat-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-success">
            <div class="inner">
                <h3><?php echo number_format($delivery_rate, 1); ?><sup style="font-size: 1.2rem;">%</sup></h3>
                <p>Delivery Rate</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="reports.php" class="stat-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-warning text-dark">
            <div class="inner">
                <h3><?php echo number_format($contacts_count); ?></h3>
                <p>Contacts</p>
            </div>
            <div class="icon">
                <i class="fas fa-address-book"></i>
            </div>
            <a href="#" class="stat-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-info">
            <div class="inner">
                <h3><?php echo get_currency_symbol(); ?><?php echo number_format($user['balance'], 2); ?></h3>
                <p>Wallet Balance (<?php echo get_currency_code(); ?>)</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
            <a href="add-funds.php" class="stat-box-footer">Add Funds <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Activity Chart -->
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Transactions</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_transactions)): ?>
                                <tr><td colspan="5" class="text-center">No recent transactions.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $index => $txn): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?>.</td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($txn['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['description']); ?></td>
                                    <td><?php echo get_currency_symbol(); ?><?php echo number_format($txn['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($txn['status']);
                                            $badge_class = 'bg-secondary';
                                            if ($status == 'completed') $badge_class = 'bg-success';
                                            if ($status == 'failed' || $status == 'cancelled') $badge_class = 'bg-danger';
                                            if ($status == 'pending') $badge_class = 'bg-warning';
                                            echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('activityChart').getContext('2d');

    fetch('api/get_chart_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                new Chart(ctx, {
                    type: 'line',
                    data: data.chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0 // Ensure y-axis shows whole numbers
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false // Hide legend as there's only one dataset
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        hover: {
                            mode: 'nearest',
                            intersect: true
                        }
                    }
                });
            } else {
                console.error('Failed to load chart data:', data.message);
            }
        })
        .catch(error => console.error('Error fetching chart data:', error));
});
</script>

<?php include 'includes/footer.php'; ?>
