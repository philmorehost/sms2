// SMS-Android/src/services/authService.js
import apiClient, { setToken } from './apiClient';

const authService = {
    login: async (login, password) => {
        const body = new URLSearchParams();
        body.append('login', login);
        body.append('password', password);

        const response = await apiClient('/auth.php?action=login', {
            method: 'POST',
            body: body.toString(),
        });

        if (response.status === 'success') {
            setToken(response.token);
        }
        return response;
    },

    register: async ({ username, email, password, phone }) => {
        const body = new URLSearchParams();
        body.append('username', username);
        body.append('email', email);
        body.append('password', password);
        body.append('phone', phone);

        const response = await apiClient('/auth.php?action=register', {
            method: 'POST',
            body: body.toString(),
        });

        if (response.status === 'success') {
            setToken(response.token);
        }
        return response;
    },

    forgotPassword: async (email) => {
        const body = new URLSearchParams();
        body.append('email', email);
        return await apiClient('/auth.php?action=forgot_password', {
            method: 'POST',
            body: body.toString(),
        });
    },

    resetPassword: async (email, otp, password) => {
        const body = new URLSearchParams();
        body.append('email', email);
        body.append('otp', otp);
        body.append('password', password);
        return await apiClient('/auth.php?action=reset_password', {
            method: 'POST',
            body: body.toString(),
        });
    }
};

export default authService;
