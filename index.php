<?php
require_once 'app/bootstrap.php';

// Fetch admin contact details
$admin_stmt = $conn->prepare("SELECT email, phone_number FROM users WHERE id = 1");
$admin_stmt->execute();
$admin_contact = $admin_stmt->get_result()->fetch_assoc();
$admin_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to <?php echo SITE_NAME; ?> - The Ultimate Messaging Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/landing.css">
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
                <ul class="nav-menu">
                    <li><a href="#services">Services</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                <div class="nav-buttons">
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                </div>
                <div class="hamburger">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Powerful, Reliable & Fast Messaging</h1>
            <p>Connect with your customers instantly through our robust Bulk SMS, Voice, OTP, and WhatsApp services.</p>
            <a href="register.php" class="btn btn-primary btn-lg">Get Started for Free</a>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Services</h2>
                <p>A complete suite of messaging solutions to fit your business needs.</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <i class="fas fa-sms"></i>
                    <h3>Bulk SMS</h3>
                    <p>Send promotional or transactional SMS to thousands of users instantly. High delivery rates and detailed reports.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-phone-volume"></i>
                    <h3>Voice SMS</h3>
                    <p>Deliver pre-recorded voice messages to your audience. Perfect for alerts, reminders, and political campaigns.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-key"></i>
                    <h3>OTP Service</h3>
                    <p>Secure your application with our fast and reliable One-Time Password (OTP) service via SMS or Voice.</p>
                </div>
                <div class="service-card">
                    <i class="fab fa-whatsapp"></i>
                    <h3>WhatsApp Messaging</h3>
                    <p>Engage with your customers on the world's most popular messaging app. Send notifications, alerts, and more.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
         <div class="container">
            <div class="section-header">
                <h2>Platform Features</h2>
                <p>Everything you need to run successful campaigns.</p>
            </div>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-code"></i>
                    <h4>Developer API</h4>
                    <p>Integrate our messaging services into your own applications with our simple and powerful API.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-address-book"></i>
                    <h4>Phone Book</h4>
                    <p>Manage your contacts with ease. Create groups, import contacts from files, and personalize messages.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-alt"></i>
                    <h4>Smart Scheduling</h4>
                    <p>Schedule your messages to be sent at the perfect time to maximize engagement and impact.</p>
                </div>
                 <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <h4>Detailed Reports</h4>
                    <p>Track the performance of your campaigns with real-time delivery reports and analytics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="section-header">
                <h2>Get In Touch</h2>
                <p>We are here to help. Contact us for support or any inquiries.</p>
            </div>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <p><?php echo htmlspecialchars($admin_contact['email'] ?? 'admin@example.com'); ?></p>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <p><?php echo htmlspecialchars($admin_contact['phone_number'] ?? '+1234567890'); ?></p>
                </div>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>123 Messaging Lane, Tech City</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="js/landing.js"></script>
</body>
</html>
