import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, RefreshControl, TouchableOpacity } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import dashboardService from '../services/dashboardService';

const DashboardScreen = ({ navigation }) => {
    const [data, setData] = useState(null);
    const [refreshing, setRefreshing] = useState(false);

    const fetchData = async () => {
        try {
            const response = await dashboardService.getSummary();
            if (response.status === 'success') {
                setData(response);
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
                    <Text style={styles.welcome}>Hello, User</Text>
                    <TouchableOpacity onPress={() => navigation.navigate('Profile')}>
                        <View style={styles.headerIcon}>
                            <Text style={styles.headerIconText}>U</Text>
                        </View>
                    </TouchableOpacity>
                </View>

                <View style={styles.walletCard}>
                    <Text style={styles.walletLabel}>Main Balance</Text>
                    <Text style={styles.walletAmount}>₦{data.stats.balance.toLocaleString()}</Text>
                    <TouchableOpacity style={styles.addFundsBtn}>
                        <Text style={styles.addFundsText}>+ Add Funds</Text>
                    </TouchableOpacity>
                </View>

                <View style={styles.quickActions}>
                    <TouchableOpacity
                        style={styles.actionItem}
                        onPress={() => navigation.navigate('Messaging', { type: 'sms' })}
                    >
                        <View style={[styles.actionIcon, { backgroundColor: '#E3F2FD' }]}>
                            <Text style={styles.actionIconText}>💬</Text>
                        </View>
                        <Text style={styles.actionLabel}>SMS</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                        style={styles.actionItem}
                        onPress={() => navigation.navigate('Messaging', { type: 'voice' })}
                    >
                        <View style={[styles.actionIcon, { backgroundColor: '#F3E5F5' }]}>
                            <Text style={styles.actionIconText}>📞</Text>
                        </View>
                        <Text style={styles.actionLabel}>Voice</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.actionItem}>
                        <View style={[styles.actionIcon, { backgroundColor: '#E8F5E9' }]}>
                            <Text style={styles.actionIconText}>📊</Text>
                        </View>
                        <Text style={styles.actionLabel}>Reports</Text>
                    </TouchableOpacity>
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
    quickActions: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: 32,
    },
    actionItem: {
        alignItems: 'center',
        width: '30%',
    },
    actionIcon: {
        width: 60,
        height: 60,
        borderRadius: 15,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 8,
    },
    actionIconText: {
        fontSize: 24,
    },
    actionLabel: {
        fontSize: 14,
        fontWeight: '500',
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
