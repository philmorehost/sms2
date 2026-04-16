import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';
import smsService from '../services/smsService';
import senderService from '../services/senderService';
import apiClient from '../services/apiClient';

const MessagingScreen = ({ route }) => {
    const { type, route: msgRoute } = route.params || { type: 'sms', route: 'promotional' };
    const [senderId, setSenderId] = useState('');
    const [recipients, setRecipients] = useState('');
    const [message, setMessage] = useState('');
    const [audioUrl, setAudioUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [senders, setSenders] = useState([]);

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
                // voice_audio
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

    const getTitle = () => {
        if (type === 'sms') return msgRoute === 'global' ? 'Global SMS' : 'Bulk SMS';
        if (type === 'voice') return 'Voice SMS (TTS)';
        return 'Voice from File';
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>{getTitle()}</Text>

                <FintechInput
                    label={type === 'sms' ? "Sender ID" : "Caller ID"}
                    value={senderId}
                    onChangeText={setSenderId}
                    placeholder="Enter approved ID"
                />

                <FintechInput
                    label="Recipients"
                    value={recipients}
                    onChangeText={setRecipients}
                    placeholder="e.g. 2348012345678, 2349087654321"
                    keyboardType="phone-pad"
                />

                {type === 'voice_audio' ? (
                    <FintechInput
                        label="Audio File URL"
                        value={audioUrl}
                        onChangeText={setAudioUrl}
                        placeholder="https://example.com/audio.mp3"
                    />
                ) : (
                    <FintechInput
                        label="Message"
                        value={message}
                        onChangeText={setMessage}
                        placeholder="Type your message here..."
                        multiline
                    />
                )}

                <FintechButton
                    title={loading ? "Sending..." : "Send Now"}
                    onPress={handleSend}
                    style={styles.btn}
                />
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
    title: {
        fontSize: typography.h2.fontSize,
        fontWeight: typography.h2.fontWeight,
        color: colors.text,
        marginBottom: 24,
    },
    btn: {
        marginTop: 10,
    }
});

export default MessagingScreen;
