import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const OtpTemplatesScreen = () => {
    const [templates, setTemplates] = useState([]);

    useEffect(() => {
        const fetchTemplates = async () => {
            const res = await apiClient('/services.php?action=otp_templates');
            if (res.status === 'success') setTemplates(res.templates);
        };
        fetchTemplates();
    }, []);

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.content}>
                <Text style={styles.title}>OTP Templates</Text>
                <FlatList
                    data={templates}
                    renderItem={({ item }) => (
                        <FintechCard style={styles.card}>
                            <View style={styles.header}>
                                <Text style={styles.name}>{item.template_name}</Text>
                                <View style={[styles.badge, { backgroundColor: item.status === 'approved' ? colors.success : colors.warning }]}>
                                    <Text style={styles.badgeText}>{item.status.toUpperCase()}</Text>
                                </View>
                            </View>
                            <Text style={styles.code}>Code: {item.template_code}</Text>
                            <Text style={styles.body}>{item.message_body}</Text>
                        </FintechCard>
                    )}
                    keyExtractor={item => item.template_code}
                />
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24, flex: 1 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text, marginBottom: 24 },
    card: { marginBottom: 16 },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
    name: { fontSize: 16, fontWeight: '700', color: colors.text },
    badge: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4 },
    badgeText: { fontSize: 10, color: colors.white, fontWeight: '700' },
    code: { fontSize: 12, color: colors.primary, fontWeight: '600', marginBottom: 8 },
    body: { fontSize: 14, color: colors.textLight, lineHeight: 20 }
});

export default OtpTemplatesScreen;
