<?php
$page_title = 'Corporate Sender ID Registration';
include 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_corporate_sender_id'])) {
    $sender_id = trim($_POST['sender_id']);

    // Validation
    if (empty($sender_id)) {
        $errors[] = "Sender ID is required.";
    } elseif (strlen($sender_id) > 11) {
        $errors[] = "Sender ID must be 11 characters or less.";
    }

    $required_files = [
        'cac_certificate' => 'C.A.C Certificate',
        'mtn_letter' => 'MTN Letter',
        'glo_letter' => 'GLO Letter',
        'airtel_letter' => 'Airtel Letter',
        'nine_mobile_letter' => '9mobile Letter',
    ];

    $uploaded_paths = [];
    $has_file_errors = false;

    foreach ($required_files as $key => $label) {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] != UPLOAD_ERR_OK) {
            $errors[] = "{$label} is required.";
            $has_file_errors = true;
        } elseif ($_FILES[$key]['type'] != 'application/pdf') {
            $errors[] = "{$label} must be a PDF file.";
            $has_file_errors = true;
        }
    }

    // Handle optional CBN license
    if (isset($_FILES['cbn_license']) && $_FILES['cbn_license']['error'] == UPLOAD_ERR_OK) {
        if ($_FILES['cbn_license']['type'] != 'application/pdf') {
            $errors[] = "CBN License must be a PDF file.";
            $has_file_errors = true;
        }
    }


    if (empty($errors) && !$has_file_errors) {
        $upload_dir = __DIR__ . '/uploads/user_' . $user['id'] . '/corporate_ids/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Process required files
        foreach ($required_files as $key => $label) {
            $file_ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
            $file_name = $key . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES[$key]['tmp_name'], $file_path)) {
                $uploaded_paths[$key] = 'uploads/user_' . $user['id'] . '/corporate_ids/' . $file_name;
            } else {
                $errors[] = "Failed to move uploaded file for {$label}.";
                $has_file_errors = true;
                break; // Stop if one file fails
            }
        }

        // Process optional CBN license
        if (!$has_file_errors && isset($_FILES['cbn_license']) && $_FILES['cbn_license']['error'] == UPLOAD_ERR_OK) {
             $file_ext = pathinfo($_FILES['cbn_license']['name'], PATHINFO_EXTENSION);
             $file_name = 'cbn_license_' . time() . '.' . $file_ext;
             $file_path = $upload_dir . $file_name;
             if (move_uploaded_file($_FILES['cbn_license']['tmp_name'], $file_path)) {
                $uploaded_paths['cbn_license'] = 'uploads/user_' . $user['id'] . '/corporate_ids/' . $file_name;
            } else {
                $errors[] = "Failed to move uploaded file for CBN License.";
                $has_file_errors = true;
            }
        }
    }

    if (empty($errors) && !$has_file_errors) {
        $stmt = $conn->prepare("INSERT INTO corporate_sender_ids (user_id, sender_id, cac_certificate_path, mtn_letter_path, glo_letter_path, airtel_letter_path, nine_mobile_letter_path, cbn_license_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $cbn_path = $uploaded_paths['cbn_license'] ?? null;

        $stmt->bind_param("isssssss",
            $user['id'],
            $sender_id,
            $uploaded_paths['cac_certificate'],
            $uploaded_paths['mtn_letter'],
            $uploaded_paths['glo_letter'],
            $uploaded_paths['airtel_letter'],
            $uploaded_paths['nine_mobile_letter'],
            $cbn_path
        );

        if ($stmt->execute()) {
            $registration_id = $conn->insert_id;

            // Now, call the external API
            $api_result = submit_corporate_sender_id_api($sender_id, $uploaded_paths);

            if ($api_result['success']) {
                // API call was successful, update our local status
                $success = "Your Corporate Sender ID registration has been submitted successfully. You will be notified once it is approved.";
                $update_stmt = $conn->prepare("UPDATE corporate_sender_ids SET api_response = ? WHERE id = ?");
                $api_response_str = json_encode($api_result['data']);
                $update_stmt->bind_param("si", $api_response_str, $registration_id);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // API call failed. We should probably mark our local record as failed.
                // For now, we will show an error to the user.
                $errors[] = "Your registration was saved, but we failed to submit it to the gateway. Please contact support. Error: " . $api_result['message'];
                // We could also delete the local record or mark it as 'failed_to_submit'
            }
        } else {
            $errors[] = "Failed to save your registration to the database. Please try again.";
        }
        $stmt->close();
    }
}

?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title h3"><?php echo $page_title; ?></h1>
        </div>
        <div class="card-body">
            <p>Please fill out the form below to register your Corporate Sender ID. All documents are mandatory unless otherwise stated and must be in PDF format.</p>
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

            <form action="corporate-sender-id.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="sender_id" class="form-label">Sender ID</label>
                    <input type="text" class="form-control" id="sender_id" name="sender_id" maxlength="11" required>
                    <div class="form-text">Your Sender ID must be 11 characters or less.</div>
                </div>

                <h5 class="mt-4">Upload Documents (Mandatory)</h5>
                <p class="text-muted">Please upload all the required documents in PDF format.</p>

                <div class="mb-3">
                    <label for="cac_certificate" class="form-label">C.A.C Certificate</label>
                    <input type="file" class="form-control" id="cac_certificate" name="cac_certificate" accept=".pdf" required>
                </div>
                <div class="mb-3">
                    <label for="mtn_letter" class="form-label">MTN Letter</label>
                    <input type="file" class="form-control" id="mtn_letter" name="mtn_letter" accept=".pdf" required>
                </div>
                <div class="mb-3">
                    <label for="glo_letter" class="form-label">GLO Letter</label>
                    <input type="file" class="form-control" id="glo_letter" name="glo_letter" accept=".pdf" required>
                </div>
                <div class="mb-3">
                    <label for="airtel_letter" class="form-label">Airtel Letter</label>
                    <input type="file" class="form-control" id="airtel_letter" name="airtel_letter" accept=".pdf" required>
                </div>
                <div class="mb-3">
                    <label for="nine_mobile_letter" class="form-label">9mobile Letter</label>
                    <input type="file" class="form-control" id="nine_mobile_letter" name="nine_mobile_letter" accept=".pdf" required>
                </div>

                <h5 class="mt-4">Additional Documents</h5>
                <div class="mb-3">
                    <label for="cbn_license" class="form-label">CBN License (Required for FINTECHS, Banks and MFBs)</label>
                    <input type="file" class="form-control" id="cbn_license" name="cbn_license" accept=".pdf">
                </div>

                <hr>
                <button type="submit" name="register_corporate_sender_id" class="btn btn-primary">Submit for Approval</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
