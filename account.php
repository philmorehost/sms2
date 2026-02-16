<?php
require_once 'app/bootstrap.php';

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_message = '';
$password_message = '';

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Basic validation
    if (!empty($name) && !empty($email) && !empty($phone)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        if ($stmt->execute()) {
            $profile_message = '<div class="alert alert-success">Profile updated successfully!</div>';
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $profile_message = '<div class="alert alert-danger">Error updating profile.</div>';
        }
        $stmt->close();
    } else {
        $profile_message = '<div class="alert alert-danger">All fields are required.</div>';
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $password_message = '<div class="alert alert-success">Password changed successfully!</div>';
                } else {
                    $password_message = '<div class="alert alert-danger">Error changing password.</div>';
                }
                $stmt->close();
            } else {
                $password_message = '<div class="alert alert-danger">New passwords do not match.</div>';
            }
        } else {
            $password_message = '<div class="alert alert-danger">Incorrect current password.</div>';
        }
    } else {
        $password_message = '<div class="alert alert-danger">All password fields are required.</div>';
    }
}


$page_title = "Account Settings";
include_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Account Settings</h5>
                </div>
                <div class="card-body">
                    <?php echo $profile_message; ?>
                    <!-- Profile Information Form -->
                    <form id="profile-form" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>

                    <hr class="my-4">

                    <!-- Change Password Form -->
                    <h5 class="mt-4">Change Password</h5>
                    <?php echo $password_message; ?>
                    <form id="password-form" method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="current-password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current-password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new-password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new-password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm-password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>

                    <hr class="my-4">

                    <!-- API Key -->
                    <h5 class="mt-4">Developer API Key</h5>
                    <div id="api-key-status"></div>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['api_key'] ?? 'Request API access from the API Docs page.'); ?>" readonly id="api-key-input">
                        <button class="btn btn-outline-secondary" type="button" id="copy-api-key">Copy</button>
                        <?php if ($user['api_access_status'] === 'approved'): ?>
                        <button class="btn btn-outline-danger" type="button" id="regenerate-api-key">Regenerate</button>
                        <?php endif; ?>
                    </div>
                    <p><a href="api-docs.php">View API Documentation</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('copy-api-key').addEventListener('click', function() {
    var apiKeyInput = document.getElementById('api-key-input');
    if (apiKeyInput.value) {
        apiKeyInput.select();
        apiKeyInput.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand('copy');
        alert('API Key copied to clipboard!');
    }
});

<?php if ($user['api_access_status'] === 'approved'): ?>
document.getElementById('regenerate-api-key').addEventListener('click', function() {
    if (confirm('Are you sure you want to regenerate your API key? Your old key will stop working immediately.')) {
        fetch('ajax/regenerate_api_key.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById('api-key-status');
            if (data.success) {
                document.getElementById('api-key-input').value = data.api_key;
                statusDiv.innerHTML = '<div class="alert alert-success">New API key generated successfully!</div>';
            } else {
                statusDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const statusDiv = document.getElementById('api-key-status');
            statusDiv.innerHTML = '<div class="alert alert-danger">An error occurred while regenerating the key.</div>';
        });
    }
});
<?php endif; ?>
</script>

<?php include_once 'includes/footer.php'; ?>
