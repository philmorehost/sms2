<?php
$page_title = 'Page Management';
include 'includes/header.php';

// Generate CSRF token for all forms on this page
$csrf_token = generate_csrf_token();

$errors = [];
$success = '';

// Function to generate a unique slug
function generate_slug($title, $conn, $ignore_id = null) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $original_slug = $slug;
    $i = 1;
    while (true) {
        $query = "SELECT id FROM pages WHERE slug = ?";
        $params = [$slug];
        if ($ignore_id) {
            $query .= " AND id != ?";
            $params[] = $ignore_id;
        }
        $stmt = $conn->prepare($query);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            return $slug;
        }
        $slug = $original_slug . '-' . $i++;
    }
}


// -- C.R.U.D. LOGIC --

// CREATE Page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_page'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $body = trim($_POST['body']);
    $visibility = $_POST['visibility'];

    if (empty($slug)) {
        $slug = generate_slug($title, $conn);
    } else {
        $slug = generate_slug($slug, $conn);
    }

    if (!empty($title) && !empty($body) && in_array($visibility, ['public', 'private', 'hidden'])) {
        $stmt = $conn->prepare("INSERT INTO pages (title, slug, body, visibility) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $slug, $body, $visibility);
        if ($stmt->execute()) {
            $success = "Page created successfully.";
        } else {
            $errors[] = "Failed to create page.";
        }
        $stmt->close();
    } else {
        $errors[] = "Title, body, and a valid visibility are required.";
    }
}

// UPDATE Page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_page'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $page_id = (int)$_POST['page_id'];
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $body = trim($_POST['body']);
    $visibility = $_POST['visibility'];

    if (empty($slug)) {
        $slug = generate_slug($title, $conn, $page_id);
    } else {
        $slug = generate_slug($slug, $conn, $page_id);
    }

    if (!empty($title) && !empty($body) && in_array($visibility, ['public', 'private', 'hidden'])) {
        $stmt = $conn->prepare("UPDATE pages SET title=?, slug=?, body=?, visibility=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $slug, $body, $visibility, $page_id);
        if ($stmt->execute()) {
            $success = "Page updated successfully.";
        } else {
            $errors[] = "Failed to update page.";
        }
        $stmt->close();
    } else {
        $errors[] = "Title, body, and a valid visibility are required.";
    }
}

// DELETE Page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_page'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $page_id = (int)$_POST['page_id'];
    $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
    $stmt->bind_param("i", $page_id);
    if ($stmt->execute()) {
        $success = "Page deleted successfully.";
    } else {
        $errors[] = "Failed to delete page.";
    }
    $stmt->close();
}

// Check for 'edit' action
$edit_page = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $page_id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->bind_param("i", $page_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_page = $result->fetch_assoc();
    }
    $stmt->close();
}


// READ Pages
$pages = [];
$sql = "SELECT id, title, slug, visibility, created_at, updated_at FROM pages ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pages[] = $row;
    }
    $stmt->close();
}
?>

<?php if ($edit_page): ?>
    <!-- Edit Page View -->
    <h3 class="mb-3">Edit Page: <?php echo htmlspecialchars($edit_page['title']); ?></h3>
    <form action="pages.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="page_id" value="<?php echo $edit_page['id']; ?>">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $edit_page['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($_POST['slug'] ?? $edit_page['slug']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label">Body</label>
                    <textarea class="form-control" id="body" name="body" rows="10"><?php echo htmlspecialchars($_POST['body'] ?? $edit_page['body']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="visibility" class="form-label">Visibility</label>
                    <select class="form-select" id="visibility" name="visibility" required>
                        <option value="public" <?php if(($_POST['visibility'] ?? $edit_page['visibility']) == 'public') echo 'selected'; ?>>Public</option>
                        <option value="private" <?php if(($_POST['visibility'] ?? $edit_page['visibility']) == 'private') echo 'selected'; ?>>Private (Logged-in users only)</option>
                        <option value="hidden" <?php if(($_POST['visibility'] ?? $edit_page['visibility']) == 'hidden') echo 'selected'; ?>>Hidden (Not in menus)</option>
                    </select>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="pages.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="edit_page" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
<?php else: ?>
    <!-- List Pages View -->
    <div class="row mb-3">
        <div class="col">
            <h3 class="m-0">Custom Pages</h3>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPageModal">
                <i class="fas fa-plus"></i> Add New Page
            </button>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Visibility</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No pages found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?php echo $page['id']; ?></td>
                        <td><?php echo htmlspecialchars($page['title']); ?></td>
                        <td><a href="../page.php?slug=<?php echo htmlspecialchars($page['slug']); ?>" target="_blank">/<?php echo htmlspecialchars($page['slug']); ?> <i class="fas fa-external-link-alt fa-xs"></i></a></td>
                        <td><span class="badge bg-info"><?php echo ucfirst(htmlspecialchars($page['visibility'])); ?></span></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($page['created_at'])); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($page['updated_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="pages.php?action=edit&id=<?php echo $page['id']; ?>" class="btn btn-info btn-sm" title="Edit Page"><i class="fas fa-edit"></i></a>
                                <form action="pages.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                    <button type="submit" name="delete_page" class="btn btn-danger btn-sm" title="Delete Page"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Page Modal -->
    <div class="modal fade" id="addPageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="pages.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Page</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="add_title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="add_slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="add_slug" name="slug" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                            <small class="form-text text-muted">Leave blank to auto-generate from title.</small>
                        </div>
                        <div class="mb-3">
                            <label for="add_body" class="form-label">Body</label>
                            <textarea class="form-control" id="add_body" name="body" rows="10"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="add_visibility" class="form-label">Visibility</label>
                            <select class="form-select" id="add_visibility" name="visibility" required>
                                <option value="public" <?php if(($_POST['visibility'] ?? 'public') == 'public') echo 'selected'; ?>>Public</option>
                                <option value="private" <?php if(($_POST['visibility'] ?? '') == 'private') echo 'selected'; ?>>Private (Logged-in users only)</option>
                                <option value="hidden" <?php if(($_POST['visibility'] ?? '') == 'hidden') echo 'selected'; ?>>Hidden (Not in menus)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_page" class="btn btn-primary">Save Page</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Basic slug generation
    const titleInput = document.getElementById('add_title');
    const slugInput = document.getElementById('add_slug');

    if (titleInput && slugInput) {
        titleInput.addEventListener('keyup', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                              .trim()
                              .replace(/[^a-z0-9\s-]/g, '') // remove non-alphanumeric characters
                              .replace(/\s+/g, '-');       // replace spaces with -
            slugInput.value = slug;
        });
    }
});
</script>

<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
<script>
    function initializeCKEditor(element, form) {
        if (element && !element.ckeditorInstance) {
            ClassicEditor
                .create(element)
                .then(editor => {
                    element.ckeditorInstance = editor;
                    // Add a submit listener to the form to update the textarea
                    form.addEventListener('submit', () => {
                        editor.updateSourceElement();
                    });
                })
                .catch(error => {
                    console.error('Error initializing CKEditor:', error);
                });
        }
    }

    // Handle the "Add Page" modal editor
    const addPageModal = document.getElementById('addPageModal');
    if (addPageModal) {
        const addForm = addPageModal.querySelector('form');
        const addEditorElement = addPageModal.querySelector('#add_body');

        addPageModal.addEventListener('shown.bs.modal', () => {
            initializeCKEditor(addEditorElement, addForm);
        });
    }

    // Handle the "Edit Page" editor (which is not in a modal)
    const editEditorElement = document.querySelector('#body');
    if (editEditorElement) {
        const editForm = editEditorElement.closest('form');
        initializeCKEditor(editEditorElement, editForm);
    }
</script>

<?php include 'includes/footer.php'; ?>
