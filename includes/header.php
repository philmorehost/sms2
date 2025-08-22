<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/helpers.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Fetch user details from the database if needed
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Define a function to check for active link
function is_active($page_name) {
    return basename($_SERVER['PHP_SELF']) == $page_name ? 'active' : '';
}

// Fetch all settings for logo/favicon
$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/main.css">
    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" href="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_favicon']); ?>">
    <?php endif; ?>
</head>
<body>
    <?php if (isset($_SESSION['original_admin_id'])): ?>
        <div class="alert alert-danger text-center mb-0 rounded-0 impersonation-banner">
            You are currently logged in as a different user.
            <a href="admin/return_to_admin.php" class="alert-link fw-bold">Return to your Administrator account</a>.
        </div>
    <?php endif; ?>
    <div class="wrapper">
        <!-- Sidebar for Desktop -->
        <aside class="main-sidebar d-none d-lg-block">
            <a href="dashboard.php" class="brand-link">
                <?php if (!empty($settings['site_logo'])): ?>
                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_logo']); ?>" alt="<?php echo SITE_NAME; ?> Logo" class="brand-image" style="max-height: 40px; width: auto;">
                <?php else: ?>
                    <span class="brand-text"><?php echo SITE_NAME; ?></span>
                <?php endif; ?>
            </a>
            <div class="sidebar">
                <nav class="nav-sidebar">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo is_active('dashboard.php'); ?>"><i class="nav-icon fas fa-tachometer-alt"></i> <span class="nav-link-text">Dashboard</span></a></li>
                        <li class="nav-item"><a href="add-funds.php" class="nav-link <?php echo is_active('add-funds.php'); ?>"><i class="nav-icon fas fa-dollar-sign"></i> <span class="nav-link-text">Add Fund</span></a></li>
                        <li class="nav-item"><a href="pricing.php" class="nav-link <?php echo is_active('pricing.php'); ?>"><i class="nav-icon fas fa-tags"></i> <span class="nav-link-text">Pricing</span></a></li>
                        <li class="nav-item"><a href="send-sms.php" class="nav-link <?php echo is_active('send-sms.php'); ?>"><i class="nav-icon fas fa-paper-plane"></i> <span class="nav-link-text">Send SMS</span></a></li>
                        <li class="nav-item"><a href="send-voice-sms.php" class="nav-link <?php echo is_active('send-voice-sms.php'); ?>"><i class="nav-icon fas fa-voicemail"></i> <span class="nav-link-text">Voice SMS (TTS)</span></a></li>
                        <li class="nav-item"><a href="send-voice-audio.php" class="nav-link <?php echo is_active('send-voice-audio.php'); ?>"><i class="nav-icon fas fa-file-audio"></i> <span class="nav-link-text">Voice From File</span></a></li>
                        <li class="nav-item"><a href="send-whatsapp.php" class="nav-link <?php echo is_active('send-whatsapp.php'); ?>"><i class="nav-icon fab fa-whatsapp"></i> <span class="nav-link-text">WhatsApp</span></a></li>
                        <li class="nav-item"><a href="otp-templates.php" class="nav-link <?php echo is_active('otp-templates.php'); ?>"><i class="nav-icon fas fa-shield-alt"></i> <span class="nav-link-text">OTP Templates</span></a></li>
                        <li class="nav-item"><a href="phonebook.php" class="nav-link <?php echo is_active('phonebook.php'); ?>"><i class="nav-icon fas fa-book"></i> <span class="nav-link-text">Phone Book</span></a></li>
                        <li class="nav-item"><a href="reports.php" class="nav-link <?php echo is_active('reports.php'); ?>"><i class="nav-icon fas fa-chart-bar"></i> <span class="nav-link-text">Reports</span></a></li>
                        <li class="nav-item"><a href="live-reports.php" class="nav-link <?php echo is_active('live-reports.php'); ?>"><i class="nav-icon fas fa-broadcast-tower"></i> <span class="nav-link-text">Live Reports</span></a></li>
                        <li class="nav-item"><a href="schedules.php" class="nav-link <?php echo is_active('schedules.php'); ?>"><i class="nav-icon fas fa-clock"></i> <span class="nav-link-text">Schedules</span></a></li>
                        <li class="nav-item"><a href="scheduled-reports.php" class="nav-link <?php echo is_active('scheduled-reports.php'); ?>"><i class="nav-icon fas fa-history"></i> <span class="nav-link-text">Scheduled Reports</span></a></li>
                        <li class="nav-item"><a href="referrals.php" class="nav-link <?php echo is_active('referrals.php'); ?>"><i class="nav-icon fas fa-users"></i> <span class="nav-link-text">Referrals</span></a></li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="nav-icon fas fa-tools"></i> <span class="nav-link-text">Tools</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="sender-id.php">Sender ID</a></li>
                                <li><a class="dropdown-item" href="caller-id.php">Caller ID</a></li>
                                <li><a class="dropdown-item" href="number-extractor.php">Number Extractor</a></li>
                                <li><a class="dropdown-item" href="number-filter.php">Number Filter</a></li>
                                <li><a class="dropdown-item" href="birthday-scheduler.php">Birthday Scheduler</a></li>
                            </ul>
                        </li>

                        <li class="nav-item"><a href="support.php" class="nav-link <?php echo is_active('support.php'); ?>"><i class="nav-icon fas fa-life-ring"></i> <span class="nav-link-text">Support</span></a></li>
                        <li class="nav-item"><a href="api-docs.php" class="nav-link <?php echo is_active('api-docs.php'); ?>"><i class="nav-icon fas fa-code"></i> <span class="nav-link-text">Dev API</span></a></li>
                        <li class="nav-item"><a href="account.php" class="nav-link <?php echo is_active('account.php'); ?>"><i class="nav-icon fas fa-user-cog"></i> <span class="nav-link-text">Account</span></a></li>
                        <li class="nav-item"><a href="logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i> <span class="nav-link-text">Logout</span></a></li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="content-wrapper">
            <!-- Top Navbar -->
            <header class="main-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <!-- Desktop Sidebar Toggle -->
                    <button id="sidebarToggler" class="btn d-none d-lg-block me-2" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <!-- Mobile Sidebar Toggle -->
                    <button class="btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="h4 m-0 d-none d-lg-block"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                </div>

                <div class="d-flex align-items-center">
                    <div class="me-3">
                        Balance: <strong class="text-primary"><?php echo get_currency_symbol(); ?><?php echo number_format($user['balance'], 2); ?></strong>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="far fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end text-small">
                            <li><a class="dropdown-item" href="account.php">Account Settings</a></li>
                            <li><a class="dropdown-item" href="transactions.php">Transactions</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="mobileSidebarLabel">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_logo']); ?>" alt="<?php echo SITE_NAME; ?> Logo" style="max-height: 40px; width: auto;">
                        <?php else: ?>
                            <?php echo SITE_NAME; ?>
                        <?php endif; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <nav class="nav-sidebar">
                        <ul class="nav flex-column">
                           <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo is_active('dashboard.php'); ?>"><i class="nav-icon fas fa-tachometer-alt"></i> Dashboard</a></li>
                           <li class="nav-item"><a href="add-funds.php" class="nav-link <?php echo is_active('add-funds.php'); ?>"><i class="nav-icon fas fa-dollar-sign"></i> Add Fund</a></li>
                           <li class="nav-item"><a href="pricing.php" class="nav-link <?php echo is_active('pricing.php'); ?>"><i class="nav-icon fas fa-tags"></i> Pricing</a></li>
                           <li class="nav-item"><a href="send-sms.php" class="nav-link <?php echo is_active('send-sms.php'); ?>"><i class="nav-icon fas fa-paper-plane"></i> Send SMS</a></li>
                           <li class="nav-item"><a href="send-voice-sms.php" class="nav-link <?php echo is_active('send-voice-sms.php'); ?>"><i class="nav-icon fas fa-voicemail"></i> Voice SMS (TTS)</a></li>
                           <li class="nav-item"><a href="send-voice-audio.php" class="nav-link <?php echo is_active('send-voice-audio.php'); ?>"><i class="nav-icon fas fa-file-audio"></i> Voice From File</a></li>
                           <li class="nav-item"><a href="send-whatsapp.php" class="nav-link <?php echo is_active('send-whatsapp.php'); ?>"><i class="nav-icon fab fa-whatsapp"></i> WhatsApp</a></li>
                           <li class="nav-item"><a href="otp-templates.php" class="nav-link <?php echo is_active('otp-templates.php'); ?>"><i class="nav-icon fas fa-shield-alt"></i> OTP Templates</a></li>
                           <li class="nav-item"><a href="phonebook.php" class="nav-link <?php echo is_active('phonebook.php'); ?>"><i class="nav-icon fas fa-book"></i> Phone Book</a></li>
                           <li class="nav-item"><a href="reports.php" class="nav-link <?php echo is_active('reports.php'); ?>"><i class="nav-icon fas fa-chart-bar"></i> Reports</a></li>
                           <li class="nav-item"><a href="live-reports.php" class="nav-link <?php echo is_active('live-reports.php'); ?>"><i class="nav-icon fas fa-broadcast-tower"></i> Live Reports</a></li>
                           <li class="nav-item"><a href="schedules.php" class="nav-link <?php echo is_active('schedules.php'); ?>"><i class="nav-icon fas fa-clock"></i> Schedules</a></li>
                           <li class="nav-item"><a href="scheduled-reports.php" class="nav-link <?php echo is_active('scheduled-reports.php'); ?>"><i class="nav-icon fas fa-history"></i> Scheduled Reports</a></li>
                           <li class="nav-item"><a href="referrals.php" class="nav-link <?php echo is_active('referrals.php'); ?>"><i class="nav-icon fas fa-users"></i> Referrals</a></li>

                           <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="nav-icon fas fa-tools"></i> Tools
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="sender-id.php">Sender ID</a></li>
                                    <li><a class="dropdown-item" href="caller-id.php">Caller ID</a></li>
                                    <li><a class="dropdown-item" href="number-extractor.php">Number Extractor</a></li>
                                    <li><a class="dropdown-item" href="number-filter.php">Number Filter</a></li>
                                    <li><a class="dropdown-item" href="birthday-scheduler.php">Birthday Scheduler</a></li>
                                </ul>
                            </li>

                            <li class="nav-item"><a href="support.php" class="nav-link <?php echo is_active('support.php'); ?>"><i class="nav-icon fas fa-life-ring"></i> Support</a></li>
                            <li class="nav-item"><a href="api-docs.php" class="nav-link <?php echo is_active('api-docs.php'); ?>"><i class="nav-icon fas fa-code"></i> Dev API</a></li>
                            <li class="nav-item"><a href="account.php" class="nav-link <?php echo is_active('account.php'); ?>"><i class="nav-icon fas fa-user-cog"></i> Account</a></li>
                            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Page Content -->
            <main class="content">
                <?php
                // Display active notifications
                $notifications = get_active_notifications();
                foreach ($notifications as $notification):
                ?>
                    <div class="alert alert-<?php echo htmlspecialchars($notification['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo $notification['message']; // Assuming message is safe HTML from admin ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>

                <!-- The rest of the page content will be inserted here by other files -->
                <!-- For example, dashboard.php will continue its content here -->
                <h1 class="h2 mb-4 d-lg-none"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
