import 'dart:async';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/app_loader.dart';
import '../../core/app_sfx.dart';
import '../../core/fx_button.dart';
import '../../core/logo_mark.dart';
import '../../core/session_store.dart';
import '../../core/theme.dart';
import '../../data/api_client.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key, required this.api, required this.onLoggedIn});

  final ApiClient api;
  final ValueChanged<String> onLoggedIn;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final phoneCtrl = TextEditingController();
  final passCtrl = TextEditingController();
  final SessionStore _store = SessionStore();

  bool submitting = false;
  String? error;

  @override
  void initState() {
    super.initState();
    unawaited(_ensureBaseUrl());
  }

  @override
  void dispose() {
    phoneCtrl.dispose();
    passCtrl.dispose();
    super.dispose();
  }

  Future<void> _ensureBaseUrl() async {
    await _store.setBaseUrl('https://www.bwiser.co.za/api/v1');
  }

  Future<void> login() async {
    setState(() {
      submitting = true;
      error = null;
    });

    try {
      await _ensureBaseUrl();
      await widget.api.login(
        phone: phoneCtrl.text.trim(),
        password: passCtrl.text,
      );
      final activeRole = (await _store.role()) ?? 'driver';
      if (activeRole == 'station') {
        unawaited(AppSfx.playWiserTone());
      }
      if (!mounted) return;
      widget.onLoggedIn(activeRole);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => submitting = false);
    }
  }

  Future<void> _openRegister() async {
    final uri = Uri.parse('https://www.bwiser.co.za/register');
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      setState(() => error = 'Could not open registration page.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.mist,
      body: SafeArea(
        child: Container(
          decoration: const BoxDecoration(gradient: AppTheme.shellGradient),
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(18),
              child: Container(
                width: 360,
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF0B1220), Color(0xFF111827)],
                  ),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: const Color(0xFF334155)),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x334F46E5),
                      blurRadius: 24,
                      offset: Offset(0, 12),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Center(
                      child: Container(
                        width: 62,
                        height: 62,
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          gradient: AppTheme.actionGradient,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: const Center(child: LogoMark(size: 34)),
                      ),
                    ),
                    const SizedBox(height: 10),
                    const Text(
                      'bwiser',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Color(0xFFE2E8F0),
                        fontSize: 24,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 12),
                    const SizedBox(height: 18),
                    const Text(
                      'Phone Number',
                      style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                    ),
                    const SizedBox(height: 4),
                    TextField(
                      controller: phoneCtrl,
                      style: const TextStyle(color: Color(0xFFE2E8F0)),
                      decoration: _inputDecoration(),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Password',
                      style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                    ),
                    const SizedBox(height: 4),
                    TextField(
                      controller: passCtrl,
                      obscureText: true,
                      style: const TextStyle(color: Color(0xFFE2E8F0)),
                      decoration: _inputDecoration(),
                    ),
                    const SizedBox(height: 8),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton(
                        onPressed: () {},
                        child: const Text(
                          'Forgot Password ?',
                          style: TextStyle(
                            color: Color(0xFF94A3B8),
                            fontSize: 12,
                          ),
                        ),
                      ),
                    ),
                    if (error != null)
                      Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFF3B0D17),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFF7F1D1D)),
                        ),
                        child: Text(
                          error!,
                          style: const TextStyle(color: Color(0xFFFCA5A5)),
                        ),
                      ),
                    submitting
                        ? const SizedBox(
                            height: 54,
                            child: Center(
                              child: AppLoader(size: 24, showText: false),
                            ),
                          )
                        : FxButton(
                            label: 'Sign in',
                            icon: Icons.login_rounded,
                            fullWidth: true,
                            onPressed: login,
                          ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Text(
                          "Don't have an account? ",
                          style: TextStyle(
                            color: Color(0xFF94A3B8),
                            fontSize: 12,
                          ),
                        ),
                        GestureDetector(
                          onTap: _openRegister,
                          child: const Text(
                            'Sign up',
                            style: TextStyle(
                              color: Color(0xFFC4B5FD),
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              decoration: TextDecoration.underline,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  InputDecoration _inputDecoration() {
    return InputDecoration(
      isDense: true,
      filled: true,
      fillColor: const Color(0xFF0F172A),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFF334155)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFF7C3AED)),
      ),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFF334155)),
      ),
    );
  }
}
