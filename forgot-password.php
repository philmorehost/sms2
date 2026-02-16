<?php
$page_title = 'Forgot Password';
require_once 'app/bootstrap.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <a href="index.php">
                        <img src="<?php echo SITE_URL . '/' . (get_settings()['site_logo'] ?? 'assets/logo.png'); ?>" alt="<?php echo SITE_NAME; ?> Logo" class="auth-logo">
                    </a>
                    <h1 class="h3"><?php echo $page_title; ?></h1>
                    <p class="text-muted">Enter your email and we'll send you a code to reset your password.</p>
                </div>

                <div id="alert-container"></div>

                <form id="request-otp-form">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send Reset Code</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP & New Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Your Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-alert-container"></div>
                    <form id="reset-password-form">
                        <input type="hidden" id="reset-email" name="email">
                        <div class="mb-3">
                            <label for="otp" class="form-label">OTP Code</label>
                            <input type="text" class="form-control" id="otp" name="otp" required>
                            <div class="form-text">Check your email for the 6-digit code we sent you.</div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/forgot-password.js"></script>
</body>
</html>
