<?php
$page_title = 'User Management';
include 'includes/header.php';

// Generate CSRF token for all forms on this page
$csrf_token = generate_csrf_token();

$errors = [];
$success = '';

// -- C.R.U.D. LOGIC --

// CREATE User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
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
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $user_id_to_edit = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $balance = (float)$_POST['balance'];
    $profit_percentage = (float)$_POST['profit_percentage'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $password = $_POST['password'];

    if (!empty($password)) {
        // If password is set, update it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone_number=?, balance=?, profit_percentage=?, is_admin=?, password=? WHERE id=?");
        $stmt->bind_param("sssddisi", $username, $email, $phone_number, $balance, $profit_percentage, $is_admin, $hashed_password, $user_id_to_edit);
    } else {
        // Otherwise, don't update password
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone_number=?, balance=?, profit_percentage=?, is_admin=? WHERE id=?");
        $stmt->bind_param("sssddii", $username, $email, $phone_number, $balance, $profit_percentage, $is_admin, $user_id_to_edit);
    }

    if ($stmt->execute()) {
        $success = "User updated successfully.";
    } else {
        $errors[] = "Failed to update user.";
    }
    $stmt->close();
}

// DELETE User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
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


// The user list is now loaded entirely by Javascript to ensure modals are always in sync.
$users = [];
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

<div class="row mb-3">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by username, email, or phone...">
    </div>
    <div class="col-12 col-md-3 mb-2 mb-md-0">
        <select id="statusFilter" class="form-select">
            <option value="">All API Access Statuses</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="denied">Denied</option>
            <option value="none">None</option>
        </select>
    </div>
     <div class="col-12 col-md-3">
        <select id="adminFilter" class="form-select">
            <option value="">All User Roles</option>
            <option value="1">Admin</option>
            <option value="0">User</option>
        </select>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Balance (L/G)</th>
            <th>Is Admin?</th>
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
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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

<!-- Pagination Container -->
<div class="pagination-container">
    <?php
    // --- Initial Pagination Logic ---
    $limit = 10;
    $count_stmt = $conn->prepare("SELECT COUNT(id) as total FROM users");
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);
    $count_stmt->close();

    if ($total_pages > 1) {
        echo '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = $i == 1 ? 'active' : '';
            echo '<li class="page-item ' . $active_class . '"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<!-- Reusable Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editUserForm" action="users.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="user_id" id="edit-user-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalTitle">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-modal-body-content">
                        <div class="text-center"><div class="spinner-border"></div></div>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="users.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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

<!-- Edit User Modals will be loaded here by JavaScript -->


<script>
document.addEventListener('DOMContentLoaded', function() {
    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const editUserForm = document.getElementById('editUserForm');
    const editModalBody = document.getElementById('edit-modal-body-content');
    const userTableBody = document.getElementById('user-table-body');

    userTableBody.addEventListener('click', function(e) {
        const editButton = e.target.closest('.edit-user-btn');
        if (editButton) {
            e.preventDefault();
            const userId = editButton.dataset.userId;

            // Show loading spinner
            editModalBody.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            editUserModal.show();

            fetch(`ajax/get_user_details.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        document.getElementById('editUserModalTitle').innerText = 'Edit User: ' + user.username;
                        document.getElementById('edit-user-id').value = user.id;

                        // Populate the form fields inside the modal
                        editModalBody.innerHTML = `
                            <div class="form-group mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" value="${user.username}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="${user.email}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" value="${user.phone_number}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Balance</label>
                                <input type="number" step="0.01" name="balance" class="form-control" value="${user.balance}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Global SMS Profit (%)</label>
                                <input type="number" step="0.01" name="profit_percentage" class="form-control" value="${user.profit_percentage || '0.00'}">
                                <div class="form-text">A custom profit percentage for this user on global SMS.</div>
                            </div>
                            <div class="form-group mb-3">
                                <label>New Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_admin" value="1" id="is_admin_edit" ${user.is_admin ? 'checked' : ''}>
                                <label class="form-check-label" for="is_admin_edit">Administrator</label>
                            </div>
                        `;
                    } else {
                        editModalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    editModalBody.innerHTML = `<div class="alert alert-danger">An error occurred while fetching user details.</div>`;
                });
        }

        // Keep other event listeners for verify, api-action etc.
        const button = e.target.closest('button');
        if (!button) return;

        if (button.classList.contains('verify-btn')) {
            const userId = button.dataset.userId;
            if (!confirm(`Are you sure you want to manually verify user ID: ${userId}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('user_id', userId);

            fetch('ajax/manual_verify.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`user-row-${userId}`);
                    if (row) {
                        const verificationCell = row.querySelector('.verification-status-cell');
                        if (verificationCell) {
                            verificationCell.innerHTML = '<span class="badge bg-success">Yes</span>';
                        }
                        button.remove();
                    }
                } else {
                    alert('Verification failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during the verification process.');
            });
        }
        if (button.classList.contains('api-action-btn')) {
            const userId = button.dataset.userId;
            const action = button.dataset.action;

            if (!confirm(`Are you sure you want to ${action} API access for user ID: ${userId}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', action);

            fetch('../ajax/manage_api_access.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // This is a bit complex, we need to refresh the user row
                    fetchUsers(); // The simplest way is to just re-fetch the whole table
                    alert('API access status updated successfully.');
                } else {
                    alert('Action failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during the action.');
            });
        }
    });

    // The rest of the script (search, filter, pagination) remains the same
    // --- Search and Filter ---
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const adminFilter = document.getElementById('adminFilter');
    const paginationContainer = document.querySelector('.pagination-container');
    let currentPage = 1;
    let searchTimeout;

    function fetchUsers() {
        const searchTerm = searchInput.value;
        const status = statusFilter.value;
        const admin = adminFilter.value;
        userTableBody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('status', status);
        formData.append('admin', admin);
        formData.append('page', currentPage);
        fetch('ajax/search_users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                userTableBody.innerHTML = data.html;
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination;
                }
                const csrfInputs = document.querySelectorAll('input[name="csrf_token"]');
                csrfInputs.forEach(input => { input.value = data.csrfToken; });
            } else {
                 userTableBody.innerHTML = '<tr><td colspan="10" class="text-center">An error occurred.</td></tr>';
                 console.error('Search failed:', data.message);
            }
        })
        .catch(error => {
            userTableBody.innerHTML = '<tr><td colspan="10" class="text-center">An error occurred. Please try again.</td></tr>';
            console.error('Error:', error);
        });
    }

    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { currentPage = 1; fetchUsers(); }, 300);
    });
    statusFilter.addEventListener('change', () => { currentPage = 1; fetchUsers(); });
    adminFilter.addEventListener('change', () => { currentPage = 1; fetchUsers(); });
    if (paginationContainer) {
        paginationContainer.addEventListener('click', function(e) {
            e.preventDefault();
            const link = e.target.closest('a.page-link');
            if (link) {
                const page = link.dataset.page;
                if (page) { currentPage = parseInt(page, 10); fetchUsers(); }
            }
        });
    }
    fetchUsers();
});
</script>
<?php include 'includes/footer.php'; ?>