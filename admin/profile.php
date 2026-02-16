<?php
$page_title = 'My Profile';
include 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $current_user['id'];

    // --- Validation ---
    if (empty($username) || empty($email)) {
        $errors[] = "Username and Email are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Check if username or email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username or email is already in use by another account.";
    }
    $stmt->close();

    // Check password fields
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        }
    }

    // --- Update Database ---
    if (empty($errors)) {
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ?, address = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $username, $email, $phone_number, $address, $hashed_password, $user_id);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ?, address = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $email, $phone_number, $address, $user_id);
        }

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            // Refresh the $current_user variable to show updated info on the page
            $stmt_refresh = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt_refresh->bind_param("i", $user_id);
            $stmt_refresh->execute();
            $current_user = $stmt_refresh->get_result()->fetch_assoc();
            $stmt_refresh->close();
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">My Profile</h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success">
        <p class="mb-0"><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Update Your Profile</h5>
        <form action="profile.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($current_user['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($current_user['phone_number'] ?? ''); ?>">
                <div class="form-text">This phone number may be displayed publicly on the landing page.</div>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($current_user['address'] ?? ''); ?></textarea>
                <div class="form-text">This address may be displayed publicly on the landing page.</div>
            </div>
            <hr>
            <h5 class="card-title mt-4">Change Password</h5>
            <p class="text-muted">Leave the password fields blank to keep your current password.</p>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
