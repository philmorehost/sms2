</main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
                    <p>The ultimate messaging platform to connect with your customers.</p>
                    <div class="social-links">
                        <?php
                        // Settings are already fetched in the public header, but we can call it again
                        // safely as it's cached.
                        $settings = get_settings();
                        $facebook_url = $settings['social_facebook'] ?? '';
                        $twitter_url = $settings['social_twitter'] ?? '';
                        $linkedin_url = $settings['social_linkedin'] ?? '';
                        ?>
                        <?php if (!empty($facebook_url)): ?>
                            <a href="<?php echo htmlspecialchars($facebook_url); ?>" target="_blank" rel="noopener noreferrer"><i class='bx bxl-facebook'></i></a>
                        <?php endif; ?>
                        <?php if (!empty($twitter_url)): ?>
                            <a href="<?php echo htmlspecialchars($twitter_url); ?>" target="_blank" rel="noopener noreferrer"><i class='bx bxl-twitter'></i></a>
                        <?php endif; ?>
                        <?php if (!empty($linkedin_url)): ?>
                            <a href="<?php echo htmlspecialchars($linkedin_url); ?>" target="_blank" rel="noopener noreferrer"><i class='bx bxl-linkedin'></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $company_menu_items = get_menu_items('footer_company');
                if (!empty($company_menu_items)):
                ?>
                <div class="footer-col">
                    <h4>Company</h4>
                    <ul>
                        <?php
                        foreach ($company_menu_items as $item) {
                            echo '<li><a href="' . htmlspecialchars($item['link']) . '">' . htmlspecialchars($item['label']) . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php
                $support_menu_items = get_menu_items('footer_support');
                if (!empty($support_menu_items)):
                ?>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <?php
                        foreach ($support_menu_items as $item) {
                            echo '<li><a href="' . htmlspecialchars($item['link']) . '">' . htmlspecialchars($item['label']) . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>
                <div class="footer-col">
                    <h4>Get In Touch</h4>
                    <ul class="contact-info">
                        <?php
                        // We need to fetch admin contact details again for the footer
                        $admin_stmt_footer = $conn->prepare("SELECT email, phone_number, address FROM users WHERE id = 1");
                        $admin_stmt_footer->execute();
                        $admin_contact_footer = $admin_stmt_footer->get_result()->fetch_assoc();
                        $admin_stmt_footer->close();
                        ?>
                        <li><i class='bx bxs-map'></i> <?php echo htmlspecialchars($admin_contact_footer['address'] ?? '123 Messaging Lane, Tech City'); ?></li>
                        <li><i class='bx bxs-envelope'></i> <?php echo htmlspecialchars($admin_contact_footer['email'] ?? 'admin@example.com'); ?></li>
                        <li><i class='bx bxs-phone'></i> <?php echo htmlspecialchars($admin_contact_footer['phone_number'] ?? '+1234567890'); ?></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/landing.js"></script>

    <?php if (!empty($admin_contact_footer['phone_number'])): ?>
        <a href="https://wa.me/<?php echo htmlspecialchars($admin_contact_footer['phone_number']); ?>" class="whatsapp-float" target="_blank" rel="noopener noreferrer" title="Chat with us on WhatsApp">
            <i class='bx bxl-whatsapp'></i>
        </a>
    <?php endif; ?>
</body>
</html>
