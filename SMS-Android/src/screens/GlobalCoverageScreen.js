import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TextInput, RefreshControl, ActivityIndicator } from 'react-native';
import FintechCard from '../components/FintechCard';
import Icon from '../components/Icons';
import PremiumStatusBadge from '../components/PremiumStatusBadge';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import apiClient from '../services/apiClient';

const GlobalCoverageScreen = () => {
    const [coverage, setCoverage] = useState([]);
    const [filteredCoverage, setFilteredCoverage] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchCoverage = async () => {
        try {
            const res = await apiClient('/info.php?action=coverage');
            if (res.status === 'success') {
                setCoverage(res.coverage);
                setFilteredCoverage(res.coverage);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchCoverage();
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        fetchCoverage();
    };

    const handleSearch = (text) => {
        setSearch(text);
        if (!text) {
            setFilteredCoverage(coverage);
            return;
        }
        const filtered = coverage.filter(item => 
            item.country.toLowerCase().includes(text.toLowerCase()) || 
            item.code.toLowerCase().includes(text.toLowerCase())
        );
        setFilteredCoverage(filtered);
    };

    const renderItem = ({ item }) => (
        <FintechCard style={styles.card}>
            <View style={styles.row}>
                <View style={styles.countryInfo}>
                    <View style={styles.iconCircle}>
                        <Icon name="global" size={16} color={colors.primary} />
                    </View>
                    <View>
                        <Text style={styles.countryName}>{item.country}</Text>
                        <Text style={styles.countryCode}>Prefix: +{item.code}</Text>
                    </View>
                </View>
                <PremiumStatusBadge 
                    status={item.status === 'Active' ? 'success' : 'danger'} 
                    text={item.status} 
                />
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerCircle} />
                <View style={styles.headerContent}>
                    <Text style={styles.title}>Global Coverage</Text>
                    <Text style={styles.subtitle}>Check SMS delivery availability worldwide</Text>
                    
                    <View style={styles.searchContainer}>
                        <Icon name="search" size={16} color={colors.textHint} />
                        <TextInput
                            style={styles.searchInput}
                            placeholder="Find a country or prefix..."
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
                        data={filteredCoverage}
                        renderItem={renderItem}
                        keyExtractor={(item, index) => index.toString()}
                        contentContainerStyle={styles.list}
                        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                        ListEmptyComponent={
                            !loading && <EmptyState title="No results" subtitle={`We couldn't find any coverage for "${search}"`} />
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
    },
    headerCircle: {
        position: 'absolute',
        bottom: -30,
        left: -30,
        width: 140,
        height: 140,
        borderRadius: 70,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        padding: 24,
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
    iconCircle: {
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
    countryCode: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 2,
    },
    empty: {
        alignItems: 'center',
        marginTop: 40,
    }
});

export default GlobalCoverageScreen;
