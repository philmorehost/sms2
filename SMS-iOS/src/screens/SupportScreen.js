import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import supportService from '../services/supportService';

const SupportScreen = ({ navigation }) => {
    const [tickets, setTickets] = useState([]);
    const [refreshing, setRefreshing] = useState(false);

    const fetchTickets = async () => {
        try {
            const res = await supportService.list();
            if (res.status === 'success') setTickets(res.tickets);
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        fetchTickets();
    }, []);

    const onRefresh = React.useCallback(async () => {
        setRefreshing(true);
        await fetchTickets();
        setRefreshing(false);
    }, []);

    const renderItem = ({ item }) => (
        <TouchableOpacity onPress={() => navigation.navigate('SupportDetail', { ticketId: item.ticket_id })}>
            <FintechCard style={styles.ticketCard}>
                <View>
                    <Text style={styles.ticketId}>{item.ticket_id}</Text>
                    <Text style={styles.subject}>{item.subject}</Text>
                </View>
                <View style={[styles.statusBadge, getStatusStyle(item.status)]}>
                    <Text style={styles.statusText}>{item.status.replace('_', ' ').toUpperCase()}</Text>
                </View>
            </FintechCard>
        </TouchableOpacity>
    );

    const getStatusStyle = (status) => {
        switch (status) {
            case 'admin_reply': return { backgroundColor: colors.success };
            case 'user_reply': return { backgroundColor: colors.warning };
            case 'closed': return { backgroundColor: colors.secondary };
            default: return { backgroundColor: colors.primary };
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.title}>Support Tickets</Text>
                <TouchableOpacity
                    style={styles.addBtn}
                    onPress={() => navigation.navigate('CreateTicket')}
                >
                    <Text style={styles.addBtnText}>+ New</Text>
                </TouchableOpacity>
            </View>
            <FlatList
                data={tickets}
                renderItem={renderItem}
                keyExtractor={item => item.ticket_id}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    header: { padding: 20, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    title: { fontSize: 24, fontWeight: '700', color: colors.text },
    addBtn: { backgroundColor: colors.primary, paddingHorizontal: 15, paddingVertical: 8, borderRadius: 10 },
    addBtnText: { color: colors.white, fontWeight: '600' },
    list: { padding: 20 },
    ticketCard: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
    ticketId: { fontSize: 12, color: colors.textLight, marginBottom: 4 },
    subject: { fontSize: 16, fontWeight: '600', color: colors.text },
    statusBadge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6 },
    statusText: { fontSize: 10, fontWeight: '700', color: colors.white }
});

export default SupportScreen;
