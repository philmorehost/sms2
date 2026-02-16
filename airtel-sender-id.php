<?php
$page_title = 'Airtel Promotional Sender ID Registration';
include 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_airtel_sender_id'])) {
    $company_name = trim($_POST['company_name']);
    $sender_id = trim($_POST['sender_id']);
    $nature_of_business = trim($_POST['nature_of_business']);
    $service_description = trim($_POST['service_description']);
    $sample_message = trim($_POST['sample_message']);

    // Validation
    if (empty($company_name)) $errors[] = "Company Name is required.";
    if (empty($sender_id)) $errors[] = "Sender ID is required.";
    if (strlen($sender_id) > 11) $errors[] = "Sender ID must be 11 characters or less.";
    if (empty($nature_of_business)) $errors[] = "Nature of Business is required.";
    if (empty($service_description)) $errors[] = "Service Description is required.";
    if (empty($sample_message)) $errors[] = "Sample Message is required.";

    if (empty($errors)) {
        $data = [
            'company_name' => $company_name,
            'sender_id' => $sender_id,
            'nature_of_business' => $nature_of_business,
            'service_description' => $service_description,
            'sample_message' => $sample_message
        ];

        $api_result = submit_airtel_sender_id_api($data);

        if ($api_result['success']) {
            $stmt = $conn->prepare("INSERT INTO airtel_sender_ids (user_id, company_name, sender_id, nature_of_business, service_description, sample_message, api_response) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $api_response_str = json_encode($api_result['data']);
            $stmt->bind_param("issssss",
                $user['id'],
                $company_name,
                $sender_id,
                $nature_of_business,
                $service_description,
                $sample_message,
                $api_response_str
            );

            if ($stmt->execute()) {
                $success = "Your Airtel Promotional Sender ID has been submitted successfully for review.";
            } else {
                $errors[] = "API submission was successful, but failed to save your registration. Please contact support.";
            }
            $stmt->close();
        } else {
            $errors[] = "API Error: " . $api_result['message'];
        }
    }
}

?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title h3"><?php echo $page_title; ?></h1>
        </div>
        <div class="card-body">
            <p>Please fill out the form below to register your Airtel Promotional Sender ID.</p>
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

            <form action="airtel-sender-id.php" method="POST">
                <div class="mb-3">
                    <label for="company_name" class="form-label">Company Name (Business Name)</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" required>
                </div>
                <div class="mb-3">
                    <label for="sender_id" class="form-label">Promotional Sender ID</label>
                    <input type="text" class="form-control" id="sender_id" name="sender_id" maxlength="11" required>
                    <div class="form-text">Your Sender ID must be 11 characters or less.</div>
                </div>
                <div class="mb-3">
                    <label for="nature_of_business" class="form-label">Nature of Company's Business</label>
                    <textarea class="form-control" id="nature_of_business" name="nature_of_business" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="service_description" class="form-label">Description of Service</label>
                    <textarea class="form-control" id="service_description" name="service_description" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="sample_message" class="form-label">Sample Message</label>
                    <textarea class="form-control" id="sample_message" name="sample_message" rows="3" required></textarea>
                </div>

                <hr>
                <button type="submit" name="register_airtel_sender_id" class="btn btn-primary">Submit for Approval</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
