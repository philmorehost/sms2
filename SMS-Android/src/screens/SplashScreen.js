import React, { useEffect, useRef } from 'react';
import { View, Animated, StyleSheet, Easing, Image } from 'react-native';
import { colors } from '../theme/colors';

const SplashScreen = ({ navigation }) => {
    const spinValue = useRef(new Animated.Value(0)).current;

    useEffect(() => {
        Animated.timing(spinValue, {
            toValue: 1,
            duration: 1500,
            easing: Easing.linear,
            useNativeDriver: true,
        }).start(() => {
            setTimeout(() => {
                navigation.replace('Login');
            }, 500);
        });
    }, [navigation, spinValue]);

    const spin = spinValue.interpolate({
        inputRange: [0, 1],
        outputRange: ['0deg', '360deg'],
    });

    return (
        <View style={styles.container}>
            <Animated.Image
                source={require('../assets/images/logo.png')}
                style={[styles.logo, { transform: [{ rotate: spin }] }]}
                resizeMode="contain"
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.white,
        justifyContent: 'center',
        alignItems: 'center',
    },
    logo: {
        width: 150,
        height: 150,
    },
});

export default SplashScreen;
