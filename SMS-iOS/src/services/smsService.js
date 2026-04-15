// SMS-iOS/src/services/smsService.js
import apiClient from './apiClient';

const smsService = {
    sendSms: async (senderID, recipients, message, route = 'promotional') => {
        const body = new URLSearchParams();
        body.append('senderID', senderID);
        body.append('recipients', recipients);
        body.append('message', message);
        body.append('route', route);

        return await apiClient('/messaging.php?action=send_sms', {
            method: 'POST',
            body: body.toString(),
        });
    },

    sendVoice: async (callerID, recipients, message) => {
        const body = new URLSearchParams();
        body.append('callerID', callerID);
        body.append('recipients', recipients);
        body.append('message', message);

        return await apiClient('/messaging.php?action=send_voice', {
            method: 'POST',
            body: body.toString(),
        });
    }
};

export default smsService;
