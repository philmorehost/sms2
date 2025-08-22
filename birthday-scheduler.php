<?php
$page_title = 'Birthday Scheduler';
require_once 'app/bootstrap.php';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_schedule'])) {
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sender_id = trim($_POST['sender_id']);
    $send_time = trim($_POST['send_time']);
    $message_template = trim($_POST['message_template']);

    // Validation
    if (empty($sender_id)) {
        $errors[] = "Please select a Sender ID.";
    }
    if (empty($send_time)) {
        $errors[] = "Please set a valid sending time.";
    }
    if (empty($message_template)) {
        $errors[] = "The message template cannot be empty.";
    }

    if (empty($errors)) {
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both create and update
        $stmt = $conn->prepare("
            INSERT INTO birthday_schedules (user_id, is_active, sender_id, send_time, message_template)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                is_active = VALUES(is_active),
                sender_id = VALUES(sender_id),
                send_time = VALUES(send_time),
                message_template = VALUES(message_template)
        ");
        $stmt->bind_param("iisss", $user['id'], $is_active, $sender_id, $send_time, $message_template);

        if ($stmt->execute()) {
            $success = "Your birthday schedule settings have been saved successfully.";
        } else {
            $errors[] = "Failed to save your settings. Please try again.";
        }
        $stmt->close();
    }
}


// Fetch user's approved sender IDs
$approved_sender_ids = [];
$stmt_ids = $conn->prepare("SELECT sender_id FROM sender_ids WHERE user_id = ? AND status = 'approved'");
$stmt_ids->bind_param("i", $user['id']);
$stmt_ids->execute();
$result_ids = $stmt_ids->get_result();
while ($row = $result_ids->fetch_assoc()) {
    $approved_sender_ids[] = $row['sender_id'];
}
$stmt_ids->close();

// Fetch user's existing schedule settings
$schedule = [];
$stmt_schedule = $conn->prepare("SELECT * FROM birthday_schedules WHERE user_id = ?");
$stmt_schedule->bind_param("i", $user['id']);
$stmt_schedule->execute();
$result_schedule = $stmt_schedule->get_result();
$schedule = $result_schedule->fetch_assoc();
$stmt_schedule->close();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Birthday Message Scheduler</h1>
</div>

<div class="card">
    <div class="card-body">
        <p class="lead">Automate your birthday wishes!</p>
        <p>Set up a template and a time below. When this schedule is active, the system will automatically check your phone book each day. If any contacts have a birthday matching the current date, your personalized message will be sent to them at the specified time.</p>
        <p><strong>Note:</strong> This feature requires you to add birthday dates to your contacts in the <a href="phonebook.php">Phone Book</a>.</p>
        <hr>

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

        <form action="birthday-scheduler.php" method="POST">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?php if(!empty($schedule) && $schedule['is_active']) echo 'checked'; ?>>
                <label class="form-check-label" for="is_active"><strong>Enable Birthday Scheduler</strong></label>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="sender_id" class="form-label">Sender ID</label>
                    <select class="form-select" name="sender_id" required>
                        <option value="">-- Select an Approved Sender ID --</option>
                        <?php foreach ($approved_sender_ids as $sid): ?>
                            <option value="<?php echo htmlspecialchars($sid); ?>" <?php if(!empty($schedule) && $schedule['sender_id'] == $sid) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($sid); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="send_time" class="form-label">Send Time</label>
                    <input type="time" class="form-control" name="send_time" value="<?php echo $schedule['send_time'] ?? '09:00'; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="message_template" class="form-label">Message Template</label>
                <textarea name="message_template" class="form-control" rows="5" required placeholder="Type your birthday message here..."><?php echo htmlspecialchars($schedule['message_template'] ?? ''); ?></textarea>
                <div class="form-text">
                    Use placeholders to personalize your message:
                    <code>[first_name]</code>, <code>[last_name]</code>.
                    <br>
                    Example: <code>Happy Birthday, [first_name]! Wishing you all the best.</code>
                </div>
            </div>

            <button type="submit" name="save_schedule" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
