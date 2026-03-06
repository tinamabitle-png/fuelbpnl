import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SessionStore {
  static const _tokenKey = 'auth_token';
  static const _userKey = 'auth_user';
  static const _roleKey = 'selected_role';
  static const _baseUrlKey = 'base_url';

  Future<void> saveSession({
    required String token,
    required Map<String, dynamic> user,
    required String role,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user));
    await prefs.setString(_roleKey, role);
  }

  Future<String?> token() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<Map<String, dynamic>?> user() async {
    final prefs = await SharedPreferences.getInstance();
    final value = prefs.getString(_userKey);
    if (value == null || value.isEmpty) return null;
    return jsonDecode(value) as Map<String, dynamic>;
  }

  Future<String?> role() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_roleKey);
  }

  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    await prefs.remove(_roleKey);
  }

  Future<String> baseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_baseUrlKey);
    if (saved != null && saved.isNotEmpty) return saved;
    if (kIsWeb) {
      final host = Uri.base.host;
      if (host.isNotEmpty && host != 'localhost' && host != '127.0.0.1') {
        return 'http://$host:8000/api/v1';
      }
      return 'http://localhost:8000/api/v1';
    }
    return 'http://192.168.0.101:8000/api/v1';
  }

  Future<void> setBaseUrl(String value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_baseUrlKey, value);
  }
}
