<?php
$page_title = 'Privacy Policy';
require_once 'app/bootstrap.php';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="card">
        <div class="card-header">
            <h1>Privacy Policy</h1>
        </div>
        <div class="card-body">
            <p>Your privacy is important to us. It is <?php echo SITE_NAME; ?>'s policy to respect your privacy regarding any information we may collect from you across our website.</p>

            <h2>1. Information We Collect</h2>
            <p>We only ask for personal information when we truly need it to provide a service to you. We collect it by fair and lawful means, with your knowledge and consent. We also let you know why we’re collecting it and how it will be used.</p>
            <p>Information we collect includes:</p>
            <ul>
                <li>Name</li>
                <li>Email address</li>
                <li>Phone number</li>
                <li>Billing information</li>
                <li>Usage data</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>We use the information we collect in various ways, including to:</p>
            <ul>
                <li>Provide, operate, and maintain our website</li>
                <li>Improve, personalize, and expand our website</li>
                <li>Understand and analyze how you use our website</li>
                <li>Develop new products, services, features, and functionality</li>
                <li>Communicate with you, either directly or through one of our partners, including for customer service, to provide you with updates and other information relating to the website, and for marketing and promotional purposes</li>
                <li>Send you emails</li>
                <li>Find and prevent fraud</li>
            </ul>

            <h2>3. Log Files</h2>
            <p><?php echo SITE_NAME; ?> follows a standard procedure of using log files. These files log visitors when they visit websites. The information collected by log files include internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring/exit pages, and possibly the number of clicks. These are not linked to any information that is personally identifiable.</p>

            <h2>4. Cookies</h2>
            <p>Like any other website, <?php echo SITE_NAME; ?> uses 'cookies'. These cookies are used to store information including visitors' preferences, and the pages on the website that the visitor accessed or visited. The information is used to optimize the users' experience by customizing our web page content based on visitors' browser type and/or other information.</p>

            <h2>5. Security of Your Information</h2>
            <p>We take the security of your data seriously and use appropriate technical and organizational measures to protect it against unauthorized or unlawful processing and against accidental loss, destruction, or damage.</p>

            <h2>6. Changes to This Privacy Policy</h2>
            <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>

            <h2>7. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact us.</p>

            <p><em>Last updated: <?php echo date('F j, Y'); ?></em></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
