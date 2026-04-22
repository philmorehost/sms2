import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl, TouchableOpacity, Modal, Alert } from 'react-native';
import FintechCard from '../components/FintechCard';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import PremiumStatusBadge from '../components/PremiumStatusBadge';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const OtpTemplatesScreen = () => {
    const [templates, setTemplates] = useState([]);
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);
    const [modalVisible, setModalVisible] = useState(false);
    
    const [templateName, setTemplateName] = useState('');
    const [messageBody, setMessageBody] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const fetchTemplates = async () => {
        try {
            const res = await apiClient('/services.php?action=otp_templates');
            if (res.status === 'success') {
                setTemplates(res.templates);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchTemplates();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchTemplates();
    }, []);

    const handleSubmitTemplate = async () => {
        if (!templateName || !messageBody) {
            Alert.alert('Error', 'Please fill in all fields');
            return;
        }

        setSubmitting(true);
        try {
            const body = new URLSearchParams();
            body.append('name', templateName);
            body.append('message', messageBody);
            
            const res = await apiClient('/services.php?action=add_otp_template', {
                method: 'POST',
                body: body.toString()
            });

            if (res.status === 'success') {
                Alert.alert('Submitted', 'Your template has been submitted for approval.');
                setTemplateName('');
                setMessageBody('');
                setModalVisible(false);
                fetchTemplates();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to submit template');
        } finally {
            setSubmitting(false);
        }
    };

    const renderItem = ({ item }) => (
        <FintechCard style={styles.card}>
            <View style={styles.cardHeader}>
                <View style={styles.nameRow}>
                    <View style={styles.iconCircle}>
                        <Icon name="sms" size={16} color={colors.primary} />
                    </View>
                    <Text style={styles.nameText}>{item.template_name}</Text>
                </View>
                <PremiumStatusBadge status={item.status} />
            </View>
            <View style={styles.cardBody}>
                <Text style={styles.codeText}>Code: <Text style={styles.codeValue}>{item.template_code}</Text></Text>
                <Text style={styles.bodyText}>{item.message_body}</Text>
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerCircle} />
                <View style={styles.headerContent}>
                    <View>
                        <Text style={styles.title}>OTP Templates</Text>
                        <Text style={styles.subtitle}>Manage your secure message formats</Text>
                    </View>
                    <TouchableOpacity 
                        style={styles.addBtn}
                        onPress={() => setModalVisible(true)}
                    >
                        <Icon name="plus" size={16} color={colors.white} />
                    </TouchableOpacity>
                </View>
            </View>

            <View style={styles.body}>
                <FlatList
                    data={templates}
                    renderItem={renderItem}
                    keyExtractor={item => item.template_code}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={!loading && <EmptyState title="No templates" subtitle="Submit your first OTP template for approval." />}
                />
            </View>

            <Modal
                transparent
                visible={modalVisible}
                animationType="fade"
                onRequestClose={() => setModalVisible(false)}
            >
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <Text style={styles.modalTitle}>New Template Request</Text>
                        
                        <FintechInput
                            label="Template Name"
                            value={templateName}
                            onChangeText={setTemplateName}
                            placeholder="e.g. Login Verification"
                        />
                        <FintechInput
                            label="Message Body"
                            value={messageBody}
                            onChangeText={setMessageBody}
                            placeholder="e.g. Your verification code is {code}"
                            multiline
                        />

                        <View style={styles.modalButtons}>
                            <TouchableOpacity 
                                style={styles.cancelBtn} 
                                onPress={() => setModalVisible(false)}
                            >
                                <Text style={styles.cancelText}>Cancel</Text>
                            </TouchableOpacity>
                            <FintechButton 
                                title={submitting ? "Submitting..." : "Send Request"} 
                                onPress={handleSubmitTemplate}
                                style={styles.saveBtn}
                            />
                        </View>
                    </View>
                </View>
            </Modal>
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
    },
    headerCircle: {
        position: 'absolute',
        top: -40,
        left: -40,
        width: 180,
        height: 180,
        borderRadius: 90,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 24,
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
    addBtn: {
        width: 48,
        height: 48,
        borderRadius: 24,
        backgroundColor: 'rgba(255,255,255,0.2)',
        justifyContent: 'center',
        alignItems: 'center',
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
        backgroundColor: colors.primaryLight,
    },
    nameRow: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    iconCircle: {
        width: 28,
        height: 28,
        borderRadius: 14,
        backgroundColor: colors.white,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 10,
    },
    nameText: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.primary,
    },
    cardBody: {
        padding: 16,
    },
    codeText: {
        fontSize: 11,
        color: colors.textSecondary,
        marginBottom: 8,
        fontWeight: '600',
    },
    codeValue: {
        color: colors.primary,
    },
    bodyText: {
        fontSize: 14,
        color: colors.text,
        lineHeight: 20,
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'center',
        padding: 24,
    },
    modalContent: {
        backgroundColor: colors.background,
        borderRadius: 24,
        padding: 24,
    },
    modalTitle: {
        fontSize: 20,
        fontWeight: '800',
        color: colors.text,
        marginBottom: 20,
    },
    modalButtons: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 10,
    },
    cancelBtn: {
        flex: 1,
        alignItems: 'center',
    },
    cancelText: {
        color: colors.textSecondary,
        fontWeight: '600',
    },
    saveBtn: {
        flex: 2,
    }
});

export default OtpTemplatesScreen;
