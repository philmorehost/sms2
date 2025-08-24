<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/helpers.php';

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
    <title><?php echo isset($page_title) ? $page_title : 'Welcome'; ?> - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/main.css">
    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" href="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_favicon']); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="wrapper">
        <!-- Main Content Wrapper -->
        <div class="content-wrapper" style="margin-left: 0;">
            <!-- Top Navbar -->
            <header class="main-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="brand-link">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_logo']); ?>" alt="<?php echo SITE_NAME; ?> Logo" class="brand-image" style="max-height: 40px; width: auto;">
                        <?php else: ?>
                            <span class="brand-text"><?php echo SITE_NAME; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="d-flex align-items-center">
                    <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="content">
                <!-- The rest of the page content will be inserted here by other files -->
                <h1 class="h2 mb-4"><?php echo isset($page_title) ? $page_title : ''; ?></h1>
