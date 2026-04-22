import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, ActivityIndicator, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import PremiumStatusBadge from '../components/PremiumStatusBadge';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import dashboardService from '../services/dashboardService';

const TransactionsScreen = () => {
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchTransactions = async () => {
        try {
            const response = await dashboardService.getSummary();
            if (response.status === 'success') {
                setTransactions(response.recent_transactions);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchTransactions();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchTransactions();
    }, []);

    const renderItem = ({ item }) => (
        <FintechCard style={styles.card}>
            <View style={styles.iconContainer}>
                <View style={[styles.iconCircle, { backgroundColor: item.amount > 0 ? colors.successLight : colors.dangerLight }]}>
                    <Icon 
                        name={item.amount > 0 ? "wallet" : "sms"} 
                        size={18} 
                        color={item.amount > 0 ? colors.success : colors.danger} 
                    />
                </View>
            </View>
            
            <View style={styles.details}>
                <Text style={styles.desc} numberOfLines={1}>{item.description}</Text>
                <Text style={styles.date}>{new Date(item.created_at).toLocaleDateString()} • {new Date(item.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</Text>
            </View>

            <View style={styles.amountArea}>
                <Text style={[styles.amount, { color: item.amount > 0 ? colors.success : colors.danger }]}>
                    {item.amount > 0 ? '+' : ''}₦{item.amount.toLocaleString()}
                </Text>
                <PremiumStatusBadge status={item.status} style={styles.badge} />
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.title}>Transactions</Text>
                <Text style={styles.subtitle}>Your recent wallet activities</Text>
            </View>

            {loading && !refreshing ? (
                <View style={styles.loader}>
                    <ActivityIndicator size="large" color={colors.primary} />
                </View>
            ) : (
                <FlatList
                    data={transactions}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={<EmptyState title="No transactions" subtitle="Your financial history will appear here." />}
                />
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    header: {
        padding: 24,
        paddingBottom: 16,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: colors.text,
    },
    subtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        marginTop: 4,
    },
    loader: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    list: {
        padding: 24,
        paddingTop: 0,
        paddingBottom: 40,
    },
    card: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 16,
        marginBottom: 12,
        borderRadius: 20,
    },
    iconContainer: {
        marginRight: 12,
    },
    iconCircle: {
        width: 44,
        height: 44,
        borderRadius: 22,
        justifyContent: 'center',
        alignItems: 'center',
    },
    details: {
        flex: 1,
        marginRight: 8,
    },
    desc: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.text,
        marginBottom: 4,
    },
    date: {
        fontSize: 12,
        color: colors.textSecondary,
    },
    amountArea: {
        alignItems: 'flex-end',
    },
    amount: {
        fontSize: 16,
        fontWeight: '800',
        marginBottom: 6,
    },
    badge: {
        borderRadius: 6,
    }
});

export default TransactionsScreen;
