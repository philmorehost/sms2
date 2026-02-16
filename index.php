<?php
require_once 'app/bootstrap.php';

$page_title = 'Welcome to ' . SITE_NAME;
include 'includes/public_header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container hero-content">
        <div class="hero-text">
            <h1>Seamless Communication, Powerful Results</h1>
            <p>Elevate your business with our robust and reliable messaging platform. Reach your customers instantly on any channel.</p>
            <div class="hero-buttons">
                <a href="#" id="installAppBtn" class="btn btn-secondary btn-lg" style="display: none;">Download App <i class='bx bx-download'></i></a>
                <a href="register.php" class="btn btn-primary btn-lg">Get Started for Free <i class='bx bx-right-arrow-alt'></i></a>
            </div>
        </div>
        <div class="hero-image">
            <?php
            // Settings are already available from the header include, but get_settings() is cached.
            $settings = get_settings();
            $banner_image_path = $settings['landing_page_banner'] ?? '';
            $alt_text = SITE_NAME . " Banner";
            $final_image_src = 'https://via.placeholder.com/500x500.png?text=Messaging+Platform';

            if (!empty($banner_image_path) && file_exists(__DIR__ . '/' . $banner_image_path)) {
                $final_image_src = SITE_URL . '/' . htmlspecialchars($banner_image_path);
            }
            ?>
            <img src="<?php echo $final_image_src; ?>" alt="<?php echo $alt_text; ?>">
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="services-section">
    <div class="container">
        <div class="section-header">
            <span>Our Services</span>
            <h2>A Complete Suite of Messaging Solutions</h2>
            <p>From simple SMS to interactive WhatsApp campaigns, we've got you covered.</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="card-icon"><i class='bx bxs-message-dots'></i></div>
                <h3>Bulk SMS</h3>
                <p>Send promotional or transactional SMS to thousands of users instantly with high delivery rates.</p>
            </div>
            <div class="service-card">
                <div class="card-icon"><i class='bx bxs-phone-call'></i></div>
                <h3>Voice SMS</h3>
                <p>Deliver pre-recorded voice messages. Perfect for alerts, reminders, and political campaigns.</p>
            </div>
            <div class="service-card">
                <div class="card-icon"><i class='bx bxs-key'></i></div>
                <h3>OTP Service</h3>
                <p>Secure your application with our fast and reliable One-Time Password (OTP) service.</p>
            </div>
            <div class="service-card">
                <div class="card-icon"><i class='bx bxl-whatsapp'></i></div>
                <h3>WhatsApp Messaging</h3>
                <p>Engage customers on the world's most popular messaging app. Send notifications, alerts, and more.</p>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="features-section">
     <div class="container">
        <div class="section-header">
            <span>Platform Features</span>
            <h2>Everything You Need for Success</h2>
            <p>Powerful tools designed to make your communication seamless and effective.</p>
        </div>
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon"><i class='bx bx-code-alt'></i></div>
                <h4>Developer API</h4>
                <p>Integrate our services into your applications with our simple and powerful API.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class='bx bxs-contact'></i></div>
                <h4>Phone Book</h4>
                <p>Manage your contacts with ease. Create groups, import contacts, and personalize messages.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class='bx bx-time'></i></div>
                <h4>Smart Scheduling</h4>
                <p>Schedule your messages to be sent at the perfect time to maximize engagement.</p>
            </div>
             <div class="feature-item">
                <div class="feature-icon"><i class='bx bx-line-chart'></i></div>
                <h4>Detailed Reports</h4>
                <p>Track the performance of your campaigns with real-time delivery reports and analytics.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to elevate your communication?</h2>
        <p>Join thousands of businesses who trust us to deliver their messages.</p>
        <a href="register.php" class="btn btn-primary btn-lg">Sign Up Now</a>
    </div>
</section>

<?php
include 'includes/public_footer.php';
?>
