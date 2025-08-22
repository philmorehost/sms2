<?php
require_once 'app/bootstrap.php';
require_once 'app/helpers.php';

$errors = [];
$success = '';
$email_from_url = $_GET['email'] ?? '';

// Check for flash messages from registration
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    if ($flash['type'] === 'success') {
        $success = $flash['message'];
    } else {
        $errors[] = $flash['message'];
    }
    unset($_SESSION['flash_message']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = trim($_POST['otp']);
    $email = trim($_POST['email']);

    if (empty($otp)) {
        $errors[] = 'OTP is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email address is missing. Please go back to the registration page.';
    }

    if (empty($errors)) {
        // Find the user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            if ($user['is_email_verified']) {
                $success = 'Your email is already verified. You can <a href="login.php">login now</a>.';
            } elseif ($user['email_otp'] == $otp && strtotime($user['otp_expires_at']) > time()) {
                // OTP is correct and not expired
                $stmt_verify = $conn->prepare("UPDATE users SET is_email_verified = 1, email_otp = NULL, otp_expires_at = NULL WHERE id = ?");
                $stmt_verify->bind_param("i", $user['id']);
                if ($stmt_verify->execute()) {
                    // Log the user in automatically
                    $_SESSION['user_id'] = $user['id'];

                    // Set a welcome flash message for the dashboard
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => 'Welcome! Your email has been verified successfully.'
                    ];

                    // Redirect to the dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $errors[] = 'Could not verify your email. Please try again.';
                }
                $stmt_verify->close();
            } elseif ($user['email_otp'] == $otp) {
                // OTP is correct but expired
                $errors[] = 'The OTP has expired. Please request a new one.';
                // (Future enhancement: Add a "Resend OTP" button)
            } else {
                // OTP is incorrect
                $errors[] = 'The OTP you entered is incorrect.';
            }
        } else {
            $errors[] = 'No user found with this email address.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <?php
                    $settings = get_settings();
                    $logo_url = !empty($settings['site_logo']) ? SITE_URL . '/' . htmlspecialchars($settings['site_logo']) : 'https://via.placeholder.com/150x50?text=' . urlencode(SITE_NAME);
                ?>
                <img src="<?php echo $logo_url; ?>" alt="<?php echo SITE_NAME; ?> Logo" style="max-height: 50px; width: auto;">
            </div>
            <h2>Verify Your Email</h2>
            <p class="lead-text">An OTP has been sent to your email address. Please enter it below.</p>

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success">
                    <p><?php echo $success; ?></p>
                </div>
            <?php endif; ?>

            <form action="verify-email.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email_from_url); ?>">
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="text" name="otp" placeholder="Enter OTP" required pattern="\d{6}" title="OTP must be 6 digits.">
                </div>
                <button type="submit" class="btn">Verify</button>
            </form>

            <div class="auth-links">
                <p>Didn't get the code? <a href="register.php">Register again</a> or contact support.</p>
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
