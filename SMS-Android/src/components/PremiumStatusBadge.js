import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { colors } from '../theme/colors';

const PremiumStatusBadge = ({ status, text, style }) => {
    const getStatusStyles = () => {
        const s = status ? status.toLowerCase() : 'default';
        switch (s) {
            case 'success':
            case 'approved':
            case 'completed':
            case 'active':
                return {
                    bg: colors.successLight,
                    text: colors.success,
                };
            case 'danger':
            case 'failed':
            case 'rejected':
            case 'cancelled':
                return {
                    bg: colors.dangerLight,
                    text: colors.danger,
                };
            case 'warning':
            case 'pending':
                return {
                    bg: colors.warningLight,
                    text: colors.warning,
                };
            case 'info':
            case 'processing':
                return {
                    bg: colors.infoLight,
                    text: colors.info,
                };
            default:
                return {
                    bg: colors.surfaceVariant,
                    text: colors.textSecondary,
                };
        }
    };

    const styles_mapped = getStatusStyles();

    return (
        <View style={[styles.badge, { backgroundColor: styles_mapped.bg }, style]}>
            <Text style={[styles.text, { color: styles_mapped.text }]}>
                {text || status.toUpperCase()}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    badge: {
        paddingHorizontal: 10,
        paddingVertical: 4,
        borderRadius: 8,
        alignSelf: 'flex-start',
    },
    text: {
        fontSize: 10,
        fontWeight: '800',
        letterSpacing: 0.5,
    }
});

export default PremiumStatusBadge;
