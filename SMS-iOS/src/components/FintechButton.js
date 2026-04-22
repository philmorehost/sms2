import React from 'react';
import { TouchableOpacity, Text, StyleSheet } from 'react-native';
import { colors } from '../theme/colors';
import { typography } from '../theme/typography';

const FintechButton = ({ title, onPress, type = 'primary', style }) => {
    return (
        <TouchableOpacity
            style={[styles.button, styles[type], style]}
            onPress={onPress}
        >
            <Text style={styles.text}>{title}</Text>
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    button: {
        paddingVertical: 16,
        paddingHorizontal: 24,
        borderRadius: 14,
        alignItems: 'center',
        justifyContent: 'center',
        elevation: 4,
        shadowColor: colors.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 8,
    },
    primary: {
        backgroundColor: colors.primary,
    },
    secondary: {
        backgroundColor: colors.textSecondary,
    },
    success: {
        backgroundColor: colors.success,
        shadowColor: colors.success,
    },
    danger: {
        backgroundColor: colors.danger,
        shadowColor: colors.danger,
    },
    text: {
        color: colors.white,
        fontSize: 16,
        fontWeight: '700',
        letterSpacing: 0.5,
    }
});

export default FintechButton;
