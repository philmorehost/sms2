import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const GlobalWalletScreen = () => {
    const [data, setData] = useState(null);
    const [convertAmount, setConvertAmount] = useState('');
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        const res = await apiClient('/payment.php?action=global_settings');
        if (res.status === 'success') setData(res);
    };

    useEffect(() => { fetchData(); }, []);

    const handleConvert = async () => {
        if (!convertAmount) return;
        setLoading(true);
        try {
            const body = new URLSearchParams();
            body.append('amount', convertAmount);
            const res = await apiClient('/payment.php?action=convert', {
                method: 'POST',
                body: body.toString()
            });
            if (res.status === 'success') {
                Alert.alert('Success', 'Funds converted successfully');
                setConvertAmount('');
                fetchData();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Conversion failed');
        } finally {
            setLoading(false);
        }
    };

    if (!data) return null;

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>Global Wallet</Text>

                <View style={styles.balanceCard}>
                    <Text style={styles.balanceLabel}>Global Balance</Text>
                    <Text style={styles.balanceAmount}>{data.global_balance.toLocaleString()} {data.currency}</Text>
                </View>

                <Text style={styles.sectionTitle}>Convert from Main Wallet</Text>
                <FintechCard>
                    <Text style={styles.infoText}>Main Balance: ₦{data.main_balance.toLocaleString()}</Text>
                    <Text style={styles.infoText}>Rate: 1 NGN = {data.conversion_rate} {data.currency}</Text>
                    <FintechInput
                        label="Amount to Convert (NGN)"
                        value={convertAmount}
                        onChangeText={setConvertAmount}
                        placeholder="Enter NGN amount"
                        keyboardType="numeric"
                    />
                    <FintechButton title={loading ? "Converting..." : "Convert Now"} onPress={handleConvert} />
                </FintechCard>

                <Text style={styles.sectionTitle}>Crypto Deposit Addresses</Text>
                {data.crypto_methods.map(m => (
                    <FintechCard key={m.id} style={styles.cryptoCard}>
                        <Text style={styles.cryptoName}>{m.name}</Text>
                        <Text style={styles.cryptoAddr}>{m.address}</Text>
                        {m.network && <Text style={styles.cryptoNet}>Network: {m.network}</Text>}
                    </FintechCard>
                ))}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text, marginBottom: 24 },
    balanceCard: { backgroundColor: colors.info, padding: 24, borderRadius: 20, marginBottom: 24, alignItems: 'center' },
    balanceLabel: { color: 'rgba(255,255,255,0.8)', fontSize: 14, marginBottom: 4 },
    balanceAmount: { color: colors.white, fontSize: 28, fontWeight: '700' },
    sectionTitle: { fontSize: 18, fontWeight: '700', color: colors.text, marginTop: 16, marginBottom: 12 },
    infoText: { fontSize: 14, color: colors.textLight, marginBottom: 8 },
    cryptoCard: { marginBottom: 12 },
    cryptoName: { fontSize: 16, fontWeight: '700', color: colors.primary, marginBottom: 4 },
    cryptoAddr: { fontSize: 13, color: colors.text, fontWeight: '600' },
    cryptoNet: { fontSize: 11, color: colors.textLight, marginTop: 4 }
});

export default GlobalWalletScreen;
