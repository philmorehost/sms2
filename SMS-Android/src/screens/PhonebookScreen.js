import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, RefreshControl, Modal, Alert, ActivityIndicator } from 'react-native';
import FintechCard from '../components/FintechCard';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import phonebookService from '../services/phonebookService';

const PhonebookScreen = ({ navigation }) => {
    const [groups, setGroups] = useState([]);
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);
    const [modalVisible, setModalVisible] = useState(false);
    const [newGroupName, setNewGroupName] = useState('');
    const [creating, setCreating] = useState(false);

    const fetchGroups = async () => {
        try {
            const res = await phonebookService.listGroups();
            if (res.status === 'success') {
                setGroups(res.groups);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchGroups();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchGroups();
    }, []);

    const handleAddGroup = async () => {
        if (!newGroupName) {
            Alert.alert('Error', 'Please enter a group name');
            return;
        }

        setCreating(true);
        try {
            const res = await phonebookService.addGroup(newGroupName);
            if (res.status === 'success') {
                setNewGroupName('');
                setModalVisible(false);
                fetchGroups();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to create group');
        } finally {
            setCreating(false);
        }
    };

    const handleDeleteGroup = (group) => {
        Alert.alert(
            'Delete Group',
            `Are you sure you want to delete "${group.group_name}" and all its contacts?`,
            [
                { text: 'Cancel', style: 'cancel' },
                { 
                    text: 'Delete', 
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            const res = await phonebookService.deleteGroup(group.id);
                            if (res.status === 'success') {
                                fetchGroups();
                            } else {
                                Alert.alert('Error', res.message);
                            }
                        } catch (error) {
                            Alert.alert('Error', 'Failed to delete group');
                        }
                    }
                }
            ]
        );
    };

    const renderItem = ({ item }) => (
        <TouchableOpacity 
            activeOpacity={0.7}
            onPress={() => navigation.navigate('ContactList', { groupId: item.id, groupName: item.group_name })}
        >
            <FintechCard style={styles.card}>
                <View style={styles.cardContent}>
                    <View style={styles.infoArea}>
                        <View style={styles.iconCircle}>
                            <Icon name="phonebook" size={20} color={colors.primary} />
                        </View>
                        <View>
                            <Text style={styles.groupName}>{item.group_name}</Text>
                            <Text style={styles.stats}>Created: {new Date(item.created_at).toLocaleDateString()}</Text>
                        </View>
                    </View>
                    <View style={styles.actionArea}>
                        <TouchableOpacity 
                            style={styles.deleteBtn} 
                            onPress={() => handleDeleteGroup(item)}
                        >
                            <Icon name="sms" size={18} color={colors.danger} />
                        </TouchableOpacity>
                        <Icon name="chevronRight" size={16} color={colors.textHint} />
                    </View>
                </View>
            </FintechCard>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerCircle} />
                <View style={styles.headerContent}>
                    <View>
                        <Text style={styles.title}>Phone Book</Text>
                        <Text style={styles.subtitle}>Manage your contact groups</Text>
                    </View>
                    <TouchableOpacity 
                        style={styles.addBtn}
                        onPress={() => setModalVisible(true)}
                    >
                        <Icon name="plus" size={16} color={colors.white} />
                    </TouchableOpacity>
                </View>
            </View>

            <View style={styles.body}>
                <FlatList
                    data={groups}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={!loading && <EmptyState title="No groups yet" subtitle="Create your first group to start organizing contacts." />}
                />
            </View>

            <Modal
                transparent
                visible={modalVisible}
                animationType="fade"
                onRequestClose={() => setModalVisible(false)}
            >
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <Text style={styles.modalTitle}>Create New Group</Text>
                        <FintechInput
                            label="Group Name"
                            value={newGroupName}
                            onChangeText={setNewGroupName}
                            placeholder="e.g. Family, Clients, etc."
                            autoFocus
                        />
                        <View style={styles.modalButtons}>
                            <TouchableOpacity 
                                style={styles.cancelBtn} 
                                onPress={() => setModalVisible(false)}
                            >
                                <Text style={styles.cancelText}>Cancel</Text>
                            </TouchableOpacity>
                            <FintechButton 
                                title={creating ? "Creating..." : "Save Group"} 
                                onPress={handleAddGroup}
                                style={styles.saveBtn}
                            />
                        </View>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    header: {
        height: 180,
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
    },
    headerCircle: {
        position: 'absolute',
        top: -30,
        right: -30,
        width: 150,
        height: 150,
        borderRadius: 75,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 30,
        marginTop: 20,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: colors.white,
    },
    subtitle: {
        fontSize: 14,
        color: 'rgba(255,255,255,0.8)',
        marginTop: 4,
    },
    addBtn: {
        width: 48,
        height: 48,
        borderRadius: 24,
        backgroundColor: 'rgba(255,255,255,0.2)',
        justifyContent: 'center',
        alignItems: 'center',
    },
    body: {
        flex: 1,
        marginTop: -30,
    },
    list: {
        padding: 24,
        paddingBottom: 40,
    },
    card: {
        marginBottom: 16,
        padding: 16,
    },
    cardContent: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    infoArea: {
        flexDirection: 'row',
        alignItems: 'center',
        flex: 1,
    },
    actionArea: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    deleteBtn: {
        padding: 8,
        marginRight: 4,
    },
    iconCircle: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    groupName: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.text,
    },
    stats: {
        fontSize: 12,
        color: colors.textSecondary,
        marginTop: 2,
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'center',
        padding: 24,
    },
    modalContent: {
        backgroundColor: colors.background,
        borderRadius: 24,
        padding: 24,
        elevation: 8,
    },
    modalTitle: {
        fontSize: 20,
        fontWeight: '800',
        color: colors.text,
        marginBottom: 20,
    },
    modalButtons: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 10,
    },
    cancelBtn: {
        flex: 1,
        alignItems: 'center',
    },
    cancelText: {
        color: colors.textSecondary,
        fontWeight: '600',
    },
    saveBtn: {
        flex: 2,
    }
});

export default PhonebookScreen;
