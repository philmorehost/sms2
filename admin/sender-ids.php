<?php
$page_title = 'Sender ID Management';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle Manual Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manual_add_sender_id'])) {
    $user_id = (int)$_POST['user_id'];
    $sender_id = trim($_POST['sender_id']);
    $sample_message = trim($_POST['sample_message']);
    $status = $_POST['status'];

    if ($user_id > 0 && !empty($sender_id) && !empty($sample_message) && in_array($status, ['approved', 'pending', 'rejected'])) {
        $stmt = $conn->prepare("INSERT INTO sender_ids (user_id, sender_id, sample_message, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $sender_id, $sample_message, $status);
        if ($stmt->execute()) {
            $success = "Sender ID manually added successfully.";
        } else {
            $errors[] = "Failed to add Sender ID. It might already exist for this user.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid data provided. Please fill all fields correctly.";
    }
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $sender_id_record_id = (int)$_POST['id'];
    $new_status = $_POST['status'];

    if (in_array($new_status, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE sender_ids SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $sender_id_record_id);
        if ($stmt->execute()) {
            $success = "Sender ID status updated to '$new_status'.";
        } else {
            $errors[] = "Failed to update status.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid status provided.";
    }
}

// Fetch all sender ID submissions
$submissions = [];
$sql = "SELECT s.id, s.sender_id, s.sample_message, s.status, s.created_at, u.username
        FROM sender_ids s
        JOIN users u ON s.user_id = u.id
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}

// Fetch all users for the modal dropdown
$all_users = [];
$users_result = $conn->query("SELECT id, username, email FROM users ORDER BY username ASC");
while($user = $users_result->fetch_assoc()) {
    $all_users[] = $user;
}

?>

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

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">All Sender ID Submissions</h3>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualAddModal">
            <i class="fas fa-plus"></i> Manually Add Sender ID
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                    <th>Username</th>
                    <th>Sender ID</th>
                    <th>Sample Message</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr><td colspan="6" class="text-center">No Sender ID submissions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['username']); ?></td>
                        <td><?php echo htmlspecialchars($sub['sender_id']); ?></td>
                        <td><?php echo htmlspecialchars($sub['sample_message']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($sub['created_at'])); ?></td>
                        <td>
                            <?php
                                $status = htmlspecialchars($sub['status']);
                                $badge_class = 'badge-secondary';
                                if ($status == 'approved') $badge_class = 'badge-success';
                                if ($status == 'rejected') $badge_class = 'badge-danger';
                                if ($status == 'pending') $badge_class = 'badge-warning';
                                echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                            ?>
                        </td>
                            <td>
                                <?php if ($sub['status'] == 'pending'): ?>
                                    <form action="sender-ids.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" name="update_status" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form action="sender-ids.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" name="update_status" class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span>No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Manual Add Modal -->
<div class="modal fade" id="manualAddModal" tabindex="-1" aria-labelledby="manualAddModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="sender-ids.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualAddModalLabel">Manually Add Sender ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="" disabled selected>-- Select a User --</option>
                            <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sender_id" class="form-label">Sender ID</label>
                        <input type="text" class="form-control" id="sender_id" name="sender_id" required maxlength="11">
                    </div>
                    <div class="mb-3">
                        <label for="sample_message" class="form-label">Sample Message</label>
                        <textarea class="form-control" id="sample_message" name="sample_message" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="approved" selected>Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="manual_add_sender_id" class="btn btn-primary">Add Sender ID</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
