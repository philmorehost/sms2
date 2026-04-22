import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity, Linking, ActivityIndicator } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import paymentService from '../services/paymentService';
import Clipboard from '@react-native-clipboard/clipboard';

const FundWalletScreen = () => {
    const [amount, setAmount] = useState('');
    const [reference, setReference] = useState('');
    const [settings, setSettings] = useState(null);
    const [loading, setLoading] = useState(false);
    const [fetching, setFetching] = useState(true);
    const [method, setMethod] = useState('manual'); // 'manual' or 'online'

    useEffect(() => {
        const fetchSettings = async () => {
            try {
                const res = await paymentService.getSettings();
                if (res.status === 'success') setSettings(res);
            } catch (error) {
                console.error(error);
            } finally {
                setFetching(false);
            }
        };
        fetchSettings();
    }, []);

    const handleCopy = (text) => {
        Clipboard.setString(text);
        Alert.alert('Copied', `${text} copied to clipboard`);
    };

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

    if (fetching) {
        return (
            <View style={styles.loader}>
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        );
    }

    if (!settings) return null;

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.scrollContent}>
                <View style={styles.header}>
                    <View style={styles.headerCircle} />
                    <View style={styles.headerContent}>
                        <Text style={styles.title}>Fund Wallet</Text>
                        <Text style={styles.subtitle}>Add balance to your account securely</Text>
                    </View>
                </View>

                <View style={styles.body}>
                    <FintechInput
                        label="Amount to Deposit"
                        value={amount}
                        onChangeText={setAmount}
                        placeholder="₦ 0.00"
                        keyboardType="numeric"
                        style={styles.amountInput}
                    />

                    <View style={styles.methodSelector}>
                        <TouchableOpacity 
                            onPress={() => setMethod('manual')} 
                            style={[styles.methodTab, method === 'manual' && styles.activeMethodTab]}
                        >
                            <Icon name="wallet" size={16} color={method === 'manual' ? colors.primary : colors.textSecondary} style={{ marginRight: 8 }} />
                            <Text style={[styles.methodText, method === 'manual' && styles.activeMethodText]}>Transfer</Text>
                        </TouchableOpacity>
                        <TouchableOpacity 
                            onPress={() => setMethod('online')} 
                            style={[styles.methodTab, method === 'online' && styles.activeMethodTab]}
                        >
                            <Icon name="global" size={16} color={method === 'online' ? colors.primary : colors.textSecondary} style={{ marginRight: 8 }} />
                            <Text style={[styles.methodText, method === 'online' && styles.activeMethodText]}>Online</Text>
                        </TouchableOpacity>
                    </View>

                    {method === 'manual' ? (
                        <View>
                            <FintechCard style={styles.bankCard}>
                                <View style={styles.bankHeader}>
                                    <View style={styles.bankIconBg}>
                                        <Icon name="wallet" size={24} color={colors.primary} />
                                    </View>
                                    <View>
                                        <Text style={styles.bankName}>{settings.manual_payment.bank_name}</Text>
                                        <Text style={styles.bankSub}>{settings.manual_payment.account_name}</Text>
                                    </View>
                                </View>
                                
                                <View style={styles.accountBox}>
                                    <Text style={styles.accountNumber}>{settings.manual_payment.account_number}</Text>
                                    <TouchableOpacity 
                                        style={styles.copyBtn} 
                                        onPress={() => handleCopy(settings.manual_payment.account_number)}
                                    >
                                        <Icon name="copy" size={16} color={colors.primary} />
                                        <Text style={styles.copyBtnText}>Copy</Text>
                                    </TouchableOpacity>
                                </View>
                            </FintechCard>

                            <View style={styles.infoBox}>
                                <View style={styles.infoIconBg}>
                                    <Icon name="support" size={18} color={colors.primary} />
                                </View>
                                <View style={{ flex: 1 }}>
                                    <Text style={styles.infoTitle}>Instructions</Text>
                                    <Text style={styles.infoText}>{settings.manual_payment.instructions}</Text>
                                </View>
                            </View>

                            <FintechInput
                                label="Transaction Reference / Session ID"
                                value={reference}
                                onChangeText={setReference}
                                placeholder="Paste your reference here"
                            />

                            <FintechButton
                                title={loading ? "Verifying..." : "Notify Admin"}
                                onPress={handleSubmitManual}
                                style={styles.submitBtn}
                            />
                        </View>
                    ) : (
                        <View style={styles.onlineBox}>
                            <View style={styles.illustrationBg}>
                                <Icon name="global" size={60} color={colors.primary} />
                            </View>
                            <Text style={styles.onlineTitle}>Secure Online Checkout</Text>
                            <Text style={styles.onlineSubtitle}>
                                Pay instantly with your Card or USSD for immediate wallet crediting.
                            </Text>
                            <FintechButton
                                title="Pay Instantly Now"
                                onPress={() => Linking.openURL('https://app.philmoresms.com/add-funds.php')}
                                style={styles.onlineBtn}
                            />
                        </View>
                    )}
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    loader: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: colors.background,
    },
    scrollContent: {
        flexGrow: 1,
    },
    header: {
        height: 180,
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
        justifyContent: 'center',
        paddingHorizontal: 24,
    },
    headerCircle: {
        position: 'absolute',
        bottom: -30,
        right: -30,
        width: 150,
        height: 150,
        borderRadius: 75,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        marginTop: 20,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: colors.white,
    },
    subtitle: {
        fontSize: 14,
        color: 'rgba(255,255,255,0.7)',
        marginTop: 4,
    },
    body: {
        padding: 24,
        marginTop: -30,
    },
    amountInput: {
        backgroundColor: colors.white,
        borderRadius: 20,
        paddingVertical: 12,
        elevation: 2,
    },
    methodSelector: {
        flexDirection: 'row',
        backgroundColor: colors.surfaceVariant,
        borderRadius: 16,
        padding: 6,
        marginVertical: 24,
    },
    methodTab: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingVertical: 12,
        borderRadius: 12,
    },
    activeMethodTab: {
        backgroundColor: colors.white,
        elevation: 2,
    },
    methodText: {
        fontSize: 14,
        fontWeight: '600',
        color: colors.textSecondary,
    },
    activeMethodText: {
        color: colors.primary,
        fontWeight: '700',
    },
    bankCard: {
        padding: 24,
        borderRadius: 24,
        marginBottom: 24,
    },
    bankHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 20,
    },
    bankIconBg: {
        width: 48,
        height: 48,
        borderRadius: 16,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 16,
    },
    bankName: {
        fontSize: 16,
        fontWeight: '800',
        color: colors.text,
    },
    bankSub: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 2,
    },
    accountBox: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        backgroundColor: colors.surfaceVariant,
        padding: 16,
        borderRadius: 16,
    },
    accountNumber: {
        fontSize: 20,
        fontWeight: '800',
        color: colors.primary,
        letterSpacing: 1,
    },
    copyBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.white,
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 10,
    },
    copyBtnText: {
        fontSize: 12,
        fontWeight: '700',
        color: colors.primary,
        marginLeft: 4,
    },
    infoBox: {
        flexDirection: 'row',
        backgroundColor: 'rgba(74, 144, 226, 0.05)',
        padding: 16,
        borderRadius: 20,
        marginBottom: 24,
        borderWidth: 1,
        borderColor: colors.primaryLight,
    },
    infoIconBg: {
        width: 32,
        height: 32,
        borderRadius: 16,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    infoTitle: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.primary,
        marginBottom: 4,
    },
    infoText: {
        fontSize: 13,
        color: colors.textSecondary,
        lineHeight: 18,
    },
    submitBtn: {
        marginTop: 10,
    },
    onlineBox: {
        alignItems: 'center',
        padding: 24,
        backgroundColor: colors.white,
        borderRadius: 24,
        elevation: 1,
    },
    illustrationBg: {
        width: 120,
        height: 120,
        borderRadius: 60,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 24,
    },
    onlineTitle: {
        fontSize: 20,
        fontWeight: '800',
        color: colors.text,
    },
    onlineSubtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        textAlign: 'center',
        lineHeight: 22,
        marginVertical: 16,
    },
    onlineBtn: {
        width: '100%',
        marginTop: 10,
    }
});

export default FundWalletScreen;
