# React Native Mobile App - SMS Platform

This directory contains the React Native source code for the iOS application.

## Prerequisites
- Node.js (v18 or newer)
- Xcode (latest version)
- CocoaPods
- macOS

## Setup Instructions

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Initialize Native Folders**
   Since the native `ios` folder is excluded to keep the repository clean, you need to initialize it:
   ```bash
   npx react-native eject
   # OR if using a specific template
   npx react-native init PhilmoreSMS --template react-native-template-typescript --directory .
   ```

3. **Install CocoaPods**
   ```bash
   cd ios
   pod install
   ```

4. **Configure API**
   Update the `BASE_URL` in `src/services/apiClient.js` to match your server's domain.

5. **Run Application**
   ```bash
   npm run ios
   ```

## Building for Production

To build for iOS, open the workspace in Xcode:
```bash
open ios/PhilmoreSMS.xcworkspace
```
Configure your signing certificates and click **Archive** under the **Product** menu.
