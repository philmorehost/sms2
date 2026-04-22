import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl, TouchableOpacity, ActivityIndicator } from 'react-native';
import Icon from '../components/Icons';
import FintechCard from '../components/FintechCard';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const NotificationCenterScreen = () => {
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchNotifications = async () => {
        try {
            // Assuming a standard endpoint for notifications
            const res = await apiClient('/services.php?action=notifications');
            if (res.status === 'success') {
                setNotifications(res.notifications);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchNotifications();
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        fetchNotifications();
    };

    const getIcon = (type) => {
        switch (type) {
            case 'success': return { name: 'wallet', color: colors.success };
            case 'warning': return { name: 'sms', color: colors.warning };
            case 'error': return { name: 'sms', color: colors.danger };
            default: return { name: 'dashboard', color: colors.primary };
        }
    };

    const renderItem = ({ item }) => {
        const iconData = getIcon(item.type);
        return (
            <TouchableOpacity activeOpacity={0.8}>
                <FintechCard style={[styles.card, !item.is_read && styles.unreadCard]}>
                    <View style={styles.iconArea}>
                        <View style={[styles.iconCircle, { backgroundColor: iconData.color + '20' }]}>
                            <Icon name={iconData.name} size={18} color={iconData.color} />
                        </View>
                        {!item.is_read && <View style={styles.unreadDot} />}
                    </View>
                    <View style={styles.contentArea}>
                        <View style={styles.row}>
                            <Text style={styles.notifTitle}>{item.title}</Text>
                            <Text style={styles.notifDate}>{new Date(item.created_at).toLocaleDateString()}</Text>
                        </View>
                        <Text style={styles.notifMsg}>{item.message}</Text>
                    </View>
                </FintechCard>
            </TouchableOpacity>
        );
    };

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.title}>Notifications</Text>
                <Text style={styles.subtitle}>Recent updates and alerts</Text>
            </View>

            {loading && !refreshing ? (
                <View style={styles.loader}>
                    <ActivityIndicator size="large" color={colors.primary} />
                </View>
            ) : (
                <FlatList
                    data={notifications}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={!loading && (
                        <View style={styles.emptyContainer}>
                            <EmptyState 
                                title="Inbox Zero!" 
                                subtitle="You have no notifications at the moment." 
                            />
                        </View>
                    )}
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
        fontSize: 32,
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
        paddingTop: 8,
    },
    card: {
        flexDirection: 'row',
        padding: 16,
        marginBottom: 12,
        borderRadius: 20,
        backgroundColor: colors.white,
    },
    unreadCard: {
        backgroundColor: colors.primaryLight + '10',
        borderColor: colors.primaryLight,
        borderWidth: 1,
    },
    iconArea: {
        position: 'relative',
        marginRight: 16,
    },
    iconCircle: {
        width: 48,
        height: 48,
        borderRadius: 24,
        justifyContent: 'center',
        alignItems: 'center',
    },
    unreadDot: {
        position: 'absolute',
        top: 2,
        right: 2,
        width: 12,
        height: 12,
        borderRadius: 6,
        backgroundColor: colors.primary,
        borderWidth: 2,
        borderColor: colors.white,
    },
    contentArea: {
        flex: 1,
    },
    row: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 4,
    },
    notifTitle: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.text,
    },
    notifDate: {
        fontSize: 11,
        color: colors.textSecondary,
    },
    notifMsg: {
        fontSize: 13,
        color: colors.textSecondary,
        lineHeight: 18,
    },
    emptyContainer: {
        marginTop: 60,
    }
});

export default NotificationCenterScreen;
