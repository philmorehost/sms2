<?php
$page_title = 'OTP Template Management';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

// Handle status and code updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_template'])) {
    $template_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = $_POST['status'];
    $appnamecode = trim($_POST['appnamecode']);
    $templatecode = trim($_POST['templatecode']);

    if ($template_id && in_array($status, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE otp_templates SET status = ?, appnamecode = ?, templatecode = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $appnamecode, $templatecode, $template_id);
        if ($stmt->execute()) {
            $success = "Template #" . $template_id . " has been updated.";
        } else {
            $errors[] = "Failed to update template.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid data provided.";
    }
}

// Fetch all template submissions
$submissions = [];
$sql = "SELECT t.*, u.username
        FROM otp_templates t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.status = 'pending' DESC, t.created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">OTP Template Submissions</h1>
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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                    <th>User</th>
                    <th>Template Name</th>
                    <th>Template Body</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="5" class="text-center">No OTP Template submissions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['username']); ?></td>
                        <td><?php echo htmlspecialchars($sub['template_name']); ?></td>
                        <td><?php echo htmlspecialchars($sub['template_body']); ?></td>
                        <td>
                            <?php
                                $status = htmlspecialchars($sub['status']);
                                $badge_class = 'bg-secondary';
                                if ($status == 'approved') $badge_class = 'bg-success';
                                if ($status == 'rejected') $badge_class = 'bg-danger';
                                if ($status == 'pending') $badge_class = 'bg-warning text-dark';
                                echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editTemplateModal<?php echo $sub['id']; ?>">Manage</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Edit Template Modals -->
<?php foreach ($submissions as $sub): ?>
<div class="modal fade" id="editTemplateModal<?php echo $sub['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="otp-templates.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Manage OTP Template #<?php echo $sub['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>User:</strong> <?php echo htmlspecialchars($sub['username']); ?></p>
                    <p><strong>Template:</strong> "<?php echo htmlspecialchars($sub['template_body']); ?>"</p>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?php if($sub['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="approved" <?php if($sub['status'] == 'approved') echo 'selected'; ?>>Approved</option>
                            <option value="rejected" <?php if($sub['status'] == 'rejected') echo 'selected'; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">App Name Code (from API Provider)</label>
                        <input type="text" class="form-control" name="appnamecode" value="<?php echo htmlspecialchars($sub['appnamecode']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Template Code (from API Provider)</label>
                        <input type="text" class="form-control" name="templatecode" value="<?php echo htmlspecialchars($sub['templatecode']); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_template" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
