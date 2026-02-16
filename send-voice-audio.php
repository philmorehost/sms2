<?php
$page_title = 'Send Voice Message (Audio File)';
include 'includes/header.php';

$errors = [];
$success = '';

// Fetch user's approved caller IDs
$approved_caller_ids = [];
$stmt_ids = $conn->prepare("SELECT caller_id FROM caller_ids WHERE user_id = ? AND status = 'approved'");
$stmt_ids->bind_param("i", $current_user['id']);
$stmt_ids->execute();
$result_ids = $stmt_ids->get_result();
while ($row = $result_ids->fetch_assoc()) {
    $approved_caller_ids[] = $row['caller_id'];
}
$stmt_ids->close();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_voice_audio'])) {
    $caller_id = trim($_POST['caller_id']);
    $recipients = trim($_POST['recipients']);
    $audio_url = trim($_POST['audio_url']);

    // Validation
    if (empty($caller_id)) {
        $errors[] = "Please select an approved Caller ID.";
    }
    if (empty($recipients)) {
        $errors[] = "Recipients field cannot be empty.";
    }
    if (empty($audio_url)) {
        $errors[] = "Audio URL is required.";
    } elseif (!filter_var($audio_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid URL for the audio file.";
    }

    if (empty($errors)) {
        // I will create this function in the next step
        $result = send_voice_audio_api($current_user, $caller_id, $recipients, $audio_url, $conn);

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
    }
}
?>

<form id="voice-audio-form" action="send-voice-audio.php" method="POST">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Compose Voice Message (from Audio File)</h4>
                </div>
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

                    <div class="mb-3">
                        <label for="caller_id" class="form-label">Caller ID</label>
                        <select class="form-select" name="caller_id" required <?php if(empty($approved_caller_ids)) echo 'disabled'; ?>>
                            <option value="" disabled selected>-- Select an Approved Caller ID --</option>
                            <?php foreach ($approved_caller_ids as $cid): ?>
                                <option value="<?php echo htmlspecialchars($cid); ?>"><?php echo htmlspecialchars($cid); ?></option>
                            <?php endforeach; ?>
                        </select>
                         <?php if (empty($approved_caller_ids)): ?>
                            <small class="form-text text-danger"><a href="caller-id.php" class="text-danger">Please register a Caller ID to begin.</a></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group mb-3">
                        <label for="recipients" class="form-label">Recipients</label>
                        <textarea class="form-control" id="recipients" name="recipients" rows="5" placeholder="Enter numbers, separated by commas, spaces, or on new lines."></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="audio_url" class="form-label">Audio File URL</label>
                        <input type="url" class="form-control" name="audio_url" id="audio_url" placeholder="https://example.com/audio.mp3" required>
                        <small class="form-text text-muted">Provide a direct public URL to your MP3 audio file. Max 2MB, 120 seconds.</small>
                    </div>
                </div>
                 <div class="card-footer">
                    <button type="submit" name="send_voice_audio" class="btn btn-primary" <?php if(empty($approved_caller_ids)) echo 'disabled'; ?>>
                        <i class="fas fa-volume-up"></i> Send Voice Message
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
