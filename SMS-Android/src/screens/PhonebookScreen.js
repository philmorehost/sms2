import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const PhonebookScreen = ({ navigation }) => {
    const [groups, setGroups] = useState([]);
    const [refreshing, setRefreshing] = useState(false);

    const fetchGroups = async () => {
        try {
            const res = await apiClient('/phonebook.php?action=list_groups');
            if (res.status === 'success') setGroups(res.groups);
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        fetchGroups();
    }, []);

    const onRefresh = React.useCallback(async () => {
        setRefreshing(true);
        await fetchGroups();
        setRefreshing(false);
    }, []);

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}><Text style={styles.title}>Phone Book</Text></View>
            <FlatList
                data={groups}
                renderItem={({ item }) => (
                    <TouchableOpacity onPress={() => navigation.navigate('ContactList', { groupId: item.id, groupName: item.group_name })}>
                        <FintechCard style={styles.card}>
                            <Text style={styles.name}>{item.group_name}</Text>
                            <Text style={styles.date}>Created: {new Date(item.created_at).toLocaleDateString()}</Text>
                        </FintechCard>
                    </TouchableOpacity>
                )}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    header: { padding: 20 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text },
    list: { padding: 20, paddingTop: 0 },
    card: { marginBottom: 12, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    name: { fontSize: 16, fontWeight: '600', color: colors.text },
    date: { fontSize: 12, color: colors.textLight }
});

export default PhonebookScreen;
