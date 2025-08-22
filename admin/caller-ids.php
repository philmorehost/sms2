<?php
$page_title = 'Caller ID Management';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $caller_id_record_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($caller_id_record_id && ($action == 'approve' || $action == 'reject')) {
        $new_status = ($action == 'approve') ? 'approved' : 'rejected';

        $stmt = $conn->prepare("UPDATE caller_ids SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $caller_id_record_id);
        if ($stmt->execute()) {
            $success = "Caller ID has been " . $new_status . ".";
        } else {
            $errors[] = "Failed to update status.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid action or ID.";
    }
}

// Fetch all caller ID submissions
$submissions = [];
$sql = "SELECT c.id, c.caller_id, c.status, c.created_at, u.username
        FROM caller_ids c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.status = 'pending' DESC, c.created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Caller ID Submissions</h1>
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
                    <th>Username</th>
                    <th>Submitted Caller ID</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="5" class="text-center">No Caller ID submissions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['username']); ?></td>
                        <td><?php echo htmlspecialchars($sub['caller_id']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($sub['created_at'])); ?></td>
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
                            <?php if ($sub['status'] == 'pending'): ?>
                                <form action="caller-ids.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" name="update_status" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form action="caller-ids.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" name="update_status" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php else: ?>
                                N/A
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

<?php include 'includes/footer.php'; ?>
