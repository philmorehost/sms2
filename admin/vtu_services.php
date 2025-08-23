<?php
$page_title = 'VTU Service Management';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle form submission for updating services
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_vtu_service'])) {
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $limit_count = filter_input(INPUT_POST, 'limit_count', FILTER_VALIDATE_INT);
    $limit_period = filter_input(INPUT_POST, 'limit_period', FILTER_VALIDATE_INT);

    if ($service_id) {
        $stmt = $conn->prepare("UPDATE vtu_services SET is_active = ?, transaction_limit_count = ?, transaction_limit_period_hours = ? WHERE id = ?");
        $stmt->bind_param("iiii", $is_active, $limit_count, $limit_period, $service_id);
        if ($stmt->execute()) {
            $success = "Service settings updated successfully.";
        } else {
            $errors[] = "Failed to update service settings.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid service ID.";
    }
}

// Fetch all VTU services
$services = [];
$stmt = $conn->prepare("SELECT * FROM vtu_services ORDER BY service_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    $stmt->close();
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Manage Services and Transaction Limits</h3>
    </div>
    <div class="card-body">
        <p>Enable or disable entire service categories and set transaction limits for users to prevent abuse. For example, you can limit a user to 5 airtime purchases in any 24-hour period.</p>
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
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Service Name</th>
                        <th>Status</th>
                        <th>Transaction Limit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="4" class="text-center">No services found. This might indicate a database issue.</td></tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($service['service_name']); ?></strong></td>
                            <td>
                                <?php if ($service['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($service['transaction_limit_count']); ?> transactions per <?php echo htmlspecialchars($service['transaction_limit_period_hours']); ?> hours
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editServiceModal<?php echo $service['id']; ?>">Manage</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Service Modals -->
<?php foreach ($services as $service): ?>
<div class="modal fade" id="editServiceModal<?php echo $service['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="vtu_services.php" method="POST">
                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Manage: <?php echo htmlspecialchars($service['service_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active_<?php echo $service['id']; ?>" name="is_active" value="1" <?php if($service['is_active']) echo 'checked'; ?>>
                        <label class="form-check-label" for="is_active_<?php echo $service['id']; ?>">Service is Active</label>
                    </div>
                    <hr>
                    <h5>Transaction Limits</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="limit_count_<?php echo $service['id']; ?>" class="form-label">Limit Count</label>
                            <input type="number" class="form-control" id="limit_count_<?php echo $service['id']; ?>" name="limit_count" value="<?php echo htmlspecialchars($service['transaction_limit_count']); ?>" min="0">
                            <div class="form-text">Max number of transactions allowed.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="limit_period_<?php echo $service['id']; ?>" class="form-label">Limit Period (Hours)</label>
                            <input type="number" class="form-control" id="limit_period_<?php echo $service['id']; ?>" name="limit_period" value="<?php echo htmlspecialchars($service['transaction_limit_period_hours']); ?>" min="1">
                            <div class="form-text">The time frame for the limit.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_vtu_service" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
