// SMS-Android/src/services/biometricService.js
import ReactNativeBiometrics from 'react-native-biometrics';
import * as Keychain from 'react-native-keychain';

const rnBiometrics = new ReactNativeBiometrics();

const biometricService = {
    // Check if biometric authentication is available on the device
    isSensorAvailable: async () => {
        try {
            const { available, biometryType } = await rnBiometrics.isSensorAvailable();
            return { available, biometryType };
        } catch (error) {
            console.error('Biometric sensor check failed:', error);
            return { available: false };
        }
    },

    // Securely store credentials after first successful login
    enableBiometrics: async (username, password) => {
        try {
            await Keychain.setGenericPassword(username, password, {
                service: 'com.philmoresms.auth',
                accessControl: Keychain.ACCESS_CONTROL.BIOMETRY_ANY,
                accessible: Keychain.ACCESSIBLE.WHEN_PASSCODE_SET_THIS_DEVICE_ONLY
            });
            return true;
        } catch (error) {
            console.error('Failed to store credentials for biometrics:', error);
            return false;
        }
    },

    // Retrieve credentials using biometrics
    getStoredCredentials: async () => {
        try {
            const credentials = await Keychain.getGenericPassword({
                service: 'com.philmoresms.auth',
                authenticationPrompt: {
                    title: 'Authentication Required',
                    subtitle: 'Login with your thumbprint or device credential',
                    description: 'PhilmoreSMS needs to verify your identity',
                    cancel: 'Cancel',
                }
            });

            if (credentials) {
                return { username: credentials.username, password: credentials.password };
            }
            return null;
        } catch (error) {
            console.error('Biometric authentication failed or canceled:', error);
            return null;
        }
    },

    // Clear stored credentials (e.g., on logout or when disabling biometrics)
    disableBiometrics: async () => {
        try {
            await Keychain.resetGenericPassword({ service: 'com.philmoresms.auth' });
            return true;
        } catch (error) {
            console.error('Failed to clear biometric credentials:', error);
            return false;
        }
    },

    // Check if biometrics is already enabled (credentials exist)
    isEnabled: async () => {
        try {
            const credentials = await Keychain.getGenericPassword({ service: 'com.philmoresms.auth' });
            return !!credentials;
        } catch (error) {
            return false;
        }
    }
};

export default biometricService;
