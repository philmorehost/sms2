</main> <!-- .content -->

            <!-- Footer -->
            <footer class="main-footer">
                <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="<?php echo SITE_URL; ?>"><?php echo SITE_NAME; ?></a>.</strong>
                All rights reserved.
            </footer>

        </div> <!-- .content-wrapper -->
    </div> <!-- .wrapper -->

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>

    <?php if (!empty($admin_phone_number)): ?>
        <a href="https://wa.me/<?php echo htmlspecialchars($admin_phone_number); ?>" class="whatsapp-float" target="_blank" rel="noopener noreferrer" title="Chat with us on WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    <?php endif; ?>
</body>
</html>
