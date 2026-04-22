import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, Alert, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Illustrations from '../components/Illustrations';
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
                Alert.alert('Success', 'Registration successful! You can now login.', [
                    { text: 'OK', onPress: () => navigation.replace('Login') }
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
            <KeyboardAvoidingView 
                behavior={Platform.OS === "ios" ? "padding" : "height"}
                style={{ flex: 1 }}
            >
                <ScrollView contentContainerStyle={styles.scroll}>
                    <View style={styles.header}>
                        <View style={styles.headerCircle} />
                        <Illustrations name="welcome" size={180} style={styles.illustration} />
                    </View>

                    <View style={styles.content}>
                        <Text style={styles.title}>Create Account</Text>
                        <Text style={styles.subtitle}>Join Philmore SMS and start reaching your audience today.</Text>

                        <FintechInput
                            label="Username"
                            value={username}
                            onChangeText={setUsername}
                            placeholder="Choose a username"
                        />

                        <FintechInput
                            label="Email Address"
                            value={email}
                            onChangeText={setEmail}
                            placeholder="e.g. name@example.com"
                            keyboardType="email-address"
                        />

                        <FintechInput
                            label="Phone Number"
                            value={phone}
                            onChangeText={setPhone}
                            placeholder="e.g. 234..."
                            keyboardType="phone-pad"
                        />

                        <FintechInput
                            label="Account Password"
                            value={password}
                            onChangeText={setPassword}
                            placeholder="Create a strong password"
                            secureTextEntry
                        />

                        <FintechButton
                            title={loading ? "Creating Account..." : "Register"}
                            onPress={handleRegister}
                            style={styles.btn}
                        />

                        <TouchableOpacity
                            style={styles.loginLink}
                            onPress={() => navigation.goBack()}
                        >
                            <Text style={styles.loginText}>
                                Already have an account? <Text style={styles.loginAction}>Login</Text>
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
        height: 240,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
    },
    headerCircle: {
        position: 'absolute',
        bottom: -50,
        left: -50,
        width: 200,
        height: 200,
        borderRadius: 100,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    illustration: {
        marginTop: 20,
    },
    content: {
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
    btn: {
        marginTop: 10,
        borderRadius: 16,
    },
    loginLink: {
        marginTop: 32,
        marginBottom: 20,
        alignItems: 'center',
    },
    loginText: {
        color: colors.textSecondary,
        fontSize: 14,
    },
    loginAction: {
        color: colors.primary,
        fontWeight: '700',
    }
});

export default RegisterScreen;
