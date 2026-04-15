import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TextInput, TouchableOpacity } from 'react-native';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import supportService from '../services/supportService';

const SupportTicketDetailScreen = ({ route }) => {
    const { ticketId } = route.params;
    const [data, setData] = useState(null);
    const [reply, setReply] = useState('');
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        try {
            const res = await supportService.view(ticketId);
            if (res.status === 'success') setData(res);
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        fetchData();
    }, [ticketId]);

    const handleReply = async () => {
        if (!reply) return;
        setLoading(true);
        try {
            const res = await supportService.reply(ticketId, reply);
            if (res.status === 'success') {
                setReply('');
                fetchData();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    if (!data) return null;

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.ticketId}>{ticketId}</Text>
                <Text style={styles.title}>{data.ticket.subject}</Text>

                {data.messages.map((m, index) => (
                    <View key={index} style={[styles.bubble, m.is_admin_reply ? styles.adminBubble : styles.userBubble]}>
                        <Text style={[styles.msgText, m.is_admin_reply ? styles.adminText : styles.userText]}>{m.message}</Text>
                        <Text style={styles.date}>{new Date(m.created_at).toLocaleString()}</Text>
                    </View>
                ))}
            </ScrollView>
            <View style={styles.footer}>
                <TextInput
                    style={styles.input}
                    value={reply}
                    onChangeText={setReply}
                    placeholder="Type a reply..."
                    multiline
                />
                <TouchableOpacity style={styles.sendBtn} onPress={handleReply} disabled={loading}>
                    <Text style={styles.sendText}>{loading ? '...' : 'Send'}</Text>
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 20 },
    ticketId: { fontSize: 12, color: colors.textLight, marginBottom: 4 },
    title: { fontSize: 20, fontWeight: '700', color: colors.text, marginBottom: 20 },
    bubble: { padding: 15, borderRadius: 15, marginBottom: 15, maxWidth: '85%' },
    userBubble: { backgroundColor: colors.primary, alignSelf: 'flex-end' },
    adminBubble: { backgroundColor: colors.white, alignSelf: 'flex-start', borderWidth: 1, borderColor: colors.border },
    msgText: { fontSize: 15 },
    userText: { color: colors.white },
    adminText: { color: colors.text },
    date: { fontSize: 10, color: 'rgba(0,0,0,0.4)', marginTop: 8, textAlign: 'right' },
    footer: { padding: 15, backgroundColor: colors.white, borderTopWidth: 1, borderTopColor: colors.border, flexDirection: 'row', alignItems: 'center' },
    input: { flex: 1, backgroundColor: colors.background, borderRadius: 20, paddingHorizontal: 15, paddingVertical: 10, marginRight: 10, maxHeight: 100 },
    sendBtn: { backgroundColor: colors.primary, paddingHorizontal: 20, paddingVertical: 10, borderRadius: 20 },
    sendText: { color: colors.white, fontWeight: '700' }
});

export default SupportTicketDetailScreen;
