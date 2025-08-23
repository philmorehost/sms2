<?php
$page_title = 'WhatsApp Templates';
require_once 'app/bootstrap.php';

$errors = [];
$success = '';

// Handle new template submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_template'])) {
    $template_name = trim($_POST['template_name']);
    $template_body = trim($_POST['template_body']);
    $parameters_list = trim($_POST['parameters_list']);

    if (empty($template_name) || empty($template_body)) {
        $errors[] = "Template name and body are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO whatsapp_templates (user_id, template_name, template_body, parameters_list) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $current_user['id'], $template_name, $template_body, $parameters_list);
        if ($stmt->execute()) {
            $success = "Template submitted successfully. It is now pending review.";
            // Notify admin
            $admin_email = get_admin_email();
            $subject = "New WhatsApp Template for Review";
            $message = "<p>User " . htmlspecialchars($current_user['username']) . " has submitted a new WhatsApp template for review.</p>";
            send_email($admin_email, $subject, $message);
        } else {
            $errors[] = "Failed to submit template.";
        }
        $stmt->close();
    }
}

// Fetch user's submitted templates
$templates = [];
$stmt = $conn->prepare("SELECT * FROM whatsapp_templates WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
}
$stmt->close();

include 'includes/header.php';
?>

<div class="row">
    <!-- Submit Template Form -->
    <div class="col-md-5">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Submit New WhatsApp Template</h3></div>
            <form action="whatsapp-templates.php" method="POST">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><p class="mb-0"><?php echo $success; ?></p></div>
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label for="template_name" class="form-label">Template Name</label>
                        <input type="text" class="form-control" name="template_name" required placeholder="e.g., Order Confirmation">
                    </div>
                    <div class="form-group mb-3">
                        <label for="template_body" class="form-label">Template Body</label>
                        <textarea class="form-control" name="template_body" rows="6" required placeholder="e.g., Hello {{1}}, your order {{2}} has been shipped."></textarea>
                        <div class="form-text">Use placeholders like `{{1}}`, `{{2}}` for dynamic parameters.</div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="parameters_list" class="form-label">Parameters List (Optional)</label>
                        <input type="text" class="form-control" name="parameters_list" placeholder="e.g., name, order_id">
                        <div class="form-text">A comma-separated list of what each parameter is for your own reference.</div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit_template" class="btn btn-primary">Submit for Review</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Submitted Templates List -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">My Submitted Templates</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Template Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                            <tr><td colspan="3" class="text-center">You have not submitted any templates yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                                <td>
                                    <?php
                                        $status = htmlspecialchars($template['status']);
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
                                <td><code><?php echo htmlspecialchars($template['template_code'] ?? 'N/A'); ?></code></td>
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
