</main> <!-- .content -->

            <!-- Footer -->
            <footer class="main-footer">
                <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="<?php echo SITE_URL; ?>"><?php echo SITE_NAME; ?></a>.</strong>
                All rights reserved.
            </footer>

            <?php include_once 'includes/bottom_nav.php'; ?>

        </div> <!-- .content-wrapper -->
    </div> <!-- .wrapper -->

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>

    <?php
    // Explicitly close the database connection
    if (isset($conn)) {
        $conn->close();
    }
    ?>
</body>
</html>
