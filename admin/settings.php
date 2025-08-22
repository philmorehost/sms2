<?php
$page_title = 'Platform Settings';
require_once __DIR__ . '/../app/bootstrap.php';

// Determine the active tab
// -- MODIFIED: Removed old rate tabs and added a new 'service_pricing' tab
$tabs = ['general', 'pricing', 'service_pricing', 'payment_gateways', 'email', 'api', 'cron'];
$active_tab = $_GET['tab'] ?? 'general';
if (!in_array($active_tab, $tabs)) {
    $active_tab = 'general';
}

$errors = [];
$success = '';

// Handle form submission for Callback URL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_callback_url'])) {
    $log_file = '/tmp/settings.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] --- Saving Callback URL ---\n", FILE_APPEND);
    file_put_contents($log_file, "Received POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

    $callback_url = trim($_POST['callback_url']);
    if (!empty($callback_url) && filter_var($callback_url, FILTER_VALIDATE_URL)) {
        $result = set_callback_url_api($callback_url);
        if ($result['success']) {
            $success = $result['message'];
            $key = 'callback_url';
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->bind_param("ss", $key, $callback_url);
            if ($stmt->execute()) {
                file_put_contents($log_file, "SUCCESS: Saved callback_url='{$callback_url}' to database.\n\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, "FAILURE: Database error saving callback_url: " . $stmt->error . "\n\n", FILE_APPEND);
            }
            $stmt->close();
            $settings_result = $conn->query("SELECT * FROM settings");
            $settings = [];
            while ($row = $settings_result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } else {
            $errors[] = $result['message'];
            file_put_contents($log_file, "FAILURE: API call failed: " . $result['message'] . "\n\n", FILE_APPEND);
        }
    } else {
        $errors[] = "Please enter a valid URL for the callback.";
        file_put_contents($log_file, "FAILURE: Invalid URL provided: {$callback_url}\n\n", FILE_APPEND);
    }
}

// Handle form submission for General Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_general_settings'])) {
    $log_file = '/tmp/settings.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] --- Saving General Settings ---\n", FILE_APPEND);
    file_put_contents($log_file, "Received POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

    $settings_to_update = [
        'site_name' => $_POST['site_name'],
        'admin_email' => $_POST['admin_email'],
        'site_currency' => $_POST['site_currency'],
        'site_language' => $_POST['site_language'],
        'site_timezone' => $_POST['site_timezone'],
        'vat_percentage' => $_POST['vat_percentage'],
        'referral_bonus_percentage' => $_POST['referral_bonus_percentage']
    ];

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings_to_update as $key => $value) {
        $stmt->bind_param("ss", $key, $value);
        if ($stmt->execute()) {
            file_put_contents($log_file, "SUCCESS: Saved {$key}='{$value}' to database.\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, "FAILURE: Database error saving {$key}: " . $stmt->error . "\n", FILE_APPEND);
        }
    }
    $stmt->close();
    $success = "General settings have been updated successfully.";
    file_put_contents($log_file, "[{$timestamp}] --- Finished saving general settings ---\n\n", FILE_APPEND);
}

// Handle form submission for Logo & Favicon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_site_images'])) {
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_logo_types = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_favicon_types = ['image/vnd.microsoft.icon', 'image/x-icon', 'image/png'];

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    // Handle Logo Upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        if (in_array($_FILES['site_logo']['type'], $allowed_logo_types)) {
            $logo_ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
            $logo_filename = 'logo_' . time() . '.' . $logo_ext;
            $logo_path = 'uploads/' . $logo_filename;
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_dir . $logo_filename)) {
                $key = 'site_logo';
                $stmt->bind_param("ss", $key, $logo_path);
                $stmt->execute();
                $success = 'Logo uploaded successfully.';
            } else {
                $errors[] = 'Failed to move uploaded logo.';
            }
        } else {
            $errors[] = 'Invalid file type for logo. Please use JPG, PNG, or GIF.';
        }
    }

    // Handle Favicon Upload
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] == 0) {
        if (in_array($_FILES['site_favicon']['type'], $allowed_favicon_types)) {
            $favicon_ext = pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION);
            $favicon_filename = 'favicon_' . time() . '.' . $favicon_ext;
            $favicon_path = 'uploads/' . $favicon_filename;
            if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $upload_dir . $favicon_filename)) {
                $key = 'site_favicon';
                $stmt->bind_param("ss", $key, $favicon_path);
                $stmt->execute();
                $success = ($success ? $success . ' ' : '') . 'Favicon uploaded successfully.';
            } else {
                $errors[] = 'Failed to move uploaded favicon.';
            }
        } else {
            $errors[] = 'Invalid file type for favicon. Please use ICO or PNG.';
        }
    }

    // Handle Landing Page Banner Upload
    if (isset($_FILES['landing_page_banner']) && $_FILES['landing_page_banner']['error'] == 0) {
        if (in_array($_FILES['landing_page_banner']['type'], $allowed_logo_types)) { // Reuse logo types
            $banner_ext = pathinfo($_FILES['landing_page_banner']['name'], PATHINFO_EXTENSION);
            $banner_filename = 'banner_' . time() . '.' . $banner_ext;
            $banner_path = 'uploads/' . $banner_filename;
            if (move_uploaded_file($_FILES['landing_page_banner']['tmp_name'], $upload_dir . $banner_filename)) {
                $key = 'landing_page_banner';
                $stmt->bind_param("ss", $key, $banner_path);
                $stmt->execute();
                $success = ($success ? $success . ' ' : '') . 'Landing page banner uploaded successfully.';
            } else {
                $errors[] = 'Failed to move uploaded banner.';
            }
        } else {
            $errors[] = 'Invalid file type for banner. Please use JPG, PNG, or GIF.';
        }
    }
    $stmt->close();
    // Re-fetch settings to show new images
    $settings_result = $conn->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Handle form submission for SMTP Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_smtp_settings'])) {
    $settings_to_update = [
        'smtp_host' => $_POST['smtp_host'],
        'smtp_port' => $_POST['smtp_port'],
        'smtp_user' => $_POST['smtp_user'],
        'smtp_encryption' => $_POST['smtp_encryption'],
        'smtp_from_email' => $_POST['smtp_from_email'],
        'smtp_from_name' => $_POST['smtp_from_name']
    ];

    // Only update password if a new one is entered
    if (!empty($_POST['smtp_pass'])) {
        $settings_to_update['smtp_pass'] = $_POST['smtp_pass'];
    }

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings_to_update as $key => $value) {
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    $stmt->close();
    $success = "SMTP settings have been updated successfully.";
}

// Handle form submission for API Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_api_settings'])) {
    $settings_to_update = [
        'kudisms_api_key_sms' => $_POST['kudisms_api_key_sms'],
        'kudisms_api_key_senderid' => $_POST['kudisms_api_key_senderid'],
        'kudisms_api_key_tts' => $_POST['kudisms_api_key_tts'],
        'otp_api_key' => $_POST['otp_api_key'],
        'whatsapp_api_token' => $_POST['whatsapp_api_token'],
    ];

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings_to_update as $key => $value) {
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    $stmt->close();
    $success = "API settings have been updated successfully.";
}

// Handle form submission for Payment Gateway Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_payment_settings'])) {
    $settings_to_update = [
        'paystack_secret_key' => $_POST['paystack_secret_key'],
        'paystack_public_key' => $_POST['paystack_public_key'],
        'manual_bank_name' => $_POST['manual_bank_name'],
        'manual_account_name' => $_POST['manual_account_name'],
        'manual_account_number' => $_POST['manual_account_number'],
        'manual_payment_instructions' => $_POST['manual_payment_instructions'],
    ];

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings_to_update as $key => $value) {
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    $stmt->close();
    $success = "Payment gateway settings have been updated successfully.";
}


// Security settings are now handled in banning.php


// Handle Pricing Plan Management
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_plan'])) {
    $plan_name = trim($_POST['plan_name']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $credits = filter_input(INPUT_POST, 'credits', FILTER_VALIDATE_INT);
    if (!empty($plan_name) && $price > 0 && $credits > 0) {
        $stmt = $conn->prepare("INSERT INTO pricing_plans (plan_name, price, credits) VALUES (?, ?, ?)");
        $stmt->bind_param("sdi", $plan_name, $price, $credits);
        $stmt->execute();
        $success = "New pricing plan created successfully.";
    } else {
        $errors[] = "Invalid data for pricing plan.";
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_plan'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $stmt = $conn->prepare("DELETE FROM pricing_plans WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $success = "Pricing plan deleted successfully.";
}

// Fetch all pricing plans
$pricing_plans_result = $conn->query("SELECT * FROM pricing_plans ORDER BY price ASC");


// Handle form submission for Service Pricing Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_service_pricing'])) {
    $settings_to_update = [
        'price_sms_promo'   => $_POST['price_sms_promo'],
        'price_sms_corp'    => $_POST['price_sms_corp'],
        'price_voice_tts'   => $_POST['price_voice_tts'],
        'price_otp'         => $_POST['price_otp'],
        'price_whatsapp'    => $_POST['price_whatsapp'],
        'price_voice_audio' => $_POST['price_voice_audio'],
    ];

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings_to_update as $key => $value) {
        // Sanitize to make sure it's a valid float
        $sanitized_value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $stmt->bind_param("ss", $key, $sanitized_value);
        $stmt->execute();
    }
    $stmt->close();
    $success = "Service pricing has been updated successfully.";
}


// Fetch all settings from the database AFTER all potential updates.
$settings_result = $conn->query("SELECT * FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}


include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Settings</h1>
</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs nav-tabs-responsive">
            <li class="nav-item">
                <a class="nav-link <?php if($active_tab == 'general') echo 'active'; ?>" href="?tab=general">General</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($active_tab == 'pricing') echo 'active'; ?>" href="?tab=pricing">Pricing Plans</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($active_tab == 'service_pricing') echo 'active'; ?>" href="?tab=service_pricing">Service Pricing</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($active_tab == 'payment_gateways') echo 'active'; ?>" href="?tab=payment_gateways">Payment Gateways</a>
            </li>
             <li class="nav-item">
                <a class="nav-link <?php if($active_tab == 'email') echo 'active'; ?>" href="?tab=email">Email</a>
            </li>
             <li class="nav-item">
                <a class="nav-link <?php if($active_tab == 'api') echo 'active'; ?>" href="?tab=api">Service APIs</a>
            </li>
             <li class="nav-item">
                <a class="nav-link <?php if($active_tab == 'cron') echo 'active'; ?>" href="?tab=cron">Cron Jobs</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <!-- General Settings Tab -->
            <div class="tab-pane fade <?php if($active_tab == 'general') echo 'show active'; ?>" id="general">
                <h4>General Settings</h4>
                <p>Manage basic site settings like the site name and administrator email address.</p>
                <hr>
                <?php if ($success && $active_tab == 'general'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form action="settings.php?tab=general" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Admin Email Address</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>">
                        <div class="form-text">This email is used for receiving notifications (e.g., new support tickets).</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_currency" class="form-label">Site Currency</label>
                            <input type="text" class="form-control" id="site_currency" name="site_currency" value="<?php echo htmlspecialchars($settings['site_currency'] ?? 'USD'); ?>">
                            <div class="form-text">e.g., USD, NGN, EUR</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="site_language" class="form-label">Default Language</label>
                            <input type="text" class="form-control" id="site_language" name="site_language" value="<?php echo htmlspecialchars($settings['site_language'] ?? 'en'); ?>">
                             <div class="form-text">e.g., en, fr</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="site_timezone" class="form-label">Site Timezone</label>
                        <select class="form-select" id="site_timezone" name="site_timezone">
                            <?php
                                $current_tz = $settings['site_timezone'] ?? 'UTC';
                                $timezones = DateTimeZone::listIdentifiers();
                                foreach ($timezones as $timezone) {
                                    echo '<option value="' . htmlspecialchars($timezone) . '"' . ($current_tz == $timezone ? ' selected' : '') . '>' . htmlspecialchars($timezone) . '</option>';
                                }
                            ?>
                        </select>
                        <div class="form-text">This timezone will be used for all date and time displays and for scheduling tasks.</div>
                    </div>
                    <div class="mb-3">
                        <label for="vat_percentage" class="form-label">VAT Percentage (%)</label>
                        <input type="number" class="form-control" id="vat_percentage" name="vat_percentage" value="<?php echo htmlspecialchars($settings['vat_percentage'] ?? '0'); ?>" min="0" step="0.01">
                        <div class="form-text">Enter a percentage to charge as VAT on payments. E.g., 5 for 5%. Set to 0 to disable.</div>
                    </div>
                    <div class="mb-3">
                        <label for="referral_bonus_percentage" class="form-label">Referral Bonus Percentage (%)</label>
                        <input type="number" class="form-control" id="referral_bonus_percentage" name="referral_bonus_percentage" value="<?php echo htmlspecialchars($settings['referral_bonus_percentage'] ?? '10'); ?>" min="0" step="0.01">
                        <div class="form-text">The commission percentage to award to a referrer on their referral's first deposit. Set to 0 to disable.</div>
                    </div>
                    <hr>
                    <h4>Site Logo & Favicon</h4>
                    <p>Upload a logo and favicon for your site branding. Uploading a new file will replace the old one.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_logo" class="form-label">Site Logo</label>
                            <input type="file" class="form-control" id="site_logo" name="site_logo" accept="image/png, image/jpeg, image/gif">
                            <div class="form-text">Recommended size: 200x50 pixels. Allowed types: PNG, JPG, GIF.</div>
                            <?php if (!empty($settings['site_logo'])): ?>
                                <div class="mt-2">
                                    <strong>Current Logo:</strong><br>
                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_logo']); ?>" alt="Current Logo" style="max-height: 50px; background-color: #f0f0f0; padding: 5px; border-radius: 4px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="site_favicon" class="form-label">Site Favicon</label>
                            <input type="file" class="form-control" id="site_favicon" name="site_favicon" accept="image/x-icon, image/png">
                            <div class="form-text">Recommended size: 32x32 pixels. Allowed types: ICO, PNG.</div>
                             <?php if (!empty($settings['site_favicon'])): ?>
                                <div class="mt-2">
                                    <strong>Current Favicon:</strong><br>
                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($settings['site_favicon']); ?>" alt="Current Favicon" style="max-height: 32px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="landing_page_banner" class="form-label">Landing Page Banner</label>
                        <input type="file" class="form-control" id="landing_page_banner" name="landing_page_banner" accept="image/png, image/jpeg, image/gif">
                        <div class="form-text">Recommended size: 1920x1080 pixels. Allowed types: PNG, JPG, GIF.</div>
                        <?php if (!empty($settings['landing_page_banner'])): ?>
                            <div class="mt-2">
                                <strong>Current Banner:</strong><br>
                                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($settings['landing_page_banner']); ?>" alt="Current Banner" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="save_site_images" class="btn btn-info">Upload Images</button>
                    <hr>
                    <button type="submit" name="save_general_settings" class="btn btn-primary">Save General Settings</button>
                </form>
            </div>


            <!-- Pricing Plans Tab -->
            <div class="tab-pane fade <?php if($active_tab == 'pricing') echo 'show active'; ?>" id="pricing">
                <h4>Pricing Plans</h4>
                <p>Create and manage deposit packages for users to choose from on the 'Add Funds' page.</p>
                <hr>
                <?php if ($success && $active_tab == 'pricing'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (!empty($errors) && $active_tab == 'pricing'): ?>
                    <div class="alert alert-danger"><?php foreach($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-5">
                        <h5>Add New Plan</h5>
                        <form action="settings.php?tab=pricing" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Plan Name</label>
                                <input type="text" class="form-control" name="plan_name" placeholder="e.g., Starter Pack" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price (<?php echo get_currency_symbol(); ?>)</label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Credits / Units</label>
                                <input type="number" class="form-control" name="credits" required>
                            </div>
                            <button type="submit" name="add_plan" class="btn btn-primary">Add Plan</button>
                        </form>
                    </div>
                    <div class="col-md-7">
                        <h5>Existing Plans</h5>
                        <table class="table table-striped">
                            <thead><tr><th>Name</th><th>Price</th><th>Credits</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php while($plan = $pricing_plans_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                                    <td><?php echo get_currency_symbol(); ?><?php echo number_format($plan['price'], 2); ?></td>
                                    <td><?php echo number_format($plan['credits']); ?></td>
                                    <td>
                                        <form action="settings.php?tab=pricing" method="POST" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                                            <button type="submit" name="delete_plan" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Service Pricing Tab -->
            <div class="tab-pane fade <?php if($active_tab == 'service_pricing') echo 'show active'; ?>" id="service_pricing">
                <h4>Service Pricing</h4>
                <p>Set the default price per unit for each service. This will be the cost charged to the user's wallet for a single recipient or action.</p>
                <hr>
                <?php if ($success && $active_tab == 'service_pricing'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form action="settings.php?tab=service_pricing" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price_sms_promo" class="form-label">Promotional SMS Price</label>
                            <input type="number" step="0.001" class="form-control" id="price_sms_promo" name="price_sms_promo" value="<?php echo htmlspecialchars($settings['price_sms_promo'] ?? '10'); ?>">
                            <div class="form-text">Cost per SMS recipient on the Promotional route.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="price_sms_corp" class="form-label">Corporate SMS Price</label>
                            <input type="number" step="0.001" class="form-control" id="price_sms_corp" name="price_sms_corp" value="<?php echo htmlspecialchars($settings['price_sms_corp'] ?? '20'); ?>">
                            <div class="form-text">Cost per SMS recipient on the Corporate route.</div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price_voice_tts" class="form-label">Text-to-Speech Price</label>
                            <input type="number" step="0.001" class="form-control" id="price_voice_tts" name="price_voice_tts" value="<?php echo htmlspecialchars($settings['price_voice_tts'] ?? '30'); ?>">
                            <div class="form-text">Cost per Voice call recipient.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="price_otp" class="form-label">OTP Message Price</label>
                            <input type="number" step="0.001" class="form-control" id="price_otp" name="price_otp" value="<?php echo htmlspecialchars($settings['price_otp'] ?? '5'); ?>">
                            <div class="form-text">Cost per OTP sent.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="price_voice_audio" class="form-label">Voice from File Price</label>
                            <input type="number" step="0.001" class="form-control" id="price_voice_audio" name="price_voice_audio" value="<?php echo htmlspecialchars($settings['price_voice_audio'] ?? '35'); ?>">
                            <div class="form-text">Cost per Voice call recipient from an audio file.</div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price_whatsapp" class="form-label">WhatsApp Message Price</label>
                            <input type="number" step="0.001" class="form-control" id="price_whatsapp" name="price_whatsapp" value="<?php echo htmlspecialchars($settings['price_whatsapp'] ?? '25'); ?>">
                            <div class="form-text">Cost per WhatsApp message sent.</div>
                        </div>
                    </div>
                    <button type="submit" name="save_service_pricing" class="btn btn-primary">Save Pricing</button>
                </form>
            </div>

            <!-- Payment Gateways Tab -->
            <div class="tab-pane fade <?php if($active_tab == 'payment_gateways') echo 'show active'; ?>" id="payment_gateways">
                <h4>Payment Gateway Settings</h4>
                <p>Manage settings for automatic and manual payment methods.</p>
                <hr>
                 <?php if ($success && $active_tab == 'payment_gateways'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form action="settings.php?tab=payment_gateways" method="POST">
                    <h5 class="mt-2">Paystack API Keys</h5>
                    <p class="text-muted">These keys are required for processing online payments via Paystack.</p>
                    <div class="mb-3">
                        <label for="paystack_public_key" class="form-label">Paystack Public Key</label>
                        <input type="text" class="form-control" id="paystack_public_key" name="paystack_public_key" value="<?php echo htmlspecialchars($settings['paystack_public_key'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="paystack_secret_key" class="form-label">Paystack Secret Key</label>
                        <input type="password" class="form-control" id="paystack_secret_key" name="paystack_secret_key" value="<?php echo htmlspecialchars($settings['paystack_secret_key'] ?? ''); ?>">
                    </div>
                    <hr>
                    <h5 class="mt-4">Manual Bank Deposit</h5>
                    <p class="text-muted">Display these bank details to users for manual payments. Enable or disable this method here.</p>
                     <div class="mb-3">
                        <label for="manual_bank_name" class="form-label">Bank Name</label>
                        <input type="text" class="form-control" id="manual_bank_name" name="manual_bank_name" value="<?php echo htmlspecialchars($settings['manual_bank_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="manual_account_name" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="manual_account_name" name="manual_account_name" value="<?php echo htmlspecialchars($settings['manual_account_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="manual_account_number" class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="manual_account_number" name="manual_account_number" value="<?php echo htmlspecialchars($settings['manual_account_number'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="manual_payment_instructions" class="form-label">Payment Instructions</label>
                        <textarea class="form-control" id="manual_payment_instructions" name="manual_payment_instructions" rows="3"><?php echo htmlspecialchars($settings['manual_payment_instructions'] ?? ''); ?></textarea>
                        <div class="form-text">e.g., "Use your username as payment reference."</div>
                    </div>
                    <button type="submit" name="save_payment_settings" class="btn btn-primary">Save Payment Settings</button>
                </form>
            </div>

            <!-- Email Settings Tab -->
            <div class="tab-pane fade <?php if($active_tab == 'email') echo 'show active'; ?>" id="email">
                <h4>SMTP Settings</h4>
                <p>Configure the external SMTP server for sending all platform emails.</p>
                <hr>
                <?php if ($success && $active_tab == 'email'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form action="settings.php?tab=email" method="POST">
                    <?php if ($success && $active_tab == 'email'): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_user" class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_pass" class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" placeholder="Leave blank to keep existing password">
                            <div class="form-text">For security, your password is not displayed. Enter a new password only if you wish to change it.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_encryption" class="form-label">Encryption</label>
                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                            <option value="tls" <?php if (($settings['smtp_encryption'] ?? '') == 'tls') echo 'selected'; ?>>TLS</option>
                            <option value="ssl" <?php if (($settings['smtp_encryption'] ?? '') == 'ssl') echo 'selected'; ?>>SSL</option>
                            <option value="none" <?php if (($settings['smtp_encryption'] ?? '') == 'none') echo 'selected'; ?>>None</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_from_email" class="form-label">From Email</label>
                            <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" name="save_smtp_settings" class="btn btn-primary">Save SMTP Settings</button>
                    <button type="button" id="test-smtp-btn" class="btn btn-secondary">Send Test Email</button>
                </form>
            </div>

            <!-- API Keys Tab -->
            <div class="tab-pane fade <?php if($active_tab == 'api') echo 'show active'; ?>" id="api">
                <h4>Service API Settings</h4>
                <p>Manage third-party API keys for services like SMS, Voice, and WhatsApp.</p>
                <hr>
                 <?php if ($success && $active_tab == 'api'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form action="settings.php?tab=api" method="POST">
                    <h5 class="mt-4">SMS & Voice Gateway API</h5>
                    <p class="text-muted">The API for the external service that sends SMS and Voice messages.</p>
                    <div class="mb-3">
                        <label for="kudisms_api_key_sms" class="form-label">SMS API Key</label>
                        <input type="password" class="form-control" id="kudisms_api_key_sms" name="kudisms_api_key_sms" value="<?php echo htmlspecialchars($settings['kudisms_api_key_sms'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="kudisms_api_key_tts" class="form-label">Text-to-Speech API Key</label>
                        <input type="password" class="form-control" id="kudisms_api_key_tts" name="kudisms_api_key_tts" value="<?php echo htmlspecialchars($settings['kudisms_api_key_tts'] ?? ''); ?>">
                    </div>
                    <hr>
                    <h5 class="mt-4">Sender ID API</h5>
                     <p class="text-muted">The API key for submitting and checking promotional Sender IDs.</p>
                    <div class="mb-3">
                        <label for="kudisms_api_key_senderid" class="form-label">Sender ID API Key</label>
                        <input type="password" class="form-control" id="kudisms_api_key_senderid" name="kudisms_api_key_senderid" value="<?php echo htmlspecialchars($settings['kudisms_api_key_senderid'] ?? ''); ?>">
                    </div>
                    <hr>
                    <h5 class="mt-4">OTP Gateway API</h5>
                    <div class="mb-3">
                        <label for="otp_api_key" class="form-label">OTP API Key</label>
                        <input type="password" class="form-control" id="otp_api_key" name="otp_api_key" value="<?php echo htmlspecialchars($settings['otp_api_key'] ?? ''); ?>">
                    </div>
                    <hr>
                    <h5 class="mt-4">WhatsApp Gateway API</h5>
                    <div class="mb-3">
                        <label for="whatsapp_api_token" class="form-label">WhatsApp API Token</label>
                        <input type="password" class="form-control" id="whatsapp_api_token" name="whatsapp_api_token" value="<?php echo htmlspecialchars($settings['whatsapp_api_token'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="save_api_settings" class="btn btn-primary">Save API Settings</button>
                </form>
                <hr>
                <h5 class="mt-4">Delivery Report Callback URL</h5>
                <p class="text-muted">Set the URL where the gateway will send delivery status updates (webhooks).</p>
                <form action="settings.php?tab=api" method="POST">
                    <div class="mb-3">
                        <label for="callback_url" class="form-label">Callback URL</label>
                        <input type="url" class="form-control" id="callback_url" name="callback_url" value="<?php echo htmlspecialchars($settings['callback_url'] ?? ''); ?>" placeholder="https://yourdomain.com/api/webhook-dlr.php">
                        <div class="form-text">Your callback URL should point to: <strong><?php echo SITE_URL . '/api/webhook-dlr.php'; ?></strong></div>
                    </div>
                    <button type="submit" name="save_callback_url" class="btn btn-primary">Set Callback URL</button>
                </form>
            </div>

            <!-- Cron Jobs Tab -->
            <div class="tab-pane fade <?php if($active_tab == 'cron') echo 'show active'; ?>" id="cron">
                <h4>Cron Job Setup</h4>
                <p>Cron jobs are scheduled tasks that the system runs automatically in the background. They are essential for features like checking sender ID status and cleaning up old data.</p>
                <hr>
                <h5>Required Cron Job Commands</h5>
                <p>You need to set up the following cron jobs in your hosting control panel (e.g., cPanel).</p>

                <div class="mb-3">
                    <label class="form-label">Check Pending Sender ID Status (Recommended: Every 5 minutes)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo 'wget -q -O - ' . SITE_URL . '/cron/check_sender_id_status.php'; ?>" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button">Copy</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Clean Up Unverified Users (Recommended: Once daily)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo 'wget -q -O - ' . SITE_URL . '/cron/cleanup_unverified_users.php'; ?>" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button">Copy</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Process Scheduled Messages (Recommended: Every minute)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo 'wget -q -O - ' . SITE_URL . '/cron/process_scheduled_tasks.php'; ?>" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button">Copy</button>
                    </div>
                </div>

                <hr>
                <h5>How to Set Up Cron Jobs in cPanel</h5>
                <ol>
                    <li>Log in to your cPanel account.</li>
                    <li>In the "Advanced" section, click on "Cron Jobs".</li>
                    <li>Under "Common Settings", you can select a predefined schedule, like "Once Per Five Minutes".</li>
                    <li>In the "Command" field, paste one of the commands from above.</li>
                    <li>Click "Add New Cron Job".</li>
                    <li>Repeat the process for the other command with its recommended schedule.</li>
                </ol>
                <p class="text-muted small">Note: The `wget` command is a common way to run cron jobs for web scripts and should be available on most servers. If it's not, you may need to use a different command format like `php <?php echo realpath(__DIR__ . '/../cron/check_sender_id_status.php'); ?>`. Consult your hosting provider for the correct format.</p>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 1500);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        });
    });

    document.getElementById('test-smtp-btn').addEventListener('click', function() {
        this.innerHTML = 'Sending...';
        this.disabled = true;

        fetch('ajax/test_smtp.php', {
            method: 'POST',
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test email sent successfully! Please check the admin inbox.');
            } else {
                alert('Failed to send test email. Reason: ' + data.message);
            }
            this.innerHTML = 'Send Test Email';
            this.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while trying to send the test email.');
            this.innerHTML = 'Send Test Email';
            this.disabled = false;
        });
    });
});
</script>
