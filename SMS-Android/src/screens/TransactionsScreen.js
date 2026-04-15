import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, ActivityIndicator } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import dashboardService from '../services/dashboardService';

const TransactionsScreen = () => {
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
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
            }
        };
        fetchTransactions();
    }, []);

    const renderItem = ({ item }) => (
        <FintechCard style={styles.item}>
            <View style={styles.info}>
                <Text style={styles.desc}>{item.description}</Text>
                <Text style={styles.date}>{new Date(item.created_at).toLocaleString()}</Text>
            </View>
            <View style={styles.amountContainer}>
                <Text style={[styles.amount, { color: item.amount > 0 ? colors.success : colors.danger }]}>
                    {item.amount > 0 ? '+' : ''}₦{item.amount.toLocaleString()}
                </Text>
                <View style={[styles.statusBadge, { backgroundColor: item.status === 'completed' ? '#E8F5E9' : '#FFF3E0' }]}>
                    <Text style={[styles.statusText, { color: item.status === 'completed' ? colors.success : colors.warning }]}>
                        {item.status.toUpperCase()}
                    </Text>
                </View>
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.title}>Transactions</Text>
            </View>
            {loading ? (
                <ActivityIndicator size="large" color={colors.primary} style={{ marginTop: 50 }} />
            ) : (
                <FlatList
                    data={transactions}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.list}
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
        padding: 20,
    },
    title: {
        fontSize: 24,
        fontWeight: '700',
        color: colors.text,
    },
    list: {
        padding: 20,
        paddingTop: 0,
    },
    item: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 16,
    },
    info: {
        flex: 1,
    },
    desc: {
        fontSize: 16,
        fontWeight: '600',
        color: colors.text,
        marginBottom: 4,
    },
    date: {
        fontSize: 12,
        color: colors.textLight,
    },
    amountContainer: {
        alignItems: 'flex-end',
    },
    amount: {
        fontSize: 16,
        fontWeight: '700',
        marginBottom: 4,
    },
    statusBadge: {
        paddingHorizontal: 8,
        paddingVertical: 2,
        borderRadius: 4,
    },
    statusText: {
        fontSize: 10,
        fontWeight: '700',
    }
});

export default TransactionsScreen;
