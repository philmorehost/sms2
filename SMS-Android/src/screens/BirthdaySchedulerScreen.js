import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const BirthdaySchedulerScreen = () => {
    const [birthdays, setBirthdays] = useState([]);
    const [refreshing, setRefreshing] = useState(false);

    const fetchBirthdays = async () => {
        const res = await apiClient('/scheduler.php?action=list_birthdays');
        if (res.status === 'success') setBirthdays(res.birthdays);
    };

    useEffect(() => { fetchBirthdays(); }, []);

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}><Text style={styles.title}>Birthday Scheduler</Text></View>
            <FlatList
                data={birthdays}
                renderItem={({ item }) => (
                    <FintechCard style={styles.card}>
                        <Text style={styles.name}>{item.name}</Text>
                        <Text style={styles.info}>{item.phone_number} • {item.date_of_birth}</Text>
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
    name: { fontSize: 16, fontWeight: '700', color: colors.text },
    info: { fontSize: 14, color: colors.textLight, marginTop: 4 }
});

export default BirthdaySchedulerScreen;
