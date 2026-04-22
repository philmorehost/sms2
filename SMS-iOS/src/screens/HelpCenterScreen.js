import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, TextInput, LayoutAnimation, Platform, UIManager } from 'react-native';
import Icon from '../components/Icons';
import FintechCard from '../components/FintechCard';
import { colors } from '../theme/colors';

if (Platform.OS === 'android') {
    if (UIManager.setLayoutAnimationEnabledExperimental) {
        UIManager.setLayoutAnimationEnabledExperimental(true);
    }
}

const FAQ_DATA = [
    {
        id: 1,
        question: "How do I fund my wallet?",
        answer: "You can fund your wallet by clicking the '+' button on the Dashboard. We support instant bank transfer, USSD, and card payments via Flutterwave."
    },
    {
        id: 2,
        question: "What is a Sender ID?",
        answer: "A Sender ID is the unique name (maximum 11 characters) that appears as the sender of your SMS messages. In Nigeria, all Sender IDs must be registered and approved."
    },
    {
        id: 3,
        question: "How long does Sender ID approval take?",
        answer: "Sender ID approval typically takes 24-48 business hours, depending on the network operator's verification process."
    },
    {
        id: 4,
        question: "Why was my message not delivered?",
        answer: "Non-delivery can be caused by various factors including: invalid recipient number, DND (Do Not Disturb) being active on the phone, or insufficient wallet balance."
    },
    {
        id: 5,
        question: "How do I activate DND delivery?",
        answer: "We offer special 'Corporate' and 'OTP' routes that can bypass DND for important notifications. You can select these routes in the Messaging screen."
    }
];

const HelpCenterScreen = ({ navigation }) => {
    const [search, setSearch] = useState('');
    const [expandedId, setExpandedId] = useState(null);

    const toggleExpand = (id) => {
        LayoutAnimation.configureNext(LayoutAnimation.Presets.easeInEaseOut);
        setExpandedId(expandedId === id ? null : id);
    };

    const filteredFaq = FAQ_DATA.filter(faq => 
        faq.question.toLowerCase().includes(search.toLowerCase()) ||
        faq.answer.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <SafeAreaView style={styles.container}>
            <ScrollView contentContainerStyle={styles.scroll}>
                <View style={styles.header}>
                    <Text style={styles.title}>Help Center</Text>
                    <Text style={styles.subtitle}>How can we assist you today?</Text>
                    
                    <View style={styles.searchContainer}>
                        <Icon name="search" size={18} color={colors.textHint} />
                        <TextInput
                            style={styles.searchInput}
                            placeholder="Search help articles..."
                            placeholderTextColor={colors.textHint}
                            value={search}
                            onChangeText={setSearch}
                        />
                    </View>
                </View>

                <View style={[styles.section, { marginTop: 10 }]}>
                    <Text style={styles.sectionTitle}>Need Immediate Help?</Text>
                    <View style={styles.supportRow}>
                        <TouchableOpacity style={styles.supportBox}>
                            <View style={[styles.supportIcon, { backgroundColor: '#25D366' }]}>
                                <Icon name="support" size={20} color={colors.white} />
                            </View>
                            <Text style={styles.supportLabel}>WhatsApp</Text>
                        </TouchableOpacity>
                        
                        <TouchableOpacity style={styles.supportBox} onPress={() => navigation.navigate('CreateTicket')}>
                            <View style={[styles.supportIcon, { backgroundColor: colors.primary }]}>
                                <Icon name="sms" size={20} color={colors.white} />
                            </View>
                            <Text style={styles.supportLabel}>New Ticket</Text>
                        </TouchableOpacity>
                        
                        <TouchableOpacity style={styles.supportBox}>
                            <View style={[styles.supportIcon, { backgroundColor: colors.warning }]}>
                                <Icon name="phonebook" size={20} color={colors.white} />
                            </View>
                            <Text style={styles.supportLabel}>Call Us</Text>
                        </TouchableOpacity>
                    </View>
                </View>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>Frequently Asked Questions</Text>
                    {filteredFaq.map((item) => (
                        <TouchableOpacity 
                            key={item.id} 
                            activeOpacity={0.7} 
                            onPress={() => toggleExpand(item.id)}
                        >
                            <FintechCard style={styles.faqCard}>
                                <View style={styles.faqHeader}>
                                    <Text style={styles.faqQuestion}>{item.question}</Text>
                                    <Icon 
                                        name={expandedId === item.id ? "chevronRight" : "chevronRight"} 
                                        size={14} 
                                        color={colors.textSecondary}
                                        style={expandedId === item.id ? { transform: [{ rotate: '90deg' }] } : {}}
                                    />
                                </View>
                                {expandedId === item.id && (
                                    <View style={styles.faqBody}>
                                        <Text style={styles.faqAnswer}>{item.answer}</Text>
                                    </View>
                                )}
                            </FintechCard>
                        </TouchableOpacity>
                    ))}
                    {filteredFaq.length === 0 && (
                        <Text style={styles.noResults}>No matching questions found.</Text>
                    )}
                </View>

                <TouchableOpacity style={styles.guideCard}>
                    <View style={styles.guideIcon}>
                        <Icon name="global" size={24} color={colors.primary} />
                    </View>
                    <View style={styles.guideInfo}>
                        <Text style={styles.guideTitle}>View Full User Guide</Text>
                        <Text style={styles.guideSubtitle}>Comprehensive manual for all platform features</Text>
                    </View>
                    <Icon name="chevronRight" size={20} color={colors.primary} />
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    scroll: {
        padding: 24,
        paddingBottom: 40,
    },
    header: {
        marginBottom: 24,
    },
    title: {
        fontSize: 32,
        fontWeight: '800',
        color: colors.text,
    },
    subtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        marginTop: 4,
        marginBottom: 24,
    },
    searchContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.white,
        borderRadius: 16,
        paddingHorizontal: 16,
        height: 56,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 10,
    },
    searchInput: {
        flex: 1,
        marginLeft: 12,
        fontSize: 16,
        color: colors.text,
    },
    section: {
        marginBottom: 32,
    },
    sectionTitle: {
        fontSize: 15,
        fontWeight: '700',
        color: colors.text,
        marginBottom: 16,
    },
    supportRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
    supportBox: {
        flex: 1,
        backgroundColor: colors.white,
        padding: 16,
        borderRadius: 20,
        alignItems: 'center',
        marginHorizontal: 4,
        elevation: 1,
    },
    supportIcon: {
        width: 44,
        height: 44,
        borderRadius: 22,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 8,
    },
    supportLabel: {
        fontSize: 12,
        fontWeight: '700',
        color: colors.text,
    },
    faqCard: {
        marginBottom: 12,
        padding: 16,
    },
    faqHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    faqQuestion: {
        fontSize: 14,
        fontWeight: '700',
        color: colors.text,
        flex: 1,
        paddingRight: 10,
    },
    faqBody: {
        marginTop: 12,
        paddingTop: 12,
        borderTopWidth: 1,
        borderTopColor: colors.border,
    },
    faqAnswer: {
        fontSize: 14,
        color: colors.textSecondary,
        lineHeight: 22,
    },
    noResults: {
        textAlign: 'center',
        color: colors.textHint,
        marginTop: 20,
    },
    guideCard: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.primaryLight + '20',
        padding: 20,
        borderRadius: 24,
        borderWidth: 1,
        borderColor: colors.primaryLight,
    },
    guideIcon: {
        width: 50,
        height: 50,
        borderRadius: 12,
        backgroundColor: colors.white,
        justifyContent: 'center',
        alignItems: 'center',
    },
    guideInfo: {
        flex: 1,
        marginHorizontal: 16,
    },
    guideTitle: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.primary,
    },
    guideSubtitle: {
        fontSize: 11,
        color: colors.textSecondary,
        marginTop: 2,
    }
});

export default HelpCenterScreen;
