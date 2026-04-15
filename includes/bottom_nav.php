<?php
$is_admin_nav = defined('IS_ADMIN_AREA') && IS_ADMIN_AREA === true;
?>
<div class="bottom-nav d-lg-none">
    <div class="container-fluid">
        <div class="row text-center g-0">
            <?php if ($is_admin_nav): ?>
                <div class="col">
                    <a href="dashboard.php" class="nav-item <?php echo is_active('dashboard.php'); ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="col">
                    <a href="users.php" class="nav-item <?php echo is_active('users.php'); ?>">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </div>
                <div class="col">
                    <a href="manual-deposits.php" class="nav-item <?php echo is_active('manual-deposits.php'); ?>">
                        <i class="fas fa-university"></i>
                        <span>Deposits</span>
                    </a>
                </div>
                <div class="col">
                    <a href="reports.php" class="nav-item <?php echo is_active('reports.php'); ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </div>
                <div class="col">
                    <a href="settings.php" class="nav-item <?php echo is_active('settings.php'); ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Settings</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="col">
                    <a href="dashboard.php" class="nav-item <?php echo is_active('dashboard.php'); ?>">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </div>
                <div class="col">
                    <a href="send-sms.php" class="nav-item <?php echo is_active('send-sms.php'); ?>">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send SMS</span>
                    </a>
                </div>
                <div class="col">
                    <a href="add-funds.php" class="nav-item <?php echo is_active('add-funds.php'); ?>">
                        <i class="fas fa-wallet"></i>
                        <span>Deposit</span>
                    </a>
                </div>
                <div class="col">
                    <a href="reports.php" class="nav-item <?php echo is_active('reports.php'); ?>">
                        <i class="fas fa-history"></i>
                        <span>History</span>
                    </a>
                </div>
                <div class="col">
                    <a href="support.php" class="nav-item <?php echo is_active('support.php'); ?>">
                        <i class="fas fa-headset"></i>
                        <span>Support</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
