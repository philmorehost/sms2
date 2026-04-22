import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, RefreshControl, TouchableOpacity, StatusBar } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import dashboardService from '../services/dashboardService';

const DashboardScreen = ({ navigation }) => {
    const [data, setData] = useState(null);
    const [username, setUsername] = useState('User');
    const [refreshing, setRefreshing] = useState(false);

    const fetchData = async () => {
        try {
            const response = await dashboardService.getSummary();
            if (response.status === 'success') {
                setData(response);
                setUsername(response.stats.username || 'User');
            }
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const onRefresh = React.useCallback(async () => {
        setRefreshing(true);
        await fetchData();
        setRefreshing(false);
    }, []);

    if (!data) return null;

    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="dark-content" backgroundColor={colors.background} />
            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
            >
                <View style={styles.header}>
                    <View>
                        <Text style={styles.greeting}>Good Day, 👋</Text>
                        <Text style={styles.username}>{username}</Text>
                    </View>
                    <TouchableOpacity onPress={() => navigation.navigate('Profile')} style={styles.profileBtn}>
                        <View style={styles.avatar}>
                            <Icon name="user" size={20} color={colors.white} />
                        </View>
                    </TouchableOpacity>
                </View>

                <View style={styles.walletCard}>
                    <View style={styles.walletInfo}>
                        <Text style={styles.walletLabel}>Total Balance</Text>
                        <Text style={styles.walletAmount}>₦{data.stats.balance.toLocaleString()}</Text>
                    </View>
                    <TouchableOpacity
                        style={styles.addFundsBtn}
                        onPress={() => navigation.navigate('FundWallet')}
                    >
                        <Icon name="plus" size={18} color={colors.primary} style={{ marginRight: 6 }} />
                        <Text style={styles.addFundsText}>Top Up</Text>
                    </TouchableOpacity>
                    <View style={styles.walletDecoration} />
                </View>

                <Text style={styles.sectionTitleMain}>Quick Services</Text>
                <View style={styles.servicesGrid}>
                    <ServiceItem icon="sms" label="Bulk SMS" onPress={() => navigation.navigate('Messaging', { type: 'sms' })} bg={colors.smsLight} iconColor={colors.sms} />
                    <ServiceItem icon="global" label="Global SMS" onPress={() => navigation.navigate('Messaging', { type: 'sms', route: 'global' })} bg={colors.globalLight} iconColor={colors.global} />
                    <ServiceItem icon="voice" label="Voice SMS" onPress={() => navigation.navigate('Messaging', { type: 'voice' })} bg={colors.voiceLight} iconColor={colors.voice} />
                    <ServiceItem icon="music" label="Voice File" onPress={() => navigation.navigate('Messaging', { type: 'voice_audio' })} bg="#FFF9C4" iconColor="#FBC02D" />
                    <ServiceItem icon="otp" label="OTP" onPress={() => navigation.navigate('OtpTemplates')} bg={colors.otpLight} iconColor={colors.otp} />
                    <ServiceItem icon="refer" label="Referral" onPress={() => navigation.navigate('Referral')} bg="#FCE4EC" iconColor="#EC407A" />
                    <ServiceItem icon="support" label="Support" onPress={() => navigation.navigate('Support')} bg={colors.infoLight} iconColor={colors.info} />
                    <ServiceItem icon="extractor" label="Extractor" onPress={() => navigation.navigate('NumberExtractor')} bg="#FFFDE7" iconColor="#FBC02D" />
                    <ServiceItem icon="filter" label="Filter" onPress={() => navigation.navigate('NumberFilter')} bg="#F1F8E9" iconColor="#7CB342" />
                    <ServiceItem icon="phonebook" label="Contacts" onPress={() => navigation.navigate('Phonebook')} bg="#EFEBE9" iconColor="#8D6E63" />
                    <ServiceItem icon="reports" label="Reports" onPress={() => navigation.navigate('Reports')} bg="#F3E5F5" iconColor="#AB47BC" />
                    <ServiceItem icon="register" label="Sender ID" onPress={() => navigation.navigate('RegisterId')} bg="#E8EAF6" iconColor="#3F51B5" />
                    <ServiceItem icon="wallet" label="G-Wallet" onPress={() => navigation.navigate('GlobalWallet')} bg="#E1F5FE" iconColor="#03A9F4" />
                    <ServiceItem icon="pricing" label="Pricing" onPress={() => navigation.navigate('Pricing')} bg="#FFF3E0" iconColor="#FB8C00" />
                    <ServiceItem icon="birthday" label="Birthday" onPress={() => navigation.navigate('BirthdayScheduler')} bg="#FCE4EC" iconColor="#EC407A" />
                    <ServiceItem icon="coverage" label="Coverage" onPress={() => navigation.navigate('GlobalCoverage')} bg="#E0F2F1" iconColor="#009688" />
                    <ServiceItem icon="schedules" label="Schedules" onPress={() => navigation.navigate('Schedules')} bg="#FFF8E1" iconColor="#FFB300" />
                </View>

                <View style={styles.sectionHeader}>
                    <Text style={styles.sectionTitle}>Recent Transactions</Text>
                    <TouchableOpacity onPress={() => navigation.navigate('Transactions')}>
                        <Text style={styles.seeAll}>History</Text>
                    </TouchableOpacity>
                </View>

                {data.recent_transactions.map((item) => (
                    <FintechCard key={item.id} style={styles.transactionItem}>
                        <View style={styles.transactionIconContainer}>
                            <Icon name={item.amount > 0 ? "plus" : "sms"} size={20} color={item.amount > 0 ? colors.success : colors.danger} />
                        </View>
                        <View style={styles.transactionInfo}>
                            <Text style={styles.transactionDesc} numberOfLines={1}>{item.description}</Text>
                            <Text style={styles.transactionDate}>{new Date(item.created_at).toLocaleDateString()}</Text>
                        </View>
                        <Text style={[styles.transactionAmount, { color: item.amount > 0 ? colors.success : colors.danger }]}>
                            {item.amount > 0 ? '+' : ''}₦{item.amount.toLocaleString()}
                        </Text>
                    </FintechCard>
                ))}
            </ScrollView>
        </SafeAreaView>
    );
};

const ServiceItem = ({ icon, label, onPress, bg, iconColor }) => (
    <TouchableOpacity style={styles.serviceItem} onPress={onPress}>
        <View style={[styles.serviceIcon, { backgroundColor: bg }]}>
            <Icon name={icon} size={28} color={iconColor} />
        </View>
        <Text style={styles.serviceLabel}>{label}</Text>
    </TouchableOpacity>
);

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    content: {
        padding: 20,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 30,
        marginTop: 10,
    },
    greeting: {
        fontSize: 14,
        color: colors.textSecondary,
        fontWeight: '500',
    },
    username: {
        fontSize: 22,
        fontWeight: '700',
        color: colors.text,
        marginTop: 4,
    },
    profileBtn: {
        elevation: 8,
        shadowColor: colors.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 8,
    },
    avatar: {
        width: 46,
        height: 46,
        borderRadius: 23,
        backgroundColor: colors.primary,
        justifyContent: 'center',
        alignItems: 'center',
        borderWidth: 2,
        borderColor: colors.white,
    },
    walletCard: {
        backgroundColor: colors.primary,
        borderRadius: 24,
        padding: 24,
        marginBottom: 32,
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        overflow: 'hidden',
        elevation: 10,
        shadowColor: colors.primary,
        shadowOffset: { width: 0, height: 10 },
        shadowOpacity: 0.3,
        shadowRadius: 20,
    },
    walletInfo: {
        zIndex: 2,
    },
    walletLabel: {
        color: 'rgba(255,255,255,0.7)',
        fontSize: 14,
        fontWeight: '600',
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    walletAmount: {
        color: colors.white,
        fontSize: 28,
        fontWeight: '800',
        marginTop: 8,
    },
    addFundsBtn: {
        backgroundColor: colors.white,
        paddingVertical: 10,
        paddingHorizontal: 15,
        borderRadius: 12,
        flexDirection: 'row',
        alignItems: 'center',
        zIndex: 2,
    },
    addFundsText: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 14,
    },
    walletDecoration: {
        position: 'absolute',
        right: -30,
        top: -30,
        width: 150,
        height: 150,
        borderRadius: 75,
        backgroundColor: 'rgba(255,255,255,0.1)',
        zIndex: 1,
    },
    sectionTitleMain: {
        fontSize: 18,
        fontWeight: '700',
        color: colors.text,
        marginBottom: 20,
    },
    servicesGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        justifyContent: 'space-between',
        marginBottom: 30,
    },
    serviceItem: {
        width: '23%',
        alignItems: 'center',
        marginBottom: 24,
    },
    serviceIcon: {
        width: 64,
        height: 64,
        borderRadius: 20,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 10,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.05,
        shadowRadius: 8,
    },
    serviceLabel: {
        fontSize: 11,
        fontWeight: '700',
        color: colors.textSecondary,
        textAlign: 'center',
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 20,
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: '700',
        color: colors.text,
    },
    seeAll: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 14,
    },
    transactionItem: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 16,
        marginBottom: 12,
    },
    transactionIconContainer: {
        width: 44,
        height: 44,
        borderRadius: 12,
        backgroundColor: colors.background,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 16,
    },
    transactionInfo: {
        flex: 1,
    },
    transactionDesc: {
        fontSize: 15,
        fontWeight: '600',
        color: colors.text,
    },
    transactionDate: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 4,
    },
    transactionAmount: {
        fontSize: 16,
        fontWeight: '700',
    }
});

export default DashboardScreen;
