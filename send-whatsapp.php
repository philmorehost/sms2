<?php
$page_title = 'Send WhatsApp Message';
require_once 'app/bootstrap.php';

$errors = [];
$success = '';

// Fetch user's approved WhatsApp templates
$templates = [];
$stmt = $conn->prepare("SELECT * FROM whatsapp_templates WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
}
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_whatsapp'])) {
    $recipient = trim($_POST['recipient']);
    $template_id = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT);
    $parameters = trim($_POST['parameters']);

    if (empty($recipient) || empty($template_id)) {
        $errors[] = "Recipient and a selected template are required.";
    }

    // Get template_code from template_id
    $template_code = '';
    foreach($templates as $t) {
        if ($t['id'] == $template_id) {
            $template_code = $t['template_code'];
            break;
        }
    }
    if (empty($template_code)) {
        $errors[] = "Invalid template selected or template not approved.";
    }

    if (empty($errors)) {
        $button_parameters = trim($_POST['button_parameters']);
        $header_parameters = trim($_POST['header_parameters']);

        $result = send_whatsapp_message($user, $recipient, $template_code, $parameters, $button_parameters, $header_parameters, $conn);

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Send Template-Based WhatsApp Message</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><p class="mb-0"><?php echo $success; ?></p></div>
        <?php endif; ?>

        <p>Select an approved template and provide the required information to send your message.</p>
        <hr>

        <form action="send-whatsapp.php" method="POST">
            <div class="mb-3">
                <label for="template_id" class="form-label">Select Template</label>
                <select name="template_id" id="template_id" class="form-select" required <?php if(empty($templates)) echo 'disabled'; ?>>
                    <option value="">-- Select an Approved Template --</option>
                    <?php foreach($templates as $template): ?>
                        <option value="<?php echo $template['id']; ?>">
                            <?php echo htmlspecialchars($template['template_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($templates)): ?>
                    <div class="form-text text-danger">You have no approved WhatsApp templates. Please <a href="whatsapp-templates.php">create one</a>.</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="recipient" class="form-label">Recipient Phone Number</label>
                <input type="text" class="form-control" name="recipient" required placeholder="e.g., 2348012345678">
            </div>

            <div class="mb-3">
                <label for="parameters" class="form-label">Parameters</label>
                <input type="text" class="form-control" name="parameters" placeholder="e.g., John, AB-123, <?php echo get_currency_symbol(); ?>50">
                <div class="form-text">Enter the required parameters for your template, separated by commas.</div>
            </div>

            <div class="mb-3">
                <label for="button_parameters" class="form-label">Button Parameters (Optional)</label>
                <input type="text" class="form-control" name="button_parameters" placeholder="e.g., view-order, track-shipment">
            </div>

             <div class="mb-3">
                <label for="header_parameters" class="form-label">Header Parameters (Optional)</label>
                <input type="text" class="form-control" name="header_parameters" placeholder="e.g., https://example.com/image.jpg">
            </div>

            <button type="submit" name="send_whatsapp" class="btn btn-success" <?php if(empty($templates)) echo 'disabled'; ?>>
                <i class="fab fa-whatsapp"></i> Send WhatsApp Message
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
