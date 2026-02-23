import 'package:flutter/material.dart';

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
  static const String _driverLogoAsset = 'assets/images/driver_logo.png';
  final phoneCtrl = TextEditingController();
  final passCtrl = TextEditingController();

  String role = 'driver';
  bool submitting = false;
  String? error;

  @override
  void dispose() {
    phoneCtrl.dispose();
    passCtrl.dispose();
    super.dispose();
  }

  Future<void> login() async {
    setState(() {
      submitting = true;
      error = null;
    });

    try {
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
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFDCE4F4)),
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
                        child: Image.asset(
                          _driverLogoAsset,
                          fit: BoxFit.contain,
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    const Text(
                      'Login',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: AppTheme.slate,
                        fontSize: 26,
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
                          return const Color(0xFF334155);
                        }),
                        backgroundColor: WidgetStateProperty.resolveWith((
                          states,
                        ) {
                          if (states.contains(WidgetState.selected)) {
                            return AppTheme.green;
                          }
                          return const Color(0xFFF1F5F9);
                        }),
                        side: WidgetStateProperty.all(
                          const BorderSide(color: Color(0xFFCBD5E1)),
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
                      'Username',
                      style: TextStyle(color: Color(0xFF64748B), fontSize: 13),
                    ),
                    const SizedBox(height: 4),
                    TextField(
                      controller: phoneCtrl,
                      style: const TextStyle(color: AppTheme.slate),
                      decoration: _inputDecoration(),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Password',
                      style: TextStyle(color: Color(0xFF64748B), fontSize: 13),
                    ),
                    const SizedBox(height: 4),
                    TextField(
                      controller: passCtrl,
                      obscureText: true,
                      style: const TextStyle(color: AppTheme.slate),
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
                            color: Color(0xFF64748B),
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
                    ElevatedButton(
                      onPressed: submitting ? null : login,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.green,
                        foregroundColor: Colors.white,
                        minimumSize: const Size.fromHeight(48),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                      child: submitting
                          ? const SizedBox(
                              height: 18,
                              width: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              'Sign in',
                              style: TextStyle(fontWeight: FontWeight.w700),
                            ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: const [
                        Expanded(child: Divider(color: Color(0xFF374151))),
                        Padding(
                          padding: EdgeInsets.symmetric(horizontal: 8),
                          child: Text(
                            'Login with social accounts',
                            style: TextStyle(
                              color: Color(0xFF64748B),
                              fontSize: 13,
                            ),
                          ),
                        ),
                        Expanded(child: Divider(color: Color(0xFF374151))),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        _socialIcon(Icons.g_mobiledata_rounded),
                        const SizedBox(width: 8),
                        _socialIcon(Icons.flutter_dash),
                        const SizedBox(width: 8),
                        _socialIcon(Icons.code),
                      ],
                    ),
                    const SizedBox(height: 14),
                    const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          "Don't have an account? ",
                          style: TextStyle(
                            color: Color(0xFF64748B),
                            fontSize: 12,
                          ),
                        ),
                        Text(
                          'Sign up',
                          style: TextStyle(
                            color: AppTheme.greenDeep,
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
      fillColor: const Color(0xFFF8FAFC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: AppTheme.green),
      ),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
      ),
    );
  }

  Widget _socialIcon(IconData icon) {
    return Container(
      height: 40,
      width: 40,
      decoration: BoxDecoration(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: const Color(0xFFCBD5E1)),
      ),
      child: Icon(icon, color: const Color(0xFF475569), size: 20),
    );
  }
}
