<?php
$page_title = 'Buy Exam PINs';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Fetch active Exam PIN products from the database
$exam_pins = [];
$stmt = $conn->prepare("SELECT * FROM vtu_products WHERE service_type = 'exam_pin' AND is_active = 1 ORDER BY name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $exam_pins[] = $row;
    }
    $stmt->close();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0"><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container-exam"></div>
        <form id="exam-pin-form">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="exam_type" class="form-label">Exam Type</label>
                    <select class="form-select" id="exam_type" name="product_id" required>
                        <option value="">-- Select Exam PIN --</option>
                        <?php foreach ($exam_pins as $pin): ?>
                            <option value="<?php echo htmlspecialchars($pin['id']); ?>" data-amount="<?php echo htmlspecialchars($pin['amount']); ?>" data-discount="<?php echo htmlspecialchars($pin['user_discount_percentage']); ?>">
                                <?php echo htmlspecialchars($pin['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="100" required>
                </div>
            </div>

            <div class="text-center">
                <p>Total Amount: <strong id="final-price"><?php echo get_currency_symbol(); ?>0.00</strong></p>
                <button type="submit" id="submit-btn" class="btn btn-primary btn-lg">Buy PIN(s)</button>
            </div>
        </form>
    </div>
</div>

<!-- Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Your Purchased PINs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please copy and save your PINs immediately. They will not be shown again.</p>
                <div id="pin-results-container" class="table-responsive">
                    <!-- PINs will be injected here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="print-pins-btn">Print</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const examTypeSelect = document.getElementById('exam_type');
    const quantityInput = document.getElementById('quantity');
    const finalPriceDisplay = document.getElementById('final-price');
    const form = document.getElementById('exam-pin-form');
    const alertContainer = document.getElementById('alert-container-exam');
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';
    const resultsModal = new bootstrap.Modal(document.getElementById('resultsModal'));
    const pinResultsContainer = document.getElementById('pin-results-container');

    function calculateFinalPrice() {
        const selectedOption = examTypeSelect.options[examTypeSelect.selectedIndex];
        if (!selectedOption.value) {
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

    examTypeSelect.addEventListener('change', calculateFinalPrice);
    quantityInput.addEventListener('input', calculateFinalPrice);

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const formData = new FormData(form);
        formData.append('action', 'purchase_exam_pin');

        fetch('ajax/vtu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Purchase successful!', 'success');
                // Display results in modal
                let resultsHtml = '<table class="table table-bordered"><thead><tr><th>S/N</th><th>PIN</th><th>Serial No.</th></tr></thead><tbody>';
                data.data.cards.forEach((card, index) => {
                    resultsHtml += `<tr><td>${index + 1}</td><td>${card.pin}</td><td>${card.serial_no || 'N/A'}</td></tr>`;
                });
                resultsHtml += '</tbody></table>';
                pinResultsContainer.innerHTML = resultsHtml;
                resultsModal.show();
                form.reset();
                calculateFinalPrice();
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
            submitBtn.innerHTML = 'Buy PIN(s)';
        });
    });

    document.getElementById('print-pins-btn').addEventListener('click', function() {
        const printContent = pinResultsContainer.innerHTML;
        const newWindow = window.open('', '_blank');
        newWindow.document.write('<html><head><title>Print Exam PINs</title>');
        newWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        newWindow.document.write('</head><body><div class="container mt-4">');
        newWindow.document.write(printContent);
        newWindow.document.write('</div></body></html>');
        newWindow.document.close();
        setTimeout(() => { // Wait for content to load
            newWindow.print();
            newWindow.close();
        }, 500);
    });

    // Initial calculation
    calculateFinalPrice();
});
</script>

<?php include 'includes/footer.php'; ?>
