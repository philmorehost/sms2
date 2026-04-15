import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const SchedulesScreen = () => {
    const [schedules, setSchedules] = useState([]);

    useEffect(() => {
        const fetchSchedules = async () => {
            const res = await apiClient('/scheduler.php?action=list_schedules');
            if (res.status === 'success') setSchedules(res.schedules);
        };
        fetchSchedules();
    }, []);

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}><Text style={styles.title}>Pending Schedules</Text></View>
            <FlatList
                data={schedules}
                renderItem={({ item }) => (
                    <FintechCard style={styles.card}>
                        <Text style={styles.type}>{item.task_type.toUpperCase()}</Text>
                        <Text style={styles.date}>Scheduled for: {new Date(item.scheduled_for).toLocaleString()}</Text>
                        <Text style={styles.status}>Status: {item.status}</Text>
                    </FintechCard>
                )}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={styles.list}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    header: { padding: 20 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text },
    list: { padding: 20, paddingTop: 0 },
    card: { marginBottom: 12 },
    type: { fontSize: 16, fontWeight: '700', color: colors.primary },
    date: { fontSize: 14, color: colors.text, marginTop: 4 },
    status: { fontSize: 12, color: colors.textLight, marginTop: 4 }
});

export default SchedulesScreen;
