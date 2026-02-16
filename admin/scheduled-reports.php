<?php
$page_title = 'All Scheduled Tasks';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

// Generate a CSRF token for the cancel action
$csrf_token = generate_csrf_token();

// --- Search and Filter Logic ---
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT st.*, u.username FROM scheduled_tasks st JOIN users u ON st.user_id = u.id";
$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_term)) {
    $where_clauses[] = "(u.username LIKE ? OR st.task_type LIKE ?)";
    $search_param = "%{$search_term}%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= 'ss';
}
if (!empty($status_filter)) {
    $where_clauses[] = "st.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY st.created_at DESC";

// Fetch all scheduled tasks from all users
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function get_status_badge($status) {
    switch (strtolower($status)) {
        case 'completed':
            return '<span class="badge bg-success">Completed</span>';
        case 'pending':
            return '<span class="badge bg-warning">Pending</span>';
        case 'processing':
            return '<span class="badge bg-info">Processing</span>';
        case 'failed':
            return '<span class="badge bg-danger">Failed</span>';
        case 'cancelled':
            return '<span class="badge bg-secondary">Cancelled</span>';
        default:
            return '<span class="badge bg-dark">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo $page_title; ?></h3>
        <div class="card-tools">
             <form action="" method="GET" class="form-inline">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search User, Task Type..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <select name="status" class="form-select form-select-sm" style="max-width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php if(($_GET['status'] ?? '') == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="completed" <?php if(($_GET['status'] ?? '') == 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="processing" <?php if(($_GET['status'] ?? '') == 'processing') echo 'selected'; ?>>Processing</option>
                        <option value="failed" <?php if(($_GET['status'] ?? '') == 'failed') echo 'selected'; ?>>Failed</option>
                        <option value="cancelled" <?php if(($_GET['status'] ?? '') == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <p>This page shows the status of all scheduled tasks across the platform.</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Created</th>
                        <th>Scheduled For</th>
                        <th>Task Type</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="7" class="text-center">There are no scheduled tasks.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr id="task-row-<?php echo $task['id']; ?>">
                                <td><?php echo htmlspecialchars($task['username']); ?></td>
                                <td><?php echo format_date_for_display($task['created_at']); ?></td>
                                <td><?php echo format_date_for_display($task['scheduled_for']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($task['task_type'])); ?></td>
                                <td>
                                    <?php
                                    $payload = json_decode($task['payload'], true);
                                    $details = 'N/A'; // Default value
                                    if (is_array($payload)) {
                                        switch ($task['task_type']) {
                                            case 'email':
                                                if (isset($payload['subject'])) {
                                                    $details = 'Subject: ' . $payload['subject'];
                                                }
                                                break;
                                            case 'sms':
                                            case 'voice_tts':
                                                if (isset($payload['message'])) {
                                                    $details = $payload['message'];
                                                }
                                                break;
                                            default:
                                                $details = 'No details available for this task type.';
                                                break;
                                        }
                                    }
                                    echo htmlspecialchars(substr($details, 0, 50)) . (strlen($details) > 50 ? '...' : '');
                                    ?>
                                </td>
                                <td class="status-cell"><?php echo get_status_badge($task['status']); ?></td>
                                <td class="action-cell">
                                    <?php if ($task['status'] === 'pending' || $task['status'] === 'processing'): ?>
                                        <button class="btn btn-sm btn-outline-danger cancel-task-btn" data-task-id="<?php echo $task['id']; ?>">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cancel-task-btn').forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            if (!confirm('Are you sure you want to cancel this scheduled task? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');

            fetch('../ajax/cancel_scheduled_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`task-row-${taskId}`);
                    if (row) {
                        const statusCell = row.querySelector('.status-cell');
                        const actionCell = row.querySelector('.action-cell');
                        statusCell.innerHTML = '<span class="badge bg-secondary">Cancelled</span>';
                        actionCell.innerHTML = '-';
                    }
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
