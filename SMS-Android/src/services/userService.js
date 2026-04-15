// SMS-Android/src/services/userService.js
import apiClient, { setToken } from './apiClient';

const userService = {
    getProfile: async () => {
        return await apiClient('/user.php');
    },
    logout: () => {
        setToken(null);
    }
};

export default userService;
