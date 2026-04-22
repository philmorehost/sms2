import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import Illustrations from './Illustrations';
import { colors } from '../theme/colors';

const EmptyState = ({ title = "No data found", subtitle = "There's nothing to show here right now.", illustration = "empty" }) => {
    return (
        <View style={styles.container}>
            <Illustrations name={illustration} size={150} />
            <Text style={styles.title}>{title}</Text>
            <Text style={styles.subtitle}>{subtitle}</Text>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
        padding: 40,
        marginTop: 40,
    },
    title: {
        fontSize: 18,
        fontWeight: '700',
        color: colors.text,
        marginTop: 20,
        textAlign: 'center',
    },
    subtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        marginTop: 8,
        textAlign: 'center',
        lineHeight: 20,
    }
});

export default EmptyState;
