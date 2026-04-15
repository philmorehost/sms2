import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const NumberExtractorScreen = () => {
    const [text, setText] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);

    const handleExtract = async () => {
        if (!text) return;
        setLoading(true);
        try {
            const body = new URLSearchParams();
            body.append('text', text);
            const res = await apiClient('/tools.php?action=extract', {
                method: 'POST',
                body: body.toString()
            });
            if (res.status === 'success') {
                setResults(res.numbers);
                if (res.numbers.length === 0) Alert.alert('Notice', 'No phone numbers found.');
            }
        } catch (error) {
            Alert.alert('Error', 'Extraction failed');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.title}>Number Extractor</Text>
                <FintechInput
                    label="Paste Text Here"
                    value={text}
                    onChangeText={setText}
                    placeholder="Enter text containing phone numbers..."
                    multiline
                />
                <FintechButton title={loading ? "Extracting..." : "Extract Numbers"} onPress={handleExtract} />

                {results.length > 0 && (
                    <View style={styles.resultBox}>
                        <Text style={styles.resultTitle}>Extracted ({results.length}):</Text>
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
    resultText: { fontSize: 14, color: colors.primary, lineHeight: 20 }
});

export default NumberExtractorScreen;
