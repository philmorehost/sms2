import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, Alert, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Illustrations from '../components/Illustrations';
import { colors } from '../theme/colors';
import authService from '../services/authService';

const ForgotPasswordScreen = ({ navigation }) => {
    const [email, setEmail] = useState('');
    const [otp, setOtp] = useState('');
    const [password, setPassword] = useState('');
    const [step, setStep] = useState(1); // 1: Email, 2: OTP & New Pass
    const [loading, setLoading] = useState(false);

    const handleRequestOtp = async () => {
        if (!email) {
            Alert.alert('Error', 'Please enter your email');
            return;
        }
        setLoading(true);
        try {
            const res = await authService.forgotPassword(email);
            if (res.status === 'success') {
                Alert.alert('Success', res.message);
                setStep(2);
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    const handleResetPassword = async () => {
        if (!otp || !password) {
            Alert.alert('Error', 'Please enter the OTP and new password');
            return;
        }
        setLoading(true);
        try {
            const res = await authService.resetPassword(email, otp, password);
            if (res.status === 'success') {
                Alert.alert('Success', res.message, [
                    { text: 'OK', onPress: () => navigation.navigate('Login') }
                ]);
            } else {
                Alert.alert('Error', res.message);
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
                        <Illustrations name="empty" size={180} style={styles.illustration} />
                    </View>

                    <View style={styles.content}>
                        <Text style={styles.title}>Reset Password</Text>
                        
                        {step === 1 ? (
                            <>
                                <Text style={styles.subtitle}>Don't worry, it happens. Enter your email to receive a secure reset code.</Text>
                                <FintechInput
                                    label="Registered Email"
                                    value={email}
                                    onChangeText={setEmail}
                                    placeholder="Enter your email address"
                                    keyboardType="email-address"
                                />
                                <FintechButton
                                    title={loading ? "Sending Code..." : "Send Reset Code"}
                                    onPress={handleRequestOtp}
                                    style={styles.btn}
                                />
                            </>
                        ) : (
                            <>
                                <Text style={styles.subtitle}>Enter the secure code sent to {email} and choose a new password.</Text>
                                <FintechInput
                                    label="Security Code"
                                    value={otp}
                                    onChangeText={setOtp}
                                    placeholder="Enter 6-digit code"
                                    keyboardType="numeric"
                                />
                                <FintechInput
                                    label="New Password"
                                    value={password}
                                    onChangeText={setPassword}
                                    placeholder="Create a new password"
                                    secureTextEntry
                                />
                                <FintechButton
                                    title={loading ? "Resetting..." : "Reset Password"}
                                    onPress={handleResetPassword}
                                    style={styles.btn}
                                />
                            </>
                        )}

                        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                            <Text style={styles.backText}>Return to Login</Text>
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
        top: -40,
        left: -40,
        width: 180,
        height: 180,
        borderRadius: 90,
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
    backBtn: {
        marginTop: 32,
        alignItems: 'center',
    },
    backText: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 14,
    }
});

export default ForgotPasswordScreen;
