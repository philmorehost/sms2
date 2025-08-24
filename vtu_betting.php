<?php
$page_title = 'Fund Betting Wallet';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Fetch active Betting companies from the database
$companies = [];
$stmt = $conn->prepare("SELECT * FROM vtu_products WHERE service_type = 'betting' AND is_active = 1 ORDER BY name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $stmt->close();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container-betting"></div>
        <form id="betting-form">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="betting_company" class="form-label">Betting Company</label>
                    <select class="form-select" id="betting_company" name="betting_company" required>
                        <option value="">-- Select Company --</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo htmlspecialchars($company['api_product_id']); ?>" data-discount="<?php echo htmlspecialchars($company['user_discount_percentage']); ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="customer_id" class="form-label">Customer ID</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="customer_id" name="customer_id" required>
                        <button type="button" id="verify-btn" class="btn btn-secondary">Verify</button>
                    </div>
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
                    <button type="submit" id="submit-btn" class="btn btn-primary btn-lg">Fund Wallet</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const companySelect = document.getElementById('betting_company');
    const customerIdInput = document.getElementById('customer_id');
    const verifyBtn = document.getElementById('verify-btn');
    const paymentArea = document.getElementById('payment-area');
    const customerDetailsDiv = document.getElementById('customer-details');
    const amountInput = document.getElementById('amount');
    const finalPriceDisplay = document.getElementById('final-price');
    const form = document.getElementById('betting-form');
    const alertContainer = document.getElementById('alert-container-betting');
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';

    function showAlert(message, type = 'danger') {
        alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    }

    function calculateFinalPrice() {
        const selectedCompany = companySelect.options[companySelect.selectedIndex];
        const discountPercent = parseFloat(selectedCompany.dataset.discount) || 0;
        const amount = parseFloat(amountInput.value) || 0;
        const discountAmount = amount * (discountPercent / 100);
        const finalPrice = amount - discountAmount;
        finalPriceDisplay.innerHTML = `<strong>${currencySymbol}${finalPrice.toFixed(2)}</strong>`;
    }

    amountInput.addEventListener('input', calculateFinalPrice);
    companySelect.addEventListener('change', calculateFinalPrice);

    verifyBtn.addEventListener('click', function() {
        const company = companySelect.value;
        const customerId = customerIdInput.value;

        if (!company || !customerId) {
            showAlert('Please select a company and enter a customer ID.');
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verifying...';

        const formData = new FormData();
        formData.append('action', 'verify_betting_customer');
        formData.append('betting_company', company);
        formData.append('customer_id', customerId);

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.customer_name && !data.data.customer_name.includes('Error')) {
                customerDetailsDiv.innerHTML = `<strong>Customer Name:</strong> ${data.data.customer_name}`;
                paymentArea.style.display = 'block';
                showAlert('Verification successful!', 'success');
            } else {
                showAlert(data.message || 'Could not verify Customer ID. Please check the details and try again.');
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
            verifyBtn.innerHTML = 'Verify';
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const formData = new FormData(form);
        formData.append('action', 'purchase_betting_funding');

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
            submitBtn.innerHTML = 'Fund Wallet';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
