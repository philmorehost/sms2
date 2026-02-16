<?php
require_once 'app/bootstrap.php';
require_once 'app/helpers.php';

// Check for banned IP address before anything else
if (is_banned($_SERVER['REMOTE_ADDR'], 'ip')) {
    // You can redirect to a generic error page or just exit
    die("Your IP address has been banned.");
}

$errors = [];

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_identifier = trim($_POST['login_identifier']);
    $password = $_POST['password'];

    if (empty($login_identifier)) {
        $errors[] = 'Username or Email is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password, email, is_email_verified FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $login_identifier, $login_identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Check if email is verified
                if ($user['is_email_verified'] == 1) {
                    // Password is correct and user is verified, regenerate session ID to prevent fixation
                    session_regenerate_id(true);

                    // Update last_login and start session
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // User is not verified
                    $_SESSION['flash_message'] = [
                        'type' => 'error',
                        'message' => 'Your account is not verified. Please check your email for the OTP.'
                    ];
                    header("Location: verify-email.php?email=" . urlencode($user['email']));
                    exit();
                }
            } else {
                $errors[] = 'Invalid email or password';
            }
        } else {
            $errors[] = 'Invalid email or password';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
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
            <h2>Welcome Back!</h2>
        <p class="lead-text">Please login to your account.</p>

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="login_identifier" placeholder="Username or Email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>

            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="forgot-password.php">Forgot Password?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
