import 'package:flutter/material.dart';

import 'core/app_loader.dart';
import 'core/session_store.dart';
import 'core/theme.dart';
import 'data/api_client.dart';
import 'features/auth/login_page.dart';
import 'features/driver/driver_shell.dart';
import 'features/station/station_shell.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const BwiserApp());
}

class BwiserApp extends StatefulWidget {
  const BwiserApp({super.key});

  @override
  State<BwiserApp> createState() => _BwiserAppState();
}

class _BwiserAppState extends State<BwiserApp> {
  final store = SessionStore();
  late final ApiClient api = ApiClient(store);
  bool loading = true;
  String? role;

  @override
  void initState() {
    super.initState();
    _restore();
  }

  Future<void> _restore() async {
    final token = await store.token();
    final storedRole = await store.role();
    if (token == null || storedRole == null) {
      if (!mounted) return;
      setState(() => loading = false);
      return;
    }

    try {
      await api.profile();
      if (!mounted) return;
      setState(() {
        role = storedRole;
        loading = false;
      });
    } catch (_) {
      await store.clear();
      if (!mounted) return;
      setState(() {
        role = null;
        loading = false;
      });
    }
  }

  void _onLogin(String selectedRole) {
    setState(() => role = selectedRole);
  }

  Future<void> _onLogout() async {
    await store.clear();
    setState(() => role = null);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'bwiser',
      theme: AppTheme.light,
      home: loading
          ? const Scaffold(body: Center(child: AppLoader()))
          : role == 'driver'
          ? DriverShell(api: api, onLogout: _onLogout)
          : role == 'station'
          ? StationShell(api: api, onLogout: _onLogout)
          : LoginPage(api: api, onLoggedIn: _onLogin),
    );
  }
}
