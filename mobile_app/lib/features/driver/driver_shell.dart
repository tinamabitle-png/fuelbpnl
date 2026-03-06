import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/feedback_panel.dart';
import '../../core/fx_button.dart';
import '../../core/nfc_hce_bridge.dart';
import '../../core/logo_mark.dart';
import '../../core/theme.dart';
import '../../data/api_client.dart';
import '../../data/models.dart';

class DriverShell extends StatefulWidget {
  const DriverShell({super.key, required this.api, required this.onLogout});

  final ApiClient api;
  final Future<void> Function() onLogout;

  @override
  State<DriverShell> createState() => _DriverShellState();
}

class _DriverShellState extends State<DriverShell> {
  int index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      DriverHomePage(
        api: widget.api,
        onOpenVouchers: () => setState(() => index = 1),
        onOpenApply: () => setState(() => index = 4),
      ),
      DriverVouchersPage(
        api: widget.api,
        onOpenApply: () => setState(() => index = 4),
      ),
      DriverRepaymentsPage(api: widget.api),
      DriverProfilePage(api: widget.api),
      DriverApplyVoucherPage(api: widget.api),
    ];

    const titles = ['Dashboard', 'Vouchers', 'Repay', 'Profile', 'Apply'];

    return Scaffold(
      appBar: AppBar(
        leadingWidth: 48,
        leading: Padding(
          padding: const EdgeInsets.only(left: 12, top: 8, bottom: 8),
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: const Center(child: LogoMark(size: 22)),
          ),
        ),
        title: Text(titles[index]),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none_rounded),
            onPressed: () {},
          ),
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_horiz),
            onSelected: (v) async {
              if (v == 'logout') {
                await widget.onLogout();
              }
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'logout', child: Text('Logout')),
            ],
          ),
        ],
      ),
      body: AppSurface(child: pages[index]),
      bottomNavigationBar: _DriverBottomNav(
        selectedIndex: index,
        onTap: (v) => setState(() => index = v),
      ),
    );
  }
}

class _DriverBottomNav extends StatelessWidget {
  const _DriverBottomNav({required this.selectedIndex, required this.onTap});

  final int selectedIndex;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    final items = <_DriverNavItem>[
      const _DriverNavItem('Home', Icons.home_outlined),
      const _DriverNavItem('Vouchers', Icons.confirmation_number_outlined),
      const _DriverNavItem('Repay', Icons.receipt_long_outlined),
      const _DriverNavItem('Profile', Icons.person_outline_rounded),
    ];

    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFF0B1220),
        border: Border(top: BorderSide(color: Color(0xFF334155))),
      ),
      padding: const EdgeInsets.only(top: 4, bottom: 4),
      child: Row(
        children: List.generate(items.length, (i) {
          final active = selectedIndex == i;
          final item = items[i];
          return Expanded(
            child: InkWell(
              onTap: () => onTap(i),
              child: SizedBox(
                height: 60,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      item.icon,
                      size: 22,
                      color: active
                          ? AppTheme.primaryBlue
                          : const Color(0xFF94A3B8),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      item.label,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: active ? FontWeight.w700 : FontWeight.w500,
                        color: active
                            ? AppTheme.primaryBlue
                            : const Color(0xFF94A3B8),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        }),
      ),
    );
  }
}

class _DriverNavItem {
  const _DriverNavItem(this.label, this.icon);
  final String label;
  final IconData icon;
}

class DriverHomePage extends StatefulWidget {
  const DriverHomePage({
    super.key,
    required this.api,
    required this.onOpenVouchers,
    required this.onOpenApply,
  });

  final ApiClient api;
  final VoidCallback onOpenVouchers;
  final VoidCallback onOpenApply;

  @override
  State<DriverHomePage> createState() => _DriverHomePageState();
}

class _DriverHomePageState extends State<DriverHomePage> {
  bool loading = true;
  String? error;
  Map<String, dynamic> profile = {};
  List<VoucherItem> vouchers = [];
  List<RepaymentItem> repayments = [];

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
    final p = await widget.api.profile().catchError((_) => <String, dynamic>{});
    final v = await widget.api.driverVouchers().catchError(
      (_) => <VoucherItem>[],
    );
    final r = await widget.api.driverRepayments().catchError(
      (_) => <RepaymentItem>[],
    );

    if (!mounted) return;
    setState(() {
      profile = p;
      vouchers = v;
      repayments = r;
      // Show hard error only when all dynamic endpoints failed.
      error = (p.isEmpty && v.isEmpty && r.isEmpty)
          ? 'Failed to load live data. Check API base URL and token.'
          : null;
      loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(child: Text(error!));
    }

    final approved = vouchers.where((v) => v.status == 'approved').toList();
    final latest = approved.isNotEmpty ? approved.first : null;
    final nextRepayment = repayments.where((r) => r.status != 'paid').isEmpty
        ? null
        : (repayments.where((r) => r.status != 'paid').toList()
                ..sort((a, b) => a.dueDate.compareTo(b.dueDate)))
              .first;

    return RefreshIndicator(
      onRefresh: fetch,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 8, 14, 14),
        children: [
          _welcomeCard(profile['name']?.toString() ?? 'Driver'),
          const SizedBox(height: 12),
          _infoCard(
            icon: Icons.local_gas_station_rounded,
            title: latest == null
                ? 'Active Voucher'
                : 'Active Voucher • ${latest.stationName ?? 'Station'}',
            value: latest == null
                ? 'No active voucher'
                : 'ZAR ${latest.amount.toStringAsFixed(2)}',
            gradient: const LinearGradient(
              colors: [Color(0xFF1E1B4B), Color(0xFF312E81)],
            ),
          ),
          const SizedBox(height: 12),
          _infoCard(
            icon: Icons.event_note_rounded,
            title: 'Next Repayment',
            value: nextRepayment == null
                ? 'No upcoming repayments'
                : 'Due ${_fmtDate(nextRepayment.dueDate)} • ZAR ${nextRepayment.amount.toStringAsFixed(2)}',
            gradient: const LinearGradient(
              colors: [Color(0xFF172554), Color(0xFF312E81)],
            ),
          ),
          const SizedBox(height: 12),
          _quickApplyCard(widget.onOpenApply),
          const SizedBox(height: 12),
          _infoCard(
            icon: Icons.account_balance_wallet_outlined,
            title: 'Wallet Balance',
            value: 'ZAR 0.00',
            gradient: const LinearGradient(
              colors: [Color(0xFF111827), Color(0xFF1F2937)],
            ),
          ),
          const SizedBox(height: 12),
          _brandsCard(),
          const SizedBox(height: 12),
          _wisdomCard(),
          const SizedBox(height: 12),
          const FeedbackPanel(compact: true),
        ],
      ),
    );
  }

  Widget _welcomeCard(String name) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF020DFF), Color(0xFF0208CC)],
        ),
      ),
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.92),
            ),
            child: const Center(child: LogoMark(size: 28)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Welcome back, ${name.split(' ').first}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                const Text(
                  'Track fuel vouchers and repayments in one place.',
                  style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 14),
                ),
              ],
            ),
          ),
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(14),
              color: Colors.white,
            ),
            child: const Icon(
              Icons.local_gas_station_rounded,
              color: Color(0xFF3A66D8),
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoCard({
    required IconData icon,
    required String title,
    required String value,
    required Gradient gradient,
  }) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        gradient: gradient,
      ),
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              color: Colors.white,
            ),
            child: Icon(icon, color: const Color(0xFF3A66D8)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: Color(0xFF94A3B8),
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    color: AppTheme.slate,
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _quickApplyCard(VoidCallback onOpenApply) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Voucher Access',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: Color(0xFFE2E8F0),
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Apply for new fuel access and manage issuance from one flow.',
            style: TextStyle(color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 12),
          FxButton(
            label: 'Apply for Voucher',
            icon: Icons.add_card_rounded,
            fullWidth: true,
            onPressed: onOpenApply,
          ),
        ],
      ),
    );
  }

  Widget _brandsCard() {
    const brands = <Map<String, String>>[
      {'name': 'Engen', 'logo': 'assets/images/brands/engen.png'},
      {'name': 'Shell SA', 'logo': 'assets/images/brands/shell-sa.png'},
      {'name': 'BP SA', 'logo': 'assets/images/brands/bp-southern-africa.png'},
      {'name': 'Sasol', 'logo': 'assets/images/brands/sasol.png'},
      {
        'name': 'TotalEnergies',
        'logo': 'assets/images/brands/totalenergies.png',
      },
    ];
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Top Brands in SA',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFFE2E8F0),
                  ),
                ),
              ),
              Icon(
                Icons.verified_rounded,
                color: AppTheme.primaryBlue,
                size: 18,
              ),
            ],
          ),
          const SizedBox(height: 4),
          const Text(
            'Fuel operators, oil corporations, and energy partners.',
            style: TextStyle(color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 10),
          SizedBox(
            height: 90,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: brands.length,
              separatorBuilder: (_, index) => const SizedBox(width: 8),
              itemBuilder: (context, i) {
                final brand = brands[i];
                return Container(
                  width: 120,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFF334155)),
                    gradient: const LinearGradient(
                      colors: [Color(0xFF111827), Color(0xFF1F2937)],
                    ),
                  ),
                  child: Stack(
                    children: [
                      Center(
                        child: Text(
                          brand['name'] ?? 'Brand',
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            color: AppTheme.slate,
                          ),
                        ),
                      ),
                      Positioned(
                        left: 8,
                        bottom: 8,
                        child: Container(
                          width: 26,
                          height: 26,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: Colors.white,
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(4),
                            child: ClipOval(
                              child: Image.asset(
                                brand['logo'] ?? '',
                                fit: BoxFit.cover,
                                errorBuilder: (context, _, error) =>
                                    const SizedBox.shrink(),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _wisdomCard() {
    return Container(
      width: double.infinity,
      constraints: const BoxConstraints(minHeight: 220),
      decoration: BoxDecoration(
        color: const Color(0xFFB7E219),
        borderRadius: BorderRadius.circular(12),
      ),
      padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Quote of the day',
            style: TextStyle(
              fontWeight: FontWeight.w700,
              color: Color(0xFF7F9B1D),
              letterSpacing: 0.3,
            ),
          ),
          const SizedBox(height: 8),
          RichText(
            text: const TextSpan(
              style: TextStyle(fontWeight: FontWeight.w800),
              children: [
                TextSpan(
                  text: 'Be ',
                  style: TextStyle(color: Color(0xFF465512), fontSize: 48),
                ),
                TextSpan(
                  text: 'Wealthy',
                  style: TextStyle(color: Color(0xFF7C3AED), fontSize: 48),
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            '”',
            style: TextStyle(
              color: Color(0xFFDFF886),
              fontSize: 44,
              height: 0.9,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'Wisdom is the only gift\nyou can never lose.',
            style: TextStyle(
              fontSize: 23,
              height: 1.06,
              fontWeight: FontWeight.w900,
              color: Color(0xFF465512),
            ),
          ),
          const Spacer(),
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Color(0xFF9EC415),
                ),
                child: const Center(
                  child: Icon(
                    Icons.auto_awesome_rounded,
                    size: 20,
                    color: Color(0xFF6F871A),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              const Text(
                'BWISER Driver Wisdom',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF7F9B1D),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _fmtDate(DateTime dt) {
    final d = dt.day.toString().padLeft(2, '0');
    final m = dt.month.toString().padLeft(2, '0');
    return '$d/$m/${dt.year}';
  }
}

class DriverVouchersPage extends StatefulWidget {
  const DriverVouchersPage({
    super.key,
    required this.api,
    required this.onOpenApply,
  });

  final ApiClient api;
  final VoidCallback onOpenApply;

  @override
  State<DriverVouchersPage> createState() => _DriverVouchersPageState();
}

class _DriverVouchersPageState extends State<DriverVouchersPage> {
  bool loading = true;
  String? error;
  List<VoucherItem> vouchers = [];

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
      final list = await widget.api.driverVouchers();
      setState(() => vouchers = list);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: _RepayLoadingIndicator());
    if (error != null) return Center(child: Text(error!));

    return RefreshIndicator(
      onRefresh: fetch,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
        children: [
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(18),
              gradient: AppTheme.actionGradient,
            ),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                const Expanded(
                  child: Text(
                    'Vouchers',
                    style: TextStyle(
                      color: Color(0xFFE2E8F0),
                      fontWeight: FontWeight.w800,
                      fontSize: 22,
                    ),
                  ),
                ),
                FxButton(
                  label: 'Apply',
                  height: 42,
                  onPressed: widget.onOpenApply,
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          if (vouchers.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(14),
                child: Text('No vouchers found.'),
              ),
            ),
          ...vouchers.map(
            (v) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _voucherTile(v),
            ),
          ),
        ],
      ),
    );
  }

  Widget _voucherTile(VoucherItem v) {
    final canShow = v.qrCode.isNotEmpty;
    final statusColor = v.status == 'redeemed'
        ? const Color(0xFFDBEAFE)
        : const Color(0xFFE7E7FF);

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  v.stationName ?? 'Station',
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 20,
                    color: AppTheme.slate,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: statusColor,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  v.status,
                  style: const TextStyle(
                    color: AppTheme.primaryBlue,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Issued ${DateTime.now().toIso8601String()} • ${v.id}',
            style: const TextStyle(color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 8),
          Text(
            'ZAR ${v.amount.toStringAsFixed(2)}',
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w700,
              color: AppTheme.slate,
            ),
          ),
          if (canShow) ...[
            const SizedBox(height: 8),
            InkWell(
              onTap: () => _showVoucherSheet(v),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.qr_code_2, color: AppTheme.primaryBlue, size: 20),
                  SizedBox(width: 6),
                  Text(
                    'Show QR',
                    style: TextStyle(
                      color: AppTheme.primaryBlue,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _showVoucherSheet(VoucherItem v) async {
    var mode = 'qr';
    final tapTokenFuture = widget.api.driverTapToken(v.id);

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            final qrPayload = jsonEncode({
              'voucher_id': v.id,
              'code': v.code,
              'qr_code': v.qrCode,
            });
            return Container(
              decoration: const BoxDecoration(
                color: Color(0xFF0B1220),
                borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
              ),
              child: SafeArea(
                top: false,
                child: SingleChildScrollView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    24 + MediaQuery.of(context).viewInsets.bottom,
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 42,
                        height: 4,
                        decoration: BoxDecoration(
                          color: const Color(0xFFCBD5E1),
                          borderRadius: BorderRadius.circular(999),
                        ),
                      ),
                      const SizedBox(height: 14),
                      const Text(
                        'Voucher QR',
                        style: TextStyle(
                          color: Color(0xFFE2E8F0),
                          fontSize: 26,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      Text(
                        v.stationName ?? '',
                        style: const TextStyle(color: Color(0xFF94A3B8)),
                      ),
                      const SizedBox(height: 10),
                      SegmentedButton<String>(
                        showSelectedIcon: false,
                        segments: const [
                          ButtonSegment(
                            value: 'qr',
                            label: Text('QR-Scan'),
                            icon: Icon(Icons.qr_code_2, size: 16),
                          ),
                          ButtonSegment(
                            value: 'tap',
                            label: Text('Tap'),
                            icon: Icon(Icons.nfc, size: 16),
                          ),
                        ],
                        selected: {mode},
                        onSelectionChanged: (v) =>
                            setSheetState(() => mode = v.first),
                      ),
                      const SizedBox(height: 14),
                      if (mode == 'qr')
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFF111827),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: QrImageView(
                            data: qrPayload,
                            size: 220,
                            eyeStyle: const QrEyeStyle(
                              eyeShape: QrEyeShape.square,
                              color: Color(0xFF111827),
                            ),
                            dataModuleStyle: const QrDataModuleStyle(
                              dataModuleShape: QrDataModuleShape.square,
                              color: Color(0xFF111827),
                            ),
                            backgroundColor: Colors.white,
                          ),
                        )
                      else
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: const Color(0xFF111827),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Tap token',
                                style: TextStyle(
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFFE2E8F0),
                                ),
                              ),
                              const SizedBox(height: 6),
                              FutureBuilder<String>(
                                future: tapTokenFuture,
                                builder: (context, snapshot) {
                                  if (snapshot.connectionState !=
                                      ConnectionState.done) {
                                    return const Padding(
                                      padding: EdgeInsets.symmetric(
                                        vertical: 6,
                                      ),
                                      child: SizedBox(
                                        height: 18,
                                        width: 18,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      ),
                                    );
                                  }
                                  if (snapshot.hasError || !snapshot.hasData) {
                                    return Text(
                                      snapshot.error?.toString().replaceFirst(
                                            'Exception: ',
                                            '',
                                          ) ??
                                          'Failed to generate tap token.',
                                      style: const TextStyle(
                                        color: Color(0xFFFCA5A5),
                                      ),
                                    );
                                  }
                                  return SelectableText(
                                    snapshot.data!,
                                    style: const TextStyle(
                                      fontFamily: 'monospace',
                                      fontSize: 12,
                                    ),
                                  );
                                },
                              ),
                              const SizedBox(height: 8),
                              FxButton(
                                label: 'Copy token',
                                icon: Icons.copy_rounded,
                                fullWidth: true,
                                onPressed: () async {
                                  try {
                                    final token = await tapTokenFuture;
                                    await Clipboard.setData(
                                      ClipboardData(text: token),
                                    );
                                    if (!context.mounted) return;
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text('Tap token copied.'),
                                      ),
                                    );
                                  } catch (e) {
                                    if (!context.mounted) return;
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text(
                                          e.toString().replaceFirst(
                                            'Exception: ',
                                            '',
                                          ),
                                        ),
                                      ),
                                    );
                                  }
                                },
                              ),
                              const SizedBox(height: 8),
                              FxButton(
                                label: 'Enable Phone Tap',
                                icon: Icons.nfc_rounded,
                                fullWidth: true,
                                onPressed: () async {
                                  try {
                                    final token = await tapTokenFuture;
                                    final enabled =
                                        await NfcHceBridge.isAvailable();
                                    if (!enabled) {
                                      throw Exception(
                                        'NFC/HCE is unavailable or disabled on this phone.',
                                      );
                                    }
                                    await NfcHceBridge.setTapToken(token);
                                    if (!context.mounted) return;
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text(
                                          'Phone tap is armed. Hold phone to POS reader.',
                                        ),
                                      ),
                                    );
                                  } catch (e) {
                                    if (!context.mounted) return;
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text(
                                          e.toString().replaceFirst(
                                            'Exception: ',
                                            '',
                                          ),
                                        ),
                                      ),
                                    );
                                  }
                                },
                              ),
                              const SizedBox(height: 8),
                              FxButton(
                                label: 'Disable Phone Tap',
                                icon: Icons.nfc_outlined,
                                fullWidth: true,
                                onPressed: () async {
                                  try {
                                    await NfcHceBridge.clearTapToken();
                                    if (!context.mounted) return;
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text('Phone tap disabled.'),
                                      ),
                                    );
                                  } catch (e) {
                                    if (!context.mounted) return;
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text(
                                          e.toString().replaceFirst(
                                            'Exception: ',
                                            '',
                                          ),
                                        ),
                                      ),
                                    );
                                  }
                                },
                              ),
                            ],
                          ),
                        ),
                      const SizedBox(height: 12),
                      Text(
                        v.code,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          color: Color(0xFFCBD5E1),
                        ),
                      ),
                      Text(
                        'ZAR ${v.amount.toStringAsFixed(2)} • ${v.status}',
                        style: const TextStyle(color: Color(0xFF94A3B8)),
                      ),
                      const SizedBox(height: 12),
                      FxButton(
                        label: 'Close',
                        fullWidth: true,
                        onPressed: () => Navigator.of(context).pop(),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }
}

class DriverApplyVoucherPage extends StatefulWidget {
  const DriverApplyVoucherPage({super.key, required this.api});
  final ApiClient api;

  @override
  State<DriverApplyVoucherPage> createState() => _DriverApplyVoucherPageState();
}

class _DriverApplyVoucherPageState extends State<DriverApplyVoucherPage> {
  bool loading = true;
  bool submitting = false;
  bool autopayEnabled = false;
  bool autopayReady = false;
  String? error;
  final amountCtrl = TextEditingController(text: '500');
  final stationCtrl = TextEditingController();
  String fuelType = 'petrol';
  List<Map<String, dynamic>> stations = [];
  Timer? approvalPollTimer;
  int approvedBeforeSubmit = 0;
  String? selectedStationName;
  Map<String, dynamic>? selectedStation;
  int? stationId;

  @override
  void initState() {
    super.initState();
    fetch();
  }

  @override
  void dispose() {
    approvalPollTimer?.cancel();
    amountCtrl.dispose();
    stationCtrl.dispose();
    super.dispose();
  }

  Future<void> fetch() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final profile = await widget.api.profile();
      final st = await widget.api.stations();
      final vouchers = await widget.api.driverVouchers();
      final gateway = (profile['autopay_gateway'] ?? '')
          .toString()
          .toLowerCase();
      final hasToken =
          (profile['autopay_has_token'] ?? false) == true ||
          ((profile['autopay_token'] ?? '').toString().trim().isNotEmpty);
      final status = (profile['autopay_status'] ?? 'inactive')
          .toString()
          .toLowerCase();
      final blocked = {
        'disabled',
        'failed',
        'max_retries_exceeded',
        'inactive',
      };
      final ready =
          (profile['autopay_ready'] ?? false) == true ||
          (((profile['autopay_enabled'] ?? false) == true) &&
              gateway == 'paystack' &&
              hasToken &&
              !blocked.contains(status));
      setState(() {
        stations = st;
        autopayEnabled = (profile['autopay_enabled'] ?? false) == true;
        autopayReady = ready;
        approvedBeforeSubmit = vouchers
            .where((v) => v.status.toLowerCase() == 'approved')
            .length;
      });
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> submit() async {
    if (!autopayReady) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'AutoPay is not ready. Complete Paystack AutoPay setup in Profile before applying.',
          ),
        ),
      );
      return;
    }

    if (stationId == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Select station.')));
      return;
    }

    final station =
        selectedStation ??
        stations.firstWhere(
          (s) => (s['id'] ?? 0).toString() == stationId.toString(),
          orElse: () => <String, dynamic>{},
        );
    final partner = _stationIsPartner(station);
    final funded = _stationHasFunds(station);

    if (!partner) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Selected station is not a partner station yet.'),
        ),
      );
      return;
    }
    if (!funded) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Selected partner station currently has no available funds.',
          ),
        ),
      );
      return;
    }

    setState(() => submitting = true);
    try {
      await widget.api.applyVoucher(
        stationId: stationId!,
        amount: double.tryParse(amountCtrl.text) ?? 0,
        fuelType: fuelType,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Voucher request submitted.')),
      );
      _startApprovalPolling();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => submitting = false);
    }
  }

  bool _stationIsPartner(Map<String, dynamic> station) {
    final dynamic raw =
        station['is_partner'] ??
        station['partner'] ??
        station['is_active_partner'] ??
        station['partner_station'];
    if (raw == null) return true;
    if (raw is bool) return raw;
    final text = raw.toString().trim().toLowerCase();
    return text == '1' || text == 'true' || text == 'yes' || text == 'partner';
  }

  bool _stationHasFunds(Map<String, dynamic> station) {
    final dynamic raw =
        station['wallet_balance'] ??
        station['available_balance'] ??
        station['balance'] ??
        station['prefunded_balance'] ??
        station['funded_amount'];
    if (raw == null) return true;
    final amount =
        double.tryParse(raw.toString().replaceAll(',', '').trim()) ?? 0;
    return amount > 0;
  }

  void _startApprovalPolling() {
    approvalPollTimer?.cancel();
    final startedAt = DateTime.now();
    approvalPollTimer = Timer.periodic(const Duration(seconds: 12), (
      timer,
    ) async {
      if (!mounted) {
        timer.cancel();
        return;
      }
      if (DateTime.now().difference(startedAt).inMinutes >= 5) {
        timer.cancel();
        return;
      }
      try {
        final vouchers = await widget.api.driverVouchers();
        final approved = vouchers
            .where((v) => v.status.toLowerCase() == 'approved')
            .toList();
        final grew = approved.length > approvedBeforeSubmit;
        final matchesStation = selectedStationName == null
            ? grew
            : approved.any(
                (v) => (v.stationName ?? '').toLowerCase().contains(
                  selectedStationName!.toLowerCase(),
                ),
              );
        if (grew && matchesStation) {
          approvedBeforeSubmit = approved.length;
          timer.cancel();
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'Voucher approved. You can now use it in Vouchers.',
              ),
            ),
          );
        }
      } catch (_) {
        // Keep polling quietly.
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: CircularProgressIndicator());
    if (error != null) return Center(child: Text(error!));

    return ListView(
      padding: const EdgeInsets.all(14),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                if (!autopayReady) ...[
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFF3B82F6)),
                    ),
                    child: const Text(
                      'AutoPay is required and must be healthy. Re-authorize Paystack AutoPay in Profile to continue.',
                      style: TextStyle(
                        color: Color(0xFFBFDBFE),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
                RawAutocomplete<Map<String, dynamic>>(
                  textEditingController: stationCtrl,
                  optionsBuilder: (TextEditingValue value) {
                    final query = value.text.trim().toLowerCase();
                    if (query.isEmpty) return stations.take(8);
                    return stations
                        .where((s) {
                          final name = (s['name'] ?? '')
                              .toString()
                              .toLowerCase();
                          final city = (s['city'] ?? '')
                              .toString()
                              .toLowerCase();
                          return name.contains(query) || city.contains(query);
                        })
                        .take(8);
                  },
                  displayStringForOption: (option) =>
                      '${option['name'] ?? 'Station'}',
                  onSelected: (s) {
                    setState(() {
                      selectedStation = s;
                      stationId = int.tryParse('${s['id'] ?? 0}') ?? 0;
                      selectedStationName = (s['name'] ?? '').toString();
                      stationCtrl.text = selectedStationName ?? '';
                    });
                  },
                  fieldViewBuilder:
                      (context, controller, focusNode, onFieldSubmitted) {
                        return TextField(
                          controller: controller,
                          focusNode: focusNode,
                          decoration: const InputDecoration(
                            labelText: 'Station',
                            hintText: 'Type station name',
                            suffixIcon: Icon(Icons.search),
                          ),
                          onChanged: (_) {
                            if (stationId != null) {
                              setState(() {
                                stationId = null;
                                selectedStation = null;
                                selectedStationName = null;
                              });
                            }
                          },
                        );
                      },
                  optionsViewBuilder: (context, onSelected, options) {
                    return Align(
                      alignment: Alignment.topLeft,
                      child: Material(
                        color: const Color(0xFF0B1220),
                        borderRadius: BorderRadius.circular(12),
                        child: ConstrainedBox(
                          constraints: const BoxConstraints(
                            maxHeight: 220,
                            maxWidth: 460,
                          ),
                          child: ListView.builder(
                            shrinkWrap: true,
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            itemCount: options.length,
                            itemBuilder: (context, index) {
                              final s = options.elementAt(index);
                              final partner = _stationIsPartner(s);
                              final funded = _stationHasFunds(s);
                              return ListTile(
                                dense: true,
                                title: Text(
                                  '${s['name']}',
                                  style: const TextStyle(
                                    color: Color(0xFFE2E8F0),
                                  ),
                                ),
                                subtitle: Text(
                                  '${s['city'] ?? '-'} • ${partner ? 'Partner' : 'Non-partner'} • ${funded ? 'Funded' : 'No funds'}',
                                  style: const TextStyle(
                                    color: Color(0xFF94A3B8),
                                  ),
                                ),
                                onTap: () => onSelected(s),
                              );
                            },
                          ),
                        ),
                      ),
                    );
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: amountCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Amount (ZAR)'),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: fuelType,
                  items: const [
                    DropdownMenuItem(value: 'petrol', child: Text('Petrol')),
                    DropdownMenuItem(value: 'diesel', child: Text('Diesel')),
                    DropdownMenuItem(value: 'super', child: Text('Super')),
                  ],
                  onChanged: (v) => setState(() => fuelType = v ?? 'petrol'),
                  decoration: const InputDecoration(labelText: 'Fuel Type'),
                ),
                const SizedBox(height: 18),
                submitting
                    ? const SizedBox(
                        height: 54,
                        child: Center(
                          child: SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      )
                    : FxButton(
                        label: 'Apply for Voucher',
                        icon: Icons.send_rounded,
                        fullWidth: true,
                        onPressed: autopayReady ? submit : null,
                      ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class DriverRepaymentsPage extends StatefulWidget {
  const DriverRepaymentsPage({super.key, required this.api});
  final ApiClient api;

  @override
  State<DriverRepaymentsPage> createState() => _DriverRepaymentsPageState();
}

class _DriverRepaymentsPageState extends State<DriverRepaymentsPage> {
  bool loading = true;
  String? error;
  List<RepaymentItem> items = [];

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
      final list = await widget.api.driverRepayments();
      setState(() => items = list);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> payNow(RepaymentItem item) async {
    setState(() => loading = true);
    try {
      await widget.api.payRepayment(item.id);
      await fetch();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Repayment for ${item.voucherCode} marked paid.'),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
      setState(() => loading = false);
    }
  }

  Future<void> payWithPaystack(RepaymentItem item) async {
    setState(() => loading = true);
    String? reference;
    try {
      final checkout = await widget.api.initializePaystackRepayment(item.id);
      reference = (checkout['reference'] ?? '').toString();
      final authUrl = (checkout['authorization_url'] ?? '').toString();
      if (authUrl.isEmpty || reference.isEmpty) {
        throw Exception('Paystack checkout response is incomplete.');
      }

      final uri = Uri.parse(authUrl);
      final launched = await launchUrl(
        uri,
        mode: LaunchMode.externalApplication,
      );
      if (!launched) {
        throw Exception('Unable to open Paystack checkout URL.');
      }

      if (!mounted) return;
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Complete Payment'),
          content: const Text(
            'After finishing payment on Paystack, tap Confirm to verify and post repayment.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Later'),
            ),
            FxButton(
              label: 'Confirm',
              onPressed: () => Navigator.of(context).pop(true),
            ),
          ],
        ),
      );

      if (confirmed == true) {
        await widget.api.verifyPaystackRepayment(
          repaymentId: item.id,
          reference: reference,
        );
        await fetch();
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Paystack payment verified for ${item.voucherCode}.'),
          ),
        );
      } else {
        if (mounted) setState(() => loading = false);
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
      setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: CircularProgressIndicator());
    if (error != null) return Center(child: Text(error!));

    return RefreshIndicator(
      onRefresh: fetch,
      child: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          ...items.map((item) {
            final isPaid = item.status == 'paid';
            final isOverdue = item.status == 'overdue';
            final chipColor = isPaid
                ? const Color(0xFFD1FAE5)
                : isOverdue
                ? const Color(0xFFFFEDD5)
                : const Color(0xFFDBEAFE);
            final chipText = isPaid
                ? const Color(0xFF065F46)
                : isOverdue
                ? const Color(0xFF9A3412)
                : const Color(0xFF1D4ED8);

            return Container(
              margin: const EdgeInsets.only(bottom: 12),
              decoration: BoxDecoration(
                color: const Color(0xFF081326),
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFF28486D)),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            item.voucherCode,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              color: AppTheme.slate,
                              fontSize: 22,
                            ),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 5,
                          ),
                          decoration: BoxDecoration(
                            color: chipColor,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            item.status.toUpperCase(),
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: chipText,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Due ${_fmtDate(item.dueDate)}',
                      style: const TextStyle(
                        color: Color(0xFF94A3B8),
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'ZAR ${item.amount.toStringAsFixed(2)}',
                      style: const TextStyle(
                        color: AppTheme.slate,
                        fontWeight: FontWeight.w700,
                        fontSize: 40,
                        height: 1,
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (!isPaid)
                      FxButton(
                        label: 'Pay with Paystack',
                        icon: Icons.open_in_new_rounded,
                        fullWidth: true,
                        onPressed: () => payWithPaystack(item),
                      ),
                  ],
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  String _fmtDate(DateTime dt) {
    final d = dt.day.toString().padLeft(2, '0');
    final m = dt.month.toString().padLeft(2, '0');
    return '$d/$m/${dt.year}';
  }
}

class DriverProfilePage extends StatefulWidget {
  const DriverProfilePage({super.key, required this.api});
  final ApiClient api;

  @override
  State<DriverProfilePage> createState() => _DriverProfilePageState();
}

class _DriverProfilePageState extends State<DriverProfilePage> {
  bool loading = true;
  bool enabled = false;
  bool ready = false;
  bool hasToken = false;
  String? error;
  String paymentMethod = 'paystack';
  String? autopayEmail;

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
      final p = await widget.api.profile();
      final isEnabled = (p['autopay_enabled'] ?? false) == true;
      setState(() {
        enabled = isEnabled;
        paymentMethod = (p['autopay_gateway'] ?? 'paystack').toString();
        hasToken =
            (p['autopay_has_token'] ?? false) == true ||
            ((p['autopay_token'] ?? '').toString().trim().isNotEmpty);
        final status = (p['autopay_status'] ?? 'inactive')
            .toString()
            .toLowerCase();
        final blocked = {
          'disabled',
          'failed',
          'max_retries_exceeded',
          'inactive',
        };
        ready =
            (p['autopay_ready'] ?? false) == true ||
            (isEnabled &&
                paymentMethod.toLowerCase() == 'paystack' &&
                hasToken &&
                !blocked.contains(status));
        autopayEmail = (p['autopay_email'] ?? p['email'] ?? '').toString();
      });
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> toggle(bool value) async {
    if (value) {
      final configured = await _setupPaystackAutopay();
      if (!configured) return;
    }

    setState(() => loading = true);
    try {
      await widget.api.setAutopay(enabled: value, method: 'paystack');
      setState(() {
        enabled = value;
        paymentMethod = 'paystack';
        ready = value ? hasToken : false;
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            value ? 'AutoPay enabled (Paystack)' : 'AutoPay disabled',
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<String?> _askAutopayEmail() async {
    final emailCtrl = TextEditingController(text: autopayEmail ?? '');
    final result = await showDialog<String>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Enable AutoPay (Paystack)'),
          content: SizedBox(
            width: 360,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'A small authorization transaction will be made on Paystack to tokenize your card for daily repayments.',
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Paystack Email',
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            FxButton(
              label: 'Continue',
              onPressed: () => Navigator.of(context).pop(emailCtrl.text.trim()),
            ),
          ],
        );
      },
    );

    emailCtrl.dispose();
    return result;
  }

  Future<bool> _setupPaystackAutopay() async {
    final email = await _askAutopayEmail();
    if (email == null || email.trim().isEmpty) {
      return false;
    }

    try {
      final checkout = await widget.api.initializeAutopayPaystack(email: email);
      final authUrl = (checkout['authorization_url'] ?? '').toString();
      final reference = (checkout['reference'] ?? '').toString();
      final probe = (checkout['probe_amount'] ?? 0).toString();
      if (authUrl.isEmpty || reference.isEmpty) {
        throw Exception('Paystack AutoPay initialization is incomplete.');
      }

      final opened = await launchUrl(
        Uri.parse(authUrl),
        mode: LaunchMode.externalApplication,
      );
      if (!opened) {
        throw Exception('Unable to open Paystack authorization URL.');
      }

      if (!mounted) return false;
      final confirm = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Confirm AutoPay Authorization'),
          content: Text(
            'Complete the small Paystack authorization transaction (about ZAR $probe), then tap Confirm.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Cancel'),
            ),
            FxButton(
              label: 'Confirm',
              onPressed: () => Navigator.of(context).pop(true),
            ),
          ],
        ),
      );

      if (confirm != true) return false;
      await widget.api.verifyAutopayPaystack(reference: reference);
      setState(() {
        hasToken = true;
        ready = true;
      });
      return true;
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
        );
      }
      return false;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: CircularProgressIndicator());
    if (error != null) return Center(child: Text(error!));

    return ListView(
      padding: const EdgeInsets.all(14),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    gradient: AppTheme.actionGradient,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.flash_on_rounded,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(width: 10),
                const Expanded(
                  child: Text(
                    'AutoPay',
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      color: AppTheme.slate,
                    ),
                  ),
                ),
                Text(
                  paymentMethod.toUpperCase(),
                  style: const TextStyle(
                    color: Color(0xFF94A3B8),
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(width: 8),
                Switch(value: enabled, onChanged: toggle),
              ],
            ),
          ),
        ),
        const SizedBox(height: 10),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Icon(
                  ready
                      ? Icons.check_circle_rounded
                      : Icons.error_outline_rounded,
                  color: ready
                      ? const Color(0xFF10B981)
                      : const Color(0xFFF59E0B),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    ready
                        ? 'AutoPay is healthy and tokenized for future billing.'
                        : 'AutoPay is not healthy yet. Re-authorize Paystack to continue applying for vouchers.',
                    style: const TextStyle(color: AppTheme.slate),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _RepayLoadingIndicator extends StatefulWidget {
  const _RepayLoadingIndicator();

  @override
  State<_RepayLoadingIndicator> createState() => _RepayLoadingIndicatorState();
}

class _RepayLoadingIndicatorState extends State<_RepayLoadingIndicator>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1200),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 160,
      height: 160,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: 96,
            height: 96,
            child: Stack(
              alignment: Alignment.center,
              children: [
                Container(
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFF35526F)),
                    color: const Color(0xFF0B1E33),
                  ),
                ),
                RotationTransition(
                  turns: _controller,
                  child: Container(
                    width: 64,
                    height: 64,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: SweepGradient(
                        colors: [
                          Color(0x00020DFF),
                          Color(0xFF7C3AED),
                          Color(0xFF020DFF),
                          Color(0x00020DFF),
                        ],
                      ),
                    ),
                  ),
                ),
                Container(
                  width: 20,
                  height: 20,
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F172A),
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFF334155)),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),
          const Text(
            'Loading...',
            style: TextStyle(color: Color(0xFF94A3B8), fontSize: 20),
          ),
        ],
      ),
    );
  }
}
