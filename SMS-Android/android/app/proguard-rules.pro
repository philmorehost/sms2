# PhilmoreSMS ProGuard Rules

# React Native core
-keep class com.facebook.react.bridge.CatalystInstanceImpl { *; }
-keep class com.facebook.react.bridge.WritableNativeMap { *; }
-keep class com.facebook.react.bridge.WritableNativeArray { *; }
-keep class com.facebook.react.bridge.ReadableNativeMap { *; }
-keep class com.facebook.react.bridge.ReadableNativeArray { *; }

# React Native Biometrics / Keychain
-keep class com.rnbiometrics.** { *; }
-keep class com.oblot.keychain.** { *; }

# React Navigation and other dependencies
-keep class com.swmansion.gesturehandler.** { *; }
-keep class com.swmansion.rnscreens.** { *; }
-keep class com.th3rdwave.safeareacontext.** { *; }
-keep class com.horcrux.svg.** { *; }
-keep class com.reactnativecommunity.clipboard.** { *; }

# OkHttp/Okio
-keepattributes Signature
-keepattributes *Annotation*
-keep class okhttp3.** { *; }
-keep interface okhttp3.** { *; }
-dontwarn okhttp3.**
-dontwarn okio.**

# Android 14 compatibility
-keep class android.os.Bundle { *; }
