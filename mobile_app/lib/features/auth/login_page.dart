import 'package:flutter/material.dart';

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
  final baseUrlCtrl = TextEditingController();
  final SessionStore _store = SessionStore();

  String role = 'driver';
  bool submitting = false;
  String? error;
  bool baseUrlLoaded = false;

  @override
  void initState() {
    super.initState();
    _loadBaseUrl();
  }

  @override
  void dispose() {
    phoneCtrl.dispose();
    passCtrl.dispose();
    baseUrlCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadBaseUrl() async {
    final value = await _store.baseUrl();
    if (!mounted) return;
    setState(() {
      baseUrlCtrl.text = value;
      baseUrlLoaded = true;
    });
  }

  String _normalizeBaseUrl(String value) {
    var url = value.trim();
    if (url.isEmpty) return '';
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'http://$url';
    }
    if (!url.contains('/api/v1')) {
      url = '${url.replaceAll(RegExp(r'\/+$'), '')}/api/v1';
    }
    return url;
  }

  Future<void> _saveBaseUrl() async {
    final normalized = _normalizeBaseUrl(baseUrlCtrl.text);
    if (normalized.isEmpty) {
      throw Exception('Base URL is required.');
    }
    final parsed = Uri.tryParse(normalized);
    if (parsed == null || !(parsed.hasScheme && parsed.host.isNotEmpty)) {
      throw Exception('Invalid Base URL.');
    }
    await _store.setBaseUrl(normalized);
    if (mounted) {
      setState(() => baseUrlCtrl.text = normalized);
    }
  }

  Future<void> login() async {
    setState(() {
      submitting = true;
      error = null;
    });

    try {
      await _saveBaseUrl();
      await widget.api.login(
        phone: phoneCtrl.text.trim(),
        password: passCtrl.text,
        role: role,
      );
      if (!mounted) return;
      widget.onLoggedIn(role);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => submitting = false);
    }
  }

  Future<void> quickLogin(String quickRole) async {
    setState(() {
      submitting = true;
      error = null;
    });

    try {
      await _saveBaseUrl();
      await widget.api.quickLogin(role: quickRole);
      if (!mounted) return;
      widget.onLoggedIn(quickRole == 'merchant' ? 'station' : quickRole);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => submitting = false);
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
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          gradient: AppTheme.actionGradient,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: const Center(child: LogoMark(size: 34)),
                      ),
                    ),
                    const SizedBox(height: 10),
                    const Text(
                      'Login',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Color(0xFFE2E8F0),
                        fontSize: 24,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 12),
                    SegmentedButton<String>(
                      showSelectedIcon: false,
                      style: ButtonStyle(
                        foregroundColor: WidgetStateProperty.resolveWith((
                          states,
                        ) {
                          if (states.contains(WidgetState.selected)) {
                            return Colors.white;
                          }
                          return const Color(0xFFCBD5E1);
                        }),
                        backgroundColor: WidgetStateProperty.resolveWith((
                          states,
                        ) {
                          if (states.contains(WidgetState.selected)) {
                            return const Color(0xFF7C3AED);
                          }
                          return const Color(0xFF0F172A);
                        }),
                        side: WidgetStateProperty.all(
                          const BorderSide(color: Color(0xFF334155)),
                        ),
                      ),
                      segments: const [
                        ButtonSegment(value: 'driver', label: Text('Driver')),
                        ButtonSegment(value: 'station', label: Text('Station')),
                      ],
                      selected: {role},
                      onSelectionChanged: (v) => setState(() => role = v.first),
                    ),
                    const SizedBox(height: 18),
                    const Text(
                      'Base URL',
                      style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                    ),
                    const SizedBox(height: 4),
                    TextField(
                      controller: baseUrlCtrl,
                      enabled: baseUrlLoaded && !submitting,
                      style: const TextStyle(color: Color(0xFFE2E8F0)),
                      decoration: _inputDecoration().copyWith(
                        hintText: 'http://192.168.0.102:43162/api/v1',
                        hintStyle: const TextStyle(color: Color(0xFF64748B)),
                        suffixIcon: IconButton(
                          tooltip: 'Apply Base URL',
                          onPressed: submitting
                              ? null
                              : () async {
                                  final messenger = ScaffoldMessenger.of(
                                    context,
                                  );
                                  setState(() => error = null);
                                  try {
                                    await _saveBaseUrl();
                                    if (!mounted) return;
                                    messenger.showSnackBar(
                                      const SnackBar(
                                        content: Text('Base URL updated'),
                                      ),
                                    );
                                  } catch (e) {
                                    if (!mounted) return;
                                    setState(() => error = e
                                        .toString()
                                        .replaceFirst('Exception: ', ''));
                                  }
                                },
                          icon: const Icon(
                            Icons.check_circle_outline_rounded,
                            color: Color(0xFF94A3B8),
                            size: 20,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
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
                              child: SizedBox(
                                height: 18,
                                width: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              ),
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
                      children: const [
                        Expanded(child: Divider(color: Color(0xFF334155))),
                        Padding(
                          padding: EdgeInsets.symmetric(horizontal: 8),
                          child: Text(
                            'Quick Login',
                            style: TextStyle(
                              color: Color(0xFF94A3B8),
                              fontSize: 13,
                            ),
                          ),
                        ),
                        Expanded(child: Divider(color: Color(0xFF334155))),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: FxButton(
                            label: 'Driver',
                            icon: Icons.directions_car_filled_rounded,
                            onPressed: submitting ? null : () => quickLogin('driver'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: FxButton(
                            label: 'Merchant',
                            icon: Icons.storefront_rounded,
                            onPressed: submitting ? null : () => quickLogin('merchant'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          "Don't have an account? ",
                          style: TextStyle(
                            color: Color(0xFF94A3B8),
                            fontSize: 12,
                          ),
                        ),
                        Text(
                          'Sign up',
                          style: TextStyle(
                            color: Color(0xFFC4B5FD),
                            fontSize: 12,
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
