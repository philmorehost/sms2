<?php
$page_title = 'Electricity Bill Payment';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Fetch active Electricity products (Discos) from the database
$discos = [];
$stmt = $conn->prepare("SELECT * FROM vtu_products WHERE service_type = 'electricity' AND is_active = 1 ORDER BY name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $discos[] = $row;
    }
    $stmt->close();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container-electricity"></div>
        <form id="electricity-form">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="disco_provider" class="form-label">Electricity Disco</label>
                    <select class="form-select" id="disco_provider" name="serviceID" required>
                        <option value="">-- Select Company --</option>
                        <?php foreach ($discos as $disco): ?>
                            <option value="<?php echo htmlspecialchars($disco['api_product_id']); ?>" data-discount="<?php echo htmlspecialchars($disco['user_discount_percentage']); ?>"><?php echo htmlspecialchars($disco['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="meter_type" class="form-label">Meter Type</label>
                    <select class="form-select" id="meter_type" name="variation_code" required>
                        <option value="prepaid">Prepaid</option>
                        <option value="postpaid">Postpaid</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="meter_number" class="form-label">Meter Number</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="meter_number" name="billersCode" required>
                    <button type="button" id="verify-btn" class="btn btn-secondary">Verify Meter</button>
                </div>
            </div>

            <!-- Verification and Payment (Initially Hidden) -->
            <div id="payment-area" style="display: none;">
                <hr>
                <div id="customer-details" class="alert alert-info">
                    <!-- Customer details will be injected here by JavaScript -->
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="amount" class="form-label">Amount (<?php echo get_currency_symbol(); ?>)</label>
                        <input type="number" class="form-control" id="amount" name="amount" required min="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">You Will Be Charged</label>
                        <p class="form-control-plaintext" id="final-price"><strong><?php echo get_currency_symbol(); ?>0.00</strong></p>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" id="submit-btn" class="btn btn-primary btn-lg">Pay Now</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const discoSelect = document.getElementById('disco_provider');
    const meterTypeSelect = document.getElementById('meter_type');
    const meterNumberInput = document.getElementById('meter_number');
    const verifyBtn = document.getElementById('verify-btn');
    const paymentArea = document.getElementById('payment-area');
    const customerDetailsDiv = document.getElementById('customer-details');
    const amountInput = document.getElementById('amount');
    const finalPriceDisplay = document.getElementById('final-price');
    const form = document.getElementById('electricity-form');
    const alertContainer = document.getElementById('alert-container-electricity');
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';

    function showAlert(message, type = 'danger') {
        alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    }

    function calculateFinalPrice() {
        const selectedDisco = discoSelect.options[discoSelect.selectedIndex];
        const discountPercent = parseFloat(selectedDisco.dataset.discount) || 0;
        const amount = parseFloat(amountInput.value) || 0;
        const discountAmount = amount * (discountPercent / 100);
        const finalPrice = amount - discountAmount;
        finalPriceDisplay.innerHTML = `<strong>${currencySymbol}${finalPrice.toFixed(2)}</strong>`;
    }

    amountInput.addEventListener('input', calculateFinalPrice);
    discoSelect.addEventListener('change', calculateFinalPrice);

    verifyBtn.addEventListener('click', function() {
        const serviceID = discoSelect.value;
        const billersCode = meterNumberInput.value;
        const type = meterTypeSelect.value;

        if (!serviceID || !billersCode) {
            showAlert('Please select a disco and enter a meter number.');
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verifying...';

        const formData = new FormData();
        formData.append('action', 'verify_meter');
        formData.append('serviceID', serviceID);
        formData.append('billersCode', billersCode);
        formData.append('type', type);

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.Customer_Name) {
                let detailsHtml = `<strong>Customer Name:</strong> ${data.data.Customer_Name}`;
                if(data.data.Address) {
                    detailsHtml += `<br><strong>Address:</strong> ${data.data.Address}`;
                }
                customerDetailsDiv.innerHTML = detailsHtml;
                paymentArea.style.display = 'block';
                showAlert('Verification successful!', 'success');
            } else {
                showAlert(data.message || 'Could not verify meter number. Please check the details and try again.');
                paymentArea.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An unexpected error occurred. Please try again.');
            paymentArea.style.display = 'none';
        })
        .finally(() => {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = 'Verify Meter';
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const formData = new FormData(form);
        formData.append('action', 'purchase_electricity');

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                form.reset();
                paymentArea.style.display = 'none';
            } else {
                showAlert(data.message || 'An error occurred.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An unexpected network error occurred.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Pay Now';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
