import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const ContactListScreen = ({ route }) => {
    const { groupId, groupName } = route.params;
    const [contacts, setContacts] = useState([]);
    const [refreshing, setRefreshing] = useState(false);

    const fetchContacts = async () => {
        try {
            const res = await apiClient(`/phonebook.php?action=list_contacts&group_id=${groupId}`);
            if (res.status === 'success') setContacts(res.contacts);
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        fetchContacts();
    }, [groupId]);

    const onRefresh = React.useCallback(async () => {
        setRefreshing(true);
        await fetchContacts();
        setRefreshing(false);
    }, []);

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}><Text style={styles.title}>{groupName}</Text></View>
            <FlatList
                data={contacts}
                renderItem={({ item }) => (
                    <FintechCard style={styles.card}>
                        <View>
                            <Text style={styles.name}>{item.first_name} {item.last_name}</Text>
                            <Text style={styles.phone}>{item.phone_number}</Text>
                        </View>
                    </FintechCard>
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
    card: { marginBottom: 12 },
    name: { fontSize: 16, fontWeight: '600', color: colors.text },
    phone: { fontSize: 14, color: colors.primary, marginTop: 4 }
});

export default ContactListScreen;
