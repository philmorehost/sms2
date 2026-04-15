import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList } from 'react-native';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const GlobalCoverageScreen = () => {
    const [coverage, setCoverage] = useState([]);

    useEffect(() => {
        const fetchCoverage = async () => {
            const res = await apiClient('/info.php?action=coverage');
            if (res.status === 'success') setCoverage(res.coverage);
        };
        fetchCoverage();
    }, []);

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}><Text style={styles.title}>Global Coverage</Text></View>
            <FlatList
                data={coverage}
                renderItem={({ item }) => (
                    <View style={styles.row}>
                        <Text style={styles.country}>{item.country} ({item.code})</Text>
                        <Text style={[styles.status, { color: item.status === 'Active' ? colors.success : colors.danger }]}>{item.status}</Text>
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
    status: { fontSize: 12, fontWeight: '700' }
});

export default GlobalCoverageScreen;
