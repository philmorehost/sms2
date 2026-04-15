// SMS-iOS/src/services/dashboardService.js
import apiClient from './apiClient';

const dashboardService = {
    getSummary: async () => {
        return await apiClient('/dashboard.php');
    }
};

export default dashboardService;
