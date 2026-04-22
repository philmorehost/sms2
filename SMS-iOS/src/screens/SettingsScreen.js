import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, Switch, Alert, ActivityIndicator } from 'react-native';
import Icon from '../components/Icons';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import userService from '../services/userService';

const SettingsScreen = ({ navigation }) => {
    const [profile, setProfile] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notificationsEnabled, setNotificationsEnabled] = useState(true);
    const [biometricsEnabled, setBiometricsEnabled] = useState(false);

    useEffect(() => {
        const fetchProfile = async () => {
            try {
                const res = await userService.getProfile();
                if (res.status === 'success') {
                    setProfile(res.user);
                }
            } catch (error) {
                console.error(error);
            } finally {
                setLoading(false);
            }
        };
        fetchProfile();
    }, []);

    const handleLogout = () => {
        Alert.alert(
            'Logout',
            'Are you sure you want to log out of PHILMORESMS?',
            [
                { text: 'Cancel', style: 'cancel' },
                { 
                    text: 'Logout', 
                    style: 'destructive',
                    onPress: () => {
                        userService.logout();
                        navigation.replace('Login');
                    }
                }
            ]
        );
    };

    const SettingItem = ({ icon, title, subtitle, onPress, type = 'chevron', value, onValueChange }) => (
        <TouchableOpacity 
            style={styles.item} 
            onPress={onPress}
            disabled={type === 'switch'}
            activeOpacity={0.7}
        >
            <View style={styles.itemIconContainer}>
                <Icon name={icon} size={20} color={colors.primary} />
            </View>
            <View style={styles.itemTextContainer}>
                <Text style={styles.itemTitle}>{title}</Text>
                {subtitle && <Text style={styles.itemSubtitle}>{subtitle}</Text>}
            </View>
            {type === 'chevron' && (
                <Icon name="chevronRight" size={16} color={colors.textHint} />
            )}
            {type === 'switch' && (
                <Switch 
                    value={value} 
                    onValueChange={onValueChange}
                    trackColor={{ false: colors.border, true: colors.primaryLight }}
                    thumbColor={value ? colors.primary : colors.white}
                />
            )}
        </TouchableOpacity>
    );

    if (loading) {
        return (
            <View style={styles.loader}>
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        );
    }

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.scroll}>
                <View style={styles.header}>
                    <Text style={styles.title}>Settings</Text>
                    <Text style={styles.subtitle}>Manage your account and preferences</Text>
                </View>

                {profile && (
                    <View style={styles.profileCard}>
                        <View style={styles.avatar}>
                            <Text style={styles.avatarText}>{profile.username[0].toUpperCase()}</Text>
                        </View>
                        <View style={styles.profileInfo}>
                            <Text style={styles.profileName}>{profile.username}</Text>
                            <Text style={styles.profileEmail}>{profile.email}</Text>
                        </View>
                        <TouchableOpacity style={styles.editBtn} onPress={() => navigation.navigate('Profile')}>
                            <Icon name="profile" size={18} color={colors.primary} />
                        </TouchableOpacity>
                    </View>
                )}

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>Account & Security</Text>
                    <FintechCard style={styles.card}>
                        <SettingItem 
                            icon="profile" 
                            title="Personal Information" 
                            subtitle="Edit your name, email, and phone"
                            onPress={() => navigation.navigate('Profile')}
                        />
                        <View style={styles.divider} />
                        <SettingItem 
                            icon="sms" 
                            title="Change Password" 
                            subtitle="Update your account security"
                            onPress={() => navigation.navigate('ForgotPassword')}
                        />
                        <View style={styles.divider} />
                        <SettingItem 
                            icon="wallet" 
                            title="Manage Sender IDs" 
                            subtitle="Register and track your Sender IDs"
                            onPress={() => navigation.navigate('RegisterId')}
                        />
                    </FintechCard>
                </View>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>App Preferences</Text>
                    <FintechCard style={styles.card}>
                        <SettingItem 
                            icon="dashboard" 
                            title="Push Notifications" 
                            subtitle="Receive alerts on your device"
                            type="switch"
                            value={notificationsEnabled}
                            onValueChange={setNotificationsEnabled}
                        />
                        <View style={styles.divider} />
                        <SettingItem 
                            icon="phonebook" 
                            title="Biometric Login" 
                            subtitle="Use FaceID or Fingerprint"
                            type="switch"
                            value={biometricsEnabled}
                            onValueChange={setBiometricsEnabled}
                        />
                    </FintechCard>
                </View>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>Support</Text>
                    <FintechCard style={styles.card}>
                        <SettingItem 
                            icon="global" 
                            title="Help Center" 
                            subtitle="FAQs and User Guide"
                            onPress={() => navigation.navigate('HelpCenter')}
                        />
                        <View style={styles.divider} />
                        <SettingItem 
                            icon="sms" 
                            title="Contact Support" 
                            subtitle="Open a new support ticket"
                            onPress={() => navigation.navigate('CreateTicket')}
                        />
                    </FintechCard>
                </View>

                <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
                    <Text style={styles.logoutText}>Log Out</Text>
                </TouchableOpacity>

                <Text style={styles.versionText}>PHILMORESMS v2.1.0 (Production)</Text>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    scroll: {
        padding: 24,
        paddingBottom: 40,
    },
    loader: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: colors.background,
    },
    header: {
        marginBottom: 32,
        marginTop: 10,
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
    profileCard: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.white,
        padding: 16,
        borderRadius: 24,
        marginBottom: 32,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 10,
    },
    avatar: {
        width: 56,
        height: 56,
        borderRadius: 28,
        backgroundColor: colors.primary,
        justifyContent: 'center',
        alignItems: 'center',
    },
    avatarText: {
        color: colors.white,
        fontSize: 22,
        fontWeight: '700',
    },
    profileInfo: {
        flex: 1,
        marginLeft: 16,
    },
    profileName: {
        fontSize: 18,
        fontWeight: '700',
        color: colors.text,
    },
    profileEmail: {
        fontSize: 13,
        color: colors.textSecondary,
        marginTop: 2,
    },
    editBtn: {
        width: 40,
        height: 40,
        borderRadius: 12,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
    },
    section: {
        marginBottom: 24,
    },
    sectionTitle: {
        fontSize: 14,
        fontWeight: '700',
        color: colors.textSecondary,
        marginBottom: 12,
        marginLeft: 4,
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    card: {
        padding: 0,
        overflow: 'hidden',
    },
    item: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 16,
    },
    itemIconContainer: {
        width: 40,
        height: 40,
        borderRadius: 12,
        backgroundColor: colors.surfaceVariant,
        justifyContent: 'center',
        alignItems: 'center',
    },
    itemTextContainer: {
        flex: 1,
        marginLeft: 16,
    },
    itemTitle: {
        fontSize: 16,
        fontWeight: '600',
        color: colors.text,
    },
    itemSubtitle: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 2,
    },
    divider: {
        height: 1,
        backgroundColor: colors.border,
        marginLeft: 72,
    },
    logoutBtn: {
        marginTop: 16,
        backgroundColor: 'rgba(255, 69, 58, 0.1)',
        padding: 16,
        borderRadius: 16,
        alignItems: 'center',
    },
    logoutText: {
        color: colors.danger,
        fontWeight: '700',
        fontSize: 16,
    },
    versionText: {
        textAlign: 'center',
        color: colors.textHint,
        fontSize: 12,
        marginTop: 32,
    }
});

export default SettingsScreen;
