import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../core/feedback_panel.dart';
import '../../core/theme.dart';
import '../../data/api_client.dart';
import '../../data/models.dart';

class StationShell extends StatefulWidget {
  const StationShell({super.key, required this.api, required this.onLogout});

  final ApiClient api;
  final Future<void> Function() onLogout;

  @override
  State<StationShell> createState() => _StationShellState();
}

class _StationShellState extends State<StationShell> {
  static const String _driverLogoAsset = 'assets/images/driver_logo.png';
  int index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      StationHomePage(api: widget.api),
      StationRedeemPage(api: widget.api),
    ];

    return Scaffold(
      appBar: AppBar(
        leadingWidth: 48,
        leading: Padding(
          padding: const EdgeInsets.only(left: 12, top: 8, bottom: 8),
          child: Container(
            decoration: BoxDecoration(
              gradient: AppTheme.actionGradient,
              borderRadius: BorderRadius.circular(10),
            ),
            padding: const EdgeInsets.all(4),
            child: Image.asset(_driverLogoAsset, fit: BoxFit.contain),
          ),
        ),
        title: const Text('Station Banking'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            onPressed: () async => widget.onLogout(),
          ),
        ],
      ),
      body: AppSurface(child: pages[index]),
      bottomNavigationBar: _StationMenuBar(
        selectedIndex: index,
        onTap: (v) => setState(() => index = v),
      ),
    );
  }
}

class _StationMenuBar extends StatelessWidget {
  const _StationMenuBar({required this.selectedIndex, required this.onTap});

  final int selectedIndex;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(12, 0, 12, 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFDCE4F4)),
      ),
      child: Row(
        children: [
          _item(0, Icons.fact_check_outlined, 'Approved'),
          Container(width: 1, height: 44, color: const Color(0xFFE2E8F0)),
          _item(1, Icons.qr_code_scanner, 'Redeem'),
        ],
      ),
    );
  }

  Widget _item(int i, IconData icon, String tooltip) {
    final active = selectedIndex == i;
    return Expanded(
      child: Tooltip(
        message: tooltip,
        child: InkWell(
          onTap: () => onTap(i),
          borderRadius: BorderRadius.circular(10),
          child: Container(
            height: 52,
            color: active ? const Color(0xFFF3F4F6) : Colors.transparent,
            alignment: Alignment.center,
            child: Icon(
              icon,
              size: 22,
              color: active ? const Color(0xFF4B5563) : const Color(0xFF6B7280),
            ),
          ),
        ),
      ),
    );
  }
}

class StationHomePage extends StatefulWidget {
  const StationHomePage({super.key, required this.api});
  final ApiClient api;

  @override
  State<StationHomePage> createState() => _StationHomePageState();
}

class _StationHomePageState extends State<StationHomePage> {
  bool loading = true;
  String? error;
  List<VoucherItem> items = [];

  @override
  void initState() {
    super.initState();
    fetch();
  }

  Future<void> fetch() async {
    setState(() {
      loading = true;
      error = null;
    });

    try {
      final list = await widget.api.stationApprovedVouchers();
      setState(() => items = list);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: CircularProgressIndicator());
    if (error != null) return Center(child: Text(error!));

    return RefreshIndicator(
      onRefresh: fetch,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: items.length + 1,
        itemBuilder: (context, i) {
          if (i == items.length) {
            return const Padding(
              padding: EdgeInsets.only(top: 14),
              child: FeedbackPanel(),
            );
          }
          final v = items[i];
          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            v.code,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              color: AppTheme.slate,
                            ),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFFE0F2FE), Color(0xFFE8FFF6)],
                            ),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: const Text(
                            'READY',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: AppTheme.navy,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'R ${v.amount.toStringAsFixed(2)} • ${v.fuelType.toUpperCase()}',
                    ),
                    if (v.stationName != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 3),
                        child: Text(
                          v.stationName!,
                          style: const TextStyle(color: Color(0xFF64748B)),
                        ),
                      ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class StationRedeemPage extends StatefulWidget {
  const StationRedeemPage({super.key, required this.api});
  final ApiClient api;

  @override
  State<StationRedeemPage> createState() => _StationRedeemPageState();
}

class _StationRedeemPageState extends State<StationRedeemPage> {
  final inputCtrl = TextEditingController();
  bool submitting = false;
  bool scanMode = false;

  @override
  void dispose() {
    inputCtrl.dispose();
    super.dispose();
  }

  Future<void> redeem(String value) async {
    setState(() => submitting = true);
    try {
      await widget.api.stationRedeem(scanInput: value);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Voucher redeemed successfully.')),
      );
      inputCtrl.clear();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              children: [
                OutlinedButton.icon(
                  onPressed: () => setState(() => scanMode = !scanMode),
                  icon: const Icon(Icons.qr_code_scanner),
                  label: Text(scanMode ? 'Close Scanner' : 'Open Scanner'),
                ),
                const SizedBox(height: 12),
                if (scanMode)
                  SizedBox(
                    height: 280,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: MobileScanner(
                        onDetect: (capture) {
                          final code = capture.barcodes.isNotEmpty
                              ? capture.barcodes.first.rawValue
                              : null;
                          if (code == null || submitting) return;
                          setState(() => scanMode = false);
                          redeem(code);
                        },
                      ),
                    ),
                  ),
                if (scanMode) const SizedBox(height: 12),
                TextField(
                  controller: inputCtrl,
                  minLines: 2,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Paste QR JSON / Voucher Code / HMAC Tap Token',
                  ),
                ),
                const SizedBox(height: 12),
                ElevatedButton.icon(
                  onPressed: submitting
                      ? null
                      : () => redeem(inputCtrl.text.trim()),
                  style: ElevatedButton.styleFrom(
                    padding: EdgeInsets.zero,
                    backgroundColor: Colors.transparent,
                    shadowColor: Colors.transparent,
                  ),
                  icon: const Icon(
                    Icons.check_circle_outline,
                    color: Colors.white,
                  ),
                  label: Ink(
                    decoration: BoxDecoration(
                      gradient: AppTheme.actionGradient,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Container(
                      height: 52,
                      alignment: Alignment.center,
                      child: submitting
                          ? const SizedBox(
                              height: 22,
                              width: 22,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              'Redeem Voucher',
                              style: TextStyle(color: Colors.white),
                            ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        const FeedbackPanel(),
      ],
    );
  }
}
