<?php
$page_title = 'Scheduled Tasks';
require_once 'app/bootstrap.php';

// Fetch pending scheduled tasks for the user
$tasks = [];
$stmt = $conn->prepare("SELECT * FROM scheduled_tasks WHERE user_id = ? AND status = 'pending' ORDER BY scheduled_for ASC");
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt->close();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Scheduled Tasks</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Scheduled For</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all-schedules">
                                <label class="form-check-label" for="select-all-schedules">Actions</label>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="schedules-table-body">
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="5" class="text-center">You have no pending scheduled tasks to display.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                        <tr id="task-row-<?php echo $task['id']; ?>">
                            <td><?php echo date('Y-m-d H:i', strtotime($task['scheduled_for'])); ?></td>
                            <td><span class="badge bg-info"><?php echo strtoupper($task['task_type']); ?></span></td>
                            <td>
                                <?php
                                    $payload = json_decode($task['payload'], true);
                                    echo "<strong>To:</strong> " . htmlspecialchars(substr($payload['recipients'], 0, 30)) . "...<br>";
                                    echo "<strong>Msg:</strong> " . htmlspecialchars(substr($payload['message'], 0, 40)) . "...";
                                ?>
                            </td>
                            <td>
                                <?php
                                    $status = htmlspecialchars($task['status']);
                                    $badge_class = 'bg-secondary';
                                    if ($status == 'completed') $badge_class = 'bg-success';
                                    if ($status == 'pending') $badge_class = 'bg-warning text-dark';
                                    if ($status == 'failed') $badge_class = 'bg-danger';
                                    if ($status == 'cancelled') $badge_class = 'bg-dark';
                                    if ($status == 'processing') $badge_class = 'bg-primary';
                                    echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                                ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="form-check me-2">
                                        <input class="form-check-input schedule-checkbox" type="checkbox" value="<?php echo $task['id']; ?>">
                                    </div>
                                    <a href="send-sms.php?edit_task_id=<?php echo $task['id']; ?>" class="btn btn-info btn-sm" title="Edit Schedule">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-danger btn-sm cancel-schedule-btn ms-1" data-task-id="<?php echo $task['id']; ?>" title="Cancel Schedule">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</table>
        </div>
        <?php if (!empty($tasks)): ?>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-danger" id="cancel-selected-btn" disabled><i class="fas fa-trash-alt"></i> Cancel Selected</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('schedules-table-body');
    const selectAllCheckbox = document.getElementById('select-all-schedules');
    const rowCheckboxes = document.querySelectorAll('.schedule-checkbox');
    const cancelSelectedBtn = document.getElementById('cancel-selected-btn');

    function updateCancelSelectedButtonState() {
        const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
        cancelSelectedBtn.disabled = !anyChecked;
    }

    selectAllCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateCancelSelectedButtonState();
    });

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCancelSelectedButtonState);
    });

    function handleCancellation(taskIds) {
        if (!confirm(`Are you sure you want to cancel ${taskIds.length} scheduled task(s)?`)) {
            return;
        }

        fetch('ajax/cancel_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `task_ids=${JSON.stringify(taskIds)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                taskIds.forEach(taskId => {
                    const row = document.getElementById(`task-row-${taskId}`);
                    if (row) {
                        row.classList.add('table-danger');
                        row.querySelector('td:nth-child(4)').innerHTML = '<span class="badge bg-dark">Cancelled</span>';
                        row.querySelector('td:nth-child(5)').innerHTML = ''; // Remove buttons
                    }
                });
                updateCancelSelectedButtonState();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during cancellation.');
        });
    }

    // Handle single cancel button click
    tableBody.addEventListener('click', function(e) {
        const button = e.target.closest('.cancel-schedule-btn');
        if (button) {
            const taskId = button.dataset.taskId;
            handleCancellation([taskId]);
        }
    });

    // Handle cancel selected button click
    if (cancelSelectedBtn) {
        cancelSelectedBtn.addEventListener('click', function() {
            const selectedIds = Array.from(rowCheckboxes)
                                    .filter(cb => cb.checked)
                                    .map(cb => cb.value);
            if (selectedIds.length > 0) {
                handleCancellation(selectedIds);
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
