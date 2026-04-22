import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, RefreshControl, TouchableOpacity, Modal, Alert } from 'react-native';
import FintechCard from '../components/FintechCard';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import schedulerService from '../services/schedulerService';

const BirthdaySchedulerScreen = () => {
    const [birthdays, setBirthdays] = useState([]);
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);
    const [modalVisible, setModalVisible] = useState(false);
    
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [dob, setDob] = useState('');
    const [creating, setCreating] = useState(false);

    const fetchBirthdays = async () => {
        try {
            const res = await schedulerService.listBirthdays();
            if (res.status === 'success') {
                setBirthdays(res.birthdays);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchBirthdays();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchBirthdays();
    }, []);

    const handleAddBirthday = async () => {
        if (!name || !phone || !dob) {
            Alert.alert('Error', 'Please fill in all fields');
            return;
        }

        setCreating(true);
        try {
            const res = await schedulerService.addBirthday(name, phone, dob);
            if (res.status === 'success') {
                setName('');
                setPhone('');
                setDob('');
                setModalVisible(false);
                fetchBirthdays();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to add birthday entry');
        } finally {
            setCreating(false);
        }
    };

    const handleDeleteBirthday = (item) => {
        Alert.alert(
            'Delete Entry',
            `Remove ${item.name} from the birthday scheduler?`,
            [
                { text: 'Cancel', style: 'cancel' },
                { 
                    text: 'Delete', 
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            const res = await schedulerService.deleteBirthday(item.id);
                            if (res.status === 'success') {
                                fetchBirthdays();
                            } else {
                                Alert.alert('Error', res.message);
                            }
                        } catch (error) {
                            Alert.alert('Error', 'Failed to delete entry');
                        }
                    }
                }
            ]
        );
    };

    const renderItem = ({ item }) => (
        <FintechCard style={styles.card}>
            <View style={styles.cardContent}>
                <View style={styles.infoArea}>
                    <View style={styles.iconCircle}>
                        <Icon name="profile" size={20} color={colors.primary} />
                    </View>
                    <View>
                        <Text style={styles.name}>{item.name}</Text>
                        <Text style={styles.info}>{item.phone_number} • {item.date_of_birth}</Text>
                    </View>
                </View>
                <TouchableOpacity onPress={() => handleDeleteBirthday(item)}>
                    <Icon name="sms" size={20} color={colors.danger} />
                </TouchableOpacity>
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerCircle} />
                <View style={styles.headerContent}>
                    <View>
                        <Text style={styles.title}>Birthday Scheduler</Text>
                        <Text style={styles.subtitle}>Automated birthday greetings</Text>
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
                    data={birthdays}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={!loading && <EmptyState title="No birthdays" subtitle="Add your friends to send them automated wishes." />}
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
                        <Text style={styles.modalTitle}>New Birthday Entry</Text>
                        
                        <FintechInput
                            label="Full Name"
                            value={name}
                            onChangeText={setName}
                            placeholder="e.g. Jane Doe"
                        />
                        <FintechInput
                            label="Phone Number"
                            value={phone}
                            onChangeText={setPhone}
                            placeholder="e.g. 23480..."
                            keyboardType="phone-pad"
                        />
                        <FintechInput
                            label="Birthday (MM-DD)"
                            value={dob}
                            onChangeText={setDob}
                            placeholder="e.g. 12-25"
                        />

                        <View style={styles.modalButtons}>
                            <TouchableOpacity 
                                style={styles.cancelBtn} 
                                onPress={() => setModalVisible(false)}
                            >
                                <Text style={styles.cancelText}>Cancel</Text>
                            </TouchableOpacity>
                            <FintechButton 
                                title={creating ? "Adding..." : "Save Entry"} 
                                onPress={handleAddBirthday}
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
        top: -20,
        left: -20,
        width: 140,
        height: 140,
        borderRadius: 70,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    headerContent: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 24,
        marginTop: 20,
    },
    title: {
        fontSize: 26,
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
        marginBottom: 12,
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
    name: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.text,
    },
    info: {
        fontSize: 13,
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

export default BirthdaySchedulerScreen;
