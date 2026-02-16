<?php
$page_title = 'Global SMS Coverage & Pricing';
require_once 'app/bootstrap.php';

// The header include below handles the login check and redirection.
// No need for a separate is_logged_in() check here.

// --- Backend Logic for Price Calculation ---

// 1. Fetch global settings
$global_profit_percentage = (float)($settings['global_profit_percentage'] ?? 0);
$global_wallet_currency = $settings['global_wallet_currency'] ?? 'EUR';

// 2. Fetch user-specific profit percentage
$user_profit_percentage = (float)($current_user['profit_percentage'] ?? 0);

// 3. Fetch all base pricing data from the database
$pricing_result = $conn->query("SELECT country, operator, price FROM global_sms_pricing ORDER BY country, operator ASC");
$pricing_data = [];
if ($pricing_result) {
    while ($row = $pricing_result->fetch_assoc()) {
        $base_price = (float)$row['price'];

        // 4. Calculate the final price. Per user request, we are now showing the base price.
        $final_price = $base_price;

        $pricing_data[] = [
            'country' => $row['country'],
            'operator' => $row['operator'],
            'price' => $final_price
        ];
    }
}

// --- End of Backend Logic ---


include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="text-center mb-4">Global SMS Coverage & Pricing</h1>
    <p class="text-center text-muted mb-5">
        Here is a detailed list of our coverage and the price per SMS for each destination.
        <br>
        The price shown is the final price for you, in <?php echo htmlspecialchars($global_wallet_currency); ?>, including all adjustments.
    </p>

    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="Search by country or operator...">
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="pricingTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Country</th>
                            <th>Operator</th>
                            <th>Price per SMS (<?php echo htmlspecialchars($global_wallet_currency); ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pricing_data)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No pricing information is available at the moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pricing_data as $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['country']); ?></td>
                                    <td><?php echo htmlspecialchars($data['operator']); ?></td>
                                    <td><?php echo number_format($data['price'], 5); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const pricingTable = document.getElementById('pricingTable');
    const tableRows = pricingTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();

        for (let i = 0; i < tableRows.length; i++) {
            const row = tableRows[i];
            // Assumes the first two columns are Country and Operator
            const countryCell = row.cells[0];
            const operatorCell = row.cells[1];

            if (countryCell && operatorCell) {
                const countryText = countryCell.textContent || countryCell.innerText;
                const operatorText = operatorCell.textContent || operatorCell.innerText;

                if (countryText.toLowerCase().indexOf(searchTerm) > -1 || operatorText.toLowerCase().indexOf(searchTerm) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
    });
});
</script>

<?php
include 'includes/footer.php';
?>
