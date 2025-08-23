<?php
$page_title = 'VTU Product Management';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

// C.R.U.D. Logic
// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $service_type = trim($_POST['service_type']);
    $api_provider = trim($_POST['api_provider']);
    $network = trim($_POST['network']);
    $name = trim($_POST['name']);
    $api_product_id = trim($_POST['api_product_id']);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $user_discount_percentage = filter_input(INPUT_POST, 'user_discount_percentage', FILTER_VALIDATE_FLOAT);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($service_type) || empty($api_provider) || empty($name) || empty($api_product_id) || $amount === false) {
        $errors[] = "Please fill all required fields correctly.";
    } else {
        if ($product_id) { // Update
            $stmt = $conn->prepare("UPDATE vtu_products SET service_type=?, api_provider=?, network=?, name=?, api_product_id=?, amount=?, user_discount_percentage=?, is_active=? WHERE id=?");
            $stmt->bind_param("sssssddii", $service_type, $api_provider, $network, $name, $api_product_id, $amount, $user_discount_percentage, $is_active, $product_id);
        } else { // Create
            $stmt = $conn->prepare("INSERT INTO vtu_products (service_type, api_provider, network, name, api_product_id, amount, user_discount_percentage, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssddi", $service_type, $api_provider, $network, $name, $api_product_id, $amount, $user_discount_percentage, $is_active);
        }

        if ($stmt->execute()) {
            $success = "Product saved successfully.";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($product_id) {
        $stmt = $conn->prepare("DELETE FROM vtu_products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $success = "Product deleted successfully.";
        } else {
            $errors[] = "Failed to delete product.";
        }
        $stmt->close();
    }
}

// READ all products for display
$products = [];
$stmt = $conn->prepare("SELECT * FROM vtu_products ORDER BY service_type, network, name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
}

// Fetch services and APIs for dropdowns
$vtu_services = $conn->query("SELECT service_slug, service_name FROM vtu_services WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);
$vtu_apis = $conn->query("SELECT provider_name FROM vtu_apis WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal"><i class="fas fa-plus"></i> Add New Product</button>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All VTU Products</h3>
    </div>
    <div class="card-body">
        <p>Manage all individual products that users can purchase, such as specific data plans, cable TV packages, or exam pins.</p>
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
                        <th>Service</th>
                        <th>Product Name</th>
                        <th>Network</th>
                        <th>Provider</th>
                        <th>Price</th>
                        <th>Discount (%)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="8" class="text-center">No products found. Add one to get started.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $product['service_type']))); ?></td>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['network'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($product['api_provider']); ?></td>
                            <td><?php echo get_currency_symbol(); ?><?php echo number_format($product['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['user_discount_percentage']); ?>%</td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#productModal"
                                    data-id="<?php echo $product['id']; ?>"
                                    data-service_type="<?php echo $product['service_type']; ?>"
                                    data-api_provider="<?php echo $product['api_provider']; ?>"
                                    data-network="<?php echo $product['network']; ?>"
                                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                    data-api_product_id="<?php echo htmlspecialchars($product['api_product_id']); ?>"
                                    data-amount="<?php echo $product['amount']; ?>"
                                    data-user_discount_percentage="<?php echo $product['user_discount_percentage']; ?>"
                                    data-is_active="<?php echo $product['is_active']; ?>">
                                    Edit
                                </button>
                                <form action="vtu_products.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-sm btn-danger">Delete</button>
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

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="vtu_products.php" method="POST">
                <input type="hidden" name="id" id="product_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Add/Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="service_type" class="form-label">Service Type</label>
                            <select class="form-select" id="service_type" name="service_type" required>
                                <?php foreach($vtu_services as $service): ?>
                                    <option value="<?php echo $service['service_slug']; ?>"><?php echo htmlspecialchars($service['service_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="api_provider" class="form-label">API Provider</label>
                            <select class="form-select" id="api_provider" name="api_provider" required>
                                <?php foreach($vtu_apis as $api): ?>
                                    <option value="<?php echo $api['provider_name']; ?>"><?php echo htmlspecialchars($api['provider_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., MTN 1.5GB Data (30 Days)">
                    </div>
                     <div class="mb-3">
                        <label for="network" class="form-label">Network / Biller</label>
                        <input type="text" class="form-control" id="network" name="network" placeholder="e.g., MTN, DSTV, Ikeja-Electric">
                        <div class="form-text">Required for network-specific services like Airtime, Data, Cable.</div>
                    </div>
                    <div class="mb-3">
                        <label for="api_product_id" class="form-label">API Product/Variation ID</label>
                        <input type="text" class="form-control" id="api_product_id" name="api_product_id" required>
                        <div class="form-text">The specific ID or code used by the API provider (e.g., 'dstv-padi', 'mtn-1000').</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Price (<?php echo get_currency_symbol(); ?>)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                            <div class="form-text">The base cost of this product.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="user_discount_percentage" class="form-label">User Discount (%)</label>
                            <input type="number" step="0.01" class="form-control" id="user_discount_percentage" name="user_discount_percentage" value="0.00">
                             <div class="form-text">A discount to apply for users from the base price.</div>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Product is Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_product" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productModal = document.getElementById('productModal');
    productModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const modalTitle = productModal.querySelector('.modal-title');

        const id = button.getAttribute('data-id');

        // Get the form fields
        const idInput = productModal.querySelector('#product_id');
        const serviceTypeInput = productModal.querySelector('#service_type');
        const apiProviderInput = productModal.querySelector('#api_provider');
        const networkInput = productModal.querySelector('#network');
        const nameInput = productModal.querySelector('#name');
        const apiProductIdInput = productModal.querySelector('#api_product_id');
        const amountInput = productModal.querySelector('#amount');
        const discountInput = productModal.querySelector('#user_discount_percentage');
        const isActiveInput = productModal.querySelector('#is_active');

        if (id) { // If we are editing
            modalTitle.textContent = 'Edit Product';
            idInput.value = id;
            serviceTypeInput.value = button.getAttribute('data-service_type');
            apiProviderInput.value = button.getAttribute('data-api_provider');
            networkInput.value = button.getAttribute('data-network');
            nameInput.value = button.getAttribute('data-name');
            apiProductIdInput.value = button.getAttribute('data-api_product_id');
            amountInput.value = button.getAttribute('data-amount');
            discountInput.value = button.getAttribute('data-user_discount_percentage');
            isActiveInput.checked = button.getAttribute('data-is_active') == '1';
        } else { // If we are adding
            modalTitle.textContent = 'Add New Product';
            idInput.value = '';
            // Reset form to defaults
            serviceTypeInput.value = serviceTypeInput.options[0].value;
            apiProviderInput.value = apiProviderInput.options[0].value;
            networkInput.value = '';
            nameInput.value = '';
            apiProductIdInput.value = '';
            amountInput.value = '';
            discountInput.value = '0.00';
            isActiveInput.checked = true;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
