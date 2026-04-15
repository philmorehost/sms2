import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, Share, TouchableOpacity } from 'react-native';
import FintechCard from '../components/FintechCard';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const ReferralScreen = () => {
    const [data, setData] = useState(null);

    useEffect(() => {
        const fetchReferrals = async () => {
            const res = await apiClient('/services.php?action=referrals');
            if (res.status === 'success') setData(res);
        };
        fetchReferrals();
    }, []);

    const onShare = async () => {
        try {
            await Share.share({
                message: `Join me on PhilmoreSMS! Use my referral code ${data.referral_code} to get started.`,
            });
        } catch (error) {
            console.error(error);
        }
    };

    if (!data) return null;

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.content}>
                <Text style={styles.title}>Refer & Earn</Text>

                <View style={styles.bonusCard}>
                    <Text style={styles.bonusLabel}>Referral Bonus</Text>
                    <Text style={styles.bonusAmount}>₦{data.referral_balance.toLocaleString()}</Text>
                </View>

                <FintechCard style={styles.codeCard}>
                    <Text style={styles.codeLabel}>Your Referral Code</Text>
                    <Text style={styles.codeValue}>{data.referral_code}</Text>
                    <FintechButton title="Share Code" onPress={onShare} style={styles.shareBtn} />
                </FintechCard>

                <Text style={styles.listTitle}>My Referrals</Text>
                <FlatList
                    data={data.referrals}
                    renderItem={({ item }) => (
                        <View style={styles.refItem}>
                            <Text style={styles.refUser}>{item.username}</Text>
                            <Text style={styles.refDate}>{new Date(item.created_at).toLocaleDateString()}</Text>
                        </View>
                    )}
                    keyExtractor={(item, index) => index.toString()}
                    ListEmptyComponent={<Text style={styles.empty}>No referrals yet.</Text>}
                />
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24, flex: 1 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text, marginBottom: 24 },
    bonusCard: { backgroundColor: colors.success, padding: 24, borderRadius: 20, marginBottom: 24, alignItems: 'center' },
    bonusLabel: { color: 'rgba(255,255,255,0.8)', fontSize: 14, marginBottom: 4 },
    bonusAmount: { color: colors.white, fontSize: 28, fontWeight: '700' },
    codeCard: { alignItems: 'center' },
    codeLabel: { fontSize: 12, color: colors.textLight, marginBottom: 8 },
    codeValue: { fontSize: 24, fontWeight: '800', color: colors.primary, marginBottom: 16 },
    shareBtn: { width: '100%' },
    listTitle: { fontSize: 18, fontWeight: '700', color: colors.text, marginVertical: 16 },
    refItem: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: colors.border },
    refUser: { fontSize: 16, fontWeight: '600', color: colors.text },
    refDate: { fontSize: 14, color: colors.textLight },
    empty: { textAlign: 'center', color: colors.textLight, marginTop: 20 }
});

export default ReferralScreen;
