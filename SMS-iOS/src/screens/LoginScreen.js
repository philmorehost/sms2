import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, Alert } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import authService from '../services/authService';

const LoginScreen = ({ navigation }) => {
    const [login, setLogin] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);

    const handleLogin = async () => {
        if (!login || !password) {
            Alert.alert('Error', 'Please enter login and password');
            return;
        }

        setLoading(true);
        try {
            const response = await authService.login(login, password);
            if (response.status === 'success') {
                navigation.replace('Main');
            } else {
                Alert.alert('Login Failed', response.message);
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.content}>
                <Text style={styles.title}>Welcome Back</Text>
                <Text style={styles.subtitle}>Sign in to continue</Text>

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

                <FintechButton
                    title={loading ? "Logging in..." : "Login"}
                    onPress={handleLogin}
                />

                <TouchableOpacity
                    style={styles.registerLink}
                    onPress={() => navigation.navigate('Register')}
                >
                    <Text style={styles.registerText}>
                        Don't have an account? <Text style={styles.registerAction}>Register</Text>
                    </Text>
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    content: {
        padding: 24,
        justifyContent: 'center',
        flex: 1,
    },
    title: {
        fontSize: typography.h1.fontSize,
        fontWeight: typography.h1.fontWeight,
        color: colors.primary,
        marginBottom: 8,
    },
    subtitle: {
        fontSize: typography.body.fontSize,
        color: colors.textLight,
        marginBottom: 32,
    },
    registerLink: {
        marginTop: 24,
        alignItems: 'center',
    },
    registerText: {
        color: colors.text,
        fontSize: 14,
    },
    registerAction: {
        color: colors.primary,
        fontWeight: '600',
    }
});

export default LoginScreen;
