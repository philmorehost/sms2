import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity, Modal } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import smsService from '../services/smsService';
import senderService from '../services/senderService';
import schedulerService from '../services/schedulerService';
import apiClient from '../services/apiClient';

const MessagingScreen = ({ route }) => {
    // Safe destructure – route.params may be undefined when reached via bottom tab
    const params = route?.params || {};
    const type = params.type || 'sms';
    const msgRoute = params.route || 'promotional';
    const [senderId, setSenderId] = useState('');
    const [recipients, setRecipients] = useState('');
    const [message, setMessage] = useState('');
    const [audioUrl, setAudioUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [senders, setSenders] = useState([]);
    const [showSenderPicker, setShowSenderPicker] = useState(false);
    const [showScheduleModal, setShowScheduleModal] = useState(false);
    const [scheduleTime, setScheduleTime] = useState('');
    const recipientCount = recipients ? recipients.split(',').filter(r => r.trim()).length : 0;

    useEffect(() => {
        const fetchSenders = async () => {
            const res = await senderService.list();
            if (res.status === 'success') {
                setSenders(res.sender_ids.filter(s => s.status === 'approved'));
            }
        };
        fetchSenders();
    }, []);

    const handleSend = async () => {
        if (!senderId || !recipients || (type !== 'voice_audio' && !message) || (type === 'voice_audio' && !audioUrl)) {
            Alert.alert('Error', 'Please fill in all fields');
            return;
        }

        setLoading(true);
        try {
            let res;
            if (type === 'sms') {
                res = await smsService.sendSms(senderId, recipients, message, msgRoute);
            } else if (type === 'voice') {
                res = await smsService.sendVoice(senderId, recipients, message);
            } else {
                const body = new URLSearchParams();
                body.append('callerID', senderId);
                body.append('recipients', recipients);
                body.append('audio', audioUrl);
                res = await apiClient('/messaging.php?action=send_voice_audio', {
                    method: 'POST',
                    body: body.toString()
                });
            }

            if (res.status === 'success') {
                Alert.alert('Success', res.message);
                setRecipients('');
                setMessage('');
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    const handleSchedule = async () => {
        if (!senderId || !recipients || !message || !scheduleTime) {
            Alert.alert('Error', 'Please fill in all fields and provide a schedule time');
            return;
        }

        setLoading(true);
        try {
            const res = await schedulerService.scheduleSms(senderId, recipients, message, scheduleTime, msgRoute);
            if (res.status === 'success') {
                Alert.alert('Scheduled', 'Your message has been scheduled successfully.');
                setRecipients('');
                setMessage('');
                setScheduleTime('');
                setShowScheduleModal(false);
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to schedule message');
        } finally {
            setLoading(false);
        }
    };

    const getTitle = () => {
        if (type === 'sms') return msgRoute === 'global' ? 'Global SMS' : 'Bulk SMS';
        if (type === 'voice') return 'Voice SMS (TTS)';
        return 'Voice from File';
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <View style={styles.header}>
                    <Text style={styles.title}>{getTitle()}</Text>
                    <View style={styles.routeBadge}>
                        <Text style={styles.routeBadgeText}>{msgRoute.toUpperCase()}</Text>
                    </View>
                    <Text style={styles.subtitle}>Enter details to broadcast your message</Text>
                </View>

                {/* Sender ID Selector */}
                <Text style={styles.fieldLabel}>{type === 'sms' ? 'Approved Sender ID' : 'Caller ID'}</Text>
                <TouchableOpacity
                    style={styles.senderSelector}
                    onPress={() => senders.length > 0 ? setShowSenderPicker(true) : Alert.alert('No Sender IDs', 'You have no approved sender IDs yet. Register one first.')}
                >
                    <Icon name="register" size={18} color={senderId ? colors.primary : colors.textHint} style={{marginRight: 10}} />
                    <Text style={[styles.senderSelectorText, !senderId && styles.senderPlaceholder]}>
                        {senderId || 'Tap to select sender ID...'}
                    </Text>
                    <Icon name="chevronRight" size={16} color={colors.textHint} />
                </TouchableOpacity>

                <FintechInput
                    label={`Recipient Numbers${recipientCount > 0 ? ` (${recipientCount})` : ''}`}
                    value={recipients}
                    onChangeText={setRecipients}
                    placeholder="23480..., 23470..., 23481..."
                    keyboardType="phone-pad"
                    multiline
                />

                {type === 'voice_audio' ? (
                    <FintechInput
                        label="MP3 Audio URL"
                        value={audioUrl}
                        onChangeText={setAudioUrl}
                        placeholder="https://yourserver.com/audio.mp3"
                    />
                ) : (
                    <View>
                        <FintechInput
                            label="Message Content"
                            value={message}
                            onChangeText={setMessage}
                            placeholder="Compose your message..."
                            multiline
                        />
                        <View style={styles.counterRow}>
                            <Text style={styles.counterText}>
                                {message.length} characters | {Math.ceil(message.length / 160)} page(s)
                            </Text>
                        </View>
                    </View>
                )}

                <FintechButton
                    title={loading ? "Processing..." : "Broadcast Now"}
                    onPress={handleSend}
                    style={styles.btn}
                />
                
                {type === 'sms' && (
                    <TouchableOpacity 
                        style={styles.scheduleLink}
                        onPress={() => setShowScheduleModal(true)}
                    >
                        <Icon name="sms" size={16} color={colors.primary} style={{ marginRight: 8 }} />
                        <Text style={styles.scheduleLinkText}>Schedule for later</Text>
                    </TouchableOpacity>
                )}
                
                <View style={styles.tipBox}>
                    <Text style={styles.tipText}>Tip: Use comma-separated values for multiple recipients.</Text>
                </View>
            </ScrollView>

            {/* Sender ID Picker Modal */}
            <Modal
                transparent
                visible={showSenderPicker}
                animationType="slide"
                onRequestClose={() => setShowSenderPicker(false)}
            >
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <Text style={styles.modalTitle}>Select Sender ID</Text>
                        <Text style={styles.modalSubtitle}>Choose from your approved sender IDs below.</Text>
                        {senders.map((s) => (
                            <TouchableOpacity
                                key={s.id}
                                style={styles.senderPickerItem}
                                onPress={() => { setSenderId(s.sender_id); setShowSenderPicker(false); }}
                            >
                                <Icon name="register" size={18} color={colors.primary} style={{marginRight: 12}} />
                                <View style={{flex: 1}}>
                                    <Text style={styles.senderPickerLabel}>{s.sender_id}</Text>
                                    <Text style={styles.senderPickerSub}>{s.type || 'Promotional'} · Approved</Text>
                                </View>
                                {senderId === s.sender_id && <Icon name="plus" size={16} color={colors.success} />}
                            </TouchableOpacity>
                        ))}
                        <TouchableOpacity style={styles.cancelBtn} onPress={() => setShowSenderPicker(false)}>
                            <Text style={[styles.cancelText, {marginTop: 16}]}>Close</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>

            <Modal
                transparent
                visible={showScheduleModal}
                animationType="slide"
                onRequestClose={() => setShowScheduleModal(false)}
            >
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <Text style={styles.modalTitle}>Schedule Message</Text>
                        <Text style={styles.modalSubtitle}>Your message will be sent automatically at the selected time.</Text>
                        
                        <FintechInput
                            label="Schedule Date & Time"
                            value={scheduleTime}
                            onChangeText={setScheduleTime}
                            placeholder="YYYY-MM-DD HH:MM:SS"
                        />
                        
                        <View style={styles.modalButtons}>
                            <TouchableOpacity 
                                style={styles.cancelBtn} 
                                onPress={() => setShowScheduleModal(false)}
                            >
                                <Text style={styles.cancelText}>Cancel</Text>
                            </TouchableOpacity>
                            <FintechButton 
                                title={loading ? "Scheduling..." : "Confirm Schedule"} 
                                onPress={handleSchedule}
                                style={styles.confirmBtn}
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
    content: {
        padding: 24,
    },
    header: {
        marginBottom: 32,
    },
    title: {
        fontSize: 26,
        fontWeight: '800',
        color: colors.text,
    },
    subtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        marginTop: 6,
    },
    counterRow: {
        flexDirection: 'row',
        justifyContent: 'flex-end',
        marginTop: -16,
        marginBottom: 20,
    },
    counterText: {
        fontSize: 11,
        fontWeight: '700',
        color: colors.primary,
        backgroundColor: colors.primaryLight,
        paddingHorizontal: 8,
        paddingVertical: 2,
        borderRadius: 4,
    },
    routeBadge: {
        alignSelf: 'flex-start',
        backgroundColor: colors.primaryLight,
        paddingHorizontal: 10,
        paddingVertical: 3,
        borderRadius: 8,
        marginTop: 6,
        marginBottom: 4,
    },
    routeBadgeText: {
        fontSize: 10,
        fontWeight: '800',
        color: colors.primary,
        letterSpacing: 1,
    },
    fieldLabel: {
        fontSize: 13,
        fontWeight: '700',
        color: colors.text,
        marginBottom: 8,
        marginTop: 16,
    },
    senderSelector: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.white,
        borderWidth: 1.5,
        borderColor: colors.border,
        borderRadius: 16,
        paddingHorizontal: 16,
        paddingVertical: 14,
        marginBottom: 16,
        elevation: 1,
    },
    senderSelectorText: {
        flex: 1,
        fontSize: 15,
        fontWeight: '600',
        color: colors.text,
    },
    senderPlaceholder: {
        color: colors.textHint,
        fontWeight: '400',
    },
    senderPickerItem: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 14,
        paddingHorizontal: 4,
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
    },
    senderPickerLabel: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.text,
    },
    senderPickerSub: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 2,
    },
    btn: {
        marginTop: 10,
    },
    scheduleLink: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        marginTop: 20,
        padding: 12,
    },
    scheduleLinkText: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 14,
    },
    tipBox: {
        marginTop: 24,
        padding: 16,
        backgroundColor: colors.surfaceVariant,
        borderRadius: 12,
        borderStyle: 'dashed',
        borderWidth: 1,
        borderColor: colors.border,
    },
    tipText: {
        fontSize: 12,
        color: colors.textSecondary,
        textAlign: 'center',
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: colors.background,
        borderTopLeftRadius: 32,
        borderTopRightRadius: 32,
        padding: 24,
        paddingBottom: 40,
    },
    modalTitle: {
        fontSize: 22,
        fontWeight: '800',
        color: colors.text,
        marginBottom: 8,
    },
    modalSubtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        marginBottom: 24,
        lineHeight: 20,
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
    confirmBtn: {
        flex: 2,
    }
});

export default MessagingScreen;
