<?php
$page_title = 'WhatsApp Template Management';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

// Handle status and code updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_template'])) {
    $template_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = $_POST['status'];
    $template_code = trim($_POST['template_code']);

    if ($template_id && in_array($status, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE whatsapp_templates SET status = ?, template_code = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $template_code, $template_id);
        if ($stmt->execute()) {
            $success = "WhatsApp Template #" . $template_id . " has been updated.";
            // TODO: Notify user of status change via email
        } else {
            $errors[] = "Failed to update template.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid data provided.";
    }
}

// Data is now fetched via AJAX
$submissions = [];

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">WhatsApp Template Submissions</h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><p class="mb-0"><?php echo $success; ?></p></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-12 col-md-8 mb-2 mb-md-0">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Username or Template Name/Body...">
            </div>
            <div class="col-12 col-md-4">
                <select id="statusFilter" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>User</th>
                    <th>Template Name</th>
                    <th>Body</th>
                    <th>Params</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded by AJAX -->
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Template Modals -->
<?php foreach ($submissions as $sub): ?>
<div class="modal fade" id="editTemplateModal<?php echo $sub['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="whatsapp-templates.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Manage WhatsApp Template #<?php echo $sub['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>User:</strong> <?php echo htmlspecialchars($sub['username']); ?></p>
                    <p><strong>Template Body:</strong><br><?php echo nl2br(htmlspecialchars($sub['template_body'])); ?></p>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?php if($sub['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="approved" <?php if($sub['status'] == 'approved') echo 'selected'; ?>>Approved</option>
                            <option value="rejected" <?php if($sub['status'] == 'rejected') echo 'selected'; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Template Code (from API Provider)</label>
                        <input type="text" class="form-control" name="template_code" value="<?php echo htmlspecialchars($sub['template_code']); ?>" placeholder="Enter the official code from Kudisms">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_template" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<div class="card-footer pagination-container"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.querySelector('.table tbody');
    const paginationContainer = document.querySelector('.pagination-container');
    let currentPage = 1;
    let searchTimeout;

    function fetchTemplates() {
        const searchTerm = searchInput.value;
        const status = statusFilter.value;

        tableBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('status', status);
        formData.append('page', currentPage);

        fetch('ajax/search_whatsapp_templates.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tableBody.innerHTML = data.html;
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination;
                }
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">An error occurred.</td></tr>';
            }
        })
        .catch(error => {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">An error occurred. Please try again.</td></tr>';
            console.error('Error:', error);
        });
    }

    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchTemplates();
        }, 300);
    });

    statusFilter.addEventListener('change', () => {
        currentPage = 1;
        fetchTemplates();
    });

    if (paginationContainer) {
        paginationContainer.addEventListener('click', function(e) {
            e.preventDefault();
            const link = e.target.closest('a.page-link');
            if (link) {
                const page = link.dataset.page;
                if (page) {
                    currentPage = parseInt(page, 10);
                    fetchTemplates();
                }
            }
        });
    }

    fetchTemplates();
});
</script>
<?php include 'includes/footer.php'; ?>
