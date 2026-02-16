<?php
$page_title = 'Terms of Service';
require_once 'app/bootstrap.php';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="card">
        <div class="card-header">
            <h1>Terms of Service</h1>
        </div>
        <div class="card-body">
            <p>Welcome to <?php echo SITE_NAME; ?>!</p>

            <h2>1. Introduction</h2>
            <p>These Terms of Service ("Terms") govern your use of the <?php echo SITE_NAME; ?> website and the services offered on it. By accessing or using our service, you agree to be bound by these Terms.</p>

            <h2>2. Use of Our Service</h2>
            <p>You must be at least 18 years old to use our service. You are responsible for any activity that occurs through your account and you agree you will not sell, transfer, license or assign your account, followers, username, or any account rights.</p>

            <h2>3. Content</h2>
            <p>Our Service allows you to post, link, store, share and otherwise make available certain information, text, graphics, videos, or other material ("Content"). You are responsible for the Content that you post on or through the Service, including its legality, reliability, and appropriateness.</p>

            <h2>4. Prohibited Uses</h2>
            <p>You may not use the service for any illegal or unauthorized purpose. You agree to comply with all laws, rules, and regulations applicable to your use of the service and your Content, including but not limited to, copyright laws.</p>

            <h2>5. Termination</h2>
            <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>

            <h2>6. Changes to Terms</h2>
            <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will provide notice of any changes by posting the new Terms of Service on this page.</p>

            <h2>7. Contact Us</h2>
            <p>If you have any questions about these Terms, please contact us.</p>

            <p><em>Last updated: <?php echo date('F j, Y'); ?></em></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
