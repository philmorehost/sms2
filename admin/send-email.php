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

    // Step 2: Determine the recipients
    if (empty($errors)) {
        $sql_users = "";
        $params = [];
        $types = "";

        if ($audience == 'all') {
            $sql_users = "SELECT email, username FROM users WHERE is_admin = 0";
        } elseif ($audience == 'inactive_30') {
            $sql_users = "SELECT email, username FROM users WHERE is_admin = 0 AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))";
        } elseif ($audience == 'specific') {
            $specific_users_raw = trim($_POST['specific_users']);
            if (empty($specific_users_raw)) {
                $errors[] = "Please provide at least one email address for the specific user audience.";
            } else {
                $specific_emails = array_map('trim', explode(',', $specific_users_raw));
                $placeholders = implode(',', array_fill(0, count($specific_emails), '?'));
                $sql_users = "SELECT email, username FROM users WHERE email IN ($placeholders)";
                $params = $specific_emails;
                $types = str_repeat('s', count($specific_emails));
            }
        } else {
            $errors[] = "Invalid audience selected.";
        }

        if ($sql_users) {
            $stmt = $conn->prepare($sql_users);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $users_result = $stmt->get_result();
        }
    }

    // Step 3: Send the emails
    if (empty($errors) && $users_result) {
        $email_count = 0;
        while ($recipient = $users_result->fetch_assoc()) {
            // Personalize email body
            $personalized_body = str_replace('[username]', htmlspecialchars($recipient['username']), $body);

            // Send email using the new helper
            send_email($recipient['email'], $subject, $personalized_body);
            $email_count++;

            if ($email_count % 10 == 0) { // Be kind to the mail server
                sleep(1);
            }
        }
        $success = "Email broadcast sent to " . $email_count . " user(s).";
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
                            <option value="specific">Specific User(s)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="specific-users-container" style="display: none;">
                        <label for="specific_users" class="form-label">User Emails</label>
                        <input type="text" class="form-control" name="specific_users" id="specific_users" placeholder="Enter user emails, separated by commas">
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
                    <div class="d-grid">
                        <button type="submit" name="send_broadcast" class="btn btn-primary btn-lg">Send Broadcast</button>
                    </div>
                </div>
                <div class="col-md-7">
                    <h5>&nbsp;</h5>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CKEditor 5 Superbuild CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
<style>
    .ck-editor__editable_inline {
        min-height: 400px;
    }
</style>

<script>
    let editor;
    CKEDITOR.ClassicEditor.create(document.querySelector('#custom_body'), {
        toolbar: {
            items: [
                'sourceEditing', '|',
                'heading', '|',
                'bold', 'italic', 'underline', 'link', '|',
                'bulletedList', 'numberedList', 'outdent', 'indent', '|',
                'blockQuote', 'insertTable', 'mediaEmbed', '|',
                'undo', 'redo'
            ]
        },
        language: 'en',
    }).then(newEditor => {
        editor = newEditor;
        // Keep the textarea updated in real-time
        editor.model.document.on('change:data', () => {
            document.querySelector('#custom_body').value = editor.getData();
        });
    }).catch(error => {
        console.error(error);
    });

document.addEventListener('DOMContentLoaded', function() {
    const audienceSelect = document.getElementById('audience');
    const specificUsersContainer = document.getElementById('specific-users-container');
    const messageSourceInput = document.getElementById('message_source');
    const templateTab = document.getElementById('template-tab');
    const customTab = document.getElementById('custom-tab');

    templateTab.addEventListener('click', function() {
        messageSourceInput.value = 'template';
    });
    customTab.addEventListener('click', function() {
        messageSourceInput.value = 'custom';
    });

    audienceSelect.addEventListener('change', function() {
        if (this.value === 'specific') {
            specificUsersContainer.style.display = 'block';
        } else {
            specificUsersContainer.style.display = 'none';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
