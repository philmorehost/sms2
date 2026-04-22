import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TextInput, ActivityIndicator, RefreshControl } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const PricingScreen = () => {
    const [rates, setRates] = useState([]);
    const [filteredRates, setFilteredRates] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchRates = async () => {
        try {
            const res = await apiClient('/info.php?action=pricing');
            if (res.status === 'success') {
                setRates(res.sms_rates);
                setFilteredRates(res.sms_rates);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchRates();
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        fetchRates();
    };

    const handleSearch = (text) => {
        setSearch(text);
        if (!text) {
            setFilteredRates(rates);
            return;
        }
        const filtered = rates.filter(item => 
            item.country.toLowerCase().includes(text.toLowerCase()) || 
            item.network.toLowerCase().includes(text.toLowerCase())
        );
        setFilteredRates(filtered);
    };

    const renderItem = ({ item }) => (
        <FintechCard style={styles.card}>
            <View style={styles.row}>
                <View style={styles.countryInfo}>
                    <View style={styles.flagPlaceholder}>
                        <Icon name="global" size={16} color={colors.primary} />
                    </View>
                    <View>
                        <Text style={styles.countryName}>{item.country}</Text>
                        <Text style={styles.networkName}>{item.network}</Text>
                    </View>
                </View>
                <View style={styles.priceContainer}>
                    <Text style={styles.rateText}>₦{item.rate}</Text>
                    <Text style={styles.unitText}>per unit</Text>
                </View>
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerCircle} />
                <View style={styles.headerContent}>
                    <Text style={styles.title}>SMS Pricing</Text>
                    <Text style={styles.subtitle}>View standard rates per unit</Text>
                    
                    <View style={styles.searchContainer}>
                        <Icon name="search" size={16} color={colors.textHint} />
                        <TextInput
                            style={styles.searchInput}
                            placeholder="Search country or network..."
                            placeholderTextColor={colors.textHint}
                            value={search}
                            onChangeText={handleSearch}
                        />
                    </View>
                </View>
            </View>

            <View style={styles.body}>
                {loading && !refreshing ? (
                    <View style={styles.loader}>
                        <ActivityIndicator size="large" color={colors.primary} />
                    </View>
                ) : (
                    <FlatList
                        data={filteredRates}
                        renderItem={renderItem}
                        keyExtractor={(item, index) => index.toString()}
                        contentContainerStyle={styles.list}
                        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                        ListEmptyComponent={
                            <View style={styles.empty}>
                                <Text style={styles.emptyText}>No rates found for "{search}"</Text>
                            </View>
                        }
                    />
                )}
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    header: {
        height: 220,
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
        paddingTop: 20,
    },
    headerCircle: {
        position: 'absolute',
        top: -30,
        right: -30,
        width: 140,
        height: 140,
        borderRadius: 70,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        padding: 24,
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
        marginBottom: 24,
    },
    searchContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.white,
        borderRadius: 14,
        paddingHorizontal: 16,
        height: 50,
        elevation: 4,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
    },
    searchInput: {
        flex: 1,
        marginLeft: 10,
        fontSize: 15,
        color: colors.text,
    },
    body: {
        flex: 1,
        marginTop: -20,
    },
    list: {
        padding: 24,
        paddingBottom: 40,
    },
    loader: {
        marginTop: 50,
    },
    card: {
        marginBottom: 12,
        padding: 16,
    },
    row: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    countryInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        flex: 1,
    },
    flagPlaceholder: {
        width: 36,
        height: 36,
        borderRadius: 10,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    countryName: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.text,
    },
    networkName: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 2,
    },
    priceContainer: {
        alignItems: 'flex-end',
    },
    rateText: {
        fontSize: 18,
        fontWeight: '800',
        color: colors.primary,
    },
    unitText: {
        fontSize: 10,
        color: colors.textSecondary,
    },
    empty: {
        alignItems: 'center',
        marginTop: 40,
    },
    emptyText: {
        color: colors.textSecondary,
        fontSize: 14,
    }
});

export default PricingScreen;
