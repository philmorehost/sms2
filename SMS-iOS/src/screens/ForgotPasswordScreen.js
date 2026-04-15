import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, Alert, TouchableOpacity } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
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
            <View style={styles.content}>
                <Text style={styles.title}>Reset Password</Text>
                {step === 1 ? (
                    <>
                        <Text style={styles.subtitle}>Enter your email to receive a reset code</Text>
                        <FintechInput
                            label="Email"
                            value={email}
                            onChangeText={setEmail}
                            placeholder="Enter your email"
                            keyboardType="email-address"
                        />
                        <FintechButton
                            title={loading ? "Sending..." : "Send Reset Code"}
                            onPress={handleRequestOtp}
                        />
                    </>
                ) : (
                    <>
                        <Text style={styles.subtitle}>Enter the code sent to {email} and your new password</Text>
                        <FintechInput
                            label="OTP Code"
                            value={otp}
                            onChangeText={setOtp}
                            placeholder="6-digit code"
                            keyboardType="numeric"
                        />
                        <FintechInput
                            label="New Password"
                            value={password}
                            onChangeText={setPassword}
                            placeholder="Enter new password"
                            secureTextEntry
                        />
                        <FintechButton
                            title={loading ? "Resetting..." : "Reset Password"}
                            onPress={handleResetPassword}
                        />
                    </>
                )}
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Text style={styles.backText}>Back to Login</Text>
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24, flex: 1, justifyContent: 'center' },
    title: { fontSize: 28, fontWeight: '700', color: colors.primary, marginBottom: 8 },
    subtitle: { fontSize: 16, color: colors.textLight, marginBottom: 32 },
    backBtn: { marginTop: 24, alignItems: 'center' },
    backText: { color: colors.primary, fontWeight: '600' }
});

export default ForgotPasswordScreen;
