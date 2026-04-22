// SMS-Android/src/services/apiClient.js
// Automatically configured for PhilmoreSMS
let BASE_URL = 'https://app.philmoresms.com/web/api';

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
        const responseText = await response.text();
        
        try {
            const data = JSON.parse(responseText);
            return data;
        } catch (parseError) {
            console.error('JSON Parse Error. Raw Response:', responseText);
            throw new Error(`Invalid server response: ${responseText.substring(0, 50)}...`);
        }
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
};

export default apiClient;
