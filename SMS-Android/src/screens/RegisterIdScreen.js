import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const RegisterIdScreen = () => {
    const [type, setType] = useState('promotional'); // promotional, corporate, airtel, caller
    const [senderId, setSenderId] = useState('');
    const [message, setMessage] = useState('');
    const [companyName, setCompanyName] = useState('');
    const [businessNature, setBusinessNature] = useState('');
    const [loading, setLoading] = useState(false);

    const handleRegister = async () => {
        if (!senderId || !message) {
            Alert.alert('Error', 'Please fill in all fields');
            return;
        }
        setLoading(true);
        try {
            const body = new URLSearchParams();
            body.append('senderID', senderId);
            body.append('message', message);
            body.append('type', type);
            if (type === 'airtel') {
                body.append('company_name', companyName);
                body.append('nature_of_business', businessNature);
            }

            const res = await apiClient('/sender-ids.php?action=request', {
                method: 'POST',
                body: body.toString()
            });

            if (res.status === 'success') {
                Alert.alert('Success', 'Registration request submitted for review.');
                setSenderId('');
                setMessage('');
                setCompanyName('');
                setBusinessNature('');
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Submission failed');
        } finally {
            setLoading(false);
        }
    };

    const EXAMPLES = [
        "Your OTP is {code}. Do not share this with anyone.",
        "Hello! Your order #{id} is ready for pickup. Thank you.",
        "Gentle reminder: Your appointment is scheduled for {date}.",
        "Flash Sale! Get 20% off all services today only."
    ];

    const TypeItem = ({ label, icon, id }) => (
        <TouchableOpacity 
            onPress={() => setType(id)} 
            style={[styles.typeItem, type === id && styles.activeType]}
        >
            <Icon 
                name={icon} 
                size={20} 
                color={type === id ? colors.white : colors.primary} 
            />
            <Text style={[styles.typeLabel, type === id && styles.activeTypeLabel]}>{label}</Text>
            {type === id && <View style={styles.activeDot} />}
        </TouchableOpacity>
    );

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.scrollContent}>
                <View style={styles.header}>
                    <View style={styles.headerCircle} />
                    <View style={styles.headerContent}>
                        <Text style={styles.title}>Register Sender ID</Text>
                        <Text style={styles.subtitle}>Get a branded name for your messages</Text>
                    </View>
                </View>

                <View style={styles.body}>
                    <Text style={styles.sectionTitle}>Select Route Type</Text>
                    <View style={styles.typeGrid}>
                        <TypeItem label="Promotional" icon="dashboard" id="promotional" />
                        <TypeItem label="Corporate" icon="sms" id="corporate" />
                        <TypeItem label="Airtel DND" icon="global" id="airtel" />
                        <TypeItem label="Caller ID" icon="phonebook" id="caller" />
                    </View>

                    {type === 'airtel' && (
                        <View style={styles.fadeContainer}>
                            <FintechInput
                                label="Registered Company Name"
                                value={companyName}
                                onChangeText={setCompanyName}
                                placeholder="Enter legal company name"
                            />
                            <FintechInput
                                label="Nature of Business"
                                value={businessNature}
                                onChangeText={setBusinessNature}
                                placeholder="e.g. Finance, Education"
                            />
                        </View>
                    )}

                    <FintechInput
                        label={type === 'caller' ? "Phone Number" : "Desired Sender ID"}
                        value={senderId}
                        onChangeText={setSenderId}
                        placeholder={type === 'caller' ? "23480..." : "Max 11 characters"}
                    />

                    <FintechInput
                        label="Sample Message"
                        value={message}
                        onChangeText={setMessage}
                        placeholder="Explain how you'll use this ID..."
                        multiline
                        numberOfLines={4}
                    />

                    <Text style={styles.exampleTitle}>Quick Template Suggestions</Text>
                    <Text style={styles.exampleSubtitle}>Tap a template to use it as your sample message:</Text>
                    <View style={styles.exampleList}>
                        {EXAMPLES.map((ex, index) => (
                            <TouchableOpacity 
                                key={index} 
                                style={styles.exampleCard}
                                onPress={() => setMessage(ex)}
                            >
                                <Icon name="sms" size={14} color={colors.primary} style={{ marginRight: 10 }} />
                                <Text style={styles.exampleText} numberOfLines={2}>{ex}</Text>
                            </TouchableOpacity>
                        ))}
                    </View>

                    <FintechButton 
                        title={loading ? "Submitting Request..." : "Submit for Approval"} 
                        onPress={handleRegister} 
                        style={styles.submitBtn}
                    />
                    
                    <View style={styles.noticeBox}>
                        <Icon name="support" size={16} color={colors.textSecondary} style={{ marginRight: 10 }} />
                        <Text style={styles.noticeText}>
                            Approval typically takes 24-48 business hours.
                        </Text>
                    </View>
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
        top: -30,
        right: -30,
        width: 150,
        height: 150,
        borderRadius: 75,
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
    typeGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        justifyContent: 'space-between',
        marginBottom: 24,
    },
    typeItem: {
        width: '48%',
        backgroundColor: colors.white,
        padding: 16,
        borderRadius: 20,
        marginBottom: 12,
        alignItems: 'center',
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 5,
        position: 'relative',
    },
    activeType: {
        backgroundColor: colors.primary,
    },
    typeLabel: {
        fontSize: 12,
        fontWeight: '700',
        color: colors.text,
        marginTop: 8,
    },
    activeTypeLabel: {
        color: colors.white,
    },
    activeDot: {
        position: 'absolute',
        top: 10,
        right: 10,
        width: 6,
        height: 6,
        borderRadius: 3,
        backgroundColor: colors.white,
    },
    exampleSubtitle: {
        fontSize: 12,
        color: colors.textSecondary,
        marginBottom: 12,
        marginTop: -8,
    },
    exampleList: {
        marginBottom: 24,
    },
    exampleCard: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.primaryLight + '20',
        padding: 12,
        borderRadius: 12,
        marginBottom: 8,
        borderWidth: 1,
        borderColor: colors.primaryLight,
    },
    exampleText: {
        fontSize: 13,
        color: colors.primary,
        fontStyle: 'italic',
    },
    submitBtn: {
        marginTop: 8,
    },
    noticeBox: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        marginTop: 20,
        padding: 12,
    },
    noticeText: {
        fontSize: 12,
        color: colors.textSecondary,
    },
    fadeContainer: {
        marginBottom: 16,
    }
});

export default RegisterIdScreen;
