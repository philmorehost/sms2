# React Native Mobile App - SMS Platform

This directory contains the React Native source code for the Android application.

## Prerequisites
- Node.js (v18 or newer)
- Android Studio & SDK
- JDK 17

## Setup Instructions

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Initialize Native Folders**
   Since the native `android` folder is excluded to keep the repository clean, you need to initialize it:
   ```bash
   npx react-native eject
   # OR if using a specific template
   npx react-native init SMSApp --template react-native-template-typescript --directory .
   ```

3. **Configure API**
   Update the `BASE_URL` in `src/services/apiClient.js` to match your server's domain.

4. **Run Application**
   ```bash
   npm run android
   ```

## Building for Production

To build a release APK, navigate to the `android` directory and run:
```bash
cd android
./gradlew assembleRelease
```
The APK will be located at `android/app/build/outputs/apk/release/app-release.apk`.
