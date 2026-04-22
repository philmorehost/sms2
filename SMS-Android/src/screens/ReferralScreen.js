import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, Share, TouchableOpacity, ActivityIndicator } from 'react-native';
import FintechCard from '../components/FintechCard';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const ReferralScreen = () => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchReferrals = async () => {
            try {
                const res = await apiClient('/services.php?action=referrals');
                if (res.status === 'success') setData(res);
            } catch (error) {
                console.error(error);
            } finally {
                setLoading(false);
            }
        };
        fetchReferrals();
    }, []);

    const onShare = async () => {
        try {
            await Share.share({
                message: `Join me on PhilmoreSMS! Use my referral code ${data.referral_code} to get started and send bulk SMS globally.`,
            });
        } catch (error) {
            console.error(error);
        }
    };

    if (loading) {
        return (
            <View style={styles.loader}>
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        );
    }

    if (!data) return null;

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.scrollContent}>
                <View style={styles.header}>
                    <View style={styles.headerCircle} />
                    <View style={styles.headerContent}>
                        <Text style={styles.title}>Refer & Earn</Text>
                        <Text style={styles.subtitle}>Invite friends and earn rewards on every fund</Text>
                    </View>
                </View>

                <View style={styles.body}>
                    <View style={styles.statsCard}>
                        <View style={styles.statsIconArea}>
                            <Icon name="wallet" size={32} color={colors.white} />
                        </View>
                        <View>
                            <Text style={styles.statsLabel}>Available Bonus</Text>
                            <Text style={styles.statsValue}>₦{data.referral_balance.toLocaleString()}</Text>
                        </View>
                    </View>

                    <FintechCard style={styles.codeCard}>
                        <Text style={styles.codeTitle}>Your Invitation Code</Text>
                        <View style={styles.codeBox}>
                            <Text style={styles.codeText}>{data.referral_code}</Text>
                            <TouchableOpacity onPress={onShare} style={styles.copyIcon}>
                                <Icon name="sms" size={20} color={colors.primary} />
                            </TouchableOpacity>
                        </View>
                        <FintechButton 
                            title="Invite Friends Now" 
                            onPress={onShare} 
                            style={styles.shareBtn} 
                        />
                    </FintechCard>

                    <View style={styles.listSection}>
                        <Text style={styles.sectionTitle}>My Referrals ({data.referrals.length})</Text>
                        <View style={styles.listContainer}>
                            <FlatList
                                scrollEnabled={false}
                                data={data.referrals}
                                renderItem={({ item }) => (
                                    <View style={styles.refItem}>
                                        <View style={styles.refInfo}>
                                            <View style={styles.refAvatar}>
                                                <Text style={styles.refAvatarText}>{item.username[0].toUpperCase()}</Text>
                                            </View>
                                            <View>
                                                <Text style={styles.refUser}>{item.username}</Text>
                                                <Text style={styles.refStatus}>Active Account</Text>
                                            </View>
                                        </View>
                                        <Text style={styles.refDate}>{new Date(item.created_at).toLocaleDateString()}</Text>
                                    </View>
                                )}
                                keyExtractor={(item, index) => index.toString()}
                                ListEmptyComponent={
                                    <View style={styles.emptyView}>
                                        <EmptyState 
                                            title="No referrals yet" 
                                            subtitle="Share your code with friends to start earning bonuses." 
                                        />
                                    </View>
                                }
                            />
                        </View>
                    </View>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

// Internal ScrollView to handle long lists if needed
import { ScrollView } from 'react-native-gesture-handler';

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
    scrollContent: {
        flexGrow: 1,
    },
    header: {
        height: 200,
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
        justifyContent: 'center',
        paddingHorizontal: 24,
    },
    headerCircle: {
        position: 'absolute',
        top: -40,
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
        color: 'rgba(255,255,255,0.7)',
        marginTop: 4,
    },
    body: {
        padding: 24,
        marginTop: -40,
    },
    statsCard: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.success,
        padding: 24,
        borderRadius: 24,
        marginBottom: 24,
        elevation: 8,
        shadowColor: colors.success,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 10,
    },
    statsIconArea: {
        width: 64,
        height: 64,
        borderRadius: 32,
        backgroundColor: 'rgba(255,255,255,0.2)',
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 20,
    },
    statsLabel: {
        color: 'rgba(255,255,255,0.8)',
        fontSize: 13,
        fontWeight: '600',
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    statsValue: {
        color: colors.white,
        fontSize: 28,
        fontWeight: '800',
        marginTop: 4,
    },
    codeCard: {
        padding: 24,
        borderRadius: 24,
        alignItems: 'center',
    },
    codeTitle: {
        fontSize: 14,
        color: colors.textSecondary,
        fontWeight: '600',
        marginBottom: 16,
    },
    codeBox: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.surfaceVariant,
        paddingHorizontal: 24,
        paddingVertical: 12,
        borderRadius: 16,
        marginBottom: 24,
        width: '100%',
        justifyContent: 'center',
    },
    codeText: {
        fontSize: 26,
        fontWeight: '800',
        color: colors.primary,
        letterSpacing: 4,
    },
    copyIcon: {
        marginLeft: 16,
    },
    shareBtn: {
        width: '100%',
    },
    listSection: {
        marginTop: 32,
    },
    sectionTitle: {
        fontSize: 16,
        fontWeight: '800',
        color: colors.text,
        marginBottom: 16,
    },
    listContainer: {
        backgroundColor: colors.white,
        borderRadius: 24,
        padding: 16,
        elevation: 1,
    },
    refItem: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: 12,
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
    },
    refInfo: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    refAvatar: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    refAvatarText: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 16,
    },
    refUser: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.text,
    },
    refStatus: {
        fontSize: 11,
        color: colors.success,
        marginTop: 2,
    },
    refDate: {
        fontSize: 12,
        color: colors.textHint,
    },
    emptyView: {
        paddingVertical: 40,
    }
});

export default ReferralScreen;
