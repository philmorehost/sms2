<?php
$page_title = 'Edit Email Template';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';
$template = [
    'id' => null,
    'name' => '',
    'subject' => '',
    'body' => ''
];

// Check if we are editing an existing template
$template_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($template_id) {
    $stmt = $conn->prepare("SELECT * FROM email_templates WHERE id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $template = $result->fetch_assoc();
        $page_title = 'Edit: ' . htmlspecialchars($template['name']);
    } else {
        // Redirect if template not found
        header("Location: email-templates.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_template'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $subject = trim($_POST['subject']);
    // Do not trim the body, as it can contain intentional whitespace for HTML.
    $body = $_POST['body'];

    if (empty($name) || empty($subject) || empty($body)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        if ($id) { // Update existing
            $stmt = $conn->prepare("UPDATE email_templates SET name = ?, subject = ?, body = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $subject, $body, $id);
        } else { // Insert new
            $stmt = $conn->prepare("INSERT INTO email_templates (name, subject, body) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $subject, $body);
        }

        if ($stmt->execute()) {
            $new_id = $id ? $id : $stmt->insert_id;
            header("Location: edit-email-template.php?id=$new_id&success=1");
            exit();
        } else {
            $errors[] = "Failed to save template.";
        }
        $stmt->close();
    }
}

if(isset($_GET['success'])) {
    $success = "Template saved successfully!";
}

include 'includes/header.php';
?>
<!-- CKEditor 5 CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $template_id ? 'Edit Template' : 'Create New Template'; ?></h1>
    <a href="email-templates.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to All Templates
    </a>
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
        <form action="edit-email-template.php<?php if($template_id) echo "?id=$template_id"; ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Template Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($template['name']); ?>" required>
                <div class="form-text">A friendly name for your own reference.</div>
            </div>
             <div class="mb-3">
                <label for="subject" class="form-label">Email Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($template['subject']); ?>" required>
            </div>
             <div class="mb-3">
                <label for="template_body" class="form-label">Email Body</label>
                <textarea class="form-control" id="template_body" name="body" rows="15"><?php echo htmlspecialchars($template['body']); ?></textarea>
            </div>
            <button type="submit" name="save_template" class="btn btn-primary">Save Template</button>
        </form>
    </div>
</div>


<script>
    ClassicEditor
        .create( document.querySelector( '#template_body' ) )
        .catch( error => {
            console.error( error );
        } );
</script>
<?php include 'includes/footer.php'; ?>
