<?php
$page_title = 'Send Voice SMS (Text-to-Speech)';
require_once 'app/bootstrap.php';

$errors = [];
$success = '';

// Fetch user's approved caller IDs
$approved_caller_ids = [];
$stmt_ids = $conn->prepare("SELECT caller_id FROM caller_ids WHERE user_id = ? AND status = 'approved'");
$stmt_ids->bind_param("i", $user['id']);
$stmt_ids->execute();
$result_ids = $stmt_ids->get_result();
while ($row = $result_ids->fetch_assoc()) {
    $approved_caller_ids[] = $row['caller_id'];
}
$stmt_ids->close();

// Fetch user's voice drafts (we can reuse the sms_drafts table with a naming convention, or add a type column)
// For now, let's assume a convention: titles starting with [VOICE]
$drafts = [];
$drafts_stmt = $conn->prepare("SELECT id, title, message FROM sms_drafts WHERE user_id = ? AND title LIKE '[VOICE]%' ORDER BY updated_at DESC");
$drafts_stmt->bind_param("i", $user['id']);
$drafts_stmt->execute();
$drafts_result = $drafts_stmt->get_result();
while ($row = $drafts_result->fetch_assoc()) {
    $drafts[] = $row;
}
$drafts_stmt->close();


// Handle Save Draft submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_draft'])) {
    $title = trim($_POST['draft_title']);
    $caller_id = trim($_POST['caller_id']);
    $recipients = trim($_POST['recipients']);
    $message = trim($_POST['message']);

    if (empty($title)) {
        $title = "Voice Draft saved on " . date("Y-m-d H:i");
    }

    if (!empty($message)) {
        // Add a prefix to distinguish from SMS drafts
        $prefixed_title = "[VOICE] " . $title;
        $stmt = $conn->prepare("INSERT INTO sms_drafts (user_id, title, sender_id, recipients, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user['id'], $prefixed_title, $caller_id, $recipients, $message);
        if ($stmt->execute()) {
            $success = "Message successfully saved as a draft.";
        } else {
            $errors[] = "Failed to save draft.";
        }
    } else {
        $errors[] = "Cannot save an empty message as a draft.";
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_voice_sms'])) {
    $caller_id = trim($_POST['caller_id']);
    $recipients = trim($_POST['recipients']);
    $message = trim($_POST['message']);
    $schedule_time = $_POST['schedule_time'] ?? '';

    if (!empty($schedule_time)) {
        try {
            // Get the site's configured timezone, default to UTC if not set
            $site_tz_str = get_settings()['site_timezone'] ?? 'UTC';
            $site_tz = new DateTimeZone($site_tz_str);
            $local_dt = new DateTime($schedule_time, $site_tz);
            $local_dt->setTimezone(new DateTimeZone('UTC'));
            $scheduled_for_utc = $local_dt->format('Y-m-d H:i:s');

            // Call the new function to handle debiting and scheduling
            $result = debit_and_schedule_voice_tts($current_user, $caller_id, $recipients, $message, $scheduled_for_utc, $conn);

            if ($result['success']) {
                $success = $result['message'];
                // Re-fetch user data to update balance display
                $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $current_user['id']);
                $user_stmt->execute();
                $current_user = $user_stmt->get_result()->fetch_assoc();
                $user_stmt->close();
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = "Invalid date format for scheduling. " . $e->getMessage();
        }
    } else {
        // --- Immediate Send Logic ---
        // This logic would be refactored into a helper function like `send_voice_tts()`
        // For now, keeping it inline for clarity.
        if (empty($caller_id) || !in_array($caller_id, $approved_caller_ids)) $errors[] = "Please select a valid, approved Caller ID.";
        if (empty($recipients)) $errors[] = "Recipients are required.";
        if (empty($message)) $errors[] = "Message is required.";

        // Placeholder cost calculation
        $total_cost = count(preg_split('/[\s,;\n]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY)) * 0.05;
        if ($user['balance'] < $total_cost) $errors[] = "Insufficient balance.";

        if (empty($errors)) {
            // All checks passed, proceed with API call
            $result = send_voice_tts($user, $caller_id, $recipients, $message, $conn);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Compose Voice Message</h3>
                 <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#loadDraftModal" <?php if(empty($drafts)) echo 'disabled'; ?>>
                    <i class="fas fa-folder-open"></i> Load Draft
                </button>
            </div>
            <form id="voice-sms-form" action="send-voice-sms.php" method="POST">
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

                    <div class="form-group mb-3">
                        <label for="draft_title">Draft Title (Optional)</label>
                        <input type="text" class="form-control" name="draft_title" placeholder="e.g., Weekly Voice Promo">
                    </div>

                    <div class="form-group mb-3">
                        <label for="caller_id">Caller ID</label>
                        <select class="form-control" name="caller_id" required <?php if(empty($approved_caller_ids)) echo 'disabled'; ?>>
                            <option value="" disabled selected>-- Select an Approved Caller ID --</option>
                            <?php foreach ($approved_caller_ids as $cid): ?>
                                <option value="<?php echo htmlspecialchars($cid); ?>"><?php echo htmlspecialchars($cid); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($approved_caller_ids)): ?>
                            <small class="form-text text-danger"><a href="caller-id.php" class="text-danger">You have no approved Caller IDs. Please register one to begin.</a></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group mb-3">
                        <label for="recipients">Recipients</label>
                        <textarea class="form-control" name="recipients" rows="4" placeholder="Enter numbers, separated by commas"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="message">Message (Text-to-Speech)</label>
                        <textarea class="form-control" name="message" id="message" rows="5" placeholder="Type the message to be read out loud..." required></textarea>
                    </div>

                    <hr>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="schedule-switch">
                        <label class="form-check-label" for="schedule-switch">Schedule for Later</label>
                    </div>
                    <div id="schedule-options" style="display: none;">
                        <div class="form-group">
                            <label for="schedule_time">Schedule Time</label>
                            <input type="datetime-local" class="form-control" name="schedule_time" id="schedule_time">
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button type="submit" name="save_draft" class="btn btn-secondary"><i class="fas fa-save"></i> Save as Draft</button>
                    <button type="submit" name="send_voice_sms" id="send-btn" class="btn btn-primary" <?php if(empty($approved_caller_ids)) echo 'disabled'; ?>><i class="fas fa-phone-alt"></i> Send Voice Message</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Instructions</h3></div>
            <div class="card-body">
                <p>Compose your message, save it as a draft, or schedule it to be sent at a future time.</p>
            </div>
        </div>
    </div>
</div>

<!-- Load Draft Modal (Similar to send-sms.php) -->
<div class="modal fade" id="loadDraftModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Load a Saved Voice Draft</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- AJAX content will be loaded here -->
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scheduling UI logic
    const scheduleSwitch = document.getElementById('schedule-switch');
    const scheduleOptions = document.getElementById('schedule-options');
    const sendBtn = document.getElementById('send-btn');
    scheduleSwitch.addEventListener('change', function() {
        if (this.checked) {
            scheduleOptions.style.display = 'block';
            sendBtn.innerHTML = '<i class="fas fa-clock"></i> Schedule Message';
        } else {
            scheduleOptions.style.display = 'none';
            sendBtn.innerHTML = '<i class="fas fa-phone-alt"></i> Send Voice Message';
        }
    });

    // Drafts logic (simplified, would need its own AJAX endpoint for voice drafts)
    // For now, this is just a placeholder to show the concept.
    const form = document.getElementById('voice-sms-form');
    // ... logic to fetch and populate drafts would go here ...
});
</script>

<?php include 'includes/footer.php'; ?>
