<?php
$page_title = 'Caller ID Management';
require_once 'app/bootstrap.php';

$errors = [];
$success = '';

// Handle new Caller ID submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_caller_id'])) {
    $caller_id = trim($_POST['caller_id']);

    // Basic validation (can be improved with regex for phone numbers)
    if (empty($caller_id) || !is_numeric($caller_id)) {
        $errors[] = "Please enter a valid phone number for the Caller ID.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO caller_ids (user_id, caller_id) VALUES (?, ?)");
        $stmt->bind_param("is", $current_user['id'], $caller_id);
        if ($stmt->execute()) {
            $success = "Caller ID submitted successfully. It is now pending review.";
        } else {
            $errors[] = "Failed to submit Caller ID. You may have already submitted this number.";
        }
        $stmt->close();
    }
}

// Fetch user's submitted caller IDs
$user_caller_ids = [];
$stmt = $conn->prepare("SELECT caller_id, status, created_at FROM caller_ids WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_caller_ids[] = $row;
}
$stmt->close();

include 'includes/header.php';
?>

<div class="row">
    <!-- Submit Caller ID Form -->
    <div class="col-md-5">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Register New Caller ID</h3></div>
            <form action="caller-id.php" method="POST">
                <div class="card-body">
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

                    <div class="form-group">
                        <label for="caller_id">Caller ID Number</label>
                        <input type="text" class="form-control" name="caller_id" placeholder="e.g., 2347031234567" required>
                        <small class="form-text text-muted">The phone number that will be displayed to the recipient of a voice call.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit_caller_id" class="btn btn-primary">Submit for Review</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Submitted Caller IDs List -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">My Registered Caller IDs</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Caller ID</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_caller_ids)): ?>
                            <tr><td colspan="3" class="text-center">You have not submitted any Caller IDs yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($user_caller_ids as $id_data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($id_data['caller_id']); ?></td>
                                <td>
                                    <?php
                                        $status = htmlspecialchars($id_data['status']);
                                        $badge_class = 'bg-secondary';
                                        if ($status == 'approved') {
                                            $badge_class = 'bg-success';
                                        } elseif ($status == 'rejected') {
                                            $badge_class = 'bg-danger';
                                        } elseif ($status == 'pending') {
                                            $badge_class = 'bg-warning text-dark';
                                        }
                                        echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                                    ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($id_data['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
