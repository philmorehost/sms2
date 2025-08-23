<?php
$page_title = 'Sender ID Management';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle form submission for new Sender ID
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_sender_id'])) {
    $sender_id = trim($_POST['sender_id']);
    $sample_message = trim($_POST['sample_message']);

    // Basic validation
    if (empty($sender_id)) {
        $errors[] = "Sender ID cannot be empty.";
    } elseif (strlen($sender_id) > 11) {
        $errors[] = "Sender ID cannot be more than 11 characters.";
    }
    if (empty($sample_message)) {
        $errors[] = "A sample message is required.";
    }

    if (empty($errors)) {
        // Check if the same sender ID is already pending or approved for this user
        $stmt_check = $conn->prepare("SELECT id FROM sender_ids WHERE user_id = ? AND sender_id = ? AND (status = 'pending' OR status = 'approved')");
        $stmt_check->bind_param("is", $user_id, $sender_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $errors[] = "You already have a pending or approved request for this Sender ID.";
        } else {
            $stmt = $conn->prepare("INSERT INTO sender_ids (user_id, sender_id, sample_message) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $sender_id, $sample_message);
            if ($stmt->execute()) {
                $success = "Sender ID submitted successfully for review.";
            } else {
                $errors[] = "Failed to submit Sender ID. Please try again.";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Fetch user's submitted Sender IDs
$user_sender_ids = [];
$stmt = $conn->prepare("SELECT sender_id, sample_message, status, created_at FROM sender_ids WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_sender_ids[] = $row;
}
$stmt->close();
?>

<div class="row">
    <!-- Submit Sender ID Form -->
    <div class="col-md-5">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Register New Sender ID</h3>
            </div>
            <form action="sender-ids.php" method="POST">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?><p class="m-0"><?php echo $error; ?></p><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p class="m-0"><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="sender_id">Sender ID</label>
                        <input type="text" class="form-control" name="sender_id" placeholder="Max 11 characters" maxlength="11" required>
                        <small class="form-text text-muted">The name/number that will appear as the sender of the message.</small>
                    </div>
                    <div class="form-group">
                        <label for="sample_message">Sample Message</label>
                        <textarea class="form-control" name="sample_message" rows="3" placeholder="e.g., Dear customer, your OTP is 1234." required></textarea>
                         <small class="form-text text-muted">Provide an example of the kind of message you will send with this ID.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit_sender_id" class="btn btn-primary">Submit for Review</button>
                </div>
            </form>
        </div>
    </div>

    <!-- List of Submitted Sender IDs -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Your Submitted Sender IDs</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sender ID</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_sender_ids)): ?>
                            <tr><td colspan="3" class="text-center">You have not submitted any Sender IDs yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($user_sender_ids as $id_data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($id_data['sender_id']); ?></td>
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
