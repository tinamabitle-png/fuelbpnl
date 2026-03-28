import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/app_loader.dart';
import '../../core/feedback_panel.dart';
import '../../core/fx_button.dart';
import '../../core/nfc_hce_bridge.dart';
import '../../core/logo_mark.dart';
import '../../core/theme.dart';
import '../../data/api_client.dart';
import '../../data/models.dart';
import '../../shared/currency.dart';

class DriverShell extends StatefulWidget {
  const DriverShell({super.key, required this.api, required this.onLogout});

  final ApiClient api;
  final Future<void> Function() onLogout;

  @override
  State<DriverShell> createState() => _DriverShellState();
}

class _DriverShellState extends State<DriverShell> {
  int index = 0;
  Timer? _approvalNotifierTimer;
  final Set<int> _seenApprovedVoucherIds = <int>{};
  final List<String> _notifications = <String>[];
  int _unreadNotifications = 0;

  @override
  void initState() {
    super.initState();
    _startApprovalNotifier();
  }

  @override
  void dispose() {
    _approvalNotifierTimer?.cancel();
    super.dispose();
  }

  void _startApprovalNotifier() {
    _checkApprovedVouchers(baselineOnly: true);
    _approvalNotifierTimer?.cancel();
    _approvalNotifierTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      _checkApprovedVouchers();
    });
  }

  Future<void> _checkApprovedVouchers({bool baselineOnly = false}) async {
    try {
      final vouchers = await widget.api.driverVouchers();
      final approved = vouchers
          .where((v) => v.status.toLowerCase().trim() == 'approved')
          .toList();
      if (_seenApprovedVoucherIds.isEmpty || baselineOnly) {
        _seenApprovedVoucherIds.addAll(approved.map((v) => v.id));
        return;
      }

      for (final voucher in approved) {
        if (_seenApprovedVoucherIds.add(voucher.id)) {
          if (!mounted) return;
          final station = (voucher.stationName ?? 'station').trim();
          final message =
              'Voucher approved at $station • ${formatMoney(voucher.amount)}';
          _notifications.insert(
            0,
            '${DateTime.now().toLocal().toString().substring(11, 16)}  $message',
          );
          _unreadNotifications += 1;
          ScaffoldMessenger.of(
            context,
          ).showSnackBar(SnackBar(content: Text(message)));
          setState(() {});
        }
      }
    } catch (_) {
      // Keep polling quietly.
    }
  }

  Future<void> _openNotifications() async {
    if (!mounted) return;
    await showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Notifications'),
        content: SizedBox(
          width: 420,
          child: _notifications.isEmpty
              ? const Text('No notifications yet.')
              : ListView.separated(
                  shrinkWrap: true,
                  itemCount: _notifications.length,
                  separatorBuilder: (_, _) => const Divider(height: 10),
                  itemBuilder: (_, i) => Text(_notifications[i]),
                ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Close'),
          ),
        ],
      ),
    );
    if (!mounted) return;
    setState(() => _unreadNotifications = 0);
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      DriverHomePage(
        api: widget.api,
        onOpenVouchers: () => setState(() => index = 1),
        onOpenApply: () => setState(() => index = 5),
      ),
      DriverVouchersPage(
        api: widget.api,
        onOpenApply: () => setState(() => index = 5),
      ),
      DriverNavigatePage(api: widget.api),
      DriverRepaymentsPage(api: widget.api),
      DriverProfilePage(api: widget.api),
      DriverApplyVoucherPage(api: widget.api),
    ];

    return Scaffold(
      appBar: AppBar(
        leading: const Padding(
          padding: EdgeInsets.only(left: 12, top: 8, bottom: 8),
          child: Center(child: LogoMark(size: 22)),
        ),
        title: const Text('bwiser'),
        actions: [
          IconButton(
            onPressed: _openNotifications,
            icon: Stack(
              clipBehavior: Clip.none,
              children: [
                const Icon(Icons.notifications_none_rounded),
                if (_unreadNotifications > 0)
                  Positioned(
                    right: -2,
                    top: -2,
                    child: Container(
                      constraints: const BoxConstraints(
                        minWidth: 16,
                        minHeight: 16,
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEF4444),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        _unreadNotifications > 9
                            ? '9+'
                            : '$_unreadNotifications',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
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
    final compact = MediaQuery.of(context).size.width < 390;
    final bottomInset = MediaQuery.of(context).padding.bottom;
    final items = <_DriverNavItem>[
      const _DriverNavItem('Home', Icons.home_outlined),
      const _DriverNavItem('Vouchers', Icons.confirmation_number_outlined),
      const _DriverNavItem('Navigate', Icons.map_outlined),
      const _DriverNavItem('Repay', Icons.receipt_long_outlined),
      const _DriverNavItem('Profile', Icons.person_outline_rounded),
    ];

    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFF0B1220),
        border: Border(top: BorderSide(color: Color(0xFF334155))),
      ),
      // Keep the in-app bottom nav above the Android system navigation bar (3-button/gesture).
      padding: EdgeInsets.only(top: 4, bottom: 4 + bottomInset),
      child: Row(
        children: List.generate(items.length, (i) {
          final active = selectedIndex == i;
          final item = items[i];
          return Expanded(
            child: InkWell(
              onTap: () => onTap(i),
              child: SizedBox(
                height: compact ? 56 : 60,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      item.icon,
                      size: compact ? 20 : 22,
                      color: active
                          ? AppTheme.primaryBlue
                          : const Color(0xFF94A3B8),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      item.label,
                      style: TextStyle(
                        fontSize: compact ? 10.5 : 12,
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
  String? warning;
  Map<String, dynamic> profile = {};
  List<VoucherItem> vouchers = [];
  List<RepaymentItem> repayments = [];
  Map<String, dynamic> wallet = <String, dynamic>{};

  @override
  void initState() {
    super.initState();
    fetch();
  }

  Future<void> fetch() async {
    setState(() {
      loading = true;
      error = null;
      warning = null;
    });

    Map<String, dynamic> p = <String, dynamic>{};
    List<VoucherItem> v = <VoucherItem>[];
    List<RepaymentItem> r = <RepaymentItem>[];
    Map<String, dynamic> w = <String, dynamic>{};
    var hadFailure = false;

    try {
      p = await widget.api.profile();
    } catch (_) {
      hadFailure = true;
    }
    try {
      v = await widget.api.driverVouchers();
    } catch (_) {
      hadFailure = true;
    }
    try {
      r = await widget.api.driverRepayments();
    } catch (_) {
      hadFailure = true;
    }
    try {
      w = await widget.api.walletBalance();
    } catch (_) {
      hadFailure = true;
    }

    if (!mounted) return;
    setState(() {
      profile = p;
      vouchers = v;
      repayments = r;
      wallet = w;
      error = null;
      warning = hadFailure
          ? 'Some live data could not load. Showing available data.'
          : null;
      loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return const Center(child: AppLoader());
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
          if (warning != null) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFF59E0B)),
              ),
              child: Text(
                warning!,
                style: const TextStyle(
                  color: Color(0xFFFDE68A),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
          const SizedBox(height: 12),
          _infoCard(
            icon: Icons.local_gas_station_rounded,
            title: latest == null
                ? 'Active Voucher'
                : 'Active Voucher • ${latest.stationName ?? 'Station'}',
            value: latest == null
                ? 'No active voucher'
                : formatMoney(latest.amount),
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
                : 'Due ${_fmtDate(nextRepayment.dueDate)} • ${formatMoney(nextRepayment.amount)}',
            gradient: const LinearGradient(
              colors: [Color(0xFF172554), Color(0xFF312E81)],
            ),
          ),
          const SizedBox(height: 12),
          _quickApplyCard(widget.onOpenApply),
          const SizedBox(height: 12),
          _walletAndCardsCard(),
          const SizedBox(height: 12),
          _brandsCard(),
          const SizedBox(height: 12),
          _mobilityPartnersCard(),
          const SizedBox(height: 12),
          _wisdomCard(),
          const SizedBox(height: 12),
          const FeedbackPanel(compact: true),
        ],
      ),
    );
  }

  Widget _walletAndCardsCard() {
    final w =
        (wallet['wallet'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
    final balance = double.tryParse('${w['balance'] ?? 0}') ?? 0.0;
    final available =
        double.tryParse('${wallet['wallet_available_balance'] ?? 0}') ?? 0.0;
    final reserved =
        double.tryParse('${wallet['wallet_reserved_voucher_balance'] ?? 0}') ??
        0.0;

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF111827), Color(0xFF1F2937)],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.account_balance_wallet_outlined, color: Colors.white),
              SizedBox(width: 10),
              Text(
                'Wallet',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'Balance: ${formatMoney(balance)}',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Available: ${formatMoney(available)} • Reserved: ${formatMoney(reserved)}',
            style: TextStyle(color: Colors.white.withValues(alpha: 0.75)),
          ),
        ],
      ),
    );
  }

  Widget _welcomeCard(String name) {
    final compact = MediaQuery.of(context).size.width < 390;
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
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: compact ? 18 : 20,
                    fontWeight: FontWeight.w700,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                const Text(
                  'Track fuel vouchers and repayments in one place.',
                  style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 13),
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
    final compact = MediaQuery.of(context).size.width < 390;
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
                  maxLines: compact ? 2 : 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: AppTheme.slate,
                    fontSize: compact ? 16 : 20,
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
            'Apply for new fuel access and manage issuance in one place.',
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
            child: LayoutBuilder(
              builder: (context, constraints) {
                final compact = constraints.maxWidth < 380;
                final tileWidth = compact ? 108.0 : 120.0;
                return ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: brands.length,
                  separatorBuilder: (_, index) => const SizedBox(width: 8),
                  itemBuilder: (context, i) {
                    final brand = brands[i];
                    return Container(
                      width: tileWidth,
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
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: compact ? 12 : 13,
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
                                border: Border.all(
                                  color: const Color(0xFFE2E8F0),
                                ),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.all(4),
                                child: ClipOval(
                                  child: Image.asset(
                                    brand['logo'] ?? '',
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, _, error) =>
                                        Image.asset(
                                          'assets/images/app_logo.png',
                                          fit: BoxFit.cover,
                                        ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _mobilityPartnersCard() {
    const partners = <Map<String, String>>[
      {'name': 'Uber', 'logo': 'assets/images/partners/uber.png'},
      {'name': 'Uber Eats', 'logo': 'assets/images/partners/uber-eats.png'},
      {'name': 'inDrive', 'logo': 'assets/images/partners/indrive.png'},
      {'name': 'Mr D', 'logo': 'assets/images/partners/mrd.png'},
      {'name': 'Takealot', 'logo': 'assets/images/partners/takealot.png'},
      {'name': 'Sixty60', 'logo': 'assets/images/partners/sixty60.png'},
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
                  'Mobility Partners',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFFE2E8F0),
                  ),
                ),
              ),
              Icon(
                Icons.local_shipping_rounded,
                color: AppTheme.primaryBlue,
                size: 18,
              ),
            ],
          ),
          const SizedBox(height: 4),
          const Text(
            'Where drivers earn: ride-hailing, delivery, and retail logistics.',
            style: TextStyle(color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 10),
          SizedBox(
            height: 80,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: partners.length,
              separatorBuilder: (context, index) => const SizedBox(width: 10),
              itemBuilder: (context, i) {
                final p = partners[i];
                return Container(
                  width: 110,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFF334155)),
                    gradient: const LinearGradient(
                      colors: [Color(0xFF111827), Color(0xFF1F2937)],
                    ),
                  ),
                  child: Center(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 10),
                      child: Image.asset(
                        p['logo'] ?? '',
                        fit: BoxFit.contain,
                        errorBuilder: (context, error, stackTrace) => Text(
                          p['name'] ?? 'Partner',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 12,
                            color: AppTheme.slate,
                          ),
                        ),
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

  Widget _wisdomCard() {
    final width = MediaQuery.of(context).size.width;
    final compact = width < 390;
    final titleSize = compact ? 38.0 : 48.0;
    final quoteSize = compact ? 34.0 : 44.0;
    final bodySize = compact ? 19.0 : 23.0;
    return Container(
      width: double.infinity,
      constraints: BoxConstraints(minHeight: compact ? 200 : 220),
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
          Text(
            'Stay Wise',
            style: TextStyle(
              fontWeight: FontWeight.w800,
              color: const Color(0xFF465512),
              fontSize: titleSize,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '”',
            style: TextStyle(
              color: const Color(0xFFDFF886),
              fontSize: quoteSize,
              height: 0.9,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Wisdom is the only gift\nyou can never lose.',
            style: TextStyle(
              fontSize: bodySize,
              height: 1.06,
              fontWeight: FontWeight.w900,
              color: const Color(0xFF465512),
            ),
          ),
          const SizedBox(height: 14),
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
              Expanded(
                child: Text(
                  'BWISER Driver Wisdom',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF7F9B1D),
                  ),
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
  String? warning;
  List<VoucherItem> vouchers = [];
  List<Map<String, dynamic>> stations = [];

  @override
  void initState() {
    super.initState();
    fetch();
  }

  Future<void> fetch() async {
    setState(() {
      loading = true;
      error = null;
      warning = null;
    });
    try {
      final list = await widget.api.driverVouchers();
      setState(() {
        vouchers = list;
        stations = _inferStationsFromVouchers(list);
      });
    } catch (e) {
      setState(() {
        vouchers = const [];
        stations = const [];
        error = null;
        warning =
            'Could not load vouchers from server right now. Pull to refresh.';
      });
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: AppLoader());
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
          if (warning != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFF59E0B)),
                ),
                child: Text(
                  warning!,
                  style: const TextStyle(
                    color: Color(0xFFFDE68A),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
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
    final statusColor = v.status == 'redeemed'
        ? const Color(0xFFDBEAFE)
        : const Color(0xFFE7E7FF);
    final station = _stationForVoucher(v);
    final stationMeta = _stationMeta(station, v);

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
            formatMoney(v.amount),
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w700,
              color: AppTheme.slate,
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 14,
            runSpacing: 8,
            children: [
              InkWell(
                onTap: stationMeta.hasTarget
                    ? () => _openNavigateSheet(v)
                    : null,
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.near_me_rounded,
                      color: stationMeta.hasTarget
                          ? const Color(0xFF38BDF8)
                          : const Color(0xFF64748B),
                      size: 20,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      'Navigate',
                      style: TextStyle(
                        color: stationMeta.hasTarget
                            ? const Color(0xFF38BDF8)
                            : const Color(0xFF64748B),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              InkWell(
                onTap: () => _showVoucherSheet(v),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.qr_code_2,
                      color: AppTheme.primaryBlue,
                      size: 20,
                    ),
                    SizedBox(width: 6),
                    Text(
                      'Show voucher',
                      style: TextStyle(
                        color: AppTheme.primaryBlue,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (stationMeta.label.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              stationMeta.label,
              style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
            ),
          ],
        ],
      ),
    );
  }

  Map<String, dynamic>? _stationForVoucher(VoucherItem v) {
    final target = (v.stationName ?? '').trim().toLowerCase();
    if (target.isEmpty || stations.isEmpty) return null;

    for (final station in stations) {
      final stationName = (station['name'] ?? '')
          .toString()
          .trim()
          .toLowerCase();
      if (stationName == target) return station;
    }
    for (final station in stations) {
      final stationName = (station['name'] ?? '')
          .toString()
          .trim()
          .toLowerCase();
      if (target.contains(stationName) || stationName.contains(target)) {
        return station;
      }
    }
    return null;
  }

  List<Map<String, dynamic>> _inferStationsFromVouchers(
    List<VoucherItem> list,
  ) {
    final unique = <String>{};
    final result = <Map<String, dynamic>>[];
    var nextId = 1;
    for (final voucher in list) {
      final name = (voucher.stationName ?? '').trim();
      if (name.isEmpty) continue;
      final key = name.toLowerCase();
      if (!unique.add(key)) continue;
      result.add({
        'id': nextId++,
        'name': name,
        'city': '',
        'address': '',
        'latitude': null,
        'longitude': null,
      });
    }
    return result;
  }

  _StationNavMeta _stationMeta(Map<String, dynamic>? station, VoucherItem v) {
    final name = (v.stationName ?? station?['name'] ?? 'Station')
        .toString()
        .trim();
    final rawName = (v.stationName ?? station?['name'] ?? '').toString().trim();
    final city = (station?['city'] ?? '').toString().trim();
    final address = (station?['address'] ?? '').toString().trim();
    final lat = double.tryParse('${station?['latitude'] ?? ''}');
    final lng = double.tryParse('${station?['longitude'] ?? ''}');
    final hasCoords = lat != null && lng != null;
    final hasTarget = hasCoords || rawName.isNotEmpty || address.isNotEmpty;
    final locationText = address.isNotEmpty
        ? address
        : (city.isNotEmpty ? city : 'location pending');
    final modeText = hasCoords ? 'GPS-ready' : 'Search route';
    return _StationNavMeta(
      hasTarget: hasTarget,
      label: '$name • $locationText • $modeText',
    );
  }

  Future<void> _openNavigateSheet(VoucherItem v) async {
    final station = _stationForVoucher(v);
    final destinationName = (v.stationName ?? station?['name'] ?? 'Station')
        .toString()
        .trim();
    final city = (station?['city'] ?? '').toString().trim();
    final address = (station?['address'] ?? '').toString().trim();
    final query = [
      destinationName,
      address,
      city,
    ].where((part) => part.trim().isNotEmpty).join(', ');
    final lat = double.tryParse('${station?['latitude'] ?? ''}');
    final lng = double.tryParse('${station?['longitude'] ?? ''}');
    final hasCoords = lat != null && lng != null;
    final waypoint = hasCoords
        ? '${lat.toStringAsFixed(6)},${lng.toStringAsFixed(6)}'
        : '';
    if (!hasCoords && query.trim().isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Station location data is not available.'),
        ),
      );
      return;
    }

    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return LayoutBuilder(
          builder: (context, constraints) {
            final width = constraints.maxWidth.isFinite
                ? constraints.maxWidth
                : MediaQuery.of(context).size.width;
            return SizedBox(
              width: width,
              child: Container(
                decoration: const BoxDecoration(
                  color: Color(0xFF0B1220),
                  borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
                ),
                child: SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 22),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Center(
                          child: Container(
                            width: 44,
                            height: 4,
                            decoration: BoxDecoration(
                              color: const Color(0xFF64748B),
                              borderRadius: BorderRadius.circular(999),
                            ),
                          ),
                        ),
                        const SizedBox(height: 14),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            gradient: AppTheme.actionGradient,
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 48,
                                height: 48,
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: const Icon(
                                  Icons.local_taxi_rounded,
                                  color: Color(0xFFE2E8F0),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      'Navigate to Redeem Station',
                                      style: TextStyle(
                                        color: Color(0xFFF8FAFC),
                                        fontWeight: FontWeight.w800,
                                        fontSize: 16,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      destinationName,
                                      style: const TextStyle(
                                        color: Color(0xFFBFDBFE),
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          query.isEmpty
                              ? 'Station location not available yet.'
                              : query,
                          style: const TextStyle(color: Color(0xFF94A3B8)),
                        ),
                        const SizedBox(height: 14),
                        _mapOptionTile(
                          icon: Icons.map_rounded,
                          title: 'HERE WeGo',
                          subtitle: 'Native HERE turn-by-turn route',
                          color: const Color(0xFF00AFAA),
                          onTap: () => _launchNavigation(
                            appName: 'HERE WeGo',
                            primary: hasCoords
                                ? Uri.parse(
                                    'here.directions://v1.0/mylocation/$waypoint',
                                  )
                                : Uri.parse(
                                    'https://wego.here.com/search/${Uri.encodeComponent(query)}',
                                  ),
                            fallback: hasCoords
                                ? Uri.parse(
                                    'https://wego.here.com/directions/drive/mylocation/$waypoint',
                                  )
                                : Uri.parse(
                                    'https://wego.here.com/search/${Uri.encodeComponent(query)}',
                                  ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        _mapOptionTile(
                          icon: Icons.navigation_rounded,
                          title: 'Google Maps',
                          subtitle: 'Fastest live traffic route',
                          color: const Color(0xFF3B82F6),
                          onTap: () => _launchNavigation(
                            appName: 'Google Maps',
                            primary: hasCoords
                                ? Uri.parse(
                                    'google.navigation:q=$waypoint&mode=d',
                                  )
                                : Uri.parse(
                                    'https://www.google.com/maps/dir/?api=1&destination=${Uri.encodeComponent(query)}',
                                  ),
                            fallback: hasCoords
                                ? Uri.parse(
                                    'https://www.google.com/maps/dir/?api=1&destination=$waypoint&travelmode=driving',
                                  )
                                : Uri.parse(
                                    'https://www.google.com/maps/dir/?api=1&destination=${Uri.encodeComponent(query)}',
                                  ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        _mapOptionTile(
                          icon: Icons.alt_route_rounded,
                          title: 'Waze',
                          subtitle: 'Road alerts and quickest reroutes',
                          color: const Color(0xFF38BDF8),
                          onTap: () => _launchNavigation(
                            appName: 'Waze',
                            primary: hasCoords
                                ? Uri.parse('waze://?ll=$waypoint&navigate=yes')
                                : Uri.parse(
                                    'https://waze.com/ul?q=${Uri.encodeComponent(query)}&navigate=yes',
                                  ),
                            fallback: hasCoords
                                ? Uri.parse(
                                    'https://waze.com/ul?ll=$waypoint&navigate=yes',
                                  )
                                : Uri.parse(
                                    'https://waze.com/ul?q=${Uri.encodeComponent(query)}&navigate=yes',
                                  ),
                          ),
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
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _launchNavigation({
    required String appName,
    required Uri primary,
    required Uri fallback,
  }) async {
    try {
      final launched = await launchUrl(
        primary,
        mode: LaunchMode.externalApplication,
      );
      if (!launched) {
        final fallbackLaunched = await launchUrl(
          fallback,
          mode: LaunchMode.externalApplication,
        );
        if (!fallbackLaunched) {
          throw Exception('Could not open $appName.');
        }
      }
      if (!mounted) return;
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  Widget _mapOptionTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFF111827),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: color.withValues(alpha: 0.5)),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: color),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      color: Color(0xFFE2E8F0),
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      color: Color(0xFF94A3B8),
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right_rounded, color: color),
          ],
        ),
      ),
    );
  }

  Future<void> _showVoucherSheet(VoucherItem v) async {
    final status = v.status.toLowerCase().trim();
    final isApproved = status == 'approved';
    final hasQr = v.qrCode.isNotEmpty;
    var mode = (!isApproved) ? 'details' : (hasQr ? 'qr' : 'tap');
    var tapArming = false;
    var tapArmed = false;
    var tapStatus = isApproved
        ? 'Switch to Tap to arm your phone for POS tap.'
        : 'Voucher is ${v.status}. QR/Tap becomes available once approved.';

    Future<void> armTap(
      StateSetter setSheetState,
      BuildContext sheetContext,
    ) async {
      if (!isApproved) {
        setSheetState(() {
          tapArming = false;
          tapArmed = false;
          tapStatus = 'Voucher is ${v.status}. Tap is available once approved.';
        });
        return;
      }
      if (tapArming || tapArmed) return;
      setSheetState(() {
        tapArming = true;
        tapStatus = 'Preparing secure tap token...';
      });

      try {
        final token = await widget.api.driverTapToken(v.id);
        final enabled = await NfcHceBridge.isAvailable();
        if (!enabled) {
          throw Exception('NFC/HCE is unavailable or disabled on this phone.');
        }
        await NfcHceBridge.setTapToken(token);
        if (!sheetContext.mounted) return;
        setSheetState(() {
          tapArming = false;
          tapArmed = true;
          tapStatus = 'Ready to tap. Hold phone near POS reader.';
        });
      } catch (e) {
        if (!sheetContext.mounted) return;
        setSheetState(() {
          tapArming = false;
          tapArmed = false;
          tapStatus = e.toString().replaceFirst('Exception: ', '');
        });
      }
    }

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
                        'Voucher',
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
                      if (isApproved)
                        SegmentedButton<String>(
                          showSelectedIcon: false,
                          segments: [
                            if (hasQr)
                              const ButtonSegment(
                                value: 'qr',
                                label: Text('QR-Scan'),
                                icon: Icon(Icons.qr_code_2, size: 16),
                              ),
                            const ButtonSegment(
                              value: 'tap',
                              label: Text('Tap'),
                              icon: Icon(Icons.nfc, size: 16),
                            ),
                          ],
                          selected: {mode},
                          onSelectionChanged: (v) {
                            setSheetState(() => mode = v.first);
                            if (v.first == 'tap') {
                              unawaited(armTap(setSheetState, context));
                            }
                          },
                        )
                      else
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFF111827),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: const Text(
                            'This voucher is not approved yet. You can view it now, but QR/Tap redemption becomes available after approval.',
                            style: TextStyle(
                              color: Color(0xFFCBD5E1),
                              fontWeight: FontWeight.w600,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      const SizedBox(height: 14),
                      if (!isApproved)
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: const Color(0xFF111827),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Status',
                                style: TextStyle(
                                  color: Color(0xFFE2E8F0),
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                v.status,
                                style: const TextStyle(
                                  color: Color(0xFFFCA5A5),
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Once approved, this sheet will show the QR code and enable phone tap for POS redemption.',
                                style: TextStyle(
                                  color: Color(0xFF94A3B8),
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        )
                      else if (mode == 'qr')
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
                                'Phone Tap',
                                style: TextStyle(
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFFE2E8F0),
                                ),
                              ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  if (tapArming)
                                    const AppLoader(size: 18, showText: false)
                                  else
                                    Icon(
                                      tapArmed
                                          ? Icons.nfc_rounded
                                          : Icons.error_outline_rounded,
                                      size: 18,
                                      color: tapArmed
                                          ? const Color(0xFF10B981)
                                          : const Color(0xFFFCA5A5),
                                    ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      tapStatus,
                                      style: TextStyle(
                                        color: tapArmed
                                            ? const Color(0xFF86EFAC)
                                            : const Color(0xFFFCA5A5),
                                        fontSize: 12,
                                      ),
                                    ),
                                  ),
                                ],
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
                        '${formatMoney(v.amount)} • ${v.status}',
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

class _StationNavMeta {
  const _StationNavMeta({required this.hasTarget, required this.label});

  final bool hasTarget;
  final String label;
}

class DriverNavigatePage extends StatefulWidget {
  const DriverNavigatePage({super.key, required this.api});
  final ApiClient api;

  @override
  State<DriverNavigatePage> createState() => _DriverNavigatePageState();
}

class _DriverNavigatePageState extends State<DriverNavigatePage> {
  bool loading = true;
  String? warning;
  List<String> stations = [];

  @override
  void initState() {
    super.initState();
    fetch();
  }

  Future<void> fetch() async {
    setState(() {
      loading = true;
      warning = null;
    });
    try {
      final vouchers = await widget.api.driverVouchers();
      final unique = <String>{};
      for (final voucher in vouchers) {
        final name = (voucher.stationName ?? '').trim();
        if (name.isNotEmpty) unique.add(name);
      }
      if (!mounted) return;
      setState(() {
        stations = unique.toList()..sort();
        if (stations.isEmpty) {
          warning = 'No station route targets yet. Apply for a voucher first.';
        }
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        stations = const [];
        warning = 'Could not load station routes right now. Pull to refresh.';
      });
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: AppLoader());

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
            padding: const EdgeInsets.all(16),
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Map Navigation',
                  style: TextStyle(
                    color: Color(0xFFE2E8F0),
                    fontWeight: FontWeight.w800,
                    fontSize: 22,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'Open turn-by-turn directions to voucher stations.',
                  style: TextStyle(color: Color(0xFFBFDBFE)),
                ),
              ],
            ),
          ),
          if (warning != null) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFF59E0B)),
              ),
              child: Text(
                warning!,
                style: const TextStyle(
                  color: Color(0xFFFDE68A),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
          const SizedBox(height: 12),
          if (stations.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(14),
                child: Text('No station destinations available.'),
              ),
            ),
          ...stations.map((station) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Container(
                decoration: BoxDecoration(
                  color: const Color(0xFF0B1220),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFF334155)),
                ),
                padding: const EdgeInsets.all(14),
                child: Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        color: const Color(0xFF1E293B),
                      ),
                      child: const Icon(
                        Icons.local_gas_station_rounded,
                        color: Color(0xFF38BDF8),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        station,
                        style: const TextStyle(
                          color: Color(0xFFE2E8F0),
                          fontWeight: FontWeight.w700,
                          fontSize: 16,
                        ),
                      ),
                    ),
                    FxButton(
                      label: 'Navigate',
                      height: 40,
                      onPressed: () => _openNavigationOptions(station),
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

  Future<void> _openNavigationOptions(String stationName) async {
    final query = Uri.encodeComponent(stationName);
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          decoration: const BoxDecoration(
            color: Color(0xFF0B1220),
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 22),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFF64748B),
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    stationName,
                    style: const TextStyle(
                      color: Color(0xFFE2E8F0),
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _navOption(
                    title: 'HERE WeGo',
                    icon: Icons.map_rounded,
                    onTap: () => _launchMap(
                      Uri.parse('https://wego.here.com/search/$query'),
                    ),
                  ),
                  const SizedBox(height: 8),
                  _navOption(
                    title: 'Google Maps',
                    icon: Icons.navigation_rounded,
                    onTap: () => _launchMap(
                      Uri.parse(
                        'https://www.google.com/maps/dir/?api=1&destination=$query',
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  _navOption(
                    title: 'Bing Maps',
                    icon: Icons.public_rounded,
                    onTap: () => _launchMap(
                      Uri.parse('https://www.bing.com/maps?q=$query'),
                    ),
                  ),
                  const SizedBox(height: 8),
                  _navOption(
                    title: 'OpenStreetMap (Leaflet)',
                    icon: Icons.map_outlined,
                    onTap: () => _launchMap(
                      Uri.parse(
                        'https://www.openstreetmap.org/search?query=$query',
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  _navOption(
                    title: 'Waze',
                    icon: Icons.alt_route_rounded,
                    onTap: () => _launchMap(
                      Uri.parse('https://waze.com/ul?q=$query&navigate=yes'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Future<void> _launchMap(Uri uri) async {
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!mounted) return;
    if (opened) {
      Navigator.of(context).pop();
      return;
    }
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(const SnackBar(content: Text('Unable to open map app.')));
  }

  Widget _navOption({
    required String title,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFF111827),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFF334155)),
        ),
        child: Row(
          children: [
            Icon(icon, color: const Color(0xFF38BDF8)),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                title,
                style: const TextStyle(
                  color: Color(0xFFE2E8F0),
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            const Icon(Icons.chevron_right_rounded, color: Color(0xFF38BDF8)),
          ],
        ),
      ),
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
  // Leaflet is the in-app default. Other providers are fallbacks that open externally.
  _DriverMapProvider mapProvider = _DriverMapProvider.leaflet;
  bool loading = true;
  bool submitting = false;
  bool autopayEnabled = false;
  bool autopayReady = false;
  String? error;
  final amountCtrl = TextEditingController(text: '500');
  final stationCtrl = TextEditingController();
  final FocusNode stationFocusNode = FocusNode();
  final MapController mapController = MapController();
  String fuelType = 'petrol';
  List<Map<String, dynamic>> stations = [];
  Timer? stationSearchDebounce;
  Timer? approvalPollTimer;
  int approvedBeforeSubmit = 0;
  String? selectedStationName;
  Map<String, dynamic>? selectedStation;
  int? stationId;
  double? driverLatitude;
  double? driverLongitude;

  Uri _mapProviderUri(_DriverMapProvider provider) {
    final name = (selectedStation?['name'] ?? selectedStationName ?? '')
        .toString();
    final coords = selectedStation == null
        ? null
        : _stationLatLng(selectedStation!);
    final hasCoords = coords != null;
    final lat = hasCoords ? coords.latitude : driverLatitude;
    final lng = hasCoords ? coords.longitude : driverLongitude;
    final hasDriverCoords = lat != null && lng != null;

    final q = (name.trim().isNotEmpty) ? name.trim() : 'fuel station';
    final query = Uri.encodeComponent(q);

    switch (provider) {
      case _DriverMapProvider.leaflet:
        if (hasDriverCoords) {
          // OpenStreetMap slippy map. Leaflet itself is embedded in-app.
          return Uri.parse('https://www.openstreetmap.org/#map=14/$lat/$lng');
        }
        return Uri.parse('https://www.openstreetmap.org/search?query=$query');
      case _DriverMapProvider.google:
        if (hasDriverCoords) {
          return Uri.parse(
            'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent('$lat,$lng')}&query_place_id=',
          );
        }
        return Uri.parse(
          'https://www.google.com/maps/search/?api=1&query=$query',
        );
      case _DriverMapProvider.here:
        if (hasDriverCoords) {
          return Uri.parse(
            'https://wego.here.com/search/$query?map=$lat,$lng,14,normal',
          );
        }
        return Uri.parse('https://wego.here.com/search/$query');
      case _DriverMapProvider.bing:
        if (hasDriverCoords) {
          return Uri.parse(
            'https://www.bing.com/maps?q=$query&cp=$lat~$lng&lvl=14',
          );
        }
        return Uri.parse('https://www.bing.com/maps?q=$query');
    }
  }

  Future<void> _openSelectedProvider() async {
    final uri = _mapProviderUri(mapProvider);
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!mounted) return;
    if (!opened) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Unable to open map provider.')),
      );
    }
  }

  Widget _providerChip(_DriverMapProvider provider, String label) {
    final active = mapProvider == provider;
    return InkWell(
      onTap: () => setState(() => mapProvider = provider),
      borderRadius: BorderRadius.circular(999),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(999),
          color: active ? const Color(0xFF1D4ED8) : const Color(0xFF111827),
          border: Border.all(
            color: active ? const Color(0xFF60A5FA) : const Color(0xFF334155),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: active ? Colors.white : const Color(0xFFCBD5E1),
          ),
        ),
      ),
    );
  }

  Widget _mapPanel({
    required LatLng mapCenter,
    required List<Map<String, dynamic>> nearestStations,
  }) {
    if (mapProvider != _DriverMapProvider.leaflet) {
      final title = switch (mapProvider) {
        _DriverMapProvider.google => 'Google Maps',
        _DriverMapProvider.here => 'HERE WeGo',
        _DriverMapProvider.bing => 'Bing Maps',
        _DriverMapProvider.leaflet => 'Leaflet',
      };
      return Container(
        height: 250,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFF334155)),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0B1220), Color(0xFF111827)],
          ),
        ),
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: Color(0xFFE2E8F0),
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Fallback provider opens in your map app/browser.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                ),
                const SizedBox(height: 12),
                FxButton(
                  label: 'Open Map',
                  icon: Icons.open_in_new_rounded,
                  onPressed: _openSelectedProvider,
                ),
              ],
            ),
          ),
        ),
      );
    }

    return FlutterMap(
      mapController: mapController,
      options: MapOptions(initialCenter: mapCenter, initialZoom: 12.5),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'co.za.bwiser.mobile',
        ),
        if (driverLatitude != null && driverLongitude != null)
          MarkerLayer(
            markers: [
              Marker(
                width: 26,
                height: 26,
                point: LatLng(driverLatitude!, driverLongitude!),
                child: const Icon(
                  Icons.my_location_rounded,
                  size: 22,
                  color: Color(0xFF2563EB),
                ),
              ),
            ],
          ),
        MarkerLayer(
          markers: nearestStations.map((s) {
            final point = _stationLatLng(s)!;
            final selected =
                (s['id'] ?? '').toString() == (stationId ?? '').toString();
            return Marker(
              point: point,
              width: 34,
              height: 34,
              child: GestureDetector(
                onTap: () => _selectStation(s),
                child: Icon(
                  Icons.location_on_rounded,
                  size: selected ? 32 : 28,
                  color: selected
                      ? const Color(0xFFDC2626)
                      : const Color(0xFF16A34A),
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }

  @override
  void initState() {
    super.initState();
    fetch();
  }

  @override
  void dispose() {
    stationSearchDebounce?.cancel();
    approvalPollTimer?.cancel();
    amountCtrl.dispose();
    stationCtrl.dispose();
    stationFocusNode.dispose();
    super.dispose();
  }

  Future<void> fetch() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final profile = await widget.api.profile();
      _applyProfileLocationFallback(profile);
      await _resolveCurrentLocation();
      List<Map<String, dynamic>> st = const [];
      if (driverLatitude != null && driverLongitude != null) {
        try {
          st = await widget.api.nearbyStations(
            latitude: driverLatitude!,
            longitude: driverLongitude!,
            radiusKm: 40,
            limit: 60,
          );
        } catch (_) {
          // Fallback to full stations list below.
        }
      }
      if (st.isEmpty) {
        st = await widget.api.stations();
      }
      final sortedStations = _sortStationsByDistance(st);
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
        stations = sortedStations;
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

  void _applyProfileLocationFallback(Map<String, dynamic> profile) {
    final lat = double.tryParse(
      '${profile['latitude'] ?? profile['lat'] ?? ''}',
    );
    final lng = double.tryParse(
      '${profile['longitude'] ?? profile['lng'] ?? profile['lon'] ?? ''}',
    );
    if (lat != null && lng != null) {
      driverLatitude = lat;
      driverLongitude = lng;
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

    final amount = double.tryParse(amountCtrl.text.trim()) ?? 0;
    if (amount < 500 || amount > 1200) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Voucher amount must be between R500 and R1200.'),
        ),
      );
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
        amount: amount,
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

  Future<void> _resolveCurrentLocation() async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) return;

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        return;
      }

      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
        ),
      ).timeout(const Duration(seconds: 6));
      driverLatitude = pos.latitude;
      driverLongitude = pos.longitude;
    } catch (_) {
      try {
        final last = await Geolocator.getLastKnownPosition();
        if (last != null) {
          driverLatitude = last.latitude;
          driverLongitude = last.longitude;
        }
      } catch (_) {
        // Keep station apply usable even if location lookup fails.
      }
    }
  }

  List<Map<String, dynamic>> _sortStationsByDistance(
    List<Map<String, dynamic>> input,
  ) {
    final list = List<Map<String, dynamic>>.from(input);
    list.sort((a, b) {
      final ad = _distanceKm(a) ?? double.infinity;
      final bd = _distanceKm(b) ?? double.infinity;
      return ad.compareTo(bd);
    });
    return list;
  }

  double? _distanceKm(Map<String, dynamic> station) {
    final rawDistance = station['distance'];
    if (rawDistance != null) {
      final normalized = rawDistance
          .toString()
          .toLowerCase()
          .replaceAll('km', '')
          .replaceAll(RegExp(r'[^0-9\.\-]'), '')
          .trim();
      final parsed = double.tryParse(normalized);
      if (parsed != null) return parsed;
    }

    if (driverLatitude == null || driverLongitude == null) return null;
    final lat = double.tryParse(
      '${station['latitude'] ?? station['lat'] ?? ''}',
    );
    final lng = double.tryParse(
      '${station['longitude'] ?? station['lng'] ?? station['lon'] ?? ''}',
    );
    if (lat == null || lng == null) return null;
    final meters = Geolocator.distanceBetween(
      driverLatitude!,
      driverLongitude!,
      lat,
      lng,
    );
    return meters / 1000;
  }

  LatLng? _stationLatLng(Map<String, dynamic> station) {
    final lat = double.tryParse(
      '${station['latitude'] ?? station['lat'] ?? ''}',
    );
    final lng = double.tryParse(
      '${station['longitude'] ?? station['lng'] ?? station['lon'] ?? ''}',
    );
    if (lat == null || lng == null) return null;
    return LatLng(lat, lng);
  }

  List<Map<String, dynamic>> _nearestStationsForMap() {
    return stations.where((s) => _stationLatLng(s) != null).take(30).toList();
  }

  List<Map<String, dynamic>> _matchingStations(String query) {
    final q = query.trim().toLowerCase();
    if (q.isEmpty) {
      return _sortStationsByDistance(stations).take(8).toList();
    }
    final matched = stations.where((s) {
      final name = (s['name'] ?? '').toString().toLowerCase();
      final city = (s['city'] ?? '').toString().toLowerCase();
      return name.contains(q) || city.contains(q);
    }).toList();
    matched.sort((a, b) {
      final ad = _distanceKm(a) ?? double.infinity;
      final bd = _distanceKm(b) ?? double.infinity;
      final cmp = ad.compareTo(bd);
      if (cmp != 0) return cmp;
      return (a['name'] ?? '').toString().compareTo(
        (b['name'] ?? '').toString(),
      );
    });
    return matched.take(8).toList();
  }

  void _moveMapToStation(Map<String, dynamic> station, {double zoom = 14}) {
    final point = _stationLatLng(station);
    if (point == null) return;
    try {
      mapController.move(point, zoom);
    } catch (_) {
      // Ignore map move race conditions before first frame.
    }
  }

  void _previewSearchOnMap(String query) {
    final candidates = _matchingStations(query);
    if (candidates.isEmpty) return;
    _moveMapToStation(candidates.first, zoom: 12.8);
  }

  LatLng _initialMapCenter() {
    final selected = selectedStation == null
        ? null
        : _stationLatLng(selectedStation!);
    if (selected != null) return selected;
    if (driverLatitude != null && driverLongitude != null) {
      return LatLng(driverLatitude!, driverLongitude!);
    }
    final first = stations.isEmpty ? null : _stationLatLng(stations.first);
    return first ?? const LatLng(-26.2041, 28.0473);
  }

  void _selectStation(Map<String, dynamic> station) {
    setState(() {
      selectedStation = station;
      stationId = int.tryParse('${station['id'] ?? 0}') ?? 0;
      selectedStationName = (station['name'] ?? '').toString();
      stationCtrl.text = selectedStationName ?? '';
    });
    _moveMapToStation(station);
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
        final selectedName = (selectedStationName ?? '').toLowerCase().trim();
        final matchesStation = selectedName.isEmpty
            ? grew
            : approved.any(
                (v) =>
                    (v.stationName ?? '').toLowerCase().contains(selectedName),
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

  Future<void> _searchStationsRemote(String query) async {
    stationSearchDebounce?.cancel();
    final trimmed = query.trim();
    if (trimmed.length < 2) return;

    stationSearchDebounce = Timer(const Duration(milliseconds: 260), () async {
      try {
        final found = await widget.api.searchStations(trimmed);
        if (!mounted || stationCtrl.text.trim() != trimmed) return;
        if (found.isNotEmpty) {
          setState(() {
            stations = _sortStationsByDistance(found);
          });
        }
      } catch (_) {
        // Keep local suggestions if network search fails.
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: AppLoader());
    if (error != null) return Center(child: Text(error!));

    final nearestStations = _nearestStationsForMap();
    final mapCenter = _initialMapCenter();
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
                const SizedBox(height: 10),
                ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: SizedBox(
                    height: 250,
                    child: _mapPanel(
                      mapCenter: mapCenter,
                      nearestStations: nearestStations,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _providerChip(_DriverMapProvider.leaflet, 'Leaflet'),
                      const SizedBox(width: 8),
                      _providerChip(_DriverMapProvider.google, 'Google'),
                      const SizedBox(width: 8),
                      _providerChip(_DriverMapProvider.bing, 'Bing'),
                      const SizedBox(width: 8),
                      _providerChip(_DriverMapProvider.here, 'HERE'),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                const Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Closest partner stations near you. Tap a marker to auto-fill station search.',
                    style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                  ),
                ),
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton.icon(
                    onPressed: fetch,
                    icon: const Icon(Icons.my_location_rounded, size: 16),
                    label: const Text('Use my location'),
                  ),
                ),
                const SizedBox(height: 12),
                RawAutocomplete<Map<String, dynamic>>(
                  textEditingController: stationCtrl,
                  focusNode: stationFocusNode,
                  optionsBuilder: (TextEditingValue value) {
                    return _matchingStations(value.text);
                  },
                  displayStringForOption: (option) =>
                      '${option['name'] ?? 'Station'}',
                  onSelected: (s) {
                    _selectStation(s);
                  },
                  fieldViewBuilder:
                      (context, controller, focusNode, onFieldSubmitted) {
                        return TextField(
                          controller: controller,
                          focusNode: focusNode,
                          style: const TextStyle(color: Color(0xFFFFFFFF)),
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
                            _searchStationsRemote(controller.text);
                            _previewSearchOnMap(controller.text);
                          },
                        );
                      },
                  optionsViewBuilder: (context, onSelected, options) {
                    final viewportWidth = MediaQuery.of(context).size.width;
                    final width = (viewportWidth - 56).clamp(260.0, 460.0);
                    return Material(
                      color: const Color(0xFF0B1220),
                      elevation: 0,
                      shadowColor: Colors.transparent,
                      surfaceTintColor: Colors.transparent,
                      clipBehavior: Clip.antiAlias,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                        side: const BorderSide(color: Color(0xFF334155)),
                      ),
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxHeight: 180),
                        child: SizedBox(
                          width: width,
                          child: ListView.builder(
                            shrinkWrap: true,
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            itemCount: options.length,
                            itemBuilder: (context, index) {
                              final s = options.elementAt(index);
                              final partner = _stationIsPartner(s);
                              final funded = _stationHasFunds(s);
                              final km = _distanceKm(s);
                              final distanceText = km == null
                                  ? 'distance unknown'
                                  : '${km.toStringAsFixed(1)} km away';
                              return ListTile(
                                dense: true,
                                title: Text(
                                  '${s['name']}',
                                  style: const TextStyle(
                                    color: Color(0xFFE2E8F0),
                                  ),
                                ),
                                subtitle: Text(
                                  '${s['city'] ?? '-'} • $distanceText • ${partner ? 'Partner' : 'Non-partner'} • ${funded ? 'Funded' : 'No funds'}',
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
                  style: const TextStyle(color: Color(0xFFFFFFFF)),
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Amount (R)'),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: fuelType,
                  style: const TextStyle(color: Color(0xFFFFFFFF)),
                  dropdownColor: const Color(0xFF0F172A),
                  items: const [
                    DropdownMenuItem(
                      value: 'petrol',
                      child: Text(
                        'Petrol',
                        style: TextStyle(color: Color(0xFFFFFFFF)),
                      ),
                    ),
                    DropdownMenuItem(
                      value: 'diesel',
                      child: Text(
                        'Diesel',
                        style: TextStyle(color: Color(0xFFFFFFFF)),
                      ),
                    ),
                    DropdownMenuItem(
                      value: 'super',
                      child: Text(
                        'Super',
                        style: TextStyle(color: Color(0xFFFFFFFF)),
                      ),
                    ),
                  ],
                  onChanged: (v) => setState(() => fuelType = v ?? 'petrol'),
                  decoration: const InputDecoration(labelText: 'Fuel Type'),
                ),
                const SizedBox(height: 18),
                submitting
                    ? const SizedBox(
                        height: 54,
                        child: Center(
                          child: AppLoader(size: 22, showText: false),
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

enum _DriverMapProvider { leaflet, google, bing, here }

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
    if (loading) return const Center(child: AppLoader());
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
                      formatMoney(item.amount),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppTheme.slate,
                        fontWeight: FontWeight.w700,
                        fontSize: 32,
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
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('AutoPay enabled (Paystack).')),
      );
      await fetch();
      return;
    }

    setState(() => loading = true);
    try {
      await widget.api.setAutopay(enabled: false, method: 'paystack');
      if (!mounted) return;
      setState(() {
        enabled = false;
        ready = false;
      });
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('AutoPay disabled.')));
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
      final probe = double.tryParse('${checkout['probe_amount'] ?? 0}') ?? 0;
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
            'Complete the small Paystack authorization transaction (about ${formatMoney(probe)}), then tap Confirm.',
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

  Future<void> _authorizeAutopayCard() async {
    setState(() => loading = true);
    try {
      final configured = await _setupPaystackAutopay();
      if (!configured) return;
      if (!mounted) return;
      await fetch();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('AutoPay card authorized successfully.')),
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
    if (loading) return const Center(child: AppLoader());
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
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
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
                if (!ready) ...[
                  const SizedBox(height: 12),
                  FxButton(
                    label: 'Authorize AutoPay Card',
                    icon: Icons.credit_card_rounded,
                    fullWidth: true,
                    onPressed: _authorizeAutopayCard,
                  ),
                ],
              ],
            ),
          ),
        ),
      ],
    );
  }
}
