<?php
$page_title = 'OTP Templates';
require_once 'app/bootstrap.php';

$errors = [];
$success = '';

// Handle new template submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_template'])) {
    $template_name = trim($_POST['template_name']);
    $template_body = trim($_POST['template_body']);

    // Validation
    if (empty($template_name) || empty($template_body)) {
        $errors[] = "Template name and body are required.";
    }
    // Ensure the [OTP] placeholder exists
    if (strpos($template_body, '[OTP]') === false) {
        $errors[] = "Your template body must include the `[OTP]` placeholder.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO otp_templates (user_id, template_name, template_body) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $current_user['id'], $template_name, $template_body);
        if ($stmt->execute()) {
            $success = "OTP Template submitted successfully. It is now pending review.";
        } else {
            $errors[] = "Failed to submit template. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch user's submitted templates
$templates = [];
$stmt = $conn->prepare("SELECT * FROM otp_templates WHERE user_id = ? ORDER BY created_at DESC");
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
            <div class="card-header"><h3 class="card-title">Submit New OTP Template</h3></div>
            <form action="otp-templates.php" method="POST">
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
                        <label for="template_name" class="form-label">Template Name</label>
                        <input type="text" class="form-control" name="template_name" placeholder="e.g., My App Verification" required>
                        <small class="form-text text-muted">A friendly name for your reference.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="template_body" class="form-label">Template Body</label>
                        <textarea class="form-control" name="template_body" rows="4" required></textarea>
                        <div class="form-text text-muted">
                            Your message must include the `[OTP]` placeholder.
                            <br>Example: `Your verification code for My App is [OTP].`
                        </div>
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
            <div class="card-header"><h3 class="card-title">My OTP Templates</h3></div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Template</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                            <tr><td colspan="3" class="text-center">You have not submitted any OTP templates yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                                <td><?php echo htmlspecialchars($template['template_body']); ?></td>
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
