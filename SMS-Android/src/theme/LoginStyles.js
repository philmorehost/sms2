import { StyleSheet } from 'react-native';
import { colors } from './colors';

export const loginStyles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: colors.background,
    },
    scroll: {
        flexGrow: 1,
    },
    header: {
        height: 280,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: colors.primary,
        borderBottomLeftRadius: 40,
        borderBottomRightRadius: 40,
        overflow: 'hidden',
    },
    headerCircle: {
        position: 'absolute',
        top: -50,
        right: -50,
        width: 180,
        height: 180,
        borderRadius: 90,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    logoContainer: {
        width: 140,
        height: 140,
        backgroundColor: colors.white,
        borderRadius: 70,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 10,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 5 },
        shadowOpacity: 0.2,
        shadowRadius: 10,
    },
    logo: {
        width: 100,
        height: 100,
    },
    content: {
        flex: 1,
        padding: 30,
        marginTop: -20,
        backgroundColor: colors.background,
        borderTopLeftRadius: 30,
        borderTopRightRadius: 30,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: colors.text,
        marginBottom: 8,
    },
    subtitle: {
        fontSize: 14,
        color: colors.textSecondary,
        marginBottom: 32,
        lineHeight: 20,
    },
    forgotLink: {
        alignSelf: 'flex-end',
        marginBottom: 24,
        marginTop: -10,
    },
    forgotText: {
        color: colors.primary,
        fontWeight: '700',
        fontSize: 14,
    },
    loginBtn: {
        marginTop: 10,
        borderRadius: 16,
    },
    registerLink: {
        marginTop: 32,
        alignItems: 'center',
    },
    registerText: {
        color: colors.textSecondary,
        fontSize: 14,
    },
    registerAction: {
        color: colors.primary,
        fontWeight: '700',
    },
    biometricBtn: {
        marginTop: 30,
        alignItems: 'center',
        justifyContent: 'center',
    },
    biometricText: {
        marginTop: 10,
        color: colors.primary,
        fontWeight: '600',
        fontSize: 14,
    }
});
