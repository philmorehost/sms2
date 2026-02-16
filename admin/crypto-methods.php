<?php
$page_title = 'Cryptocurrency Payment Methods';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle form submissions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    // Handle Add/Edit
    if (isset($_POST['save_method'])) {
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $network = trim($_POST['network']);
        $instructions = trim($_POST['instructions']);
        $charge_percentage = filter_input(INPUT_POST, 'charge_percentage', FILTER_VALIDATE_FLOAT);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($address)) {
            $errors[] = "Method name and address are required.";
        } else {
            if ($id) { // Update
                $stmt = $conn->prepare("UPDATE crypto_payment_methods SET name = ?, address = ?, network = ?, instructions = ?, charge_percentage = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("ssssdii", $name, $address, $network, $instructions, $charge_percentage, $is_active, $id);
                if ($stmt->execute()) {
                    $success = "Method updated successfully.";
                } else {
                    $errors[] = "Failed to update method.";
                }
            } else { // Insert
                $stmt = $conn->prepare("INSERT INTO crypto_payment_methods (name, address, network, instructions, charge_percentage, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdi", $name, $address, $network, $instructions, $charge_percentage, $is_active);
                if ($stmt->execute()) {
                    $success = "New method added successfully.";
                } else {
                    $errors[] = "Failed to add new method.";
                }
            }
            $stmt->close();
        }
    }

    // Handle Delete
    if (isset($_POST['delete_method'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM crypto_payment_methods WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Method deleted successfully.";
        } else {
            $errors[] = "Failed to delete method.";
        }
        $stmt->close();
    }
}

// Fetch all methods for display
$methods_result = $conn->query("SELECT * FROM crypto_payment_methods ORDER BY name ASC");

// Fetch a single method for editing if an ID is provided in the URL
$edit_method = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM crypto_payment_methods WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_method = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Cryptocurrency Payment Methods</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5><?php echo $edit_method ? 'Edit Method' : 'Add New Method'; ?></h5>
            </div>
            <div class="card-body">
                <form action="crypto-methods.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="id" value="<?php echo $edit_method['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Cryptocurrency Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($edit_method['name'] ?? ''); ?>" placeholder="e.g., Bitcoin" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Wallet Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($edit_method['address'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="network" class="form-label">Network</label>
                        <input type="text" class="form-control" id="network" name="network" value="<?php echo htmlspecialchars($edit_method['network'] ?? ''); ?>" placeholder="e.g., BTC, ERC20, TRC20">
                    </div>
                    <div class="mb-3">
                        <label for="instructions" class="form-label">Payment Instructions</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="3"><?php echo htmlspecialchars($edit_method['instructions'] ?? ''); ?></textarea>
                        <div class="form-text">Optional instructions for the user.</div>
                    </div>
                    <div class="mb-3">
                        <label for="charge_percentage" class="form-label">Deposit Charge (%)</label>
                        <input type="number" class="form-control" id="charge_percentage" name="charge_percentage" value="<?php echo htmlspecialchars($edit_method['charge_percentage'] ?? '0.00'); ?>" step="0.01">
                        <div class="form-text">A percentage fee to charge on deposits made with this method.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($edit_method['is_active']) && $edit_method['is_active'] == 1) || !$edit_method ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                    <button type="submit" name="save_method" class="btn btn-primary">Save Method</button>
                    <?php if ($edit_method): ?>
                        <a href="crypto-methods.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5>Existing Methods</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Network</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($method = $methods_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($method['name']); ?></td>
                            <td><?php echo htmlspecialchars($method['address']); ?></td>
                            <td><?php echo htmlspecialchars($method['network']); ?></td>
                            <td>
                                <?php if ($method['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?edit=<?php echo $method['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <form action="crypto-methods.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this method?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                                    <button type="submit" name="delete_method" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
