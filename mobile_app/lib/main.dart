import 'package:flutter/material.dart';
import 'dart:async';
import 'dart:ui';

import 'core/app_loader.dart';
import 'core/session_store.dart';
import 'core/theme.dart';
import 'data/api_client.dart';
import 'features/auth/login_page.dart';
import 'features/driver/driver_shell.dart';
import 'features/station/station_shell.dart';

final ValueNotifier<String?> _fatalError = ValueNotifier<String?>(null);

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    _fatalError.value = details.exceptionAsString();
  };
  PlatformDispatcher.instance.onError = (error, stack) {
    _fatalError.value = error.toString();
    return true;
  };

  runZonedGuarded(
    () => runApp(const BwiserBootstrap()),
    (error, stack) {
      _fatalError.value = error.toString();
    },
  );
}

class BwiserBootstrap extends StatelessWidget {
  const BwiserBootstrap({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String?>(
      valueListenable: _fatalError,
      builder: (context, error, _) {
        if (error != null) {
          return MaterialApp(
            debugShowCheckedModeBanner: false,
            theme: AppTheme.light,
            home: StartupRecoveryScreen(
              message: error,
              onRetry: () => _fatalError.value = null,
            ),
          );
        }
        return const BwiserApp();
      },
    );
  }
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
    try {
      final token = await store.token();
      final storedRole = await store.role();
      if (token == null || storedRole == null) {
        if (!mounted) return;
        setState(() => loading = false);
        return;
      }

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
      title: 'BWiser',
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

class StartupRecoveryScreen extends StatelessWidget {
  const StartupRecoveryScreen({
    super.key,
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppSurface(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'We hit a startup issue',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 10),
                      const Text(
                        'Bwiser recovered instead of closing. You can retry below.',
                      ),
                      const SizedBox(height: 12),
                      Text(
                        message,
                        style: const TextStyle(
                          color: Color(0xFFFCA5A5),
                          fontSize: 12,
                        ),
                      ),
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: onRetry,
                          child: const Text('Retry startup'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
