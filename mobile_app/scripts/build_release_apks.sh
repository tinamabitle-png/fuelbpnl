#!/usr/bin/env bash
set -euo pipefail

# Ensures Flutter can consistently find the Android SDK/NDK toolchains on macOS.
export ANDROID_SDK_ROOT="${ANDROID_SDK_ROOT:-/Users/apple/Library/Android/sdk}"
export ANDROID_HOME="${ANDROID_HOME:-$ANDROID_SDK_ROOT}"
export ANDROID_NDK_HOME="${ANDROID_NDK_HOME:-$ANDROID_SDK_ROOT/ndk/27.0.12077973}"
export ANDROID_NDK_ROOT="${ANDROID_NDK_ROOT:-$ANDROID_NDK_HOME}"

cd "$(dirname "$0")/.."

flutter pub get

# Universal (most compatible) APK: includes both armeabi-v7a + arm64-v8a.
flutter build apk --release --target-platform android-arm,android-arm64

# Split APKs (smaller): use the armeabi-v7a one for POS devices that require 32-bit ARM.
flutter build apk --release --split-per-abi --target-platform android-arm,android-arm64

echo "Built APKs:"
ls -lah build/app/outputs/flutter-apk/*.apk

