import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, RefreshControl, TouchableOpacity, StatusBar } from 'react-native';
import Icon from '../components/Icons';
import ServiceItem from '../components/ServiceItem';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import { dashboardStyles as styles } from '../theme/DashboardStyles';
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

export default DashboardScreen;
