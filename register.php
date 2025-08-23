<?php
require_once 'app/bootstrap.php';
require_once 'app/helpers.php';

$errors = [];
$success = '';

// Check for referral code in URL
$ref_code_from_url = '';
if (isset($_GET['ref'])) {
    $ref_code_from_url = htmlspecialchars(trim($_GET['ref']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = trim($_POST['phone_number']);
    $referral_code_input = trim($_POST['referral_code']);
    $referred_by_id = null;

    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    if (empty($phone_number)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone_number)) {
        $errors[] = 'Please enter a valid phone number (10-15 digits, no symbols).';
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = 'Username or email already taken';
    }
    $stmt->close();

    // --- Security Checks using Helper Functions ---
    if (is_banned($_SERVER['REMOTE_ADDR'], 'ip')) {
        $errors[] = "Registration from this IP address is not allowed.";
    }
    if (is_banned($email, 'email')) {
        $errors[] = "This email address is not allowed.";
    }
    // Check email domain
    $email_domain = substr(strrchr($email, "@"), 1);
    if ($email_domain && is_banned($email_domain, 'email_domain')) {
        $errors[] = "Registration with this email provider is not allowed.";
    }
    if (contains_banned_word($username)) {
        $errors[] = "The chosen username contains a banned word.";
    }
    // --- End Security Checks ---


    // Check if referral code is valid
    if (!empty($referral_code_input)) {
        $stmt_ref = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt_ref->bind_param("s", $referral_code_input);
        $stmt_ref->execute();
        $result_ref = $stmt_ref->get_result();
        if ($referrer = $result_ref->fetch_assoc()) {
            $referred_by_id = $referrer['id'];
        } else {
            $errors[] = "The provided referral code is not valid.";
        }
        $stmt_ref->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $new_user_referral_code = substr(md5(uniqid()), 0, 8);
        $otp = random_int(100000, 999999);
        $otp_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone_number, referral_code, referred_by, email_otp, otp_expires_at, is_email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssssiss", $username, $email, $hashed_password, $phone_number, $new_user_referral_code, $referred_by_id, $otp, $otp_expires_at);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // Send OTP email to user
            $subject = "Your " . SITE_NAME . " Verification Code";
            $body = "
                <p>Hello,</p>
                <p>Your verification code for " . SITE_NAME . " is:</p>
                <p><strong>" . $otp . "</strong></p>
                <p>This code will expire in 15 minutes.</p>
                <p>If you did not request this, you can safely ignore this email.</p>
            ";

            $email_result = send_email($email, $subject, $body);

            if ($email_result['success']) {
                // Send notification to admin
                $admin_email = get_admin_email();
                $admin_subject = "New User Registration (Pending Verification)";
                $admin_message = "<p>A new user has registered and is pending email verification:</p><ul><li>Username: " . htmlspecialchars($username) . "</li><li>Email: " . htmlspecialchars($email) . "</li></ul>";
                send_email($admin_email, $admin_subject, $admin_message);

                // Set a flash message and redirect
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Registration successful! An OTP has been sent to ' . htmlspecialchars($email) . '. Please check your inbox and spam folder.'
                ];
                header("Location: verify-email.php?email=" . urlencode($email));
                exit();

            } else {
                // Email failed to send. This is a problem.
                // We should delete the user we just created so they can try again.
                $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();
                $delete_stmt->close();
                $errors[] = "Could not send verification email. Please try registering again. Error: " . $email_result['message'];
            }

        } else {
            $errors[] = 'Something went wrong with registration. Please try again later.';
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
    <title>Register - <?php echo SITE_NAME; ?></title>
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
            <h2>Create an Account</h2>
        <p class="lead-text">Join our platform to start sending SMS.</p>

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
            <?php else: ?>
                <form action="register.php" method="POST">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone_number" placeholder="Phone Number" required value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-gift"></i>
                        <input type="text" name="referral_code" placeholder="Referral Code (Optional)" value="<?php echo $ref_code_from_url; ?>">
                    </div>
                    <button type="submit" class="btn">Register</button>
                </form>
            <?php endif; ?>

            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
