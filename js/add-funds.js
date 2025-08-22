document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const paystackForm = document.getElementById('paystack-form');
    const paystackButton = paystackForm.querySelector('button');
    const paymentAmountInputs = document.querySelectorAll('.payment-amount-input');
    const manualAmountInput = document.getElementById('manual_amount');
    const tabs = document.querySelectorAll('.payment-method-tab');
    const contents = document.querySelectorAll('.payment-method-content');
    const pricingPlanButtons = document.querySelectorAll('.pricing-plan-btn');

    // VAT Calculation Elements
    const vatCalculationDiv = document.getElementById('vat-calculation');
    const subtotalDisplay = document.getElementById('subtotal-display');
    const vatDisplay = document.getElementById('vat-display');
    const totalDisplay = document.getElementById('total-display');
    const vatRate = vatCalculationDiv ? parseFloat(vatCalculationDiv.dataset.vatRate) : 0;

    const cardElement = document.querySelector('.card[data-currency-symbol]');
    const currencySymbol = cardElement ? cardElement.dataset.currencySymbol : '$';

    // Function to calculate and update all displayed costs
    function calculateCosts(amount) {
        const subtotal = parseFloat(amount) || 0;
        let totalAmount = subtotal;

        if (vatRate > 0 && subtotal > 0) {
            const vatAmount = subtotal * (vatRate / 100);
            totalAmount = subtotal + vatAmount;

            subtotalDisplay.textContent = `${currencySymbol}${subtotal.toFixed(2)}`;
            vatDisplay.textContent = `${currencySymbol}${vatAmount.toFixed(2)}`;
            totalDisplay.textContent = `${currencySymbol}${totalAmount.toFixed(2)}`;
            vatCalculationDiv.style.display = 'block';
        } else if (vatCalculationDiv) {
            vatCalculationDiv.style.display = 'none';
        }

        // Enable/disable the paystack button
        paystackButton.disabled = totalAmount <= 0;

        // Sync the TOTAL amount to hidden inputs in other forms
        paymentAmountInputs.forEach(input => {
            input.value = totalAmount.toFixed(2);
        });

        // Sync the subtotal amount to the manual amount input
        if (manualAmountInput) {
            manualAmountInput.value = subtotal.toFixed(2);
        }
    }

    // Event listener for the main amount input field
    amountInput.addEventListener('input', function() {
        calculateCosts(this.value);
    });

    // Event listeners for the pricing plan buttons
    pricingPlanButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const price = this.dataset.price;
            amountInput.value = parseFloat(price).toFixed(2);
            calculateCosts(price);
        });
    });

    // Event listeners for tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            // Deactivate all
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // Activate clicked tab and its corresponding content
            this.classList.add('active');
            document.querySelector(this.dataset.target).classList.add('active');
        });
    });
});
