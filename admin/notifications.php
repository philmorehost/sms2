<?php
$page_title = 'Push Notifications';
include_once 'includes/header.php';

$message = '';
$edit_notification = null;

// Handle Create/Update Notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notification'])) {
    $id = $_POST['id'] ?? null;
    $message_text = trim($_POST['message']);
    $type = $_POST['type'];
    $placement = trim($_POST['placement']);
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($message_text) || empty($type) || empty($placement)) {
        $message = '<div class="alert alert-danger">Message, Type, and Placement are required.</div>';
    } else {
        if ($id) { // Update
            $stmt = $conn->prepare("UPDATE notifications SET message = ?, type = ?, placement = ?, start_time = ?, end_time = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $message_text, $type, $placement, $start_time, $end_time, $is_active, $id);
            $action = 'updated';
        } else { // Create
            $stmt = $conn->prepare("INSERT INTO notifications (message, type, placement, start_time, end_time, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $message_text, $type, $placement, $start_time, $end_time, $is_active);
            $action = 'created';
        }

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Notification ' . $action . ' successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error saving notification.</div>';
        }
        $stmt->close();
    }
}

// Handle Delete Notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Notification deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting notification.</div>';
    }
    $stmt->close();
}

// Handle Edit Request
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_notification = $result->fetch_assoc();
    $stmt->close();
}

// Fetch all notifications
$notifications = [];
$stmt = $conn->prepare("SELECT * FROM notifications ORDER BY created_at DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><?php echo $edit_notification ? 'Edit Notification' : 'Create New Notification'; ?></h5>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $edit_notification['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required><?php echo htmlspecialchars($edit_notification['message'] ?? ''); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <?php $types = ['info', 'success', 'warning', 'danger']; ?>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo isset($edit_notification) && $edit_notification['type'] == $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="placement" class="form-label">Placement</label>
                            <input type="text" class="form-control" id="placement" name="placement" value="<?php echo htmlspecialchars($edit_notification['placement'] ?? 'all'); ?>" required>
                            <small class="form-text text-muted">Use 'all' for all pages, or a page name like 'dashboard.php'. Separate multiple pages with commas.</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time (Optional)</label>
                            <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="<?php echo isset($edit_notification['start_time']) ? date('Y-m-d\TH:i', strtotime($edit_notification['start_time'])) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">End Time (Optional)</label>
                            <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="<?php echo isset($edit_notification['end_time']) ? date('Y-m-d\TH:i', strtotime($edit_notification['end_time'])) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($edit_notification) && !$edit_notification['is_active']) ? '' : 'checked'; ?>>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                    <button type="submit" name="save_notification" class="btn btn-primary">Save Notification</button>
                    <?php if ($edit_notification): ?>
                        <a href="notifications.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Existing Notifications</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Placement</th>
                            <th>Active Dates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notif): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($notif['message'], 0, 50)); ?>...</td>
                                <td><span class="badge bg-<?php echo $notif['type']; ?>"><?php echo ucfirst($notif['type']); ?></span></td>
                                <td><?php echo htmlspecialchars($notif['placement']); ?></td>
                                <td><?php echo $notif['start_time'] ? date('M j, Y H:i', strtotime($notif['start_time'])) : 'N/A'; ?> - <?php echo $notif['end_time'] ? date('M j, Y H:i', strtotime($notif['end_time'])) : 'N/A'; ?></td>
                                <td><?php echo $notif['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $notif['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline-block;">
                                        <input type="hidden" name="delete_notification" value="1">
                                        <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
