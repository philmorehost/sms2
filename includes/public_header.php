<?php
// This header is for public-facing pages that do not require a login.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/landing.css">
    <?php
    // We need to fetch settings for the favicon
    $settings = get_settings();
    if (!empty($settings['site_favicon'])):
    ?>
        <link rel="icon" href="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_favicon']); ?>">
    <?php endif; ?>
    <!-- PWA -->
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.php">
    <meta name="theme-color" content="<?php echo htmlspecialchars($settings['pwa_theme_color'] ?? '#0d6efd'); ?>">
</head>
<body>
<?php if (isset($settings['pwa_enabled']) && $settings['pwa_enabled'] == '1'): ?>
<script>
    // If PWA is enabled, register the service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo SITE_URL; ?>/sw.js').then(registration => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, err => {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
</script>
<?php else: ?>
<script>
    // If PWA is disabled, unregister any existing service workers to remove the PWA for users.
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.unregister();
                console.log('ServiceWorker unregistered successfully.');
            }
        }).catch(function(err) {
            console.log('Service Worker unregistration failed: ', err);
        });
    }
</script>
<?php endif; ?>

    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <?php if (!empty($settings['site_logo'])): ?>
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_logo']); ?>" alt="<?php echo SITE_NAME; ?> Logo" style="max-height: 40px; width: auto;">
                    <?php else: ?>
                        <?php echo SITE_NAME; ?>
                    <?php endif; ?>
                </a>
                <div class="nav-menu-wrapper">
                    <ul class="nav-menu">
                        <?php
                        $header_menu_items = get_menu_items('header');
                        foreach ($header_menu_items as $item) {
                            echo '<li><a href="' . htmlspecialchars($item['link']) . '" class="nav-link">' . htmlspecialchars($item['label']) . '</a></li>';
                        }
                        ?>
                        <li class="nav-menu-login-btn"><a href="login.php" class="nav-link">Login</a></li>
                    </ul>
                </div>
                <div class="nav-buttons">
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                </div>
                <div class="hamburger">
                    <i class='bx bx-menu'></i>
                </div>
            </nav>
        </div>
    </header>

    <main class="public-main">
