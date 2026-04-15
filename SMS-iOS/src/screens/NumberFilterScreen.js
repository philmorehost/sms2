import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const NumberFilterScreen = () => {
    const [numbers, setNumbers] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);

    const handleFilter = async () => {
        if (!numbers) return;
        setLoading(true);
        try {
            const body = new URLSearchParams();
            body.append('numbers', numbers);
            const res = await apiClient('/tools.php?action=filter', {
                method: 'POST',
                body: body.toString()
            });
            if (res.status === 'success') {
                setResults(res.numbers);
            }
        } catch (error) {
            Alert.alert('Error', 'Filtering failed');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>Number Filter</Text>
                <FintechInput
                    label="Phone Numbers"
                    value={numbers}
                    onChangeText={setNumbers}
                    placeholder="Enter numbers separated by comma or space..."
                    multiline
                />
                <FintechButton title={loading ? "Filtering..." : "Filter Numbers"} onPress={handleFilter} />

                {results.length > 0 && (
                    <View style={styles.resultBox}>
                        <Text style={styles.resultTitle}>Valid Numbers ({results.length}):</Text>
                        <Text style={styles.resultText}>{results.join(', ')}</Text>
                    </View>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: colors.background },
    content: { padding: 24 },
    title: { fontSize: 24, fontWeight: '700', color: colors.text, marginBottom: 24 },
    resultBox: { marginTop: 24, padding: 15, backgroundColor: colors.white, borderRadius: 12 },
    resultTitle: { fontSize: 16, fontWeight: '700', marginBottom: 8 },
    resultText: { fontSize: 14, color: colors.success, lineHeight: 20 }
});

export default NumberFilterScreen;
