<?php
// This is the admin header. It requires bootstrap.php to run.
// bootstrap.php will handle session start, db connection, and fetching user data.
require_once __DIR__ . '/../../app/bootstrap.php';
// Explicitly including helpers.php to ensure all helper functions are available across the admin panel.
require_once __DIR__ . '/../../app/helpers.php';

// Centralized check for admin access. is_admin() is defined in helpers.php.
// It checks if the user is an admin and not currently impersonating another user.
if (!is_admin()) {
    // If not an admin, redirect to the main site login.
    header("Location: " . SITE_URL . "/login.php");
    exit();
}

// A simple helper function to determine if a nav link should be 'active'
function is_active($page_name) {
    // This will now work for both admin and user pages that might share a script name if needed.
    return basename($_SERVER['PHP_SELF']) == $page_name ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar for Desktop -->
        <aside class="main-sidebar d-none d-lg-block">
            <a href="dashboard.php" class="brand-link">
                <span class="brand-text">Admin Panel</span>
            </a>
            <div class="sidebar">
                <nav class="nav-sidebar">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo is_active('dashboard.php'); ?>"><i class="nav-icon fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="users.php" class="nav-link <?php echo is_active('users.php'); ?>"><i class="nav-icon fas fa-users"></i> User Management</a></li>
                        <li class="nav-item"><a href="sender-ids.php" class="nav-link <?php echo is_active('sender-ids.php'); ?>"><i class="nav-icon fas fa-id-card"></i> Sender IDs</a></li>
                        <li class="nav-item"><a href="caller-ids.php" class="nav-link <?php echo is_active('caller-ids.php'); ?>"><i class="nav-icon fas fa-phone-alt"></i> Caller IDs</a></li>
                        <li class="nav-item"><a href="otp-templates.php" class="nav-link <?php echo is_active('otp-templates.php'); ?>"><i class="nav-icon fas fa-shield-alt"></i> OTP Templates</a></li>
                        <li class="nav-item"><a href="whatsapp-templates.php" class="nav-link <?php echo is_active('whatsapp-templates.php'); ?>"><i class="nav-icon fab fa-whatsapp"></i> WhatsApp Templates</a></li>
                        <li class="nav-item"><a href="transactions.php" class="nav-link <?php echo is_active('transactions.php'); ?>"><i class="nav-icon fas fa-exchange-alt"></i> All Transactions</a></li>
                        <li class="nav-item"><a href="manual-deposits.php" class="nav-link <?php echo is_active('manual-deposits.php'); ?>"><i class="nav-icon fas fa-university"></i> Manual Deposits</a></li>
                        <li class="nav-item"><a href="support-tickets.php" class="nav-link <?php echo is_active('support-tickets.php'); ?>"><i class="nav-icon fas fa-life-ring"></i> Support Tickets</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="nav-icon fas fa-envelope"></i> Email Marketing
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="email-templates.php">Manage Templates</a></li>
                                <li><a class="dropdown-item" href="send-email.php">Send Broadcast</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a href="reports.php" class="nav-link <?php echo is_active('reports.php'); ?>"><i class="nav-icon fas fa-chart-bar"></i> Reports</a></li>
                        <li class="nav-item"><a href="live-reports.php" class="nav-link <?php echo is_active('live-reports.php'); ?>"><i class="nav-icon fas fa-broadcast-tower"></i> Live Reports</a></li>
                        <li class="nav-item"><a href="scheduled-reports.php" class="nav-link <?php echo is_active('scheduled-reports.php'); ?>"><i class="nav-icon fas fa-history"></i> Scheduled Reports</a></li>
                        <li class="nav-item"><a href="banning.php" class="nav-link <?php echo is_active('banning.php'); ?>"><i class="nav-icon fas fa-ban"></i> Banning</a></li>
                        <li class="nav-item"><a href="notifications.php" class="nav-link <?php echo is_active('notifications.php'); ?>"><i class="nav-icon fas fa-bullhorn"></i> Notifications</a></li>
                        <li class="nav-item"><a href="settings.php" class="nav-link <?php echo is_active('settings.php'); ?>"><i class="nav-icon fas fa-cogs"></i> Settings</a></li>
                        <li class="nav-item"><a href="profile.php" class="nav-link <?php echo is_active('profile.php'); ?>"><i class="nav-icon fas fa-user-circle"></i> My Profile</a></li>
                        <li class="nav-item"><a href="logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="content-wrapper">
            <!-- Top Navbar -->
            <header class="main-header d-flex justify-content-between align-items-center">
                 <!-- Mobile Sidebar Toggle -->
                <button class="btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileAdminSidebar" aria-controls="mobileAdminSidebar">
                    <i class="fas fa-bars"></i>
                </button>

                <h1 class="h4 m-0 d-none d-lg-block"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>

                <div class="dropdown">
                    <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="far fa-user-circle me-1"></i> <?php echo htmlspecialchars($current_user['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end text-small">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i> View Reports</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sign out</a></li>
                    </ul>
                </div>
            </header>

            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileAdminSidebar" aria-labelledby="mobileAdminSidebarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="mobileAdminSidebarLabel">Admin Panel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                     <nav class="nav-sidebar">
                        <ul class="nav flex-column">
                            <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo is_active('dashboard.php'); ?>"><i class="nav-icon fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li class="nav-item"><a href="users.php" class="nav-link <?php echo is_active('users.php'); ?>"><i class="nav-icon fas fa-users"></i> User Management</a></li>
                            <li class="nav-item"><a href="sender-ids.php" class="nav-link <?php echo is_active('sender-ids.php'); ?>"><i class="nav-icon fas fa-id-card"></i> Sender IDs</a></li>
                            <li class="nav-item"><a href="caller-ids.php" class="nav-link <?php echo is_active('caller-ids.php'); ?>"><i class="nav-icon fas fa-phone-alt"></i> Caller IDs</a></li>
                            <li class="nav-item"><a href="otp-templates.php" class="nav-link <?php echo is_active('otp-templates.php'); ?>"><i class="nav-icon fas fa-shield-alt"></i> OTP Templates</a></li>
                            <li class="nav-item"><a href="whatsapp-templates.php" class="nav-link <?php echo is_active('whatsapp-templates.php'); ?>"><i class="nav-icon fab fa-whatsapp"></i> WhatsApp Templates</a></li>
                            <li class="nav-item"><a href="transactions.php" class="nav-link <?php echo is_active('transactions.php'); ?>"><i class="nav-icon fas fa-exchange-alt"></i> All Transactions</a></li>
                            <li class="nav-item"><a href="manual-deposits.php" class="nav-link <?php echo is_active('manual-deposits.php'); ?>"><i class="nav-icon fas fa-university"></i> Manual Deposits</a></li>
                            <li class="nav-item"><a href="support-tickets.php" class="nav-link <?php echo is_active('support-tickets.php'); ?>"><i class="nav-icon fas fa-life-ring"></i> Support Tickets</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="nav-icon fas fa-envelope"></i> Email Marketing
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="email-templates.php">Manage Templates</a></li>
                                    <li><a class="dropdown-item" href="send-email.php">Send Broadcast</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a href="reports.php" class="nav-link <?php echo is_active('reports.php'); ?>"><i class="nav-icon fas fa-chart-bar"></i> Reports</a></li>
                            <li class="nav-item"><a href="live-reports.php" class="nav-link <?php echo is_active('live-reports.php'); ?>"><i class="nav-icon fas fa-broadcast-tower"></i> Live Reports</a></li>
                            <li class="nav-item"><a href="scheduled-reports.php" class="nav-link <?php echo is_active('scheduled-reports.php'); ?>"><i class="nav-icon fas fa-history"></i> Scheduled Reports</a></li>
                            <li class="nav-item"><a href="banning.php" class="nav-link <?php echo is_active('banning.php'); ?>"><i class="nav-icon fas fa-ban"></i> Banning</a></li>
                            <li class="nav-item"><a href="notifications.php" class="nav-link <?php echo is_active('notifications.php'); ?>"><i class="nav-icon fas fa-bullhorn"></i> Notifications</a></li>
                            <li class="nav-item"><a href="settings.php" class="nav-link <?php echo is_active('settings.php'); ?>"><i class="nav-icon fas fa-cogs"></i> Settings</a></li>
                            <li class="nav-item"><a href="profile.php" class="nav-link <?php echo is_active('profile.php'); ?>"><i class="nav-icon fas fa-user-circle"></i> My Profile</a></li>
                            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Page Content -->
            <main class="content">
                 <h1 class="h2 mb-4 d-lg-none"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                <!-- The rest of the page content will be inserted here by other files -->
                <div class="card">
                    <div class="card-body">
                        <!-- Page content starts here -->
