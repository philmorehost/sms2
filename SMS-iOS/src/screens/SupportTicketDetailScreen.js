import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TextInput, TouchableOpacity, KeyboardAvoidingView, Platform, ActivityIndicator, Alert } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import PremiumStatusBadge from '../components/PremiumStatusBadge';
import { colors } from '../theme/colors';
import supportService from '../services/supportService';

const SupportTicketDetailScreen = ({ route, navigation }) => {
    const { ticketId, subject } = route.params;
    const [messages, setMessages] = useState([]);
    const [ticketInfo, setTicketInfo] = useState(null);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);

    const fetchTicketDetails = async () => {
        try {
            const res = await supportService.getTicketDetails(ticketId);
            if (res.status === 'success') {
                setMessages(res.messages);
                setTicketInfo(res.ticket);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTicketDetails();
    }, [ticketId]);

    const handleReply = async () => {
        if (!newMessage.trim()) return;

        setSending(true);
        try {
            const res = await supportService.replyTicket(ticketId, newMessage);
            if (res.status === 'success') {
                setNewMessage('');
                fetchTicketDetails();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to send reply');
        } finally {
            setSending(false);
        }
    };

    const renderMessage = ({ item }) => {
        const isUser = item.sender_type === 'user';
        return (
            <View style={[styles.messageWrapper, isUser ? styles.userWrapper : styles.adminWrapper]}>
                <View style={[styles.messageBubble, isUser ? styles.userBubble : styles.adminBubble]}>
                    <Text style={[styles.messageText, isUser ? styles.userText : styles.adminText]}>
                        {item.message}
                    </Text>
                    <Text style={[styles.messageTime, isUser ? styles.userTime : styles.adminTime]}>
                        {new Date(item.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </Text>
                </View>
            </View>
        );
    };

    if (loading) {
        return (
            <View style={styles.loader}>
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        );
    }

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerRow}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="chevronRight" size={24} color={colors.text} style={{ transform: [{ rotate: '180deg' }] }} />
                    </TouchableOpacity>
                    <View style={styles.titleInfo}>
                        <Text style={styles.subject} numberOfLines={1}>{subject}</Text>
                        <Text style={styles.ticketId}>Ticket #{ticketId}</Text>
                    </View>
                    {ticketInfo && <PremiumStatusBadge status={ticketInfo.status} />}
                </View>
            </View>

            <FlatList
                data={messages}
                renderItem={renderMessage}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={styles.messageList}
                inverted={false} // Or true if you want latest at bottom
            />

            <KeyboardAvoidingView
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                keyboardVerticalOffset={Platform.OS === 'ios' ? 90 : 0}
            >
                <View style={styles.inputArea}>
                    <View style={styles.inputWrapper}>
                        <TextInput
                            style={styles.input}
                            placeholder="Type your message..."
                            value={newMessage}
                            onChangeText={setNewMessage}
                            multiline
                        />
                        <TouchableOpacity 
                            style={[styles.sendBtn, !newMessage.trim() && styles.disabledSendBtn]} 
                            onPress={handleReply}
                            disabled={!newMessage.trim() || sending}
                        >
                            {sending ? (
                                <ActivityIndicator size="small" color={colors.white} />
                            ) : (
                                <Icon name="plus" size={20} color={colors.white} />
                            )}
                        </TouchableOpacity>
                    </View>
                </View>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    loader: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    header: {
        backgroundColor: colors.white,
        padding: 20,
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
        elevation: 2,
    },
    headerRow: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    backBtn: {
        width: 40,
        height: 40,
        borderRadius: 12,
        backgroundColor: colors.surfaceVariant,
        justifyContent: 'center',
        alignItems: 'center',
    },
    titleInfo: {
        flex: 1,
        marginLeft: 16,
    },
    subject: {
        fontSize: 16,
        fontWeight: '800',
        color: colors.text,
    },
    ticketId: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 2,
    },
    messageList: {
        padding: 20,
        paddingBottom: 40,
    },
    messageWrapper: {
        marginBottom: 16,
        maxWidth: '85%',
    },
    userWrapper: {
        alignSelf: 'flex-end',
    },
    adminWrapper: {
        alignSelf: 'flex-start',
    },
    messageBubble: {
        padding: 12,
        borderRadius: 18,
        elevation: 1,
    },
    userBubble: {
        backgroundColor: colors.primary,
        borderBottomRightRadius: 4,
    },
    adminBubble: {
        backgroundColor: colors.white,
        borderBottomLeftRadius: 4,
        borderWidth: 1,
        borderColor: colors.border,
    },
    messageText: {
        fontSize: 14,
        lineHeight: 20,
    },
    userText: {
        color: colors.white,
    },
    adminText: {
        color: colors.text,
    },
    messageTime: {
        fontSize: 10,
        marginTop: 4,
        alignSelf: 'flex-end',
    },
    userTime: {
        color: 'rgba(255,255,255,0.7)',
    },
    adminTime: {
        color: colors.textHint,
    },
    inputArea: {
        padding: 16,
        backgroundColor: colors.white,
        borderTopWidth: 1,
        borderTopColor: colors.border,
    },
    inputWrapper: {
        flexDirection: 'row',
        alignItems: 'flex-end',
        backgroundColor: colors.surfaceVariant,
        borderRadius: 24,
        paddingHorizontal: 16,
        paddingVertical: 8,
    },
    input: {
        flex: 1,
        maxHeight: 100,
        paddingTop: 8,
        paddingBottom: 8,
        fontSize: 15,
        color: colors.text,
    },
    sendBtn: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: colors.primary,
        justifyContent: 'center',
        alignItems: 'center',
        marginLeft: 8,
        marginBottom: 2,
    },
    disabledSendBtn: {
        backgroundColor: colors.textHint,
    }
});

export default SupportTicketDetailScreen;
