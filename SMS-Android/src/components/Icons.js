import React from 'react';
import { Svg, Path, Rect, Circle, G } from 'react-native-svg';
import { colors } from '../theme/colors';

const Icon = ({ name, size = 24, color = colors.text, style }) => {
    const icons = {
        sms: (
            <Path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        ),
        global: (
            <>
                <Circle cx="12" cy="12" r="10" />
                <Path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
            </>
        ),
        voice: (
            <>
                <Path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z" />
                <Path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v4M8 23h8" />
            </>
        ),
        music: (
            <>
                <Path d="M9 18V5l12-2v13" />
                <Circle cx="6" cy="18" r="3" />
                <Circle cx="18" cy="16" r="3" />
            </>
        ),
        otp: (
            <>
                <Rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <Path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </>
        ),
        refer: (
            <>
                <Path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <Circle cx="9" cy="7" r="4" />
                <Path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            </>
        ),
        support: (
            <>
                <Path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
            </>
        ),
        extractor: (
            <>
                <Path d="M3 12h18M3 6h18M3 18h18" />
                <Path d="M7 12l5 5 5-5M7 6l5 5 5-5" />
            </>
        ),
        filter: (
            <Path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
        ),
        phonebook: (
            <>
                <Path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                <Path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
            </>
        ),
        reports: (
            <>
                <Path d="M12 20V10M18 20V4M6 20v-4" />
            </>
        ),
        register: (
            <>
                <Rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <Path d="M16 2v4M8 2v4M3 10h18" />
            </>
        ),
        wallet: (
            <>
                <Path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4" />
                <Path d="M4 6v12c0 1.1.9 2 2 2h14v-4" />
                <Path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z" />
            </>
        ),
        pricing: (
            <>
                <Circle cx="12" cy="12" r="10" />
                <Path d="M12 8v8M8 12h8" />
            </>
        ),
        birthday: (
            <>
                <Path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <Circle cx="12" cy="7" r="4" />
            </>
        ),
        coverage: (
            <>
                <Path d="M5 12.55a11 11 0 0 1 14.08 0" />
                <Path d="M1.42 9a16 16 0 0 1 21.16 0" />
                <Path d="M8.59 16.11a6 6 0 0 1 6.82 0" />
                <Path d="M12 20h.01" />
            </>
        ),
        schedules: (
            <>
                <Circle cx="12" cy="12" r="10" />
                <Path d="M12 6v6l4 2" />
            </>
        ),
        plus: <Path d="M12 5v14M5 12h14" />,
        copy: (
            <>
                <Rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                <Path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
            </>
        ),
        chevronRight: <Path d="M9 18l6-6-6-6" />,
        user: (
            <>
                <Path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <Circle cx="12" cy="7" r="4" />
            </>
        ),
    };

    return (
        <Svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke={color}
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            style={style}
        >
            {icons[name] || icons.sms}
        </Svg>
    );
};

export default Icon;
