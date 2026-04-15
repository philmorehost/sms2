import React from 'react';
import { StatusBar, useEffect } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import AppNavigator from './src/navigation/AppNavigator';

const App = () => {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <StatusBar, useEffect barStyle="dark-content" />
      <AppNavigator />
    </GestureHandlerRootView>
  );
};

export default App;
