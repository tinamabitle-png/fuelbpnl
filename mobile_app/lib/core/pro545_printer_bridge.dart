import 'package:flutter/services.dart';

class Pro545PrinterBridge {
  static const MethodChannel _channel = MethodChannel('bwiser/pro545_printer');

  static Future<bool> isAvailable() async {
    final value = await _channel.invokeMethod<bool>('isAvailable');
    return value ?? false;
  }

  static Future<void> printReceipt(Map<String, dynamic> payload) async {
    await _channel.invokeMethod('printReceipt', {'payload': payload});
  }
}

