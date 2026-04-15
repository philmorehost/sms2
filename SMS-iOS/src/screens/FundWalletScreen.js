import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, Linking } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import paymentService from '../services/paymentService';

const FundWalletScreen = () => {
    const [amount, setAmount] = useState('');
    const [reference, setReference] = useState('');
    const [settings, setSettings] = useState(null);
    const [loading, setLoading] = useState(false);
    const [method, setMethod] = useState('manual'); // 'manual' or 'online'

    useEffect(() => {
        const fetchSettings = async () => {
            const res = await paymentService.getSettings();
            if (res.status === 'success') setSettings(res);
        };
        fetchSettings();
    }, []);

    const handleSubmitManual = async () => {
        if (!amount || !reference) {
            Alert.alert('Error', 'Please enter amount and reference');
            return;
        }
        setLoading(true);
        try {
            const res = await paymentService.submitManual(amount, reference);
            if (res.status === 'success') {
                Alert.alert('Success', res.message);
                setAmount('');
                setReference('');
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    if (!settings) return null;

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>Fund Wallet</Text>

                <FintechInput
                    label="Amount to Deposit"
                    value={amount}
                    onChangeText={setAmount}
                    placeholder="Enter amount"
                    keyboardType="numeric"
                />

                <View style={styles.tabHeader}>
                    <TouchableOpacity onPress={() => setMethod('manual')} style={[styles.tab, method === 'manual' && styles.activeTab]}>
                        <Text style={[styles.tabTitle, method === 'manual' && styles.activeTabText]}>Manual</Text>
                    </TouchableOpacity>
                    <TouchableOpacity onPress={() => setMethod('online')} style={[styles.tab, method === 'online' && styles.activeTab]}>
                        <Text style={[styles.tabTitle, method === 'online' && styles.activeTabText]}>Pay Online</Text>
                    </TouchableOpacity>
                </View>

                {method === 'manual' ? (
                    <>
                <FintechCard>
                    <Text style={styles.bankLabel}>Bank Name</Text>
                    <Text style={styles.bankValue}>{settings.manual_payment.bank_name}</Text>
                    <View style={styles.spacer} />
                    <Text style={styles.bankLabel}>Account Name</Text>
                    <Text style={styles.bankValue}>{settings.manual_payment.account_name}</Text>
                    <View style={styles.spacer} />
                    <Text style={styles.bankLabel}>Account Number</Text>
                    <Text style={styles.bankValue}>{settings.manual_payment.account_number}</Text>
                </FintechCard>

                <Text style={styles.instrTitle}>Instructions:</Text>
                <Text style={styles.instrText}>{settings.manual_payment.instructions}</Text>

                <FintechInput
                    label="Transaction Reference"
                    value={reference}
                    onChangeText={setReference}
                    placeholder="Enter payment reference"
                />

                <FintechButton
                    title={loading ? "Submitting..." : "Submit Proof of Payment"}
                    onPress={handleSubmitManual}
                />
                </>
                ) : (
                    <View style={styles.onlineContainer}>
                        <Text style={styles.onlineText}>To pay online with your card or bank account, please use our secure web checkout.</Text>
                        <FintechButton
                            title="Go to Web Checkout"
                            type="success"
                            onPress={() => Linking.openURL('https://app.philmoresms.com/add-funds.php')}
                        />
                    </View>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text, marginBottom: 24 },
    tabHeader: { flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: colors.border, marginBottom: 16 },
    tab: { paddingVertical: 8, paddingHorizontal: 16, marginRight: 8 },
    activeTab: { borderBottomWidth: 2, borderBottomColor: colors.primary },
    tabTitle: { fontSize: 16, fontWeight: '600', color: colors.textLight },
    activeTabText: { color: colors.primary },
    bankLabel: { fontSize: 12, color: colors.textLight, textTransform: 'uppercase' },
    bankValue: { fontSize: 16, fontWeight: '700', color: colors.text, marginTop: 4 },
    spacer: { height: 12 },
    instrTitle: { fontSize: 14, fontWeight: '700', color: colors.text, marginTop: 16, marginBottom: 4 },
    instrText: { fontSize: 14, color: colors.textLight, marginBottom: 24, lineHeight: 20 },
    onlineContainer: { padding: 20, alignItems: 'center' },
    onlineText: { fontSize: 16, textAlign: 'center', color: colors.text, marginBottom: 24 }
});

export default FundWalletScreen;
