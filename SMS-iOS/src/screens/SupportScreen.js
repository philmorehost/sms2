import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import PremiumStatusBadge from '../components/PremiumStatusBadge';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import supportService from '../services/supportService';

const SupportScreen = ({ navigation }) => {
    const [tickets, setTickets] = useState([]);
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);

    const fetchTickets = async () => {
        try {
            const res = await supportService.list();
            if (res.status === 'success') {
                setTickets(res.tickets);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchTickets();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchTickets();
    }, []);

    const renderItem = ({ item }) => (
        <TouchableOpacity 
            activeOpacity={0.7}
            onPress={() => navigation.navigate('SupportDetail', { ticketId: item.ticket_id })}
        >
            <FintechCard style={styles.card}>
                <View style={styles.cardContent}>
                    <View style={styles.infoArea}>
                        <Text style={styles.ticketId}>TICKET #{item.ticket_id}</Text>
                        <Text style={styles.subject} numberOfLines={1}>{item.subject}</Text>
                        <Text style={styles.date}>Last updated: {new Date(item.updated_at || item.created_at).toLocaleDateString()}</Text>
                    </View>
                    <View style={styles.badgeArea}>
                        <PremiumStatusBadge status={item.status === 'admin_reply' ? 'success' : item.status === 'user_reply' ? 'warning' : item.status} text={item.status.replace('_', ' ').toUpperCase()} />
                        <Icon name="chevronRight" size={16} color={colors.textHint} style={{ marginTop: 10 }} />
                    </View>
                </View>
            </FintechCard>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.title}>Support Tickets</Text>
                    <Text style={styles.subtitle}>Get help with your account</Text>
                </View>
                <TouchableOpacity
                    style={styles.addBtn}
                    onPress={() => navigation.navigate('CreateTicket')}
                    activeOpacity={0.8}
                >
                    <Icon name="plus" size={14} color={colors.white} style={{ marginRight: 6 }} />
                    <Text style={styles.addBtnText}>New Ticket</Text>
                </TouchableOpacity>
            </View>

            <FlatList
                data={tickets}
                renderItem={renderItem}
                keyExtractor={item => item.ticket_id}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                ListEmptyComponent={!loading && <EmptyState title="No tickets found" subtitle="Need help? Create a support ticket and we'll get back to you." />}
            />
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
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
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
    addBtn: {
        backgroundColor: colors.primary,
        paddingHorizontal: 16,
        paddingVertical: 10,
        borderRadius: 14,
        flexDirection: 'row',
        alignItems: 'center',
        elevation: 4,
        shadowColor: colors.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 8,
    },
    addBtnText: {
        color: colors.white,
        fontWeight: '700',
        fontSize: 13,
    },
    list: {
        padding: 24,
        paddingTop: 0,
        paddingBottom: 40,
    },
    card: {
        marginBottom: 16,
        padding: 16,
    },
    cardContent: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    infoArea: {
        flex: 1,
        marginRight: 12,
    },
    ticketId: {
        fontSize: 10,
        fontWeight: '800',
        color: colors.primary,
        letterSpacing: 1,
        marginBottom: 4,
    },
    subject: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.text,
        marginBottom: 6,
    },
    date: {
        fontSize: 12,
        color: colors.textSecondary,
    },
    badgeArea: {
        alignItems: 'flex-end',
    }
});

export default SupportScreen;
