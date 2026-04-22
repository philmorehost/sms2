// SMS-Android/src/services/schedulerService.js
import apiClient from './apiClient';

const schedulerService = {
    listSchedules: async () => {
        return await apiClient('/scheduler.php?action=list_schedules');
    },

    scheduleSms: async (senderID, recipients, message, scheduleTime, route = 'promotional') => {
        const body = new URLSearchParams();
        body.append('senderID', senderID);
        body.append('recipients', recipients);
        body.append('message', message);
        body.append('schedule_time', scheduleTime);
        body.append('route', route);

        return await apiClient('/scheduler.php?action=schedule_sms', {
            method: 'POST',
            body: body.toString(),
        });
    },

    cancelSchedule: async (scheduleId) => {
        const body = new URLSearchParams();
        body.append('id', scheduleId);
        return await apiClient('/scheduler.php?action=cancel_schedule', {
            method: 'POST',
            body: body.toString(),
        });
    },

    listBirthdays: async () => {
        return await apiClient('/scheduler.php?action=list_birthdays');
    },

    addBirthday: async (name, phone, dob) => {
        const body = new URLSearchParams();
        body.append('name', name);
        body.append('phone', phone);
        body.append('dob', dob);
        return await apiClient('/scheduler.php?action=add_birthday', {
            method: 'POST',
            body: body.toString(),
        });
    },

    deleteBirthday: async (id) => {
        const body = new URLSearchParams();
        body.append('id', id);
        return await apiClient('/scheduler.php?action=delete_birthday', {
            method: 'POST',
            body: body.toString(),
        });
    }
};

export default schedulerService;
