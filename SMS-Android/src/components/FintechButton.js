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
        paddingVertical: 15,
        borderRadius: 12,
        alignItems: 'center',
        justifyContent: 'center',
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
    },
    primary: {
        backgroundColor: colors.primary,
    },
    secondary: {
        backgroundColor: colors.secondary,
    },
    success: {
        backgroundColor: colors.success,
    },
    text: {
        color: colors.white,
        fontSize: typography.button.fontSize,
        fontWeight: typography.button.fontWeight,
    }
});

export default FintechButton;
