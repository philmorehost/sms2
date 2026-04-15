import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';

import SplashScreen from '../screens/SplashScreen';
import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import ForgotPasswordScreen from '../screens/ForgotPasswordScreen';
import DashboardScreen from '../screens/DashboardScreen';
import MessagingScreen from '../screens/MessagingScreen';
import TransactionsScreen from '../screens/TransactionsScreen';
import ProfileScreen from '../screens/ProfileScreen';
import FundWalletScreen from '../screens/FundWalletScreen';
import SupportScreen from '../screens/SupportScreen';
import SupportTicketDetailScreen from '../screens/SupportTicketDetailScreen';
import CreateTicketScreen from '../screens/CreateTicketScreen';
import ReferralScreen from '../screens/ReferralScreen';
import OtpTemplatesScreen from '../screens/OtpTemplatesScreen';
import NumberExtractorScreen from '../screens/NumberExtractorScreen';
import NumberFilterScreen from '../screens/NumberFilterScreen';
import ReportsScreen from '../screens/ReportsScreen';
import PhonebookScreen from '../screens/PhonebookScreen';
import ContactListScreen from '../screens/ContactListScreen';
import RegisterIdScreen from '../screens/RegisterIdScreen';
import PricingScreen from '../screens/PricingScreen';
import GlobalWalletScreen from '../screens/GlobalWalletScreen';
import BirthdaySchedulerScreen from '../screens/BirthdaySchedulerScreen';
import GlobalCoverageScreen from '../screens/GlobalCoverageScreen';
import SchedulesScreen from '../screens/SchedulesScreen';
import { colors } from '../theme/colors';

const Stack = createStackNavigator();
const Tab = createBottomTabNavigator();

const MainTabs = () => (
    <Tab.Navigator
        screenOptions={{
            tabBarActiveTintColor: colors.primary,
            tabBarInactiveTintColor: colors.textLight,
            tabBarStyle: { paddingBottom: 5, height: 60 },
        }}
    >
        <Tab.Screen name="Home" component={DashboardScreen} />
        <Tab.Screen name="Messaging" component={MessagingScreen} />
        <Tab.Screen name="Transactions" component={TransactionsScreen} />
        <Tab.Screen name="Profile" component={ProfileScreen} />
    </Tab.Navigator>
);

const AppNavigator = () => {
    return (
        <NavigationContainer>
            <Stack.Navigator screenOptions={{ headerShown: false }}>
                <Stack.Screen name="Splash" component={SplashScreen} />
                <Stack.Screen name="Login" component={LoginScreen} />
                <Stack.Screen name="Register" component={RegisterScreen} />
                <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
                <Stack.Screen name="Main" component={MainTabs} />
                <Stack.Screen name="FundWallet" component={FundWalletScreen} />
                <Stack.Screen name="Support" component={SupportScreen} />
                <Stack.Screen name="SupportDetail" component={SupportTicketDetailScreen} />
                <Stack.Screen name="CreateTicket" component={CreateTicketScreen} />
                <Stack.Screen name="Referral" component={ReferralScreen} />
                <Stack.Screen name="OtpTemplates" component={OtpTemplatesScreen} />
                <Stack.Screen name="NumberExtractor" component={NumberExtractorScreen} />
                <Stack.Screen name="NumberFilter" component={NumberFilterScreen} />
                <Stack.Screen name="Reports" component={ReportsScreen} />
                <Stack.Screen name="Phonebook" component={PhonebookScreen} />
                <Stack.Screen name="ContactList" component={ContactListScreen} />
                <Stack.Screen name="RegisterId" component={RegisterIdScreen} />
                <Stack.Screen name="Pricing" component={PricingScreen} />
                <Stack.Screen name="GlobalWallet" component={GlobalWalletScreen} />
                <Stack.Screen name="BirthdayScheduler" component={BirthdaySchedulerScreen} />
                <Stack.Screen name="GlobalCoverage" component={GlobalCoverageScreen} />
                <Stack.Screen name="Schedules" component={SchedulesScreen} />
            </Stack.Navigator>
        </NavigationContainer>
    );
};

export default AppNavigator;
