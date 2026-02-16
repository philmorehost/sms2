document.addEventListener('DOMContentLoaded', function() {
    const mainCard = document.querySelector('.card[data-invoice-amount]');
    const amountInput = document.getElementById('amount');
    const manualAmountInput = document.getElementById('manual_amount');
    const paystackForm = document.getElementById('paystack-form');
    const paystackButton = paystackForm ? paystackForm.querySelector('button[name="process_payment"]') : null;
    const paystackAmountInput = paystackForm ? paystackForm.querySelector('input[name="amount"]') : null;

    const vatCalculationDiv = document.getElementById('vat-calculation');
    const subtotalDisplay = document.getElementById('subtotal-display');
    const vatDisplay = document.getElementById('vat-display');
    const totalDisplay = document.getElementById('total-display');

    const planButtons = document.querySelectorAll('.pricing-plan-btn');
    const paymentTabs = document.querySelectorAll('.payment-method-tab');
    const paymentContents = document.querySelectorAll('.payment-method-content');

    // --- Main Function to Update UI Based on Amount ---
    function updatePaymentUI() {
        const amount = parseFloat(amountInput.value) || 0;

        // Update hidden input for Paystack form
        if (paystackAmountInput) {
            paystackAmountInput.value = amount;
        }

        // Enable/disable payment button
        if (paystackButton) {
            paystackButton.disabled = amount <= 0;
        }

        // Calculate and display VAT if applicable
        if (vatCalculationDiv) {
            const vatRate = parseFloat(vatCalculationDiv.dataset.vatRate) / 100;
            if (amount > 0 && vatRate > 0) {
                const currencySymbol = vatCalculationDiv.dataset.currencySymbol;
                const subtotal = amount;
                const vatAmount = subtotal * vatRate;
                const totalAmount = subtotal + vatAmount;

                subtotalDisplay.textContent = `${currencySymbol}${subtotal.toFixed(2)}`;
                vatDisplay.textContent = `${currencySymbol}${vatAmount.toFixed(2)}`;
                totalDisplay.textContent = `${currencySymbol}${totalAmount.toFixed(2)}`;
                vatCalculationDiv.style.display = 'block';
            } else {
                vatCalculationDiv.style.display = 'none';
            }
        }
    }

    // --- Event Listeners ---
    if (amountInput) {
        amountInput.addEventListener('input', updatePaymentUI);
    }

    planButtons.forEach(button => {
        button.addEventListener('click', function() {
            const price = this.dataset.price;
            if (amountInput) {
                amountInput.value = price;
                // Manually trigger the input event
                amountInput.dispatchEvent(new Event('input'));
            }
            if (manualAmountInput) {
                manualAmountInput.value = price;
            }
        });
    });

    paymentTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Deactivate all tabs and content
            paymentTabs.forEach(t => t.classList.remove('active'));
            paymentContents.forEach(c => c.classList.remove('active'));

            // Activate the clicked tab and its content
            this.classList.add('active');
            const targetContent = document.querySelector(this.dataset.target);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });


    // --- Initial Page Load Logic ---

    // Check if we are paying for a specific invoice
    if (mainCard && mainCard.dataset.invoiceAmount) {
        const invoiceAmount = parseFloat(mainCard.dataset.invoiceAmount);
        if (invoiceAmount > 0) {
            amountInput.value = invoiceAmount;
            amountInput.readOnly = true; // Lock the input
            
            // Also update the manual amount input if it exists
            if (manualAmountInput) {
                manualAmountInput.value = invoiceAmount;
                manualAmountInput.readOnly = true;
            }

            // Trigger the UI update to calculate VAT and enable buttons
            updatePaymentUI();
        }
    }
});
