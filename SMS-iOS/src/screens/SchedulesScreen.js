import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl, TouchableOpacity, Alert } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import PremiumStatusBadge from '../components/PremiumStatusBadge';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import schedulerService from '../services/schedulerService';

const SchedulesScreen = ({ navigation }) => {
    const [schedules, setSchedules] = useState([]);
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);

    const fetchSchedules = async () => {
        try {
            const res = await schedulerService.listSchedules();
            if (res.status === 'success') {
                setSchedules(res.schedules);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchSchedules();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchSchedules();
    }, []);

    const handleCancelSchedule = (id) => {
        Alert.alert(
            'Cancel Schedule',
            'Are you sure you want to cancel this scheduled message?',
            [
                { text: 'No', style: 'cancel' },
                { 
                    text: 'Yes, Cancel', 
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            const res = await schedulerService.cancelSchedule(id);
                            if (res.status === 'success') {
                                Alert.alert('Cancelled', 'Schedule has been removed.');
                                fetchSchedules();
                            } else {
                                Alert.alert('Error', res.message);
                            }
                        } catch (error) {
                            Alert.alert('Error', 'Failed to cancel schedule');
                        }
                    }
                }
            ]
        );
    };

    const renderItem = ({ item }) => (
        <FintechCard style={styles.card}>
            <View style={styles.cardHeader}>
                <View style={styles.typeTag}>
                    <Icon name={item.task_type === 'sms' ? 'sms' : 'wallet'} size={12} color={colors.primary} style={{ marginRight: 6 }} />
                    <Text style={styles.typeText}>{item.task_type.toUpperCase()}</Text>
                </View>
                <PremiumStatusBadge status={item.status} />
            </View>

            <View style={styles.cardBody}>
                <View style={styles.infoRow}>
                    <Icon name="sms" size={14} color={colors.textSecondary} style={{ marginRight: 8 }} />
                    <Text style={styles.infoText}>Scheduled for: {new Date(item.scheduled_for).toLocaleString()}</Text>
                </View>
                {item.recipients && (
                    <Text style={styles.recipients} numberOfLines={1}>To: {item.recipients}</Text>
                )}
            </View>

            <TouchableOpacity 
                style={styles.cancelBtn}
                onPress={() => handleCancelSchedule(item.id)}
            >
                <Text style={styles.cancelBtnText}>Cancel Schedule</Text>
            </TouchableOpacity>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerCircle} />
                <View style={styles.headerContent}>
                    <Text style={styles.title}>Schedules</Text>
                    <Text style={styles.subtitle}>Manage your upcoming broadcasts</Text>
                </View>
            </View>

            <View style={styles.body}>
                <FlatList
                    data={schedules}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={!loading && <EmptyState title="No pending schedules" subtitle="Scheduled messages will appear here." />}
                />
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    header: {
        height: 180,
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
        justifyContent: 'center',
        paddingHorizontal: 24,
    },
    headerCircle: {
        position: 'absolute',
        bottom: -40,
        right: -40,
        width: 160,
        height: 160,
        borderRadius: 80,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        marginTop: 20,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: colors.white,
    },
    subtitle: {
        fontSize: 14,
        color: 'rgba(255,255,255,0.8)',
        marginTop: 4,
    },
    body: {
        flex: 1,
        marginTop: -30,
    },
    list: {
        padding: 24,
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
        backgroundColor: colors.surfaceVariant,
    },
    typeTag: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: 'rgba(0,0,0,0.05)',
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 6,
    },
    typeText: {
        fontSize: 10,
        fontWeight: '700',
        color: colors.primary,
    },
    cardBody: {
        padding: 16,
    },
    infoRow: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 8,
    },
    infoText: {
        fontSize: 14,
        color: colors.text,
        fontWeight: '600',
    },
    recipients: {
        fontSize: 12,
        color: colors.textSecondary,
    },
    cancelBtn: {
        borderTopWidth: 1,
        borderTopColor: colors.border,
        padding: 12,
        alignItems: 'center',
    },
    cancelBtnText: {
        color: colors.danger,
        fontWeight: '700',
        fontSize: 13,
    }
});

export default SchedulesScreen;
