<?php
$page_title = 'Recharge Card Printing';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Fetch active Recharge Card products from the database
$rc_products = [];
$stmt = $conn->prepare("SELECT * FROM vtu_products WHERE service_type = 'recharge_card' AND is_active = 1 ORDER BY network, amount ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rc_products[$row['network']][] = $row;
    }
    $stmt->close();
}
$networks = array_keys($rc_products);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container-rc"></div>
        <form id="rc-form">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="rc_network" class="form-label">Network</label>
                    <select class="form-select" id="rc_network" name="network" required>
                        <option value="">-- Select Network --</option>
                        <?php foreach ($networks as $network): ?>
                            <option value="<?php echo htmlspecialchars($network); ?>"><?php echo htmlspecialchars($network); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="rc_value" class="form-label">Card Value</label>
                    <select class="form-select" id="rc_value" name="product_id" required>
                        <option value="">-- Select Network First --</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="rc_quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="rc_quantity" name="quantity" value="1" min="1" max="100" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="business_name" class="form-label">Business Name (Optional)</label>
                <input type="text" class="form-control" id="business_name" name="business_name" placeholder="Your business name to print on cards">
            </div>

            <div class="text-center">
                <p>Total Amount: <strong id="final-price"><?php echo get_currency_symbol(); ?>0.00</strong></p>
                <button type="submit" id="submit-btn" class="btn btn-primary btn-lg">Generate PINs</button>
            </div>
        </form>
    </div>
</div>

<!-- Results Modal -->
<div class="modal fade" id="resultsModalRC" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Your Generated Recharge Cards</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="printable-area">
                    <!-- Printable cards will be injected here by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="print-btn">Print Cards</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const networkSelect = document.getElementById('rc_network');
    const valueSelect = document.getElementById('rc_value');
    const quantityInput = document.getElementById('rc_quantity');
    const finalPriceDisplay = document.getElementById('final-price');
    const form = document.getElementById('rc-form');
    const alertContainer = document.getElementById('alert-container-rc');
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';
    const resultsModal = new bootstrap.Modal(document.getElementById('resultsModalRC'));
    const printableArea = document.getElementById('printable-area');

    const allProducts = <?php echo json_encode($rc_products); ?>;

    function updateValueOptions() {
        const selectedNetwork = networkSelect.value;
        valueSelect.innerHTML = '<option value="">-- Select Value --</option>';
        if (selectedNetwork && allProducts[selectedNetwork]) {
            allProducts[selectedNetwork].forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${currencySymbol}${parseInt(product.amount)}`;
                option.dataset.amount = product.amount;
                option.dataset.discount = product.user_discount_percentage;
                valueSelect.appendChild(option);
            });
        }
        calculateFinalPrice();
    }

    function calculateFinalPrice() {
        const selectedOption = valueSelect.options[valueSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            finalPriceDisplay.innerHTML = `${currencySymbol}0.00`;
            return;
        }

        const amount = parseFloat(selectedOption.dataset.amount) || 0;
        const discountPercent = parseFloat(selectedOption.dataset.discount) || 0;
        const quantity = parseInt(quantityInput.value) || 0;

        const pricePerPin = amount - (amount * (discountPercent / 100));
        const totalPrice = pricePerPin * quantity;

        finalPriceDisplay.innerHTML = `<strong>${currencySymbol}${totalPrice.toFixed(2)}</strong>`;
    }

    networkSelect.addEventListener('change', updateValueOptions);
    valueSelect.addEventListener('change', calculateFinalPrice);
    quantityInput.addEventListener('input', calculateFinalPrice);

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

        const formData = new FormData(form);
        formData.append('action', 'purchase_recharge_card');

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('PINs generated successfully!', 'success');
                generatePrintableCards(data.data.cards, data.data.print_details);
                resultsModal.show();
                form.reset();
                updateValueOptions();
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
            submitBtn.innerHTML = 'Generate PINs';
        });
    });

    function generatePrintableCards(cards, details) {
        let cardHtml = '<div class="printable-container">';
        cards.forEach(card => {
            cardHtml += `
                <div class="recharge-card">
                    <div class="rc-header">${details.business_name || 'Recharge Card'}</div>
                    <div class="rc-network">${details.network} ${details.value}</div>
                    <div class="rc-pin"><strong>PIN:</strong> ${card.pin}</div>
                    <div class="rc-serial"><strong>S/N:</strong> ${card.sno}</div>
                    <div class="rc-footer">
                        To load: *DIAL_CODE*${card.pin}#<br>
                        Balance: *BALANCE_CODE*
                    </div>
                </div>
            `;
        });
        cardHtml += '</div>';
        printableArea.innerHTML = cardHtml;
    }

    document.getElementById('print-btn').addEventListener('click', function() {
        const printContent = printableArea.innerHTML;
        const newWindow = window.open('', '_blank');
        newWindow.document.write('<html><head><title>Print Recharge Cards</title>');
        const styles = `
            .printable-container { display: flex; flex-wrap: wrap; gap: 10px; }
            .recharge-card { border: 1px solid #ccc; padding: 10px; width: calc(25% - 10px); text-align: center; font-family: monospace; }
            .rc-header { font-weight: bold; font-size: 1.1em; }
            .rc-network { font-size: 1.2em; margin: 5px 0; }
            .rc-pin { font-size: 1.3em; font-weight: bold; margin: 10px 0; }
            .rc-serial { font-size: 0.8em; }
            .rc-footer { font-size: 0.8em; margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 5px; }
            @media print { body { -webkit-print-color-adjust: exact; } }
        `;
        newWindow.document.write(`<style>${styles}</style>`);
        newWindow.document.write('</head><body>');
        newWindow.document.write(printContent);
        newWindow.document.write('</body></html>');
        newWindow.document.close();
        setTimeout(() => { newWindow.print(); newWindow.close(); }, 500);
    });

    updateValueOptions();
});
</script>

<?php include 'includes/footer.php'; ?>
