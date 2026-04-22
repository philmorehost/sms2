import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, Alert, TouchableOpacity, Clipboard } from 'react-native';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const NumberExtractorScreen = () => {
    const [text, setText] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);

    const handleExtract = async () => {
        if (!text) {
            Alert.alert('Error', 'Please enter some text to extract numbers from.');
            return;
        }
        
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
                if (res.numbers.length === 0) {
                    Alert.alert('No Results', 'We couldn\'t find any valid phone numbers in the provided text.');
                }
            } else {
                Alert.alert('Error', res.message || 'Extraction failed');
            }
        } catch (error) {
            Alert.alert('Error', 'An unexpected error occurred during extraction');
        } finally {
            setLoading(false);
        }
    };

    const copyToClipboard = () => {
        if (results.length > 0) {
            Clipboard.setString(results.join(', '));
            Alert.alert('Copied', 'All extracted numbers have been copied to your clipboard.');
        }
    };

    const clearAll = () => {
        setText('');
        setResults([]);
    };

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.scrollContent}>
                <View style={styles.header}>
                    <View style={styles.headerCircle} />
                    <View style={styles.headerContent}>
                        <Text style={styles.title}>Number Extractor</Text>
                        <Text style={styles.subtitle}>Pull phone numbers from any block of text</Text>
                    </View>
                </View>

                <View style={styles.body}>
                    <FintechInput
                        label="Source Content"
                        value={text}
                        onChangeText={setText}
                        placeholder="Paste your text here (emails, chats, lists...)"
                        multiline
                        numberOfLines={8}
                        style={styles.input}
                    />
                    
                    <View style={styles.actionRow}>
                        <TouchableOpacity style={styles.clearBtn} onPress={clearAll}>
                            <Text style={styles.clearText}>Clear Input</Text>
                        </TouchableOpacity>
                        <FintechButton 
                            title={loading ? "Extracting..." : "Extract Numbers"} 
                            onPress={handleExtract} 
                            style={styles.extractBtn}
                        />
                    </View>

                    {results.length > 0 && (
                        <View style={styles.resultContainer}>
                            <View style={styles.resultHeader}>
                                <View>
                                    <Text style={styles.resultTitle}>Extracted Results</Text>
                                    <Text style={styles.resultCount}>{results.length} valid numbers found</Text>
                                </View>
                                <TouchableOpacity onPress={copyToClipboard} style={styles.copyBtn}>
                                    <Icon name="sms" size={16} color={colors.primary} style={{ marginRight: 6 }} />
                                    <Text style={styles.copyText}>Copy All</Text>
                                </TouchableOpacity>
                            </View>
                            <View style={styles.resultBox}>
                                <Text style={styles.resultText}>{results.join(', ')}</Text>
                            </View>
                        </View>
                    )}
                    
                    <View style={styles.tipBox}>
                        <Icon name="global" size={18} color={colors.textSecondary} style={{ marginBottom: 10 }} />
                        <Text style={styles.tipText}>
                            This tool automatically identifies international and local number formats from your provided content.
                        </Text>
                    </View>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    scrollContent: {
        flexGrow: 1,
    },
    header: {
        height: 200,
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
        justifyContent: 'center',
        paddingHorizontal: 24,
    },
    headerCircle: {
        position: 'absolute',
        top: -30,
        left: -30,
        width: 150,
        height: 150,
        borderRadius: 75,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        marginTop: 20,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: colors.white,
    },
    subtitle: {
        fontSize: 14,
        color: 'rgba(255,255,255,0.7)',
        marginTop: 4,
    },
    body: {
        padding: 24,
        marginTop: -30,
    },
    input: {
        backgroundColor: colors.white,
        borderRadius: 20,
        paddingTop: 16,
        minHeight: 180,
    },
    actionRow: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 10,
    },
    clearBtn: {
        flex: 1,
        alignItems: 'center',
    },
    clearText: {
        color: colors.textSecondary,
        fontWeight: '600',
        fontSize: 14,
    },
    extractBtn: {
        flex: 2,
    },
    resultContainer: {
        marginTop: 32,
    },
    resultHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 16,
    },
    resultTitle: {
        fontSize: 18,
        fontWeight: '800',
        color: colors.text,
    },
    resultCount: {
        fontSize: 12,
        color: colors.success,
        fontWeight: '600',
        marginTop: 2,
    },
    copyBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.primaryLight,
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 10,
    },
    copyText: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 13,
    },
    resultBox: {
        backgroundColor: colors.white,
        borderRadius: 20,
        padding: 20,
        borderWidth: 1,
        borderColor: colors.border,
        borderStyle: 'dashed',
    },
    resultText: {
        fontSize: 14,
        color: colors.text,
        lineHeight: 22,
        letterSpacing: 0.5,
    },
    tipBox: {
        marginTop: 40,
        alignItems: 'center',
        paddingHorizontal: 40,
    },
    tipText: {
        fontSize: 12,
        color: colors.textSecondary,
        textAlign: 'center',
        lineHeight: 18,
    }
});

export default NumberExtractorScreen;
