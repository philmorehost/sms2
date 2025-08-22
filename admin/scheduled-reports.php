<?php
$page_title = 'All Scheduled Tasks';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

// Fetch all scheduled tasks from all users
$stmt = $conn->prepare("SELECT st.*, u.username FROM scheduled_tasks st JOIN users u ON st.user_id = u.id ORDER BY st.created_at DESC");
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
                                    echo htmlspecialchars(substr($payload['message'], 0, 50)) . '...';
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

            // Note: The AJAX endpoint is in the main ajax folder, not an admin-specific one.
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
