import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import supportService from '../services/supportService';

const CreateTicketScreen = ({ navigation }) => {
    const [subject, setSubject] = useState('');
    const [message, setMessage] = useState('');
    const [loading, setLoading] = useState(false);

    const handleCreate = async () => {
        if (!subject || !message) {
            Alert.alert('Error', 'Please fill in all fields');
            return;
        }
        setLoading(true);
        try {
            const res = await supportService.create(subject, message);
            if (res.status === 'success') {
                Alert.alert('Success', res.message, [
                    { text: 'OK', onPress: () => navigation.goBack() }
                ]);
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>Open New Ticket</Text>

                <FintechInput
                    label="Subject"
                    value={subject}
                    onChangeText={setSubject}
                    placeholder="What is the issue about?"
                />

                <FintechInput
                    label="Message"
                    value={message}
                    onChangeText={setMessage}
                    placeholder="Describe your issue in detail..."
                    multiline
                />

                <FintechButton
                    title={loading ? "Submitting..." : "Submit Ticket"}
                    onPress={handleCreate}
                />
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text, marginBottom: 24 }
});

export default CreateTicketScreen;
