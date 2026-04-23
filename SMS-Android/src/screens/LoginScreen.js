import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, Alert, KeyboardAvoidingView, Platform, ScrollView, Image } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import authService from '../services/authService';
import biometricService from '../services/biometricService';
import Illustrations from '../components/Illustrations';
import { loginStyles as styles } from '../theme/LoginStyles';

const logo = require('../assets/images/logo.png');

const LoginScreen = ({ navigation }) => {
    const [login, setLogin] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [isBiometricAvailable, setIsBiometricAvailable] = useState(false);
    const [biometricType, setBiometricType] = useState(null);
    const [isBiometricEnabled, setIsBiometricEnabled] = useState(false);

    React.useEffect(() => {
        checkBiometrics();
    }, []);

    const checkBiometrics = async () => {
        const { available, biometryType } = await biometricService.isSensorAvailable();
        const enabled = await biometricService.isEnabled();
        setIsBiometricAvailable(available);
        setBiometricType(biometryType);
        setIsBiometricEnabled(enabled);

        // Auto-prompt if enabled
        if (available && enabled) {
            handleBiometricLogin();
        }
    };

    const handleBiometricLogin = async () => {
        const credentials = await biometricService.getStoredCredentials();
        if (credentials) {
            setLoading(true);
            try {
                const response = await authService.login(credentials.username, credentials.password);
                if (response.status === 'success') {
                    navigation.replace('Main');
                } else {
                    Alert.alert('Login Failed', response.message);
                }
            } catch (error) {
                Alert.alert('Error', error.message || 'An unexpected error occurred');
            } finally {
                setLoading(false);
            }
        }
    };

    const handleLogin = async () => {
        if (!login || !password) {
            Alert.alert('Error', 'Please enter login and password');
            return;
        }

        setLoading(true);
        try {
            const response = await authService.login(login, password);
            if (response.status === 'success') {
                // If login success and biometrics not enabled, ask to enable
                if (isBiometricAvailable && !isBiometricEnabled) {
                    Alert.alert(
                        'Enable Biometric Login',
                        'Would you like to use your fingerprint or device lock for faster login next time?',
                        [
                            { text: 'No', onPress: () => navigation.replace('Main') },
                            { 
                                text: 'Yes, Enable', 
                                onPress: async () => {
                                    await biometricService.enableBiometrics(login, password);
                                    navigation.replace('Main');
                                }
                            }
                        ]
                    );
                } else {
                    navigation.replace('Main');
                }
            } else {
                Alert.alert('Login Failed', response.message);
            }
        } catch (error) {
            Alert.alert('Error', error.message || 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <KeyboardAvoidingView 
                behavior={Platform.OS === "ios" ? "padding" : "height"}
                style={{ flex: 1 }}
            >
                <ScrollView contentContainerStyle={styles.scroll}>
                    <View style={styles.header}>
                        <View style={styles.headerCircle} />
                        <View style={styles.logoContainer}>
                            <Image source={logo} style={styles.logo} resizeMode="contain" />
                        </View>
                    </View>

                    <View style={styles.content}>
                        <Text style={styles.title}>Welcome Back</Text>
                        <Text style={styles.subtitle}>Sign in to your account to continue broadcasting.</Text>

                        <FintechInput
                            label="Username or Email"
                            value={login}
                            onChangeText={setLogin}
                            placeholder="Enter your login"
                        />

                        <FintechInput
                            label="Password"
                            value={password}
                            onChangeText={setPassword}
                            placeholder="Enter your password"
                            secureTextEntry
                        />

                        <TouchableOpacity
                            style={styles.forgotLink}
                            onPress={() => navigation.navigate('ForgotPassword')}
                        >
                            <Text style={styles.forgotText}>Forgot Password?</Text>
                        </TouchableOpacity>

                        <FintechButton
                            title={loading ? "Verifying..." : "Login"}
                            onPress={handleLogin}
                            style={styles.loginBtn}
                        />

                        {isBiometricAvailable && isBiometricEnabled && (
                            <TouchableOpacity 
                                style={styles.biometricBtn} 
                                onPress={handleBiometricLogin}
                            >
                                <Illustrations name="fingerprint" size={60} />
                                <Text style={styles.biometricText}>Login with Biometrics</Text>
                            </TouchableOpacity>
                        )}

                        <TouchableOpacity
                            style={styles.registerLink}
                            onPress={() => navigation.navigate('Register')}
                        >
                            <Text style={styles.registerText}>
                                New to Philmore? <Text style={styles.registerAction}>Create Account</Text>
                            </Text>
                        </TouchableOpacity>
                    </View>
                </ScrollView>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

export default LoginScreen;
