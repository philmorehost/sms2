<?php
$page_title = 'Global Sender ID Requests';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $request_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = $_POST['status'];

    if (in_array($status, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE global_sender_id_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $request_id);
        if ($stmt->execute()) {
            $success = "Request status updated successfully.";
        } else {
            $errors[] = "Failed to update status.";
        }
        $stmt->close();
    }
}

// Fetch all requests
$requests_result = $conn->query("SELECT r.*, u.username FROM global_sender_id_requests r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Global Sender ID Requests</h1>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5>All Requests</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Business Name</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($request = $requests_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['username']); ?></td>
                        <td><?php echo htmlspecialchars($request['business_name']); ?></td>
                        <td><?php echo format_date_for_display($request['created_at']); ?></td>
                        <td>
                            <?php
                                $status_badge = 'secondary';
                                if ($request['status'] == 'approved') $status_badge = 'success';
                                if ($request['status'] == 'rejected') $status_badge = 'danger';
                            ?>
                            <span class="badge bg-<?php echo $status_badge; ?>"><?php echo ucfirst($request['status']); ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $request['id']; ?>">View</button>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $request['id']; ?>">Update Status</button>
                        </td>
                    </tr>

                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal<?php echo $request['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Request Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php foreach ($request as $key => $value): ?>
                                        <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo nl2br(htmlspecialchars($value ?? 'N/A')); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status Modal -->
                    <div class="modal fade" id="updateStatusModal<?php echo $request['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="global-sender-ids.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Update the status for the request from "<?php echo htmlspecialchars($request['username']); ?>".</p>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?php if($request['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                            <option value="approved" <?php if($request['status'] == 'approved') echo 'selected'; ?>>Approved</option>
                                            <option value="rejected" <?php if($request['status'] == 'rejected') echo 'selected'; ?>>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="update_status" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
