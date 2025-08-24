<?php
$page_title = 'Cable TV Subscription';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Fetch active Cable TV products from the database
$products = [];
$stmt = $conn->prepare("SELECT * FROM vtu_products WHERE service_type = 'cable_tv' AND is_active = 1 ORDER BY network, name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[$row['network']][] = $row;
    }
    $stmt->close();
}

$cable_providers = array_keys($products);

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container"></div>
        <form id="cable-tv-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="cable_provider" class="form-label">Cable TV Provider</label>
                        <select class="form-select" id="cable_provider" name="serviceID" required>
                            <option value="">-- Select Provider --</option>
                            <?php foreach ($cable_providers as $provider): ?>
                                <option value="<?php echo htmlspecialchars($provider); ?>"><?php echo htmlspecialchars(ucfirst($provider)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="smartcard_number" class="form-label">Smartcard / IUC Number</label>
                        <input type="text" class="form-control" id="smartcard_number" name="billersCode" required>
                    </div>
                </div>
            </div>

            <div class="text-center mb-3">
                <button type="button" id="verify-btn" class="btn btn-secondary">Verify Details</button>
            </div>

            <!-- Verification and Plan Selection (Initially Hidden) -->
            <div id="details-and-plans" style="display: none;">
                <hr>
                <div id="customer-details" class="alert alert-info">
                    <!-- Customer details will be injected here by JavaScript -->
                </div>

                <div class="mb-3">
                    <label for="cable_plan" class="form-label">Select Plan</label>
                    <select class="form-select" id="cable_plan" name="variation_code" required>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>

                <div class="text-center">
                    <p>Total Amount: <strong id="total-amount"><?php echo get_currency_symbol(); ?>0.00</strong></p>
                    <button type="submit" id="submit-btn" class="btn btn-primary btn-lg">Pay Now</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('cable_provider');
    const smartcardNumberInput = document.getElementById('smartcard_number');
    const verifyBtn = document.getElementById('verify-btn');
    const detailsAndPlansDiv = document.getElementById('details-and-plans');
    const customerDetailsDiv = document.getElementById('customer-details');
    const planSelect = document.getElementById('cable_plan');
    const totalAmountSpan = document.getElementById('total-amount');
    const form = document.getElementById('cable-tv-form');
    const alertContainer = document.getElementById('alert-container');

    const allProducts = <?php echo json_encode($products); ?>;

    function showAlert(message, type = 'danger') {
        alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    }

    providerSelect.addEventListener('change', function() {
        detailsAndPlansDiv.style.display = 'none';
        planSelect.innerHTML = ''; // Clear previous plans
    });

    verifyBtn.addEventListener('click', function() {
        const serviceID = providerSelect.value;
        const billersCode = smartcardNumberInput.value;

        if (!serviceID || !billersCode) {
            showAlert('Please select a provider and enter a smartcard number.');
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verifying...';

        const formData = new FormData();
        formData.append('action', 'verify_smartcard');
        formData.append('serviceID', serviceID);
        formData.append('billersCode', billersCode);

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Display customer details
                let detailsHtml = `<strong>Customer Name:</strong> ${data.data.Customer_Name}`;
                if(data.data.Due_Date) {
                    detailsHtml += `<br><strong>Due Date:</strong> ${new Date(data.data.Due_Date).toLocaleDateString()}`;
                }
                if(data.data.Status) {
                    detailsHtml += `<br><strong>Status:</strong> ${data.data.Status}`;
                }
                customerDetailsDiv.innerHTML = detailsHtml;

                // Populate plans for the selected provider
                const providerPlans = allProducts[serviceID] || [];
                planSelect.innerHTML = '<option value="">-- Select a Plan --</option>';
                providerPlans.forEach(plan => {
                    const option = document.createElement('option');
                    option.value = plan.api_product_id;
                    option.textContent = `${plan.name} - <?php echo get_currency_symbol(); ?>${parseFloat(plan.amount).toFixed(2)}`;
                    option.dataset.amount = plan.amount;
                    planSelect.appendChild(option);
                });

                detailsAndPlansDiv.style.display = 'block';
                showAlert('Verification successful!', 'success');
            } else {
                showAlert(data.message || 'Could not verify smartcard number. Please check the details and try again.');
                detailsAndPlansDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An unexpected error occurred. Please try again.');
            detailsAndPlansDiv.style.display = 'none';
        })
        .finally(() => {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = 'Verify Details';
        });
    });

    planSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const amount = selectedOption.dataset.amount || 0;
        totalAmountSpan.textContent = `<?php echo get_currency_symbol(); ?>${parseFloat(amount).toFixed(2)}`;
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const formData = new FormData(form);
        formData.append('action', 'purchase_cable_tv');

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                form.reset();
                detailsAndPlansDiv.style.display = 'none';
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
