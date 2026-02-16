<?php
$page_title = 'Email Templates';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_template'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM email_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Email template deleted successfully.";
        } else {
            $errors[] = "Failed to delete template.";
        }
        $stmt->close();
    }
}

// --- Search Logic ---
$search_term = $_GET['search'] ?? '';
$sql = "SELECT * FROM email_templates";
$params = [];
$types = '';

if (!empty($search_term)) {
    $sql .= " WHERE name LIKE ? OR subject LIKE ?";
    $search_param = "%{$search_term}%";
    $params = [$search_param, $search_param];
    $types = 'ss';
}

$sql .= " ORDER BY name ASC";

// Fetch all templates
$templates = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Email Templates</h1>
    <a href="edit-email-template.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Template
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
    <div class="card-header">
        <div class="card-tools">
            <form action="" method="GET" class="form-inline">
                <div class="input-group input-group-sm" style="width: 300px;">
                    <input type="text" name="search" class="form-control float-right" placeholder="Search by Name or Subject..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                    <th>Template Name</th>
                    <th>Subject</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="4" class="text-center">No email templates created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($template['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($template['subject']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($template['updated_at'])); ?></td>
                        <td>
                            <a href="edit-email-template.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                            <form action="email-templates.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                <button type="submit" name="delete_template" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
