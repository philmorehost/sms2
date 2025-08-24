<?php
$page_title = 'Airtime Service Settings';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

$errors = [];
$success = '';

$networks = ['MTN', 'GLO', 'Airtel', '9mobile'];
$providers = ['VTPass', 'ClubKonnect'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_airtime_settings'])) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    foreach ($networks as $network) {
        $provider_key = 'airtime_provider_' . strtolower($network);
        $discount_key = 'airtime_discount_' . strtolower($network);

        $provider_value = $_POST[$provider_key] ?? 'VTPass';
        $discount_value = filter_input(INPUT_POST, $discount_key, FILTER_VALIDATE_FLOAT);

        // Save provider setting
        $stmt->bind_param("ss", $provider_key, $provider_value);
        $stmt->execute();

        // Save discount setting
        $stmt->bind_param("ss", $discount_key, $discount_value);
        $stmt->execute();
    }
    $stmt->close();
    $success = "Airtime settings have been updated successfully.";

    // Re-fetch settings after update
    $settings_result = $conn->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $GLOBALS['app_settings'] = $settings;
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Airtime Provider & Discount Configuration</h3>
    </div>
    <div class="card-body">
        <p>For each mobile network, choose which API provider to use for processing airtime top-ups and set the discount percentage for users.</p>
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
        <form action="vtu_airtime_settings.php" method="POST">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Mobile Network</th>
                            <th>API Provider</th>
                            <th>User Discount (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($networks as $network):
                            $provider_key = 'airtime_provider_' . strtolower($network);
                            $discount_key = 'airtime_discount_' . strtolower($network);
                            $current_provider = $settings[$provider_key] ?? 'VTPass';
                            $current_discount = $settings[$discount_key] ?? '0.00';
                        ?>
                        <tr>
                            <td><strong><?php echo $network; ?></strong></td>
                            <td>
                                <select class="form-select" name="<?php echo $provider_key; ?>">
                                    <?php foreach ($providers as $provider): ?>
                                        <option value="<?php echo $provider; ?>" <?php if($current_provider == $provider) echo 'selected'; ?>><?php echo $provider; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control" name="<?php echo $discount_key; ?>" value="<?php echo htmlspecialchars($current_discount); ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end">
                <button type="submit" name="save_airtime_settings" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
