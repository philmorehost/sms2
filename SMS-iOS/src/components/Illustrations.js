import React from 'react';
import { View } from 'react-native';
import Svg, { Path, Circle, Rect, Defs, LinearGradient, Stop, G } from 'react-native-svg';
import { colors } from '../theme/colors';

const Illustrations = ({ name, size = 200, style }) => {
    const illustrations = {
        login: (
            <G>
                <Defs>
                    <LinearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                        <Stop offset="0%" stopColor={colors.primary} stopOpacity="0.2" />
                        <Stop offset="100%" stopColor={colors.accent} stopOpacity="0.1" />
                    </LinearGradient>
                </Defs>
                <Circle cx="100" cy="100" r="80" fill="url(#grad1)" />
                <Rect x="70" y="60" width="60" height="80" rx="10" fill={colors.white} stroke={colors.primary} strokeWidth="2" />
                <Rect x="80" y="75" width="40" height="4" rx="2" fill={colors.primaryLight} />
                <Rect x="80" y="85" width="30" height="4" rx="2" fill={colors.primaryLight} />
                <Circle cx="100" cy="115" r="10" fill={colors.accent} />
                <Path d="M120 140l20 20M140 120l-20 20" stroke={colors.primary} strokeWidth="3" strokeLinecap="round" />
            </G>
        ),
        welcome: (
            <G>
                <Circle cx="100" cy="100" r="90" fill={colors.primaryLight} opacity="0.4" />
                <Path d="M60 140c0-20 15-40 40-40s40 20 40 40" fill={colors.primary} />
                <Circle cx="100" cy="70" r="25" fill={colors.primary} />
                <Circle cx="130" cy="60" r="10" fill={colors.accent} />
                <Path d="M150 100l10-10M160 110l-10-10" stroke={colors.accent} strokeWidth="3" />
            </G>
        ),
        empty: (
            <G>
                <Rect x="50" y="50" width="100" height="100" rx="20" fill={colors.surfaceVariant} />
                <Path d="M80 90h40M80 110h20" stroke={colors.textHint} strokeWidth="4" strokeLinecap="round" />
                <Circle cx="130" cy="130" r="25" fill={colors.white} stroke={colors.border} strokeWidth="2" />
                <Path d="M120 130h20M130 120v20" stroke={colors.primary} strokeWidth="3" strokeLinecap="round" />
            </G>
        )
    };

    return (
        <View style={[{ width: size, height: size, justifyContent: 'center', alignItems: 'center' }, style]}>
            <Svg width={size} height={size} viewBox="0 0 200 200">
                {illustrations[name] || illustrations.empty}
            </Svg>
        </View>
    );
};

export default Illustrations;
