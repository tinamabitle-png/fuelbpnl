import 'package:flutter/services.dart';

class NfcHceBridge {
  static const MethodChannel _channel = MethodChannel('bwiser/nfc_hce');

  static Future<bool> isAvailable() async {
    final value = await _channel.invokeMethod<bool>('isAvailable');
    return value ?? false;
  }

  static Future<void> setTapToken(String token) async {
    await _channel.invokeMethod('setTapToken', {'token': token});
  }

  static Future<void> clearTapToken() async {
    await _channel.invokeMethod('clearTapToken');
  }
}

