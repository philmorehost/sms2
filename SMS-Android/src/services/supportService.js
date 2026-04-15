// SMS-Android/src/services/supportService.js
import apiClient from './apiClient';

const supportService = {
    list: async () => {
        return await apiClient('/support.php?action=list');
    },
    view: async (id) => {
        return await apiClient(`/support.php?action=view&id=${id}`);
    },
    create: async (subject, message) => {
        const body = new URLSearchParams();
        body.append('subject', subject);
        body.append('message', message);
        return await apiClient('/support.php?action=create', {
            method: 'POST',
            body: body.toString(),
        });
    },
    reply: async (id, message) => {
        const body = new URLSearchParams();
        body.append('id', id);
        body.append('message', message);
        return await apiClient('/support.php?action=reply', {
            method: 'POST',
            body: body.toString(),
        });
    }
};

export default supportService;
