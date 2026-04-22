// SMS-Android/src/services/senderService.js
import apiClient from './apiClient';

const senderService = {
    list: async () => {
        return await apiClient('/sender-ids.php?action=list');
    },
    request: async (senderID, message) => {
        const body = new URLSearchParams();
        body.append('senderID', senderID);
        body.append('message', message);

        return await apiClient('/sender-ids.php?action=request', {
            method: 'POST',
            body: body.toString(),
        });
    }
};

export default senderService;
