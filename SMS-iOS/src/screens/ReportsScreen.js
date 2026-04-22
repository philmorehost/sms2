import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl, TouchableOpacity } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import PremiumStatusBadge from '../components/PremiumStatusBadge';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const ReportsScreen = ({ navigation }) => {
    const [messages, setMessages] = useState([]);
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);

    const fetchMessages = async () => {
        try {
            const res = await apiClient('/reports.php?action=messages');
            if (res.status === 'success') {
                setMessages(res.messages);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
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
            <View style={styles.cardHeader}>
                <View style={styles.senderInfo}>
                    <View style={styles.iconCircle}>
                        <Icon name="sms" size={16} color={colors.primary} />
                    </View>
                    <Text style={styles.senderId}>{item.sender_id}</Text>
                </View>
                <PremiumStatusBadge status={item.status} />
            </View>

            <View style={styles.divider} />

            <View style={styles.cardBody}>
                <Text style={styles.recipients} numberOfLines={1}>
                    To: {item.recipients.length > 40 ? item.recipients.substring(0, 40) + '...' : item.recipients}
                </Text>
                <Text style={styles.messageText}>{item.message}</Text>
            </View>

            <View style={styles.cardFooter}>
                <View style={styles.tag}>
                    <Text style={styles.tagText}>{item.type.replace('_', ' ').toUpperCase()}</Text>
                </View>
                <Text style={styles.dateText}>{new Date(item.created_at).toLocaleDateString()} {new Date(item.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</Text>
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.title}>Message Reports</Text>
                <Text style={styles.subtitle}>Track your delivery status</Text>
            </View>

            <FlatList
                data={messages}
                renderItem={renderItem}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                ListEmptyComponent={!loading && <EmptyState title="No reports found" subtitle="Your sent messages will appear here." />}
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
    list: {
        padding: 24,
        paddingTop: 0,
        paddingBottom: 40,
    },
    card: {
        marginBottom: 16,
        padding: 0,
        overflow: 'hidden',
    },
    cardHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 16,
    },
    senderInfo: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    iconCircle: {
        width: 32,
        height: 32,
        borderRadius: 16,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 10,
    },
    senderId: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.primary,
    },
    divider: {
        height: 1,
        backgroundColor: colors.border,
        marginHorizontal: 16,
    },
    cardBody: {
        padding: 16,
    },
    recipients: {
        fontSize: 12,
        color: colors.textSecondary,
        fontWeight: '600',
        marginBottom: 8,
    },
    messageText: {
        fontSize: 14,
        color: colors.text,
        lineHeight: 20,
    },
    cardFooter: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 12,
        paddingHorizontal: 16,
        backgroundColor: colors.surfaceVariant,
    },
    tag: {
        backgroundColor: 'rgba(0,0,0,0.05)',
        paddingHorizontal: 8,
        paddingVertical: 2,
        borderRadius: 4,
    },
    tagText: {
        fontSize: 10,
        fontWeight: '700',
        color: colors.textSecondary,
    },
    dateText: {
        fontSize: 11,
        color: colors.textSecondary,
    }
});

export default ReportsScreen;
