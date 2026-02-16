<?php
$page_title = 'Support Tickets';
require_once __DIR__ . '/../app/bootstrap.php';

// Handle filtering
$status_filter = $_GET['status'] ?? 'open';
$allowed_filters = ['open', 'closed', 'all'];
if (!in_array($status_filter, $allowed_filters)) {
    $status_filter = 'open'; // Default to a safe value
}

$sql_where = "";
if ($status_filter == 'open') {
    $sql_where = "WHERE t.status IN ('open', 'user_reply')";
} elseif ($status_filter == 'closed') {
    $sql_where = "WHERE t.status = 'closed'";
}
// if 'all', $sql_where remains empty

// Data is now fetched via AJAX
$tickets = [];

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Support Ticket Management</h1>
</div>

<div class="card">
    <div class="card-header">
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs card-header-tabs nav-tabs-responsive">
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter == 'open') echo 'active'; ?>" href="?status=open">Open Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter == 'all') echo 'active'; ?>" href="?status=all">All Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter == 'closed') echo 'active'; ?>" href="?status=closed">Closed Tickets</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-12">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Ticket ID, User, or Subject...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded by AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-footer pagination-container"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.querySelector('.table tbody');
    const paginationContainer = document.querySelector('.pagination-container');
    const statusFilter = new URLSearchParams(window.location.search).get('status') || 'open';
    let currentPage = 1;
    let searchTimeout;

    function fetchTickets() {
        const searchTerm = searchInput.value;

        tableBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('status', statusFilter);
        formData.append('page', currentPage);

        fetch('ajax/search_support_tickets.php', {
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
            fetchTickets();
        }, 300);
    });

    if (paginationContainer) {
        paginationContainer.addEventListener('click', function(e) {
            e.preventDefault();
            const link = e.target.closest('a.page-link');
            if (link) {
                const page = link.dataset.page;
                if (page) {
                    currentPage = parseInt(page, 10);
                    fetchTickets();
                }
            }
        });
    }

    fetchTickets();
});
</script>
<?php include 'includes/footer.php'; ?>
