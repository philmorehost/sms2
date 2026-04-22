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
    const { type, route: msgRoute } = route.params || { type: 'sms', route: 'promotional' };
    const [senderId, setSenderId] = useState('');
    const [recipients, setRecipients] = useState('');
    const [message, setMessage] = useState('');
    const [audioUrl, setAudioUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [senders, setSenders] = useState([]);
    
    const [showScheduleModal, setShowScheduleModal] = useState(false);
    const [scheduleTime, setScheduleTime] = useState('');

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
                    <Text style={styles.subtitle}>Enter details to broadcast your message</Text>
                </View>

                <FintechInput
                    label={type === 'sms' ? "Approved Sender ID" : "Caller ID"}
                    value={senderId}
                    onChangeText={setSenderId}
                    placeholder="e.g. PHILMORE"
                />

                <FintechInput
                    label="Recipient Numbers"
                    value={recipients}
                    onChangeText={setRecipients}
                    placeholder="23480..., 23470..."
                    keyboardType="phone-pad"
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
