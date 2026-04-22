import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, RefreshControl, TouchableOpacity } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import userService from '../services/userService';

const ProfileScreen = ({ navigation }) => {
    const [user, setUser] = useState(null);
    const [refreshing, setRefreshing] = useState(false);

    const fetchUser = async () => {
        try {
            const response = await userService.getProfile();
            if (response.status === 'success') {
                setUser(response.user);
            }
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        fetchUser();
    }, []);

    const onRefresh = React.useCallback(async () => {
        setRefreshing(true);
        await fetchUser();
        setRefreshing(false);
    }, []);

    const handleLogout = () => {
        userService.logout();
        navigation.replace('Login');
    };

    if (!user) return null;

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
            >
                <View style={styles.header}>
                    <View style={styles.avatarContainer}>
                        <View style={styles.avatar}>
                            <Text style={styles.avatarText}>{user.username.substring(0, 1).toUpperCase()}</Text>
                        </View>
                        <TouchableOpacity style={styles.editBadge}>
                            <Icon name="plus" size={12} color={colors.white} />
                        </TouchableOpacity>
                    </View>
                    <Text style={styles.username}>{user.username}</Text>
                    <Text style={styles.email}>{user.email}</Text>
                </View>

                <View style={styles.sectionHeader}>
                    <Text style={styles.sectionTitle}>Account Information</Text>
                </View>

                <FintechCard style={styles.profileCard}>
                    <InfoRow icon="user" label="Account ID" value={`#${user.id}`} />
                    <InfoRow icon="sms" label="Phone Number" value={user.phone || 'Not set'} />
                    <InfoRow icon="global" label="Referral Code" value={user.referral_code} />
                    <InfoRow icon="calendar" label="Member Since" value={new Date(user.created_at).toLocaleDateString()} isLast />
                </FintechCard>

                <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
                    <Icon name="sms" size={20} color={colors.danger} style={{ marginRight: 10 }} />
                    <Text style={styles.logoutText}>Sign Out</Text>
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const InfoRow = ({ icon, label, value, isLast }) => (
    <View style={[styles.infoRow, isLast && { borderBottomWidth: 0 }]}>
        <View style={styles.infoLeft}>
            <View style={styles.iconCircle}>
                <Icon name={icon} size={18} color={colors.primary} />
            </View>
            <Text style={styles.infoLabel}>{label}</Text>
        </View>
        <Text style={styles.infoValue}>{value}</Text>
    </View>
);

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    content: {
        padding: 24,
    },
    header: {
        alignItems: 'center',
        marginBottom: 40,
        marginTop: 20,
    },
    avatarContainer: {
        position: 'relative',
        marginBottom: 16,
    },
    avatar: {
        width: 100,
        height: 100,
        borderRadius: 50,
        backgroundColor: colors.primary,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 8,
        shadowColor: colors.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.3,
        shadowRadius: 10,
        borderWidth: 4,
        borderColor: colors.white,
    },
    editBadge: {
        position: 'absolute',
        bottom: 5,
        right: 5,
        backgroundColor: colors.success,
        width: 28,
        height: 28,
        borderRadius: 14,
        borderWidth: 3,
        borderColor: colors.white,
        justifyContent: 'center',
        alignItems: 'center',
    },
    avatarText: {
        fontSize: 36,
        color: colors.white,
        fontWeight: '800',
    },
    username: {
        fontSize: 24,
        fontWeight: '700',
        color: colors.text,
    },
    email: {
        fontSize: 14,
        color: colors.textSecondary,
        marginTop: 4,
    },
    sectionHeader: {
        marginBottom: 16,
    },
    sectionTitle: {
        fontSize: 14,
        fontWeight: '700',
        color: colors.textSecondary,
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    profileCard: {
        padding: 4,
    },
    infoRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: 18,
        paddingHorizontal: 16,
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
    },
    infoLeft: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    iconCircle: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    infoLabel: {
        fontSize: 14,
        color: colors.textSecondary,
        fontWeight: '600',
    },
    infoValue: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.text,
    },
    logoutButton: {
        marginTop: 32,
        padding: 16,
        backgroundColor: colors.dangerLight,
        borderRadius: 16,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        borderWidth: 1,
        borderColor: colors.danger,
    },
    logoutText: {
        color: colors.danger,
        fontSize: 16,
        fontWeight: '700',
    }
});

export default ProfileScreen;
