import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList } from 'react-native';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const PricingScreen = () => {
    const [rates, setRates] = useState([]);

    useEffect(() => {
        const fetchRates = async () => {
            const res = await apiClient('/info.php?action=pricing');
            if (res.status === 'success') setRates(res.sms_rates);
        };
        fetchRates();
    }, []);

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}><Text style={styles.title}>SMS Pricing</Text></View>
            <FlatList
                data={rates}
                renderItem={({ item }) => (
                    <View style={styles.row}>
                        <Text style={styles.country}>{item.country} ({item.network})</Text>
                        <Text style={styles.rate}>₦{item.rate}/unit</Text>
                    </View>
                )}
                keyExtractor={(item, index) => index.toString()}
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
    row: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 15, borderBottomWidth: 1, borderBottomColor: colors.border },
    country: { fontSize: 14, color: colors.text, flex: 1 },
    rate: { fontSize: 14, fontWeight: '700', color: colors.primary }
});

export default PricingScreen;
