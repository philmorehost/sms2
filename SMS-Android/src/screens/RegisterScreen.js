import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, Alert, ScrollView } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import authService from '../services/authService';

const RegisterScreen = ({ navigation }) => {
    const [username, setUsername] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [phone, setPhone] = useState('');
    const [loading, setLoading] = useState(false);

    const handleRegister = async () => {
        if (!username || !email || !password) {
            Alert.alert('Error', 'Please fill in all required fields');
            return;
        }

        setLoading(true);
        try {
            const response = await authService.register({ username, email, password, phone });
            if (response.status === 'success') {
                Alert.alert('Success', 'Registration successful', [
                    { text: 'OK', onPress: () => navigation.replace('Main') }
                ]);
            } else {
                Alert.alert('Registration Failed', response.message);
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>Create Account</Text>
                <Text style={styles.subtitle}>Join our fintech platform</Text>

                <FintechInput
                    label="Username"
                    value={username}
                    onChangeText={setUsername}
                    placeholder="Choose a username"
                />

                <FintechInput
                    label="Email"
                    value={email}
                    onChangeText={setEmail}
                    placeholder="Enter your email"
                    keyboardType="email-address"
                />

                <FintechInput
                    label="Phone Number"
                    value={phone}
                    onChangeText={setPhone}
                    placeholder="Enter your phone number"
                    keyboardType="phone-pad"
                />

                <FintechInput
                    label="Password"
                    value={password}
                    onChangeText={setPassword}
                    placeholder="Choose a strong password"
                    secureTextEntry
                />

                <FintechButton
                    title={loading ? "Creating Account..." : "Register"}
                    onPress={handleRegister}
                />

                <TouchableOpacity
                    style={styles.loginLink}
                    onPress={() => navigation.goBack()}
                >
                    <Text style={styles.loginText}>
                        Already have an account? <Text style={styles.loginAction}>Login</Text>
                    </Text>
                </TouchableOpacity>
            </ScrollView>
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
    loginLink: {
        marginTop: 24,
        alignItems: 'center',
    },
    loginText: {
        color: colors.text,
        fontSize: 14,
    },
    loginAction: {
        color: colors.primary,
        fontWeight: '600',
    }
});

export default RegisterScreen;
