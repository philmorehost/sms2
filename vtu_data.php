<?php
$page_title = 'Buy Data Bundles';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Network prefixes for auto-detection
$network_prefixes = [
    'MTN' => ['7025','7026','803','806','703','704','706','707','813','810','814','816','903','906','913','916'],
    'GLO' => ['805','807','705','815','811','905','915'],
    'Airtel' => ['802','808','708','812','701','902','907','901','904','911','912'],
    '9mobile' => ['809','818','817','909','908']
];

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container-data"></div>
        <form id="data-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone" required placeholder="e.g., 08012345678">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="network" class="form-label">Mobile Network</label>
                        <input type="text" class="form-control" id="network" name="network" readonly>
                    </div>
                </div>
            </div>

            <!-- Plan Selection (Initially Hidden) -->
            <div id="plan-selection-area" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="plan_type" class="form-label">Plan Type</label>
                        <select class="form-select" id="plan_type" name="plan_type" required>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="data_plan" class="form-label">Select Data Plan</label>
                        <select class="form-select" id="data_plan" name="api_product_id" required>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                </div>
                <div class="text-center">
                    <p>You Will Be Charged: <strong id="final-price"></strong></p>
                    <button type="submit" id="submit-btn-data" class="btn btn-primary btn-lg">Buy Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone_number');
    const networkInput = document.getElementById('network');
    const planSelectionArea = document.getElementById('plan-selection-area');
    const planTypeSelect = document.getElementById('plan_type');
    const planSelect = document.getElementById('data_plan');
    const finalPriceDisplay = document.getElementById('final-price');
    const form = document.getElementById('data-form');
    const alertContainer = document.getElementById('alert-container-data');
    const submitBtn = document.getElementById('submit-btn-data');

    const prefixes = <?php echo json_encode($network_prefixes); ?>;
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';

    let currentPlans = [];

    function showAlert(message, type = 'danger') {
        alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    }

    function fetchPlanTypes(network) {
        planSelectionArea.style.display = 'none';
        planTypeSelect.innerHTML = '<option>Loading plan types...</option>';
        planSelect.innerHTML = '';

        const formData = new FormData();
        formData.append('action', 'get_data_plans');
        formData.append('network', network);

        fetch('ajax/vtu_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                planTypeSelect.innerHTML = '<option value="">-- Select Plan Type --</option>';
                data.data.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type;
                    planTypeSelect.appendChild(option);
                });
                planSelectionArea.style.display = 'block';
            } else {
                planSelectionArea.style.display = 'none';
            }
        });
    }

    function fetchPlans(network, planType) {
        planSelect.innerHTML = '<option>Loading plans...</option>';

        const formData = new FormData();
        formData.append('action', 'get_data_plans');
        formData.append('network', network);
        formData.append('plan_type', planType);

        fetch('ajax/vtu_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                currentPlans = data.data;
                planSelect.innerHTML = '<option value="">-- Select Data Plan --</option>';
                currentPlans.forEach(plan => {
                    const option = document.createElement('option');
                    option.value = plan.api_product_id;
                    const discount = parseFloat(plan.amount) * (parseFloat(plan.user_discount_percentage || 0) / 100);
                    const finalPrice = parseFloat(plan.amount) - discount;
                    option.textContent = `${plan.name} - ${currencySymbol}${finalPrice.toFixed(2)}`;
                    planSelect.appendChild(option);
                });
            } else {
                planSelect.innerHTML = '<option>No plans available.</option>';
            }
        });
    }

    function detectNetwork() {
        const number = phoneInput.value.replace(/\s+/g, '');
        let detectedNetwork = '';
        if (number.length >= 4) {
            const prefix = number.substring(0, 4);
            for (const network in prefixes) {
                if (prefixes[network].includes(prefix)) {
                    detectedNetwork = network;
                    break;
                }
            }
        }

        const previousNetwork = networkInput.value;
        networkInput.value = detectedNetwork;

        if (detectedNetwork && detectedNetwork !== previousNetwork) {
            fetchPlanTypes(detectedNetwork);
        } else if (!detectedNetwork) {
            planSelectionArea.style.display = 'none';
        }
    }

    planTypeSelect.addEventListener('change', function() {
        const network = networkInput.value;
        const planType = this.value;
        planSelect.innerHTML = '';
        finalPriceDisplay.innerHTML = '';
        if (network && planType) {
            fetchPlans(network, planType);
        }
    });

    planSelect.addEventListener('change', function() {
        const selectedProductId = this.value;
        const selectedPlan = currentPlans.find(p => p.api_product_id == selectedProductId);
        if (selectedPlan) {
            const discount = parseFloat(selectedPlan.amount) * (parseFloat(selectedPlan.user_discount_percentage || 0) / 100);
            const finalPrice = parseFloat(selectedPlan.amount) - discount;
            finalPriceDisplay.innerHTML = `<strong>${currencySymbol}${finalPrice.toFixed(2)}</strong>`;
        } else {
            finalPriceDisplay.innerHTML = '';
        }
    });

    phoneInput.addEventListener('input', detectNetwork);

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const formData = new FormData(form);
        formData.append('action', 'purchase_data');

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                form.reset();
                detectNetwork();
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
            submitBtn.innerHTML = 'Buy Data';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
