import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import supportService from '../services/supportService';

const CreateTicketScreen = ({ navigation }) => {
    const [subject, setSubject] = useState('');
    const [message, setMessage] = useState('');
    const [category, setCategory] = useState('billing'); // billing, technical, sender_id, general
    const [loading, setLoading] = useState(false);

    const handleCreate = async () => {
        if (!subject || !message) {
            Alert.alert('Error', 'Please fill in all fields');
            return;
        }

        setLoading(true);
        try {
            const res = await supportService.createTicket(subject, message, category);
            if (res.status === 'success') {
                Alert.alert('Success', 'Support ticket created successfully!');
                navigation.goBack();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to create support ticket');
        } finally {
            setLoading(false);
        }
    };

    const CategoryItem = ({ label, id, icon }) => (
        <TouchableOpacity 
            style={[styles.catItem, category === id && styles.activeCatItem]}
            onPress={() => setCategory(id)}
        >
            <View style={[styles.catIcon, category === id && styles.activeCatIcon]}>
                <Icon name={icon} size={18} color={category === id ? colors.white : colors.primary} />
            </View>
            <Text style={[styles.catLabel, category === id && styles.activeCatLabel]}>{label}</Text>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.scrollContent}>
                <View style={styles.header}>
                    <View style={styles.headerCircle} />
                    <View style={styles.headerContent}>
                        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                            <Icon name="chevronRight" size={24} color={colors.white} style={{ transform: [{ rotate: '180deg' }] }} />
                        </TouchableOpacity>
                        <View style={styles.titleArea}>
                            <Text style={styles.title}>New Support Ticket</Text>
                            <Text style={styles.subtitle}>Tell us what you need help with</Text>
                        </View>
                    </View>
                </View>

                <View style={styles.body}>
                    <Text style={styles.sectionTitle}>Support Category</Text>
                    <View style={styles.categoryGrid}>
                        <CategoryItem id="billing" label="Billing" icon="wallet" />
                        <CategoryItem id="technical" label="Technical" icon="dashboard" />
                        <CategoryItem id="sender_id" label="Sender ID" icon="sms" />
                        <CategoryItem id="general" label="General" icon="global" />
                    </View>

                    <FintechInput
                        label="Subject"
                        value={subject}
                        onChangeText={setSubject}
                        placeholder="e.g. Wallet funding issue"
                    />

                    <FintechInput
                        label="Message Detail"
                        value={message}
                        onChangeText={setMessage}
                        placeholder="Describe your issue in detail..."
                        multiline
                        numberOfLines={6}
                    />

                    <View style={styles.tipBox}>
                        <Icon name="support" size={16} color={colors.textSecondary} style={{ marginRight: 12 }} />
                        <Text style={styles.tipText}>
                            Including your Transaction ID or Sender ID helps us resolve issues faster.
                        </Text>
                    </View>

                    <FintechButton 
                        title={loading ? "Creating Ticket..." : "Open Support Ticket"} 
                        onPress={handleCreate} 
                        style={styles.submitBtn}
                    />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
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
        padding: 24,
    },
    headerCircle: {
        position: 'absolute',
        top: -20,
        right: -20,
        width: 140,
        height: 140,
        borderRadius: 70,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        marginTop: 20,
    },
    backBtn: {
        width: 40,
        height: 40,
        borderRadius: 12,
        backgroundColor: 'rgba(255,255,255,0.2)',
        justifyContent: 'center',
        alignItems: 'center',
    },
    titleArea: {
        marginTop: 20,
    },
    title: {
        fontSize: 26,
        fontWeight: '800',
        color: colors.white,
    },
    subtitle: {
        fontSize: 14,
        color: 'rgba(255,255,255,0.8)',
        marginTop: 4,
    },
    body: {
        padding: 24,
        marginTop: -30,
    },
    sectionTitle: {
        fontSize: 14,
        fontWeight: '700',
        color: colors.textSecondary,
        marginBottom: 16,
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    categoryGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        justifyContent: 'space-between',
        marginBottom: 24,
    },
    catItem: {
        width: '48%',
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.white,
        padding: 12,
        borderRadius: 16,
        marginBottom: 12,
        elevation: 2,
    },
    activeCatItem: {
        backgroundColor: colors.primary,
    },
    catIcon: {
        width: 32,
        height: 32,
        borderRadius: 10,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 10,
    },
    activeCatIcon: {
        backgroundColor: 'rgba(255,255,255,0.2)',
    },
    catLabel: {
        fontSize: 13,
        fontWeight: '700',
        color: colors.text,
    },
    activeCatLabel: {
        color: colors.white,
    },
    tipBox: {
        flexDirection: 'row',
        backgroundColor: colors.surfaceVariant,
        padding: 16,
        borderRadius: 16,
        marginBottom: 24,
        alignItems: 'center',
    },
    tipText: {
        flex: 1,
        fontSize: 12,
        color: colors.textSecondary,
        lineHeight: 18,
    },
    submitBtn: {
        marginTop: 8,
    }
});

export default CreateTicketScreen;
