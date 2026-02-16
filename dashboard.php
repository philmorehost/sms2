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

// 5. Fetch Active Banner Ads
$banner_ads = [];
$banner_stmt = $conn->prepare("SELECT image_path, link FROM banner_ads WHERE expires_at IS NULL OR expires_at > NOW() ORDER BY created_at DESC");
$banner_stmt->execute();
$banner_result = $banner_stmt->get_result();
while ($row = $banner_result->fetch_assoc()) {
    $banner_ads[] = $row;
}
$banner_stmt->close();
?>

<div class="row">
    <!-- Wallet and Services Column -->
    <div class="col-lg-7 col-xl-8 mb-4">
        <div class="row">
            <!-- Wallet Card -->
            <div class="col-12 mb-4">
                <div class="card wallet-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title text-white mb-0">My Wallet</h5>
                            <a href="add-funds.php" class="btn btn-light btn-sm">
                                <i class="fas fa-plus-circle me-1"></i> Add Fund
                            </a>
                        </div>
                        <div class="row text-center">
                            <div class="col-6 border-end border-light">
                                <p class="text-white-50 mb-1">Main Balance</p>
                                <h4 class="text-white fw-bold mb-0"><?php echo get_currency_symbol(); ?><?php echo number_format($user['balance'], 2); ?></h4>
                            </div>
                            <div class="col-6">
                                <p class="text-white-50 mb-1">Referral Bonus</p>
                                <h4 class="text-white fw-bold mb-0"><?php echo get_currency_symbol(); ?><?php echo number_format($user['referral_balance'], 2); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-4 col-md-4 col-lg-4">
                        <a href="send-sms.php" class="service-btn">
                            <i class="fas fa-paper-plane"></i>
                            <span>SMS</span>
                        </a>
                    </div>
                    <div class="col-4 col-md-4 col-lg-4">
                        <a href="send-voice-sms.php" class="service-btn">
                            <i class="fas fa-voicemail"></i>
                            <span>Voice SMS</span>
                        </a>
                    </div>
                    <div class="col-4 col-md-4 col-lg-4">
                        <a href="otp-templates.php" class="service-btn">
                            <i class="fas fa-shield-alt"></i>
                            <span>OTP</span>
                        </a>
                    </div>
                    <div class="col-4 col-md-4 col-lg-4">
                        <a href="send-whatsapp.php" class="service-btn">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                    </div>
                    <div class="col-4 col-md-4 col-lg-4">
                        <a href="referrals.php" class="service-btn">
                            <i class="fas fa-users"></i>
                            <span>Refer</span>
                        </a>
                    </div>
                    <div class="col-4 col-md-4 col-lg-4">
                        <a href="support.php" class="service-btn">
                            <i class="fas fa-headset"></i>
                            <span>Chat Admin</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-6 col-lg-6">
                        <a href="global-sms.php" class="service-btn">
                            <i class="fas fa-globe-americas"></i>
                            <span>Global SMS</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-6 col-lg-6">
                        <a href="global-coverage.php" class="service-btn">
                            <i class="fas fa-map-marked-alt"></i>
                            <span>Global Coverage</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Column -->
    <div class="col-lg-5 col-xl-4 mb-4">
        <!-- Banner Ads Slider -->
        <?php if (!empty($banner_ads)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-images"></i> Promotions</h5>
            </div>
            <div class="card-body">
                <div id="bannerCarousel" class="carousel slide banner-slider" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($banner_ads as $index => $banner): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank">
                                <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" class="d-block w-100" alt="Banner Ad">
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($banner_ads) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-12 col-md-6 col-lg-12">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">Messages Sent</p>
                        <h5 class="stat-value"><?php echo number_format($messages_sent_count); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-12">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">Delivery Rate</p>
                        <h5 class="stat-value"><?php echo number_format($delivery_rate, 1); ?>%</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-12">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">Contacts</p>
                        <h5 class="stat-value"><?php echo number_format($contacts_count); ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity and Transactions -->
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-history"></i> Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_transactions)): ?>
                                <tr><td colspan="4" class="text-center">No recent transactions.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $txn): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($txn['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['description']); ?></td>
                                    <td class="text-nowrap"><?php echo get_currency_symbol(); ?><?php echo number_format($txn['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($txn['status']);
                                            $badge_class = 'bg-secondary';
                                            if (in_array($status, ['completed', 'success'])) $badge_class = 'bg-success';
                                            elseif (in_array($status, ['failed', 'cancelled'])) $badge_class = 'bg-danger';
                                            elseif ($status === 'pending') $badge_class = 'bg-warning';
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
                    type: 'bar',
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
