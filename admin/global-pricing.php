<?php
$page_title = 'Global SMS Pricing';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    // Handle Add/Edit
    if (isset($_POST['save_price'])) {
        $id = $_POST['id'] ?? null;
        $country = trim($_POST['country']);
        $operator = trim($_POST['operator']);
        $mcc = trim($_POST['mcc']);
        $mnc = trim($_POST['mnc']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

        if (empty($country) || $price === false) {
            $errors[] = "Country and a valid price are required.";
        } else {
            if ($id) { // Update
                $stmt = $conn->prepare("UPDATE global_sms_pricing SET country = ?, operator = ?, mcc = ?, mnc = ?, price = ? WHERE id = ?");
                $stmt->bind_param("ssssdi", $country, $operator, $mcc, $mnc, $price, $id);
            } else { // Insert
                $stmt = $conn->prepare("INSERT INTO global_sms_pricing (country, operator, mcc, mnc, price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssd", $country, $operator, $mcc, $mnc, $price);
            }
            if ($stmt->execute()) {
                $success = "Pricing record saved successfully.";
            } else {
                $errors[] = "Failed to save pricing record.";
            }
            $stmt->close();
        }
    }

    // Handle Delete
    if (isset($_POST['delete_price'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM global_sms_pricing WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Pricing record deleted successfully.";
    }

    // Handle Run Seeder
    if (isset($_POST['run_seeder'])) {
        $result = seed_global_pricing_data($conn);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }

    // Handle Increase Prices
    if (isset($_POST['increase_prices'])) {
        $increase_country = $_POST['increase_country'];
        $increase_percentage = (float)$_POST['increase_percentage'];

        $result = increase_global_prices($increase_country, $increase_percentage, $conn);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Search and Filter
$search_query = $_GET['q'] ?? '';
$filter_country = $_GET['country'] ?? '';

$sql = "SELECT * FROM global_sms_pricing WHERE 1=1";
$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " AND (country LIKE ? OR operator LIKE ?)";
    $search_param = "%{$search_query}%";
    array_push($params, $search_param, $search_param);
    $types .= 'ss';
}
if (!empty($filter_country)) {
    $sql .= " AND country = ?";
    $params[] = $filter_country;
    $types .= 's';
}
$sql .= " ORDER BY country, operator ASC";

$stmt_prices = $conn->prepare($sql);
if (!empty($params)) {
    $stmt_prices->bind_param($types, ...$params);
}
$stmt_prices->execute();
$prices_result = $stmt_prices->get_result();

// Get distinct countries for the filter dropdown
$countries_result = $conn->query("SELECT DISTINCT country FROM global_sms_pricing ORDER BY country ASC");

// Fetch a single record for editing
$edit_price = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM global_sms_pricing WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_price = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Global SMS Pricing</h1>
    <a href="?edit=new" class="btn btn-primary">Add New Price</a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Bulk Price Management</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Run Price Seeder</h6>
                <p>This will populate the pricing table with the default list of prices. This can be run multiple times; it will not create duplicate entries if the country and operator already exist.</p>
                <form action="global-pricing.php" method="POST" onsubmit="return confirm('Are you sure you want to run the seeder? This may take a moment.');">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" name="run_seeder" class="btn btn-secondary">Run Seeder</button>
                </form>
            </div>
            <div class="col-md-6">
                <h6>Increase Prices by Percentage</h6>
                <form action="global-pricing.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <select name="increase_country" class="form-select">
                            <option value="all">All Countries</option>
                            <?php
                            $countries_result_for_increase = $conn->query("SELECT DISTINCT country FROM global_sms_pricing ORDER BY country ASC");
                            while($country = $countries_result_for_increase->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($country['country']); ?>"><?php echo htmlspecialchars($country['country']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Percentage Increase (%)</label>
                        <input type="number" step="0.01" name="increase_percentage" class="form-control" required>
                    </div>
                    <button type="submit" name="increase_prices" class="btn btn-warning">Apply Increase</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div><?php endif; ?>

<?php if ($edit_price || isset($_GET['edit']) && $_GET['edit'] === 'new'): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5><?php echo $edit_price ? 'Edit Price' : 'Add New Price'; ?></h5>
    </div>
    <div class="card-body">
        <form action="global-pricing.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo $edit_price['id'] ?? ''; ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($edit_price['country'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Operator</label>
                    <input type="text" class="form-control" name="operator" value="<?php echo htmlspecialchars($edit_price['operator'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">MCC</label>
                    <input type="text" class="form-control" name="mcc" value="<?php echo htmlspecialchars($edit_price['mcc'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">MNC</label>
                    <input type="text" class="form-control" name="mnc" value="<?php echo htmlspecialchars($edit_price['mnc'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.0001" class="form-control" name="price" value="<?php echo htmlspecialchars($edit_price['price'] ?? ''); ?>" required>
                </div>
            </div>
            <button type="submit" name="save_price" class="btn btn-primary">Save Price</button>
            <a href="global-pricing.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Manage Prices</h5>
        <form action="global-pricing.php" method="GET" class="mt-3">
            <div class="row">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control" placeholder="Search country or operator..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-5">
                    <select name="country" class="form-select">
                        <option value="">All Countries</option>
                        <?php while($country = $countries_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($country['country']); ?>" <?php if($filter_country == $country['country']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($country['country']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Operator</th>
                        <th>MCC</th>
                        <th>MNC</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($price = $prices_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($price['country']); ?></td>
                        <td><?php echo htmlspecialchars($price['operator']); ?></td>
                        <td><?php echo htmlspecialchars($price['mcc']); ?></td>
                        <td><?php echo htmlspecialchars($price['mnc']); ?></td>
                        <td><?php echo number_format($price['price'], 5); ?></td>
                        <td>
                            <a href="?edit=<?php echo $price['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="global-pricing.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="id" value="<?php echo $price['id']; ?>">
                                <button type="submit" name="delete_price" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
