// SMS-Android/src/services/paymentService.js
import apiClient from './apiClient';

const paymentService = {
    getSettings: async () => {
        return await apiClient('/payment.php?action=settings');
    },
    submitManual: async (amount, reference) => {
        const body = new URLSearchParams();
        body.append('amount', amount);
        body.append('reference', reference);
        return await apiClient('/payment.php?action=submit_manual', {
            method: 'POST',
            body: body.toString(),
        });
    }
};

export default paymentService;
