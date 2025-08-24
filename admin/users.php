<?php
$page_title = 'User Management';
include 'includes/header.php';

$errors = [];
$success = '';

// -- C.R.U.D. LOGIC --

// CREATE User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    // Similar to public registration but without confirmation
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone_number = trim($_POST['phone_number']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if(!empty($username) && !empty($email) && !empty($password) && !empty($phone_number)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $referral_code = substr(md5(uniqid()), 0, 8);

        // API key is no longer generated on creation. It's granted upon approval.
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone_number, is_admin, referral_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $username, $email, $hashed_password, $phone_number, $is_admin, $referral_code);
        if ($stmt->execute()) {
            $success = "User created successfully.";
        } else {
            $errors[] = "Failed to create user. Email or username might be taken.";
        }
        $stmt->close();
    } else {
        $errors[] = "All fields are required to create a user.";
    }
}

// UPDATE User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $user_id_to_edit = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $balance = (float)$_POST['balance'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $status = $_POST['status'];
    $password = $_POST['password'];

    if (!in_array($status, ['active', 'suspended', 'banned'])) {
        $errors[] = "Invalid status provided.";
    } else {
        if (!empty($password)) {
            // If password is set, update it
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone_number=?, balance=?, is_admin=?, status=?, password=? WHERE id=?");
            $stmt->bind_param("sssdissi", $username, $email, $phone_number, $balance, $is_admin, $status, $hashed_password, $user_id_to_edit);
        } else {
            // Otherwise, don't update password
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone_number=?, balance=?, is_admin=?, status=? WHERE id=?");
            $stmt->bind_param("sssdiss", $username, $email, $phone_number, $balance, $is_admin, $status, $user_id_to_edit);
        }

        if ($stmt->execute()) {
            $success = "User updated successfully.";
        } else {
            $errors[] = "Failed to update user.";
        }
        $stmt->close();
    }
}

// DELETE User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = (int)$_POST['user_id'];
    // Basic protection against deleting the main admin
    if ($user_id_to_delete == 1 || $user_id_to_delete == $current_user['id']) {
        $errors[] = "This user cannot be deleted.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        if ($stmt->execute()) {
            $success = "User deleted successfully.";
        } else {
            $errors[] = "Failed to delete user.";
        }
        $stmt->close();
    }
}


// READ Users
$users = [];
$sql = "SELECT u.id, u.username, u.email, u.phone_number, u.balance, u.created_at, u.is_admin, u.is_email_verified, u.status, u.api_access_status, r.username as referrer_username
        FROM users u
        LEFT JOIN users r ON u.referred_by = r.id
        ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}
?>

<div class="row mb-3">
    <div class="col">
        <h3 class="m-0">Registered Users</h3>
    </div>
    <div class="col text-end">
        <div class="btn-group">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Add New User
            </button>
            <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="export_users.php?format=emails"><i class="fas fa-envelope me-2"></i> Export Emails</a></li>
                <li><a class="dropdown-item" href="export_users.php?format=phones"><i class="fas fa-phone me-2"></i> Export Phone Numbers</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="export_users.php?format=full"><i class="fas fa-file-csv me-2"></i> Export Full Details</a></li>
            </ul>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success">
        <p><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Balance</th>
            <th>Is Admin?</th>
            <th>Account Status</th>
            <th>Email Verified?</th>
            <th>API Access</th>
            <th>Referred By</th>
            <th>Registered</th>
            <th>Actions</th>
            </tr>
        </thead>
        <tbody id="user-table-body">
            <?php foreach ($users as $user): ?>
            <tr id="user-row-<?php echo $user['id']; ?>">
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo get_currency_symbol(); ?><?php echo number_format($user['balance'], 2); ?></td>
                <td><?php echo $user['is_admin'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                <td>
                    <?php
                        $status_class = 'bg-secondary';
                        if ($user['status'] == 'active') $status_class = 'bg-success';
                        if ($user['status'] == 'suspended') $status_class = 'bg-warning text-dark';
                        if ($user['status'] == 'banned') $status_class = 'bg-danger';
                        echo '<span class="badge ' . $status_class . '">' . ucfirst($user['status']) . '</span>';
                    ?>
                </td>
                <td class="verification-status-cell">
                    <?php echo $user['is_email_verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?>
                </td>
                <td class="api-status-cell">
                    <?php
                    $status = $user['api_access_status'];
                    $badge_class = 'bg-secondary';
                    if ($status == 'approved') $badge_class = 'bg-success';
                    if ($status == 'requested') $badge_class = 'bg-warning text-dark';
                    if ($status == 'denied') $badge_class = 'bg-danger';
                    echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                    ?>
                </td>
                <td><?php echo htmlspecialchars($user['referrer_username'] ?? 'N/A'); ?></td>
                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                <td class="api-action-cell">
                    <div class="btn-group">
                        <a href="switch_user.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm" title="Login as this user"><i class="fas fa-sign-in-alt"></i></a>
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>" title="Edit user"><i class="fas fa-edit"></i></button>
                        <?php if (!$user['is_email_verified']): ?>
                            <button class="btn btn-warning btn-sm verify-btn" data-user-id="<?php echo $user['id']; ?>" title="Manually verify user"><i class="fas fa-check-circle"></i></button>
                        <?php endif; ?>
                        <form action="users.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm" title="Delete user"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <div class="api-actions mt-1">
                        <?php if ($user['api_access_status'] == 'requested'): ?>
                            <button class="btn btn-success btn-sm api-action-btn" data-action="approve" data-user-id="<?php echo $user['id']; ?>">Approve</button>
                            <button class="btn btn-danger btn-sm api-action-btn" data-action="deny" data-user-id="<?php echo $user['id']; ?>">Deny</button>
                        <?php elseif ($user['api_access_status'] == 'approved'): ?>
                            <button class="btn btn-warning btn-sm api-action-btn" data-action="revoke" data-user-id="<?php echo $user['id']; ?>">Revoke</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="users.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" required>
                    </div>
                     <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_admin" value="1" id="is_admin_add">
                        <label class="form-check-label" for="is_admin_add">Make this user an Administrator</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modals -->
<?php foreach ($users as $user): ?>
<div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="users.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User: <?php echo htmlspecialchars($user['username']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                    </div>
                     <div class="form-group">
                        <label>Balance</label>
                        <input type="number" step="0.01" name="balance" class="form-control" value="<?php echo $user['balance']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_admin" value="1" id="is_admin_edit_<?php echo $user['id']; ?>" <?php if($user['is_admin']) echo 'checked'; ?>>
                        <label class="form-check-label" for="is_admin_edit_<?php echo $user['id']; ?>">Administrator</label>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php if($user['status'] == 'active') echo 'selected'; ?>>Active</option>
                            <option value="suspended" <?php if($user['status'] == 'suspended') echo 'selected'; ?>>Suspended</option>
                            <option value="banned" <?php if($user['status'] == 'banned') echo 'selected'; ?>>Banned</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const userTableBody = document.getElementById('user-table-body');

    userTableBody.addEventListener('click', function(e) {
        const button = e.target.closest('button'); // Get the button element even if the icon is clicked
        if (!button) return;

        if (button.classList.contains('verify-btn')) {
            const userId = button.dataset.userId;

            if (!confirm(`Are you sure you want to manually verify user ID ${userId}?`)) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('ajax/manual_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the UI
                    const statusCell = document.querySelector(`#user-row-${userId} .verification-status-cell`);
                    statusCell.innerHTML = '<span class="badge bg-success">Yes</span>';
                    button.remove(); // Remove the verify button
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-check-circle"></i>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-check-circle"></i>';
            });
        }

        if (button.classList.contains('api-action-btn')) {
            const action = button.dataset.action;
            const userId = button.dataset.userId;

            if (!confirm(`Are you sure you want to ${action} API access for user ID ${userId}?`)) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('../ajax/manage_api_access.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the UI dynamically
                    const statusCell = document.querySelector(`#user-row-${userId} .api-status-cell`);
                    const actionCell = document.querySelector(`#user-row-${userId} .api-action-cell .api-actions`);

                    let newBadgeClass = 'bg-secondary';
                    if (data.new_status === 'approved') newBadgeClass = 'bg-success';
                    if (data.new_status === 'requested') newBadgeClass = 'bg-warning text-dark';
                    if (data.new_status === 'denied') newBadgeClass = 'bg-danger';

                    statusCell.innerHTML = `<span class="badge ${newBadgeClass}">${data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1)}</span>`;

                    let newButtons = '';
                    if (data.new_status === 'approved') {
                        newButtons = `<button class="btn btn-warning btn-sm api-action-btn" data-action="revoke" data-user-id="${userId}">Revoke</button>`;
                    } else if (data.new_status === 'requested') {
                         newButtons = `<button class="btn btn-success btn-sm api-action-btn" data-action="approve" data-user-id="${userId}">Approve</button>
                                       <button class="btn btn-danger btn-sm api-action-btn" data-action="deny" data-user-id="${userId}">Deny</button>`;
                    }
                    // For 'denied' or 'none', no buttons are shown, so newButtons remains empty.

                    actionCell.innerHTML = newButtons;
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                    button.textContent = action.charAt(0).toUpperCase() + action.slice(1);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
                button.disabled = false;
                button.textContent = action.charAt(0).toUpperCase() + action.slice(1);
            });
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>
