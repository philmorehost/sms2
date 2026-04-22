import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, RefreshControl, Modal, Alert, ActivityIndicator } from 'react-native';
import FintechCard from '../components/FintechCard';
import FintechInput from '../components/FintechInput';
import FintechButton from '../components/FintechButton';
import Icon from '../components/Icons';
import EmptyState from '../components/EmptyState';
import { colors } from '../theme/colors';
import phonebookService from '../services/phonebookService';

const ContactListScreen = ({ route, navigation }) => {
    const { groupId, groupName } = route.params;
    const [contacts, setContacts] = useState([]);
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);
    
    const [modalVisible, setModalVisible] = useState(false);
    const [firstName, setFirstName] = useState('');
    const [lastName, setLastName] = useState('');
    const [phone, setPhone] = useState('');
    const [creating, setCreating] = useState(false);

    const fetchContacts = async () => {
        try {
            const res = await phonebookService.listContacts(groupId);
            if (res.status === 'success') {
                setContacts(res.contacts);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchContacts();
    }, [groupId]);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchContacts();
    }, []);

    const handleAddContact = async () => {
        if (!firstName || !phone) {
            Alert.alert('Error', 'First name and phone number are required');
            return;
        }

        setCreating(true);
        try {
            const res = await phonebookService.addContact(groupId, firstName, lastName, phone);
            if (res.status === 'success') {
                setFirstName('');
                setLastName('');
                setPhone('');
                setModalVisible(false);
                fetchContacts();
            } else {
                Alert.alert('Error', res.message);
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to add contact');
        } finally {
            setCreating(false);
        }
    };

    const handleDeleteContact = (contact) => {
        Alert.alert(
            'Delete Contact',
            `Remove ${contact.first_name} ${contact.last_name} from this group?`,
            [
                { text: 'Cancel', style: 'cancel' },
                { 
                    text: 'Delete', 
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            const res = await phonebookService.deleteContact(contact.id);
                            if (res.status === 'success') {
                                fetchContacts();
                            } else {
                                Alert.alert('Error', res.message);
                            }
                        } catch (error) {
                            Alert.alert('Error', 'Failed to delete contact');
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
                    <View style={styles.avatarCircle}>
                        <Text style={styles.avatarText}>{item.first_name[0]}{item.last_name ? item.last_name[0] : ''}</Text>
                    </View>
                    <View>
                        <Text style={styles.name}>{item.first_name} {item.last_name}</Text>
                        <Text style={styles.phone}>{item.phone_number}</Text>
                    </View>
                </View>
                <TouchableOpacity onPress={() => handleDeleteContact(item)}>
                    <Icon name="sms" size={20} color={colors.danger} />
                </TouchableOpacity>
            </View>
        </FintechCard>
    );

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View style={styles.headerCircle} />
                <View style={styles.navRow}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="chevronRight" size={24} color={colors.white} style={{ transform: [{ rotate: '180deg' }] }} />
                    </TouchableOpacity>
                    <TouchableOpacity 
                        style={styles.addBtn}
                        onPress={() => setModalVisible(true)}
                    >
                        <Icon name="plus" size={16} color={colors.white} />
                    </TouchableOpacity>
                </View>
                <View style={styles.headerTitleArea}>
                    <Text style={styles.title}>{groupName}</Text>
                    <Text style={styles.subtitle}>{contacts.length} Contacts</Text>
                </View>
            </View>

            <View style={styles.body}>
                <FlatList
                    data={contacts}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={!loading && <EmptyState title="No contacts" subtitle="Start adding contacts to this group." />}
                />
            </View>

            <Modal
                transparent
                visible={modalVisible}
                animationType="slide"
                onRequestClose={() => setModalVisible(false)}
            >
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <Text style={styles.modalTitle}>Add New Contact</Text>
                        
                        <FintechInput
                            label="First Name"
                            value={firstName}
                            onChangeText={setFirstName}
                            placeholder="e.g. John"
                        />
                        <FintechInput
                            label="Last Name"
                            value={lastName}
                            onChangeText={setLastName}
                            placeholder="e.g. Doe"
                        />
                        <FintechInput
                            label="Phone Number"
                            value={phone}
                            onChangeText={setPhone}
                            placeholder="e.g. 23480..."
                            keyboardType="phone-pad"
                        />

                        <View style={styles.modalButtons}>
                            <TouchableOpacity 
                                style={styles.cancelBtn} 
                                onPress={() => setModalVisible(false)}
                            >
                                <Text style={styles.cancelText}>Cancel</Text>
                            </TouchableOpacity>
                            <FintechButton 
                                title={creating ? "Adding..." : "Add Contact"} 
                                onPress={handleAddContact}
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
        height: 200,
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
        padding: 24,
    },
    headerCircle: {
        position: 'absolute',
        bottom: -50,
        left: -50,
        width: 200,
        height: 200,
        borderRadius: 100,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    navRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginTop: 10,
    },
    backBtn: {
        width: 40,
        height: 40,
        borderRadius: 12,
        backgroundColor: 'rgba(255,255,255,0.2)',
        justifyContent: 'center',
        alignItems: 'center',
    },
    addBtn: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: 'rgba(255,255,255,0.2)',
        justifyContent: 'center',
        alignItems: 'center',
    },
    headerTitleArea: {
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
    avatarCircle: {
        width: 44,
        height: 44,
        borderRadius: 22,
        backgroundColor: colors.primaryLight,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    avatarText: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.primary,
    },
    name: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.text,
    },
    phone: {
        fontSize: 13,
        color: colors.textSecondary,
        marginTop: 2,
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: colors.background,
        borderTopLeftRadius: 32,
        borderTopRightRadius: 32,
        padding: 24,
        paddingBottom: 40,
    },
    modalTitle: {
        fontSize: 22,
        fontWeight: '800',
        color: colors.text,
        marginBottom: 24,
    },
    modalButtons: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 20,
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

export default ContactListScreen;
