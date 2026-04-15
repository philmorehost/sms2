import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const ReportsScreen = () => {
    const [messages, setMessages] = useState([]);
    const [refreshing, setRefreshing] = useState(false);

    const fetchMessages = async () => {
        try {
            const res = await apiClient('/reports.php?action=messages');
            if (res.status === 'success') setMessages(res.messages);
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        fetchMessages();
    }, []);

    const onRefresh = React.useCallback(async () => {
        setRefreshing(true);
        await fetchMessages();
        setRefreshing(false);
    }, []);

    const renderItem = ({ item }) => (
        <FintechCard style={styles.card}>
            <View style={styles.row}>
                <Text style={styles.sender}>{item.sender_id}</Text>
                <View style={[styles.status, { backgroundColor: item.status === 'success' ? colors.success : colors.danger }]}>
                    <Text style={styles.statusText}>{item.status.toUpperCase()}</Text>
                </View>
            </View>
            <Text style={styles.recipients}>To: {item.recipients.substring(0, 30)}...</Text>
            <Text style={styles.msg}>{item.message}</Text>
            <View style={styles.footer}>
                <Text style={styles.type}>{item.type.replace('_', ' ').toUpperCase()}</Text>
                <Text style={styles.date}>{new Date(item.created_at).toLocaleString()}</Text>
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}><Text style={styles.title}>Message Reports</Text></View>
            <FlatList
                data={messages}
                renderItem={renderItem}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    header: { padding: 20 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text },
    list: { padding: 20, paddingTop: 0 },
    card: { marginBottom: 12 },
    row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
    sender: { fontSize: 16, fontWeight: '700', color: colors.primary },
    status: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4 },
    statusText: { fontSize: 10, color: colors.white, fontWeight: '700' },
    recipients: { fontSize: 12, color: colors.textLight, marginBottom: 8 },
    msg: { fontSize: 14, color: colors.text, marginBottom: 12 },
    footer: { flexDirection: 'row', justifyContent: 'space-between', borderTopWidth: 1, borderTopColor: colors.border, paddingTop: 8 },
    type: { fontSize: 10, fontWeight: '600', color: colors.secondary },
    date: { fontSize: 10, color: colors.textLight }
});

export default ReportsScreen;
