<?php
$page_title = 'Caller ID Management';
require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $caller_id_record_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($caller_id_record_id && ($action == 'approve' || $action == 'reject')) {
        $new_status = ($action == 'approve') ? 'approved' : 'rejected';

        $stmt = $conn->prepare("UPDATE caller_ids SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $caller_id_record_id);
        if ($stmt->execute()) {
            $success = "Caller ID has been " . $new_status . ".";
        } else {
            $errors[] = "Failed to update status.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid action or ID.";
    }
}

// Data is now fetched via AJAX
$submissions = [];

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Caller ID Submissions</h1>
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

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-12 col-md-8 mb-2 mb-md-0">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Username or Caller ID...">
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
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                    <th>Username</th>
                    <th>Submitted Caller ID</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded by AJAX -->
            </tbody>
        </table>
        </div>
        <div class="pagination-container"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.querySelector('.table tbody');
    const paginationContainer = document.querySelector('.pagination-container');
    let currentPage = 1;
    let searchTimeout;

    function fetchCallerIds() {
        const searchTerm = searchInput.value;
        const status = statusFilter.value;

        tableBody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('status', status);
        formData.append('page', currentPage);

        fetch('ajax/search_caller_ids.php', {
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
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">An error occurred.</td></tr>';
            }
        })
        .catch(error => {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">An error occurred. Please try again.</td></tr>';
            console.error('Error:', error);
        });
    }

    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchCallerIds();
        }, 300);
    });

    statusFilter.addEventListener('change', () => {
        currentPage = 1;
        fetchCallerIds();
    });

    if (paginationContainer) {
        paginationContainer.addEventListener('click', function(e) {
            e.preventDefault();
            const link = e.target.closest('a.page-link');
            if (link) {
                const page = link.dataset.page;
                if (page) {
                    currentPage = parseInt(page, 10);
                    fetchCallerIds();
                }
            }
        });
    }

    fetchCallerIds();
});
</script>
<?php include 'includes/footer.php'; ?>
