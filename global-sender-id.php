<?php
$page_title = 'Register Global Sender ID';
include 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    // Extracting all form data
    $messaging_provider = $_POST['messaging_provider'] ?? null;
    $business_name = $_POST['business_name'] ?? null;
    $business_address = $_POST['business_address'] ?? null;
    $business_city_state = $_POST['business_city_state'] ?? null;
    $business_zip = $_POST['business_zip'] ?? null;
    $contact_first_name = $_POST['contact_first_name'] ?? null;
    $contact_last_name = $_POST['contact_last_name'] ?? null;
    $contact_email = $_POST['contact_email'] ?? null;
    $contact_phone = $_POST['contact_phone'] ?? null;
    $tfn = $_POST['tfn'] ?? null;
    $estimated_monthly_volume = $_POST['estimated_monthly_volume'] ?? null;
    $use_case_summary = $_POST['use_case_summary'] ?? null;
    $corporate_website = $_POST['corporate_website'] ?? null;
    $example_messages = $_POST['example_messages'] ?? null;
    $brand_description = $_POST['brand_description'] ?? null;
    $help_mt_response = $_POST['help_mt_response'] ?? null;
    $opt_out_mt_response = $_POST['opt_out_mt_response'] ?? null;
    $opt_in_method = $_POST['opt_in_method'] ?? null;
    $is_commercial = isset($_POST['is_commercial']) ? 1 : 0;
    $previous_messaging_service = $_POST['previous_messaging_service'] ?? null;
    $previous_content = $_POST['previous_content'] ?? null;
    $previous_numbers = $_POST['previous_numbers'] ?? null;
    $number_published_location = $_POST['number_published_location'] ?? null;
    $is_fortune_1000 = isset($_POST['is_fortune_1000']) ? 1 : 0;
    $cta_urls = $_POST['cta_urls'] ?? null;
    $cta_numbers = $_POST['cta_numbers'] ?? null;

    $stmt = $conn->prepare("INSERT INTO global_sender_id_requests (user_id, messaging_provider, business_name, business_address, business_city_state, business_zip, contact_first_name, contact_last_name, contact_email, contact_phone, tfn, estimated_monthly_volume, use_case_summary, corporate_website, example_messages, brand_description, help_mt_response, opt_out_mt_response, opt_in_method, is_commercial, previous_messaging_service, previous_content, previous_numbers, number_published_location, is_fortune_1000, cta_urls, cta_numbers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("isssssssssssssssssssissssis", $current_user['id'], $messaging_provider, $business_name, $business_address, $business_city_state, $business_zip, $contact_first_name, $contact_last_name, $contact_email, $contact_phone, $tfn, $estimated_monthly_volume, $use_case_summary, $corporate_website, $example_messages, $brand_description, $help_mt_response, $opt_out_mt_response, $opt_in_method, $is_commercial, $previous_messaging_service, $previous_content, $previous_numbers, $number_published_location, $is_fortune_1000, $cta_urls, $cta_numbers);

    if ($stmt->execute()) {
        $success = "Your Sender ID registration request has been submitted successfully.";
    } else {
        $errors[] = "Failed to submit your request. Please try again. Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Global Sender ID Registration</h4>
        </div>
        <div class="card-body">
            <p>Please fill out this form completely to register your Sender ID for global messaging. Approval is required for certain countries and networks.</p>
            <hr>

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

            <form action="global-sender-id.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <h5>Business Information</h5>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Messaging Provider</label><input type="text" name="messaging_provider" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Business Name</label><input type="text" name="business_name" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Business Registered Address</label><textarea name="business_address" class="form-control" rows="2"></textarea></div>
                <div class="row">
                    <div class="col-md-8 mb-3"><label class="form-label">City, State</label><input type="text" name="business_city_state" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Zip Code</label><input type="text" name="business_zip" class="form-control"></div>
                </div>

                <h5>Business Contact</h5>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">First Name</label><input type="text" name="contact_first_name" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Last Name</label><input type="text" name="contact_last_name" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Contact Email</label><input type="email" name="contact_email" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Contact Phone</label><input type="tel" name="contact_phone" class="form-control"></div>
                </div>

                <hr class="my-4">

                <h5>Program Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">TFN (Toll-Free Number)</label><input type="text" name="tfn" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Estimated Monthly Volume</label><input type="text" name="estimated_monthly_volume" class="form-control"></div>
                </div>
                <div class="mb-3"><label class="form-label">Summarize the use-case</label><textarea name="use_case_summary" class="form-control" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label">Corporate Website</label><input type="url" name="corporate_website" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Example Message(s)</label><textarea name="example_messages" class="form-control" rows="4" placeholder="Please include examples of all types of messages that are going to be sent. List any URLs or Phone Numbers that may be included."></textarea></div>
                <div class="mb-3"><label class="form-label">Brand Description (What content are subscribers signing up for?)</label><textarea name="brand_description" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">HELP Message Template</label><textarea name="help_mt_response" class="form-control" rows="2" placeholder="When a user messages HELP, the MT response must include the business name, customer care contact information (email or phone number), and opt-out instructions."></textarea></div>
                <div class="mb-3"><label class="form-label">STOP (Opt-Out) Message Template</label><textarea name="opt_out_mt_response" class="form-control" rows="2" placeholder="When a user messages STOP, the MT response must include the business name and confirmation that messages have been stopped."></textarea></div>
                <div class="mb-3"><label class="form-label">How will consumers be opting into SMS programs on this number?</label><textarea name="opt_in_method" class="form-control" rows="3"></textarea></div>

                <hr class="my-4">

                <h5>Traffic Information</h5>
                <div class="mb-3">
                    <label class="form-label">Will the text messages be advertising or promoting a commercial product or service?</label>
                    <div>
                        <input type="radio" name="is_commercial" value="1" id="is_commercial_yes"><label for="is_commercial_yes" class="ms-1 me-3">Yes</label>
                        <input type="radio" name="is_commercial" value="0" id="is_commercial_no"><label for="is_commercial_no" class="ms-1">No</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Was this traffic previously on another messaging service?</label>
                    <input type="text" name="previous_messaging_service" class="form-control" placeholder="e.g., New to Messaging, Short-Code, Long-Number">
                </div>
                <div class="mb-3"><label class="form-label">If so, please provide sample content:</label><textarea name="previous_content" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">If so, please provide previous number(s):</label><input type="text" name="previous_numbers" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Where is the number published (if anywhere)?</label><input type="text" name="number_published_location" class="form-control"></div>
                <div class="mb-3">
                    <label class="form-label">Is this a Fortune 500 or 1000 company?</label>
                    <div>
                        <input type="radio" name="is_fortune_1000" value="1" id="is_fortune_yes"><label for="is_fortune_yes" class="ms-1 me-3">Yes</label>
                        <input type="radio" name="is_fortune_1000" value="0" id="is_fortune_no"><label for="is_fortune_no" class="ms-1">No</label>
                    </div>
                </div>
                <div class="mb-3"><label class="form-label">Call to Actions (URLs included in messages)</label><textarea name="cta_urls" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Call to Actions (Numbers included in messages)</label><textarea name="cta_numbers" class="form-control" rows="2"></textarea></div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
