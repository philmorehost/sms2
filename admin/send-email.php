<?php
$page_title = 'Send Email to Users';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

// Handle form submission for sending broadcast
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_broadcast'])) {
    $audience = $_POST['audience'];
    $message_source = $_POST['message_source'];
    $subject = '';
    $body = '';
    $users_result = null;

    // Step 1: Determine the email content (template or custom)
    if ($message_source === 'template') {
        $template_id = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT);
        if (!$template_id) {
            $errors[] = "Please select an email template.";
        } else {
            $template_stmt = $conn->prepare("SELECT * FROM email_templates WHERE id = ?");
            $template_stmt->bind_param("i", $template_id);
            $template_stmt->execute();
            $template = $template_stmt->get_result()->fetch_assoc();
            if ($template) {
                $subject = $template['subject'];
                $body = $template['body'];
            } else {
                $errors[] = "Selected email template not found.";
            }
        }
    } elseif ($message_source === 'custom') {
        $subject = trim($_POST['custom_subject']);
        $body = trim($_POST['custom_body']);
        if (empty($subject) || empty($body)) {
            $errors[] = "Custom subject and body cannot be empty.";
        }
    } else {
        $errors[] = "Invalid message source.";
    }

    // Step 2 & 3: Determine recipients and send emails
    if (empty($errors)) {
        $recipients = [];
        $email_count = 0;

        if ($audience == 'all' || $audience == 'inactive_30') {
            $sql = "SELECT email, username FROM users WHERE is_admin = 0";
            if ($audience == 'inactive_30') {
                $sql .= " AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))";
            }
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()) {
                $recipients[] = $row;
            }
        } elseif ($audience == 'specific' || $audience == 'external') {
            $raw_emails = ($audience == 'specific') ? $_POST['specific_users'] : $_POST['external_emails'];
            $emails = preg_split('/[\s,;\n]+/', $raw_emails, -1, PREG_SPLIT_NO_EMPTY);
            $emails = array_unique(array_map('trim', $emails));

            if (empty($emails)) {
                $errors[] = "The email list cannot be empty.";
            } else {
                // Find which emails belong to registered users for personalization
                $placeholders = implode(',', array_fill(0, count($emails), '?'));
                $stmt = $conn->prepare("SELECT email, username FROM users WHERE email IN ($placeholders)");
                $stmt->bind_param(str_repeat('s', count($emails)), ...$emails);
                $stmt->execute();
                $registered_users = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $registered_users[$row['email']] = $row['username'];
                }
                $stmt->close();

                // Prepare the final recipients list
                foreach ($emails as $email) {
                    $recipients[] = [
                        'email' => $email,
                        'username' => $registered_users[$email] ?? null // Username will be null for external emails
                    ];
                }
            }
        } else {
            $errors[] = "Invalid audience selected.";
        }

        // Step 3: Schedule or Send
        if (empty($errors) && !empty($recipients)) {
            $is_scheduled = isset($_POST['schedule_email']) && $_POST['schedule_email'] == 'on';
            $scheduled_for = $_POST['scheduled_for'] ?? null;

            if ($is_scheduled) {
                if (empty($scheduled_for)) {
                    $errors[] = "Please select a valid date and time for scheduling.";
                } else {
                    // Convert to UTC for storage
                    $app_settings = get_settings();
                    $user_tz = new DateTimeZone($app_settings['site_timezone'] ?? 'UTC');
                    $schedule_time = new DateTime($scheduled_for, $user_tz);
                    $schedule_time->setTimezone(new DateTimeZone('UTC'));
                    $scheduled_for_utc = $schedule_time->format('Y-m-d H:i:s');

                    $payload = json_encode([
                        'subject' => $subject,
                        'body' => $body,
                        'recipients' => $recipients
                    ]);

                    $stmt = $conn->prepare("INSERT INTO scheduled_tasks (user_id, task_type, payload, scheduled_for, status) VALUES (?, 'email', ?, ?, 'pending')");
                    $stmt->bind_param("iss", $current_user['id'], $payload, $scheduled_for_utc);

                    if ($stmt->execute()) {
                        $success = "Email broadcast successfully scheduled for " . htmlspecialchars($scheduled_for) . ".";
                    } else {
                        $errors[] = "Failed to schedule the email broadcast.";
                    }
                    $stmt->close();
                }
            } else {
                // Send immediately
                foreach ($recipients as $recipient) {
                    $personalized_body = $body;
                    if (!empty($recipient['username'])) {
                        $personalized_body = str_replace('[username]', htmlspecialchars($recipient['username']), $body);
                        $personalized_body = "Dear " . htmlspecialchars($recipient['username']) . ",<br><br>" . $personalized_body;
                    }
                    send_email($recipient['email'], $subject, $personalized_body);
                    $email_count++;
                    if ($email_count % 10 == 0) {
                        sleep(1);
                    }
                }
                $success = "Email broadcast sent to " . $email_count . " recipient(s).";
            }
        } elseif (empty($errors)) {
            $errors[] = "No recipients found for the selected audience.";
        }
    }
}


// Fetch all email templates
$templates = [];
$result = $conn->query("SELECT * FROM email_templates ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Send Email Broadcast</h1>
</div>

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

<div class="card">
    <div class="card-body">
        <form action="send-email.php" method="POST" onsubmit="return confirm('Are you sure you want to send this email to the selected user group? This action cannot be undone.');">
            <div class="row">
                <div class="col-md-5">
                    <h5>Step 1: Configure Broadcast</h5>
                    <hr>
                    <div class="mb-3">
                        <label for="audience" class="form-label">Target Audience</label>
                        <select name="audience" id="audience" class="form-select">
                            <option value="all">All Users</option>
                            <option value="inactive_30">Users not logged in for 30+ days</option>
                            <option value="specific">Specific Registered User(s)</option>
                            <option value="external">External Email List</option>
                        </select>
                    </div>
                    <div class="mb-3" id="specific-users-container" style="display: none;">
                        <label for="specific_users" class="form-label">User Emails</label>
                        <input type="text" class="form-control" name="specific_users" id="specific_users" placeholder="Enter user emails, separated by commas">
                    </div>
                    <div class="mb-3" id="external-emails-container" style="display: none;">
                        <label for="external_emails" class="form-label">External Email Addresses</label>
                        <textarea class="form-control" name="external_emails" id="external_emails" rows="5" placeholder="Enter email addresses, separated by commas or new lines"></textarea>
                    </div>
                    <ul class="nav nav-tabs nav-tabs-responsive" id="messageSourceTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="template-tab" data-bs-toggle="tab" data-bs-target="#template-pane" type="button" role="tab">Use Template</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="custom-tab" data-bs-toggle="tab" data-bs-target="#custom-pane" type="button" role="tab">Compose Custom</button>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 p-3 mb-3">
                        <input type="hidden" name="message_source" id="message_source" value="template">
                        <div class="tab-pane fade show active" id="template-pane" role="tabpanel">
                            <label for="template_id" class="form-label">Email Template</label>
                            <select name="template_id" id="template_id" class="form-select">
                                <option value="">-- Select a Template --</option>
                                <?php foreach($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>" data-subject="<?php echo htmlspecialchars($template['subject']); ?>" data-body="<?php echo htmlspecialchars($template['body']); ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tab-pane fade" id="custom-pane" role="tabpanel">
                             <div class="mb-3">
                                <label for="custom_subject" class="form-label">Custom Subject</label>
                                <input type="text" class="form-control" name="custom_subject" id="custom_subject">
                            </div>
                            <div class="mb-3">
                                <label for="custom_body" class="form-label">Custom Body</label>
                                <textarea class="form-control" name="custom_body" id="custom_body" rows="10"></textarea>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h5>Step 2: Schedule (Optional)</h5>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="schedule_email" id="schedule_email">
                        <label class="form-check-label" for="schedule_email">
                            Schedule this broadcast for a later time
                        </label>
                    </div>
                    <div class="mb-3" id="schedule-container" style="display: none;">
                        <label for="scheduled_for" class="form-label">Scheduled Time</label>
                        <input type="datetime-local" class="form-control" name="scheduled_for" id="scheduled_for">
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="send_broadcast" id="send-broadcast-btn" class="btn btn-primary btn-lg">Send Broadcast</button>
                    </div>
                </div>
                <div class="col-md-7">
                    <h5>Step 2: Preview Template</h5>
                    <hr>
                    <div id="email-preview-container">
                        <div class="mb-2"><strong>Subject:</strong> <span id="preview-subject"></span></div>
                        <iframe id="preview-body" src="about:blank" style="width: 100%; height: 400px; border: 1px solid #ccc;"></iframe>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
<script>
    if (document.querySelector('#custom_body')) {
        ClassicEditor
            .create( document.querySelector( '#custom_body' ) )
            .catch( error => {
                console.error( error );
            } );
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const templateSelect = document.getElementById('template_id');
    const subjectPreview = document.getElementById('preview-subject');
    const bodyPreview = document.getElementById('preview-body');
    const audienceSelect = document.getElementById('audience');
    const specificUsersContainer = document.getElementById('specific-users-container');
    const externalEmailsContainer = document.getElementById('external-emails-container');
    const messageSourceInput = document.getElementById('message_source');
    const templateTab = document.getElementById('template-tab');
    const customTab = document.getElementById('custom-tab');
    const scheduleCheckbox = document.getElementById('schedule_email');
    const scheduleContainer = document.getElementById('schedule-container');
    const sendBtn = document.getElementById('send-broadcast-btn');

    if (scheduleCheckbox) {
        scheduleCheckbox.addEventListener('change', function() {
            if (this.checked) {
                scheduleContainer.style.display = 'block';
                sendBtn.textContent = 'Schedule Broadcast';
            } else {
                scheduleContainer.style.display = 'none';
                sendBtn.textContent = 'Send Broadcast';
            }
        });
    }

    templateTab.addEventListener('click', function() {
        messageSourceInput.value = 'template';
    });
    customTab.addEventListener('click', function() {
        messageSourceInput.value = 'custom';
    });

    audienceSelect.addEventListener('change', function() {
        specificUsersContainer.style.display = 'none';
        externalEmailsContainer.style.display = 'none';

        if (this.value === 'specific') {
            specificUsersContainer.style.display = 'block';
        } else if (this.value === 'external') {
            externalEmailsContainer.style.display = 'block';
        }
    });

    templateSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const subject = selectedOption.dataset.subject;
            const body = selectedOption.dataset.body;

            subjectPreview.textContent = subject;
            bodyPreview.srcdoc = body; // Safely render HTML in iframe
        } else {
            subjectPreview.textContent = '';
            bodyPreview.srcdoc = '';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
