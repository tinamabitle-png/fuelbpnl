import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/feedback_panel.dart';
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
  static const String _driverLogoAsset = 'assets/images/driver_logo.png';
  int index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      DriverHomePage(
        api: widget.api,
        onOpenVouchers: () => setState(() => index = 1),
        onOpenStations: () => setState(() => index = 4),
      ),
      DriverVouchersPage(
        api: widget.api,
        onOpenApply: () => setState(() => index = 4),
      ),
      DriverRepaymentsPage(api: widget.api),
      DriverProfilePage(api: widget.api),
      DriverApplyVoucherPage(api: widget.api),
    ];

    const titles = [
      'Dashboard',
      'Vouchers',
      'Repayments',
      'Profile',
      'Stations',
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
      const _DriverNavItem('Repayments', Icons.receipt_long_outlined),
      const _DriverNavItem('Profile', Icons.person_outline_rounded),
      const _DriverNavItem('Stations', Icons.map_outlined),
    ];

    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFFF6F7FB),
        border: Border(top: BorderSide(color: Color(0xFFD8DEEB))),
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
                          : const Color(0xFF6B7280),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      item.label,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: active ? FontWeight.w700 : FontWeight.w500,
                        color: active
                            ? AppTheme.primaryBlue
                            : const Color(0xFF6B7280),
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
    required this.onOpenStations,
  });

  final ApiClient api;
  final VoidCallback onOpenVouchers;
  final VoidCallback onOpenStations;

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
    try {
      final p = await widget.api.profile();
      final v = await widget.api.driverVouchers();
      final r = await widget.api.driverRepayments();
      setState(() {
        profile = p;
        vouchers = v;
        repayments = r;
      });
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
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
              colors: [Color(0xFFD8D4FB), Color(0xFFC8D3F5)],
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
              colors: [Color(0xFFD4E6FF), Color(0xFFC9DAF4)],
            ),
          ),
          const SizedBox(height: 12),
          _stationToolsCard(widget.onOpenStations),
          const SizedBox(height: 12),
          _infoCard(
            icon: Icons.account_balance_wallet_outlined,
            title: 'Wallet Balance',
            value: 'ZAR 0.00',
            gradient: const LinearGradient(
              colors: [Color(0xFFDCE8FA), Color(0xFFCFDDF2)],
            ),
          ),
          const SizedBox(height: 12),
          _brandsCard(),
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
            padding: const EdgeInsets.all(8),
            child: Image.asset(
              _DriverShellState._driverLogoAsset,
              fit: BoxFit.contain,
            ),
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
                    fontSize: 26,
                    fontWeight: FontWeight.w700,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                const Text(
                  'Track fuel vouchers and repayments in one place.',
                  style: TextStyle(color: Color(0xFFDCE8FF), fontSize: 14),
                ),
              ],
            ),
          ),
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(14),
              color: Colors.white.withValues(alpha: 0.16),
            ),
            child: const Icon(
              Icons.local_gas_station_rounded,
              color: Colors.white,
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
              color: Colors.white.withValues(alpha: 0.65),
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
                    color: Color(0xFF5A6582),
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    color: AppTheme.slate,
                    fontSize: 27,
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

  Widget _stationToolsCard(VoidCallback onOpenStations) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF4F7FB),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFDEE6F3)),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Station Attendant Tools',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: Color(0xFF4D5C7A),
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Scan voucher QR codes, record pump number, and approve returns.',
            style: TextStyle(color: Color(0xFF667085)),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: onOpenStations,
              icon: const Icon(Icons.qr_code_scanner_rounded),
              label: const Text('Open Station Attendant'),
              style: ElevatedButton.styleFrom(
                padding: EdgeInsets.zero,
                backgroundColor: Colors.transparent,
                shadowColor: Colors.transparent,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _brandsCard() {
    const brands = ['Engen', 'Shell SA', 'BP SA', 'Sasol', 'TotalEnergies'];
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF7F8FC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFDDE5F3)),
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
                    color: Color(0xFF4D5C7A),
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
            style: TextStyle(color: Color(0xFF667085)),
          ),
          const SizedBox(height: 10),
          SizedBox(
            height: 90,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: brands.length,
              separatorBuilder: (_, index) => const SizedBox(width: 8),
              itemBuilder: (context, i) {
                return Container(
                  width: 120,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFD8E0F0)),
                    gradient: const LinearGradient(
                      colors: [Color(0xFFF3F6FC), Color(0xFFE8EEF8)],
                    ),
                  ),
                  child: Center(
                    child: Text(
                      brands[i],
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        color: AppTheme.slate,
                      ),
                    ),
                  ),
                );
              },
            ),
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
    if (loading) return const Center(child: CircularProgressIndicator());
    if (error != null) return Center(child: Text(error!));

    return RefreshIndicator(
      onRefresh: fetch,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
        children: [
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(18),
              gradient: const LinearGradient(
                colors: [Color(0xFF232C7A), Color(0xFF0208CC)],
              ),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                const Expanded(
                  child: Text(
                    'Vouchers',
                    style: TextStyle(
                      color: Color(0xFF9FA9E8),
                      fontWeight: FontWeight.w800,
                      fontSize: 34,
                    ),
                  ),
                ),
                FilledButton(
                  onPressed: widget.onOpenApply,
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFFC8D1EF),
                    foregroundColor: const Color(0xFF0208CC),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 18,
                      vertical: 8,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text('Apply'),
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
          const SizedBox(height: 12),
          const FeedbackPanel(compact: true),
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
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFDCE4F2)),
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
                    fontSize: 28,
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
            style: const TextStyle(color: Color(0xFF64748B)),
          ),
          const SizedBox(height: 8),
          Text(
            'ZAR ${v.amount.toStringAsFixed(2)}',
            style: const TextStyle(
              fontSize: 31,
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

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: false,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            final qrPayload = jsonEncode({
              'voucher_id': v.id,
              'code': v.code,
              'qr_code': v.qrCode,
            });
            final tapToken = widget.api.buildHmacTapToken(
              voucherId: v.id,
              voucherCode: v.code,
            );

            return Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
              ),
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
              child: SafeArea(
                top: false,
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
                        fontSize: 33,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    Text(
                      v.stationName ?? '',
                      style: const TextStyle(color: Color(0xFF64748B)),
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
                          color: const Color(0xFFF8FAFE),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFDCE4F2)),
                        ),
                        child: QrImageView(
                          data: qrPayload,
                          size: 220,
                          eyeStyle: const QrEyeStyle(
                            eyeShape: QrEyeShape.square,
                            color: AppTheme.slate,
                          ),
                          dataModuleStyle: const QrDataModuleStyle(
                            dataModuleShape: QrDataModuleShape.square,
                            color: AppTheme.slate,
                          ),
                          backgroundColor: Colors.white,
                        ),
                      )
                    else
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFE),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFDCE4F2)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Tap token',
                              style: TextStyle(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 6),
                            SelectableText(
                              tapToken,
                              style: const TextStyle(
                                fontFamily: 'monospace',
                                fontSize: 12,
                              ),
                            ),
                            const SizedBox(height: 8),
                            OutlinedButton.icon(
                              onPressed: () async {
                                await Clipboard.setData(
                                  ClipboardData(text: tapToken),
                                );
                                if (!context.mounted) return;
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Tap token copied.'),
                                  ),
                                );
                              },
                              icon: const Icon(Icons.copy_rounded),
                              label: const Text('Copy token'),
                            ),
                          ],
                        ),
                      ),
                    const SizedBox(height: 12),
                    Text(
                      v.code,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF4B5563),
                      ),
                    ),
                    Text(
                      'ZAR ${v.amount.toStringAsFixed(2)} • ${v.status}',
                      style: const TextStyle(color: Color(0xFF64748B)),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () => Navigator.of(context).pop(),
                        child: const Text('Close'),
                      ),
                    ),
                  ],
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
  String? error;
  final amountCtrl = TextEditingController(text: '500');
  final stationCtrl = TextEditingController();
  String fuelType = 'petrol';
  List<Map<String, dynamic>> stations = [];
  int? stationId;

  @override
  void initState() {
    super.initState();
    fetch();
  }

  @override
  void dispose() {
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
      final st = await widget.api.stations();
      setState(() => stations = st);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> submit() async {
    if (stationId == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Select station.')));
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
                TextField(
                  controller: stationCtrl,
                  readOnly: true,
                  decoration: InputDecoration(
                    labelText: 'Station',
                    suffixIcon: PopupMenuButton<Map<String, dynamic>>(
                      icon: const Icon(Icons.search),
                      itemBuilder: (context) => stations
                          .map(
                            (s) => PopupMenuItem<Map<String, dynamic>>(
                              value: s,
                              child: Text('${s['name']} (${s['city'] ?? '-'})'),
                            ),
                          )
                          .toList(),
                      onSelected: (s) {
                        setState(() {
                          stationId = (s['id'] ?? 0) as int;
                          stationCtrl.text = s['name'].toString();
                        });
                      },
                    ),
                  ),
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
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: submitting ? null : submit,
                    child: submitting
                        ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Text('Apply for Voucher'),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        const FeedbackPanel(compact: true),
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

            return Card(
              margin: const EdgeInsets.only(bottom: 10),
              child: Padding(
                padding: const EdgeInsets.all(14),
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
                      style: const TextStyle(color: Color(0xFF64748B)),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'ZAR ${item.amount.toStringAsFixed(2)}',
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (!isPaid)
                      OutlinedButton.icon(
                        onPressed: () => payNow(item),
                        icon: const Icon(Icons.check_circle_outline),
                        label: const Text('Pay now'),
                      ),
                  ],
                ),
              ),
            );
          }),
          const SizedBox(height: 12),
          const FeedbackPanel(compact: true),
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
  String? error;

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
      setState(() => enabled = (p['autopay_enabled'] ?? false) == true);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> toggle(bool value) async {
    setState(() => loading = true);
    try {
      await widget.api.setAutopay(enabled: value);
      setState(() => enabled = value);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(value ? 'AutoPay enabled' : 'AutoPay disabled')),
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
                    'Daily AutoPay',
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      color: AppTheme.slate,
                    ),
                  ),
                ),
                Switch(value: enabled, onChanged: toggle),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        const FeedbackPanel(compact: true),
      ],
    );
  }
}
