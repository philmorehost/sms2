// SMS-Android/src/services/phonebookService.js
import apiClient from './apiClient';

const phonebookService = {
    listGroups: async () => {
        return await apiClient('/phonebook.php?action=list_groups');
    },

    addGroup: async (groupName) => {
        const body = new URLSearchParams();
        body.append('group_name', groupName);
        return await apiClient('/phonebook.php?action=add_group', {
            method: 'POST',
            body: body.toString(),
        });
    },

    deleteGroup: async (groupId) => {
        const body = new URLSearchParams();
        body.append('group_id', groupId);
        return await apiClient('/phonebook.php?action=delete_group', {
            method: 'POST',
            body: body.toString(),
        });
    },

    listContacts: async (groupId) => {
        return await apiClient(`/phonebook.php?action=list_contacts&group_id=${groupId}`);
    },

    addContact: async (groupId, firstName, lastName, phone) => {
        const body = new URLSearchParams();
        body.append('group_id', groupId);
        body.append('first_name', firstName);
        body.append('last_name', lastName);
        body.append('phone_number', phone);
        return await apiClient('/phonebook.php?action=add_contact', {
            method: 'POST',
            body: body.toString(),
        });
    },

    deleteContact: async (contactId) => {
        const body = new URLSearchParams();
        body.append('contact_id', contactId);
        return await apiClient('/phonebook.php?action=delete_contact', {
            method: 'POST',
            body: body.toString(),
        });
    }
};

export default phonebookService;
