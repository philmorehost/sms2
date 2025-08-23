<?php
require_once '../app/bootstrap.php';

// The admin authentication is handled by the header file.
// No further checks are needed here.

$message = '';

// Handle Add Ban
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ban'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);

    if (!empty($type) && !empty($value)) {
        $stmt = $conn->prepare("INSERT INTO banned (type, value) VALUES (?, ?)");
        $stmt->bind_param("ss", $type, $value);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Ban added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding ban. It might already exist.</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">Type and Value are required.</div>';
    }
}

// Handle Delete Ban
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ban'])) {
    $ban_id = $_POST['ban_id'];
    $stmt = $conn->prepare("DELETE FROM banned WHERE id = ?");
    $stmt->bind_param("i", $ban_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Ban removed successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error removing ban.</div>';
    }
    $stmt->close();
}

// Fetch all banned items
$banned_items = [];
$stmt = $conn->prepare("SELECT * FROM banned ORDER BY created_at DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $banned_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


$page_title = "Banning System";
include_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Add New Ban</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <form method="POST">
                        <input type="hidden" name="add_ban" value="1">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Ban Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="ip">IP Address</option>
                                        <option value="email">Email Address</option>
                                        <option value="word">Word</option>
                                        <option value="phone_number">Phone Number</option>
                                        <option value="country">Country</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="value" class="form-label">Value</label>
                                    <input type="text" class="form-control" id="value" name="value" placeholder="e.g., 192.168.1.1, spam@example.com, etc." required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">Add Ban</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Banned Items</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Date Banned</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($banned_items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No banned items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($banned_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['type']); ?></td>
                                        <td><?php echo htmlspecialchars($item['value']); ?></td>
                                        <td><?php echo $item['created_at']; ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this ban?');">
                                                <input type="hidden" name="delete_ban" value="1">
                                                <input type="hidden" name="ban_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
