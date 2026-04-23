import React from 'react';
import { TouchableOpacity, View, Text, StyleSheet } from 'react-native';
import Icon from './Icons';
import { colors } from '../theme/colors';

const ServiceItem = ({ icon, label, onPress, bg, iconColor }) => (
    <TouchableOpacity style={styles.serviceItem} onPress={onPress}>
        <View style={[styles.serviceIcon, { backgroundColor: bg }]}>
            <Icon name={icon} size={28} color={iconColor} />
        </View>
        <Text style={styles.serviceLabel}>{label}</Text>
    </TouchableOpacity>
);

const styles = StyleSheet.create({
    serviceItem: {
        width: '23%',
        alignItems: 'center',
        marginBottom: 24,
    },
    serviceIcon: {
        width: 64,
        height: 64,
        borderRadius: 20,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 10,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.05,
        shadowRadius: 8,
    },
    serviceLabel: {
        fontSize: 11,
        fontWeight: '700',
        color: colors.textSecondary,
        textAlign: 'center',
    },
});

export default ServiceItem;
