<?php
$page_title = 'Buy Airtime';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Network prefixes for auto-detection
$network_prefixes = [
    'MTN' => ['7025','7026','803','806','703','704','706','707','813','810','814','816','903','906','913','916'],
    'GLO' => ['805','807','705','815','811','905','915'],
    'Airtel' => ['802','808','708','812','701','902','907','901','904','911','912'],
    '9mobile' => ['809','818','817','909','908']
];

// Fetch airtime discounts to pass to JavaScript
$airtime_discounts = [];
foreach ($network_prefixes as $network => $prefixes) {
    $key = 'airtime_discount_' . strtolower($network);
    $airtime_discounts[$network] = (float)($settings[$key] ?? 0);
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container-airtime"></div>
        <form id="airtime-form">
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
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (<?php echo get_currency_symbol(); ?>)</label>
                        <input type="number" class="form-control" id="amount" name="amount" required min="50" max="10000">
                    </div>
                </div>
                <div class="col-md-6">
                     <div class="mb-3">
                        <label class="form-label">You Will Be Charged</label>
                        <p class="form-control-plaintext" id="final-price"><strong><?php echo get_currency_symbol(); ?>0.00</strong></p>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" id="submit-btn-airtime" class="btn btn-primary btn-lg">Buy Airtime</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone_number');
    const networkInput = document.getElementById('network');
    const amountInput = document.getElementById('amount');
    const finalPriceDisplay = document.getElementById('final-price');
    const form = document.getElementById('airtime-form');
    const alertContainer = document.getElementById('alert-container-airtime');

    const prefixes = <?php echo json_encode($network_prefixes); ?>;
    const discounts = <?php echo json_encode($airtime_discounts); ?>;
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';

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
        networkInput.value = detectedNetwork;
        calculateFinalPrice();
    }

    function calculateFinalPrice() {
        const network = networkInput.value;
        const amount = parseFloat(amountInput.value) || 0;
        let finalPrice = amount;

        if (network && discounts[network] !== undefined) {
            const discountPercent = parseFloat(discounts[network]);
            const discountAmount = amount * (discountPercent / 100);
            finalPrice = amount - discountAmount;
        }

        finalPriceDisplay.innerHTML = `<strong>${currencySymbol}${finalPrice.toFixed(2)}</strong>`;
    }

    phoneInput.addEventListener('input', detectNetwork);
    amountInput.addEventListener('input', calculateFinalPrice);

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn-airtime');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const formData = new FormData(form);
        formData.append('action', 'purchase_airtime');

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                form.reset();
                detectNetwork(); // Reset fields
            } else {
                alertContainer.innerHTML = `<div class="alert alert-danger">${data.message || 'An error occurred.'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertContainer.innerHTML = `<div class="alert alert-danger">An unexpected network error occurred.</div>`;
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Buy Airtime';
        });
    });

    // Initial detection on page load if number is pre-filled
    detectNetwork();
});
</script>

<?php include 'includes/footer.php'; ?>
