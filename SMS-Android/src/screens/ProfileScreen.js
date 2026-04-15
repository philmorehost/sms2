import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, ScrollView, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
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
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            >
                <View style={styles.header}>
                    <View style={styles.avatar}>
                        <Text style={styles.avatarText}>{user.username.substring(0, 1).toUpperCase()}</Text>
                    </View>
                    <Text style={styles.username}>{user.username}</Text>
                    <Text style={styles.email}>{user.email}</Text>
                </View>

                <FintechCard>
                    <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Account ID</Text>
                        <Text style={styles.infoValue}>#{user.id}</Text>
                    </View>
                    <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Phone</Text>
                        <Text style={styles.infoValue}>{user.phone || 'Not set'}</Text>
                    </View>
                    <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Referral Code</Text>
                        <Text style={styles.infoValue}>{user.referral_code}</Text>
                    </View>
                    <View style={[styles.infoRow, { borderBottomWidth: 0 }]}>
                        <Text style={styles.infoLabel}>Member Since</Text>
                        <Text style={styles.infoValue}>{new Date(user.created_at).toLocaleDateString()}</Text>
                    </View>
                </FintechCard>

                <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
                    <Text style={styles.logoutText}>Logout</Text>
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

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
        marginBottom: 32,
    },
    avatar: {
        width: 80,
        height: 80,
        borderRadius: 40,
        backgroundColor: colors.primary,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 16,
    },
    avatarText: {
        fontSize: 32,
        color: colors.white,
        fontWeight: '700',
    },
    username: {
        fontSize: 22,
        fontWeight: '700',
        color: colors.text,
    },
    email: {
        fontSize: 16,
        color: colors.textLight,
    },
    infoRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: 15,
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
    },
    infoLabel: {
        fontSize: 16,
        color: colors.textLight,
    },
    infoValue: {
        fontSize: 16,
        fontWeight: '600',
        color: colors.text,
    },
    logoutButton: {
        marginTop: 24,
        padding: 18,
        backgroundColor: colors.white,
        borderRadius: 12,
        alignItems: 'center',
        borderWidth: 1,
        borderColor: colors.danger,
    },
    logoutText: {
        color: colors.danger,
        fontSize: 16,
        fontWeight: '600',
    }
});

export default ProfileScreen;
