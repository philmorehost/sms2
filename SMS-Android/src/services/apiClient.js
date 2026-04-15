// SMS-Android/src/services/apiClient.js
// Default to empty, should be set during app initialization or via environment variables
let BASE_URL = 'https://yourdomain.com/web/api';

let userToken = null;

export const setBaseUrl = (url) => {
    BASE_URL = url;
};

export const setToken = (token) => {
    userToken = token;
};

const apiClient = async (endpoint, options = {}) => {
    const url = `${BASE_URL}${endpoint}`;
    const headers = {
        'Content-Type': 'application/x-www-form-urlencoded',
        ...(userToken && { 'Authorization': `Bearer ${userToken}` }),
        ...options.headers,
    };

    const config = {
        ...options,
        headers,
    };

    try {
        const response = await fetch(url, config);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
};

export default apiClient;
