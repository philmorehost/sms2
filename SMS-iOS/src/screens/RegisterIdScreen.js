import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
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
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Submission failed');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>Register New ID</Text>

                <View style={styles.typeGrid}>
                    <TypeItem label="Promo" active={type==='promotional'} onPress={()=>setType('promotional')} />
                    <TypeItem label="Corp" active={type==='corporate'} onPress={()=>setType('corporate')} />
                    <TypeItem label="Airtel" active={type==='airtel'} onPress={()=>setType('airtel')} />
                    <TypeItem label="Caller" active={type==='caller'} onPress={()=>setType('caller')} />
                </View>

                {type === 'airtel' && (
                    <FintechInput
                        label="Company Name"
                        value={companyName}
                        onChangeText={setCompanyName}
                        placeholder="Enter registered company name"
                    />
                )}

                <FintechInput
                    label={type === 'caller' ? "Phone Number" : "Sender ID"}
                    value={senderId}
                    onChangeText={setSenderId}
                    placeholder={type === 'caller' ? "e.g. 234..." : "Max 11 chars"}
                />

                {type === 'airtel' && (
                    <FintechInput
                        label="Nature of Business"
                        value={businessNature}
                        onChangeText={setBusinessNature}
                        placeholder="e.g. Finance, Education"
                    />
                )}

                <FintechInput
                    label="Sample Message"
                    value={message}
                    onChangeText={setMessage}
                    placeholder="Enter a sample message for approval..."
                    multiline
                />

                <FintechButton title={loading ? "Submitting..." : "Register ID"} onPress={handleRegister} />
            </ScrollView>
        </SafeAreaView>
    );
};

const TypeItem = ({ label, active, onPress }) => (
    <TouchableOpacity onPress={onPress} style={[styles.typeItem, active && styles.activeType]}>
        <Text style={[styles.typeLabel, active && styles.activeTypeLabel]}>{label}</Text>
    </TouchableOpacity>
);

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text, marginBottom: 24 },
    typeGrid: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 24 },
    typeItem: { paddingVertical: 10, paddingHorizontal: 12, borderRadius: 10, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.border },
    activeType: { backgroundColor: colors.primary, borderColor: colors.primary },
    typeLabel: { fontSize: 12, fontWeight: '600', color: colors.textLight },
    activeTypeLabel: { color: colors.white }
});

export default RegisterIdScreen;
