import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, Alert, KeyboardAvoidingView, Platform, ScrollView, Image } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import authService from '../services/authService';
import biometricService from '../services/biometricService';
import Illustrations from '../components/Illustrations';

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

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    scroll: {
        flexGrow: 1,
    },
    header: {
        height: 280,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
    },
    headerCircle: {
        position: 'absolute',
        top: -50,
        right: -50,
        width: 180,
        height: 180,
        borderRadius: 90,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    logoContainer: {
        width: 140,
        height: 140,
        backgroundColor: colors.white,
        borderRadius: 70,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 10,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 5 },
        shadowOpacity: 0.2,
        shadowRadius: 10,
    },
    logo: {
        width: 100,
        height: 100,
    },
    content: {
        flex: 1,
        padding: 30,
        marginTop: -20,
        backgroundColor: colors.background,
        borderTopLeftRadius: 30,
        borderTopRightRadius: 30,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: colors.text,
        marginBottom: 8,
    },
    subtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        marginBottom: 32,
        lineHeight: 20,
    },
    forgotLink: {
        alignSelf: 'flex-end',
        marginBottom: 24,
        marginTop: -10,
    },
    forgotText: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 14,
    },
    loginBtn: {
        marginTop: 10,
        borderRadius: 16,
    },
    registerLink: {
        marginTop: 32,
        alignItems: 'center',
    },
    registerText: {
        color: colors.textSecondary,
        fontSize: 14,
    },
    registerAction: {
        color: colors.primary,
        fontWeight: '700',
    },
    biometricBtn: {
        marginTop: 30,
        alignItems: 'center',
        justifyContent: 'center',
    },
    biometricText: {
        marginTop: 10,
        color: colors.primary,
        fontWeight: '600',
        fontSize: 14,
    }
});

export default LoginScreen;
