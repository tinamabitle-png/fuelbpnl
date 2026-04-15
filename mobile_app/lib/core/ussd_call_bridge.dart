import 'dart:io';

import 'package:flutter/services.dart';

class UssdCallBridge {
  static const MethodChannel _channel = MethodChannel('bwiser/ussd_call');

  static Future<bool> isSupported() async {
    if (!Platform.isAndroid) return false;
    final value = await _channel.invokeMethod<bool>('isSupported');
    return value ?? false;
  }

  static Future<bool> hasCallPermission() async {
    if (!Platform.isAndroid) return false;
    final value = await _channel.invokeMethod<bool>('hasCallPermission');
    return value ?? false;
  }

  static Future<bool> requestCallPermission() async {
    if (!Platform.isAndroid) return false;
    final value = await _channel.invokeMethod<bool>('requestCallPermission');
    return value ?? false;
  }

  static Future<void> launchUssdCall(String code) async {
    if (!Platform.isAndroid) {
      throw PlatformException(
        code: 'unsupported',
        message: 'USSD calling is currently supported on Android only.',
      );
    }

    await _channel.invokeMethod('launchUssdCall', {'code': code});
  }
}
