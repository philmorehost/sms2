import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, RefreshControl, TouchableOpacity } from 'react-native';
import FintechCard from '../components/FintechCard';
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
            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            >
                <View style={styles.header}>
                    <Text style={styles.welcome}>Hello, {username}</Text>
                    <TouchableOpacity onPress={() => navigation.navigate('Profile')}>
                        <View style={styles.headerIcon}>
                            <Text style={styles.headerIconText}>U</Text>
                        </View>
                    </TouchableOpacity>
                </View>

                <View style={styles.walletCard}>
                    <Text style={styles.walletLabel}>Main Balance</Text>
                    <Text style={styles.walletAmount}>₦{data.stats.balance.toLocaleString()}</Text>
                    <TouchableOpacity
                        style={styles.addFundsBtn}
                        onPress={() => navigation.navigate('FundWallet')}
                    >
                        <Text style={styles.addFundsText}>+ Add Funds</Text>
                    </TouchableOpacity>
                </View>

                <View style={styles.servicesGrid}>
                    <ServiceItem icon="💬" label="SMS" onPress={() => navigation.navigate('Messaging', { type: 'sms' })} bg="#E3F2FD" />
                    <ServiceItem icon="🌍" label="Global SMS" onPress={() => navigation.navigate('Messaging', { type: 'sms', route: 'global' })} bg="#FFF3E0" />
                    <ServiceItem icon="📞" label="Voice" onPress={() => navigation.navigate('Messaging', { type: 'voice' })} bg="#F3E5F5" />
                    <ServiceItem icon="🎵" label="Voice File" onPress={() => navigation.navigate('Messaging', { type: 'voice_audio' })} bg="#FFF9C4" />
                    <ServiceItem icon="🛡️" label="OTP" onPress={() => navigation.navigate('OtpTemplates')} bg="#E0F2F1" />
                    <ServiceItem icon="👥" label="Refer" onPress={() => navigation.navigate('Referral')} bg="#FCE4EC" />
                    <ServiceItem icon="🎧" label="Support" onPress={() => navigation.navigate('Support')} bg="#E8F5E9" />
                    <ServiceItem icon="🔍" label="Extractor" onPress={() => navigation.navigate('NumberExtractor')} bg="#FFFDE7" />
                    <ServiceItem icon="🧹" label="Filter" onPress={() => navigation.navigate('NumberFilter')} bg="#F1F8E9" />
                    <ServiceItem icon="📔" label="Phonebook" onPress={() => navigation.navigate('Phonebook')} bg="#EFEBE9" />
                    <ServiceItem icon="📈" label="Reports" onPress={() => navigation.navigate('Reports')} bg="#F3E5F5" />
                    <ServiceItem icon="🆔" label="Register ID" onPress={() => navigation.navigate('RegisterId')} bg="#E8EAF6" />
                    <ServiceItem icon="💳" label="G-Wallet" onPress={() => navigation.navigate('GlobalWallet')} bg="#F3E5F5" />
                    <ServiceItem icon="🏷️" label="Pricing" onPress={() => navigation.navigate('Pricing')} bg="#FFF3E0" />
                    <ServiceItem icon="🎂" label="Birthday" onPress={() => navigation.navigate('BirthdayScheduler')} bg="#FFFDE7" />
                    <ServiceItem icon="🌐" label="Coverage" onPress={() => navigation.navigate('GlobalCoverage')} bg="#E1F5FE" />
                    <ServiceItem icon="⏳" label="Schedules" onPress={() => navigation.navigate('Schedules')} bg="#F1F8E9" />
                </View>

                <View style={styles.sectionHeader}>
                    <Text style={styles.sectionTitle}>Recent Transactions</Text>
                    <TouchableOpacity onPress={() => navigation.navigate('Transactions')}>
                        <Text style={styles.seeAll}>See All</Text>
                    </TouchableOpacity>
                </View>

                {data.recent_transactions.map((item) => (
                    <FintechCard key={item.id} style={styles.transactionItem}>
                        <View style={styles.transactionInfo}>
                            <Text style={styles.transactionDesc}>{item.description}</Text>
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

const ServiceItem = ({ icon, label, onPress, bg }) => (
    <TouchableOpacity style={styles.serviceItem} onPress={onPress}>
        <View style={[styles.serviceIcon, { backgroundColor: bg }]}>
            <Text style={styles.serviceIconText}>{icon}</Text>
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
        marginBottom: 24,
    },
    welcome: {
        fontSize: 24,
        fontWeight: '700',
        color: colors.text,
    },
    headerIcon: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: colors.primary,
        justifyContent: 'center',
        alignItems: 'center',
    },
    headerIconText: {
        color: colors.white,
        fontWeight: '700',
    },
    walletCard: {
        backgroundColor: colors.primary,
        borderRadius: 20,
        padding: 24,
        marginBottom: 32,
    },
    walletLabel: {
        color: 'rgba(255,255,255,0.8)',
        fontSize: 16,
        marginBottom: 8,
    },
    walletAmount: {
        color: colors.white,
        fontSize: 32,
        fontWeight: '700',
        marginBottom: 20,
    },
    addFundsBtn: {
        backgroundColor: 'rgba(255,255,255,0.2)',
        paddingVertical: 10,
        paddingHorizontal: 16,
        borderRadius: 10,
        alignSelf: 'flex-start',
    },
    addFundsText: {
        color: colors.white,
        fontWeight: '600',
    },
    servicesGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        justifyContent: 'space-between',
        marginBottom: 24,
    },
    serviceItem: {
        width: '30%',
        alignItems: 'center',
        marginBottom: 20,
    },
    serviceIcon: {
        width: 60,
        height: 60,
        borderRadius: 18,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 8,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
    },
    serviceIconText: {
        fontSize: 24,
    },
    serviceLabel: {
        fontSize: 13,
        fontWeight: '600',
        color: colors.text,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 16,
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: '700',
        color: colors.text,
    },
    seeAll: {
        color: colors.primary,
        fontWeight: '600',
    },
    transactionItem: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: 16,
        marginBottom: 12,
    },
    transactionInfo: {
        flex: 1,
    },
    transactionDesc: {
        fontSize: 16,
        fontWeight: '600',
        color: colors.text,
        marginBottom: 4,
    },
    transactionDate: {
        fontSize: 12,
        color: colors.textLight,
    },
    transactionAmount: {
        fontSize: 16,
        fontWeight: '700',
    }
});

export default DashboardScreen;
