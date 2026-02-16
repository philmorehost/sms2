<?php
$page_title = 'Referrals';
require_once 'app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

$settings = get_settings();

// NOTE: The withdrawal logic has been removed as referral bonuses are now automatically added to the main wallet.


// Fetch user's referral data
$referral_link = SITE_URL . '/register.php?ref=' . $user['referral_code'];

// Fetch referred users
$referred_users = [];
$stmt_referred = $conn->prepare("SELECT username, created_at FROM users WHERE referred_by = ?");
$stmt_referred->bind_param("i", $user['id']);
$stmt_referred->execute();
$result_referred = $stmt_referred->get_result();
while($row = $result_referred->fetch_assoc()) {
    $referred_users[] = $row;
}

// Fetch referral earnings from the transactions table
$referral_earnings = [];
$stmt_earnings = $conn->prepare("SELECT amount, description, created_at FROM transactions WHERE user_id = ? AND type = 'referral_bonus' ORDER BY created_at DESC");
$stmt_earnings->bind_param("i", $user['id']);
$stmt_earnings->execute();
$result_earnings = $stmt_earnings->get_result();
while($row = $result_earnings->fetch_assoc()) {
    $referral_earnings[] = $row;
}

?>

<div class="row">
    <!-- Referral Info & Withdrawal -->
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">My Referral Dashboard</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p class="mb-0"><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <div class="row align-items-center">
                    <div class="col-md-12">
                        <p>Share your unique referral link or code with friends. When they sign up and make their first deposit, you'll earn a <strong><?php echo ($settings['referral_bonus_percentage'] ?? '10'); ?>% commission</strong> which will be added directly to your main wallet balance.</p>
                        <div class="form-group">
                            <label for="referral_link">Your Referral Link</label>
                            <div class="input-group">
                                <input type="text" id="referral_link" class="form-control" value="<?php echo $referral_link; ?>" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard()">Copy</button>
                            </div>
                        </div>
                         <p>Your Referral Code: <strong class="text-primary"><?php echo htmlspecialchars($user['referral_code']); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Referred Users List -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Users You've Referred</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Username</th><th>Date Joined</th></tr></thead>
                    <tbody>
                        <?php if(empty($referred_users)): ?>
                            <tr><td colspan="2" class="text-center">You haven't referred any users yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($referred_users as $ru): ?>
                                <tr><td><?php echo htmlspecialchars($ru['username']); ?></td><td><?php echo date('Y-m-d', strtotime($ru['created_at'])); ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Referral Earnings History -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Your Earnings History</h3></div>
            <div class="card-body table-responsive">
                 <table class="table table-sm table-hover">
                    <thead><tr><th>Amount</th><th>Description</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if(empty($referral_earnings)): ?>
                            <tr><td colspan="3" class="text-center">You have no earnings yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($referral_earnings as $re): ?>
                                <tr>
                                    <td class="text-success">+<?php echo get_currency_symbol(); ?><?php echo number_format($re['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($re['description']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($re['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const linkInput = document.getElementById('referral_link');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');
    alert('Referral link copied to clipboard!');
}
</script>

<?php include 'includes/footer.php'; ?>
