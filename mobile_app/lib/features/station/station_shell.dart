import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:nfc_manager/nfc_manager.dart';
import 'package:nfc_manager/platform_tags.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../core/app_loader.dart';
import '../../core/app_sfx.dart';
import '../../core/fx_button.dart';
import '../../core/logo_mark.dart';
import '../../core/theme.dart';
import '../../core/ussd_call_bridge.dart';
import '../../data/api_client.dart';
import '../../data/models.dart';

class ReceiptTemplateSettings {
  const ReceiptTemplateSettings({required this.fontKey, this.footerLogoAsset});

  final String fontKey;
  final String? footerLogoAsset;

  static const ReceiptTemplateSettings defaults = ReceiptTemplateSettings(
    fontKey: 'righteous',
    footerLogoAsset: null,
  );
}

const Map<String, String> _receiptFonts = {
  'righteous': 'Righteous',
  'goldman': 'Goldman',
  'silkscreen': 'Silkscreen',
  'days_one': 'Days One',
};

const Map<String, String> _receiptBrandLogos = {
  'Shell': 'assets/images/brands/shell-sa.png',
  'BP': 'assets/images/brands/bp-southern-africa.png',
  'Engen': 'assets/images/brands/engen.png',
  'Sasol': 'assets/images/brands/sasol.png',
  'TotalEnergies': 'assets/images/brands/totalenergies.png',
};

class StationShell extends StatefulWidget {
  const StationShell({super.key, required this.api, required this.onLogout});

  final ApiClient api;
  final Future<void> Function() onLogout;

  @override
  State<StationShell> createState() => _StationShellState();
}

class _StationShellState extends State<StationShell> {
  int index = 0;
  ReceiptTemplateSettings receiptSettings = ReceiptTemplateSettings.defaults;

  @override
  void initState() {
    super.initState();
    _loadReceiptSettings();
  }

  Future<void> _loadReceiptSettings() async {
    final prefs = await SharedPreferences.getInstance();
    final fontKey =
        prefs.getString('station_receipt_font') ??
        ReceiptTemplateSettings.defaults.fontKey;
    final footerLogo = prefs.getString('station_receipt_footer_logo');
    if (!mounted) return;
    setState(() {
      receiptSettings = ReceiptTemplateSettings(
        fontKey: _receiptFonts.containsKey(fontKey)
            ? fontKey
            : ReceiptTemplateSettings.defaults.fontKey,
        footerLogoAsset: footerLogo == null || footerLogo.isEmpty
            ? null
            : footerLogo,
      );
    });
  }

  Future<void> _saveReceiptSettings(ReceiptTemplateSettings next) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('station_receipt_font', next.fontKey);
    if (next.footerLogoAsset == null || next.footerLogoAsset!.isEmpty) {
      await prefs.remove('station_receipt_footer_logo');
    } else {
      await prefs.setString(
        'station_receipt_footer_logo',
        next.footerLogoAsset!,
      );
    }
    if (!mounted) return;
    setState(() => receiptSettings = next);
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      StationHomePage(api: widget.api, receiptSettings: receiptSettings),
      StationRedeemPage(api: widget.api, receiptSettings: receiptSettings),
      StationReportsPage(api: widget.api),
      StationReceiptSettingsPage(
        settings: receiptSettings,
        onChanged: _saveReceiptSettings,
      ),
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
    final bottomInset = MediaQuery.of(context).padding.bottom;
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(16),
          topRight: Radius.circular(16),
        ),
        border: const Border(top: BorderSide(color: Color(0xFF334155))),
      ),
      // Keep the in-app bottom nav above the Android system navigation bar (3-button/gesture).
      padding: EdgeInsets.only(bottom: bottomInset),
      child: Row(
        children: [
          _item(0, Icons.history_rounded, 'History'),
          Container(width: 1, height: 48, color: const Color(0xFF334155)),
          _item(1, Icons.qr_code_scanner, 'Redeem'),
          Container(width: 1, height: 48, color: const Color(0xFF334155)),
          _item(2, Icons.bar_chart_rounded, 'Reports'),
          Container(width: 1, height: 48, color: const Color(0xFF334155)),
          _item(3, Icons.receipt_long_rounded, 'Receipt'),
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
            color: active ? const Color(0xFF111827) : Colors.transparent,
            alignment: Alignment.center,
            child: Icon(
              icon,
              size: 22,
              color: active ? const Color(0xFFE2E8F0) : const Color(0xFF94A3B8),
            ),
          ),
        ),
      ),
    );
  }
}

class StationHomePage extends StatefulWidget {
  const StationHomePage({
    super.key,
    required this.api,
    required this.receiptSettings,
  });

  final ApiClient api;
  final ReceiptTemplateSettings receiptSettings;

  @override
  State<StationHomePage> createState() => _StationHomePageState();
}

class _StationHomePageState extends State<StationHomePage> {
  bool loading = true;
  int? printingVoucherId;
  String? error;
  List<VoucherItem> active = [];
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
      final approved = await widget.api.stationApprovedVouchers();
      final list = await widget.api.stationVoucherHistory(limit: 10);
      setState(() {
        active = approved;
        items = list;
      });
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
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
        padding: const EdgeInsets.all(16),
        children: [
          const Text(
            'Currently Active Vouchers',
            style: TextStyle(
              color: Color(0xFFE2E8F0),
              fontSize: 20,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 10),
          ...active.map(
            (v) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _voucherCard(v),
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Latest Voucher History',
            style: TextStyle(
              color: Color(0xFFE2E8F0),
              fontSize: 20,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 10),
          ...items.map(
            (v) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _voucherCard(v, showPrint: true),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _printVoucher(VoucherItem voucher) async {
    if (printingVoucherId != null) return;
    setState(() => printingVoucherId = voucher.id);

    try {
      final bytes = await _buildVoucherPdf(voucher);
      await Printing.layoutPdf(
        name: 'Bwiser voucher ${voucher.code}',
        onLayout: (_) async => bytes,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Voucher ${voucher.code} ready to print.')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => printingVoucherId = null);
    }
  }

  Future<pw.Font> _loadReceiptFont(String fontKey) async {
    switch (fontKey) {
      case 'goldman':
        return PdfGoogleFonts.goldmanRegular();
      case 'silkscreen':
        return PdfGoogleFonts.silkscreenRegular();
      case 'days_one':
        return PdfGoogleFonts.daysOneRegular();
      case 'righteous':
      default:
        return PdfGoogleFonts.righteousRegular();
    }
  }

  Future<Uint8List> _buildVoucherPdf(VoucherItem voucher) async {
    final doc = pw.Document();
    final receiptFont = await _loadReceiptFont(widget.receiptSettings.fontKey);
    final bwiserLogo = await _tryLoadAssetImage('assets/images/app_logo.png');
    final qrData = voucher.qrCode.isEmpty ? voucher.code : voucher.qrCode;

    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.roll80,
        margin: const pw.EdgeInsets.fromLTRB(14, 14, 14, 18),
        build: (context) => pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.start,
          children: [
            if (bwiserLogo != null)
              pw.Center(
                child: pw.Image(
                  bwiserLogo,
                  width: 56,
                  height: 56,
                  fit: pw.BoxFit.contain,
                ),
              ),
            if (bwiserLogo != null) pw.SizedBox(height: 6),
            pw.Center(
              child: pw.Text(
                'BWISER POS RECEIPT',
                style: pw.TextStyle(font: receiptFont, fontSize: 15),
              ),
            ),
            pw.SizedBox(height: 10),
            _receiptLine(
              receiptFont,
              'Station',
              voucher.stationName ?? 'Station',
            ),
            _receiptLine(
              receiptFont,
              'Driver',
              voucher.driverName ?? 'Unknown',
            ),
            _receiptLine(receiptFont, 'Voucher Number', '${voucher.id}'),
            _receiptLine(receiptFont, 'Voucher Code', voucher.code),
            _receiptLine(
              receiptFont,
              'Amount',
              'R ${voucher.amount.toStringAsFixed(2)}',
            ),
            _receiptLine(
              receiptFont,
              'Voucher Status',
              voucher.status.toUpperCase(),
            ),
            if (voucher.expiresAt != null)
              _receiptLine(
                receiptFont,
                'Expires',
                voucher.expiresAt!.toIso8601String(),
              ),
            pw.SizedBox(height: 12),
            pw.Center(
              child: pw.BarcodeWidget(
                barcode: pw.Barcode.qrCode(),
                data: qrData,
                width: 130,
                height: 130,
              ),
            ),
            pw.SizedBox(height: 8),
            pw.Center(
              child: pw.Text(
                'Scan to verify voucher',
                style: pw.TextStyle(font: receiptFont, fontSize: 9.5),
              ),
            ),
            pw.SizedBox(height: 4),
            pw.Center(
              child: pw.Text(
                'www.bwiser.co.za',
                style: pw.TextStyle(font: receiptFont, fontSize: 9.5),
              ),
            ),
            pw.SizedBox(height: 6),
          ],
        ),
      ),
    );

    return doc.save();
  }

  Future<pw.MemoryImage?> _tryLoadAssetImage(String assetPath) async {
    try {
      final data = await rootBundle.load(assetPath);
      return pw.MemoryImage(data.buffer.asUint8List());
    } catch (_) {
      return null;
    }
  }

  pw.Widget _receiptLine(pw.Font font, String label, String value) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 4),
      child: pw.Row(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.SizedBox(
            width: 76,
            child: pw.Text(
              '$label:',
              style: pw.TextStyle(font: font, fontSize: 10.2),
            ),
          ),
          pw.Expanded(
            child: pw.Text(
              value,
              style: pw.TextStyle(font: font, fontSize: 10.2),
            ),
          ),
        ],
      ),
    );
  }

  Widget _voucherCard(VoucherItem v, {bool showPrint = false}) {
    final printing = printingVoucherId == v.id;
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF2C2F36), Color(0xFF24262D)],
        ),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Container(
        margin: const EdgeInsets.fromLTRB(10, 10, 10, 10),
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
        decoration: BoxDecoration(
          color: const Color(0x332D5CFF),
          borderRadius: BorderRadius.circular(14),
          border: Border(left: BorderSide(color: AppTheme.softBlue, width: 3)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${v.stationName ?? 'Station'} · ${v.fuelType.toUpperCase()}',
              style: const TextStyle(
                color: Color(0xFF36A4FF),
                fontSize: 16,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Voucher ${v.code} · R ${v.amount.toStringAsFixed(2)} · ${v.status.toUpperCase()}',
              style: const TextStyle(
                color: Color(0xFFCBD5E1),
                fontSize: 14,
                height: 1.2,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              '#${v.id}',
              style: const TextStyle(
                color: Color(0xFFE2E8F0),
                fontWeight: FontWeight.w700,
              ),
            ),
            if (showPrint) ...[
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: printing ? null : () => _printVoucher(v),
                  icon: printing
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.print_rounded, size: 18),
                  label: Text(printing ? 'Printing...' : 'Print Voucher'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class StationReportsPage extends StatefulWidget {
  const StationReportsPage({super.key, required this.api});

  final ApiClient api;

  @override
  State<StationReportsPage> createState() => _StationReportsPageState();
}

class _StationReportsPageState extends State<StationReportsPage> {
  bool loading = true;
  String? error;
  List<VoucherItem> items = [];
  double stationBalance = 0;
  String stationBalanceLabel = 'Wallet';

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
      final results = await Future.wait<dynamic>([
        widget.api.stationVoucherHistory(limit: 60),
        widget.api.profile(),
        widget.api.stations(),
      ]);
      final history = results[0] as List<VoucherItem>;
      final profile = results[1] as Map<String, dynamic>;
      final stations = results[2] as List<Map<String, dynamic>>;

      double resolvedBalance = _toDouble(
        profile['wallet_balance'] ??
            profile['available_balance'] ??
            profile['balance'],
      );
      String resolvedLabel =
          ((profile['station_name'] ?? profile['name']) ?? '').toString();

      if (resolvedBalance <= 0 && stations.isNotEmpty) {
        resolvedBalance = _toDouble(stations.first['wallet_balance']);
        resolvedLabel = (stations.first['name'] ?? resolvedLabel).toString();
      }

      setState(() {
        items = history;
        stationBalance = resolvedBalance;
        stationBalanceLabel = resolvedLabel.trim().isEmpty
            ? 'Wallet'
            : resolvedLabel;
      });
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: AppLoader());
    if (error != null) return Center(child: Text(error!));
    if (items.isEmpty) {
      return RefreshIndicator(
        onRefresh: fetch,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: const [
            SizedBox(height: 80),
            Center(
              child: Text(
                'No voucher data yet.',
                style: TextStyle(color: Color(0xFF94A3B8)),
              ),
            ),
          ],
        ),
      );
    }

    final totalValue = items.fold<double>(
      0,
      (sum, v) => sum + _safeFinite(v.amount),
    );
    final avgTicket = items.isEmpty
        ? 0.0
        : _safeFinite(totalValue / items.length);
    final statusCounts = <String, int>{};
    final fuelValue = <String, double>{};
    for (final item in items) {
      final status = item.status.toLowerCase().trim();
      statusCounts[status] = (statusCounts[status] ?? 0) + 1;
      final fuel = item.fuelType.toUpperCase().trim();
      fuelValue[fuel] = _safeFinite((fuelValue[fuel] ?? 0) + item.amount);
    }
    final statusSorted = statusCounts.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));
    final fuelSorted = fuelValue.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));
    final recent = items.take(7).toList().reversed.toList();
    final maxRecent = recent
        .map((v) => v.amount)
        .fold<double>(0, (a, b) => a > b ? a : b);

    return RefreshIndicator(
      onRefresh: fetch,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text(
            'Station Reports',
            style: TextStyle(
              color: Color(0xFFE2E8F0),
              fontSize: 20,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 10),
          Column(
            children: [
              _metricCard(
                'Station Balance',
                'R ${stationBalance.toStringAsFixed(2)}',
                const Color(0xFF22C55E),
                subLabel: stationBalanceLabel,
              ),
              const SizedBox(height: 10),
              _metricCard('Vouchers', '${items.length}', AppTheme.primaryBlue),
              const SizedBox(height: 10),
              _metricCard(
                'Total Value',
                'R ${totalValue.toStringAsFixed(2)}',
                const Color(0xFF14B8A6),
              ),
              const SizedBox(height: 10),
              _metricCard(
                'Avg Ticket',
                'R ${avgTicket.toStringAsFixed(2)}',
                const Color(0xFFF59E0B),
              ),
            ],
          ),
          const SizedBox(height: 14),
          _chartCard(
            title: 'Voucher Status Mix',
            child: Column(
              children: statusSorted.map((entry) {
                final ratio = items.isEmpty
                    ? 0.0
                    : _safeRatio(
                        entry.value.toDouble(),
                        items.length.toDouble(),
                      );
                return _horizontalBarRow(
                  label: entry.key.toUpperCase(),
                  valueText: '${entry.value}',
                  ratio: ratio,
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 12),
          _chartCard(
            title: 'Fuel Type by Value',
            child: Column(
              children: fuelSorted.take(5).map((entry) {
                final ratio = _safeRatio(entry.value, totalValue);
                return _horizontalBarRow(
                  label: entry.key,
                  valueText: 'R ${_safeFinite(entry.value).toStringAsFixed(0)}',
                  ratio: ratio,
                  barColor: const Color(0xFF14B8A6),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 12),
          _chartCard(
            title: 'Recent Voucher Values',
            child: SizedBox(
              height: 160,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: recent.map((item) {
                  final ratio = _safeRatio(item.amount, maxRecent);
                  return Expanded(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          Text(
                            _safeFinite(item.amount).toStringAsFixed(0),
                            style: const TextStyle(
                              color: Color(0xFF94A3B8),
                              fontSize: 10,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Container(
                            height: 110 * _safeRatio(ratio, 1.0),
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                begin: Alignment.bottomCenter,
                                end: Alignment.topCenter,
                                colors: [Color(0xFF2563EB), Color(0xFF60A5FA)],
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            item.code.length > 4
                                ? item.code.substring(item.code.length - 4)
                                : item.code,
                            style: const TextStyle(
                              color: Color(0xFF64748B),
                              fontSize: 10,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ),
        ],
      ),
    );
  }

  double _toDouble(dynamic value) {
    if (value == null) return 0;
    if (value is num) return _safeFinite(value.toDouble());
    return _safeFinite(double.tryParse(value.toString()) ?? 0);
  }

  double _safeFinite(double value) {
    if (value.isNaN || value.isInfinite) return 0;
    return value;
  }

  double _safeRatio(double numerator, double denominator) {
    final n = _safeFinite(numerator);
    final d = _safeFinite(denominator);
    if (d <= 0) return 0;
    final ratio = n / d;
    if (ratio.isNaN || ratio.isInfinite) return 0;
    return ratio.clamp(0.0, 1.0);
  }

  Widget _metricCard(
    String label,
    String value,
    Color accent, {
    String? subLabel,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Color(0xFF94A3B8))),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              color: accent,
              fontWeight: FontWeight.w800,
              fontSize: 15,
            ),
          ),
          if (subLabel != null && subLabel.trim().isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              subLabel,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 10),
            ),
          ],
        ],
      ),
    );
  }

  Widget _chartCard({required String title, required Widget child}) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: Color(0xFFE2E8F0),
              fontSize: 15,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }

  Widget _horizontalBarRow({
    required String label,
    required String valueText,
    required double ratio,
    Color barColor = AppTheme.primaryBlue,
  }) {
    final bounded = _safeRatio(ratio, 1.0);
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(
                    color: Color(0xFFCBD5E1),
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Text(
                valueText,
                style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
              ),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: bounded,
              minHeight: 10,
              backgroundColor: const Color(0xFF1F2937),
              valueColor: AlwaysStoppedAnimation<Color>(barColor),
            ),
          ),
        ],
      ),
    );
  }
}

class StationReceiptSettingsPage extends StatefulWidget {
  const StationReceiptSettingsPage({
    super.key,
    required this.settings,
    required this.onChanged,
  });

  final ReceiptTemplateSettings settings;
  final Future<void> Function(ReceiptTemplateSettings) onChanged;

  @override
  State<StationReceiptSettingsPage> createState() =>
      _StationReceiptSettingsPageState();
}

class _StationReceiptSettingsPageState
    extends State<StationReceiptSettingsPage> {
  bool saving = false;

  Future<void> _save({
    required String fontKey,
    required String? footerLogoAsset,
  }) async {
    setState(() => saving = true);
    try {
      await widget.onChanged(
        ReceiptTemplateSettings(
          fontKey: fontKey,
          footerLogoAsset: footerLogoAsset,
        ),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Receipt settings saved.')));
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentFont = widget.settings.fontKey;
    final currentLogo = widget.settings.footerLogoAsset;

    return DefaultTabController(
      length: 2,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFF0B1220),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFF334155)),
            ),
            child: const TabBar(
              tabs: [
                Tab(text: 'Font'),
                Tab(text: 'Footer Logo'),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 420,
            child: TabBarView(
              children: [
                ListView(
                  children: _receiptFonts.entries.map((entry) {
                    final selected = entry.key == currentFont;
                    final style = _fontPreviewStyle(entry.key);
                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      decoration: BoxDecoration(
                        color: const Color(0xFF0B1220),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: selected
                              ? AppTheme.primaryBlue
                              : const Color(0xFF334155),
                        ),
                      ),
                      child: ListTile(
                        title: Text(entry.value, style: style),
                        subtitle: Text(
                          'BWISER POS RECEIPT',
                          style: style.copyWith(
                            fontSize: 12,
                            color: const Color(0xFF94A3B8),
                          ),
                        ),
                        trailing: selected
                            ? const Icon(
                                Icons.check_circle_rounded,
                                color: AppTheme.primaryBlue,
                              )
                            : null,
                        onTap: saving
                            ? null
                            : () => _save(
                                fontKey: entry.key,
                                footerLogoAsset: currentLogo,
                              ),
                      ),
                    );
                  }).toList(),
                ),
                ListView(
                  children: [
                    _logoOption(
                      label: 'No footer logo',
                      assetPath: null,
                      selected: currentLogo == null,
                      onTap: saving
                          ? null
                          : () => _save(
                              fontKey: currentFont,
                              footerLogoAsset: null,
                            ),
                    ),
                    ..._receiptBrandLogos.entries.map(
                      (entry) => _logoOption(
                        label: entry.key,
                        assetPath: entry.value,
                        selected: currentLogo == entry.value,
                        onTap: saving
                            ? null
                            : () => _save(
                                fontKey: currentFont,
                                footerLogoAsset: entry.value,
                              ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _receiptTemplatePreview(
            fontKey: currentFont,
            footerLogoAsset: currentLogo,
          ),
        ],
      ),
    );
  }

  TextStyle _fontPreviewStyle(String key) {
    switch (key) {
      case 'goldman':
        return GoogleFonts.goldman(color: const Color(0xFFE2E8F0));
      case 'silkscreen':
        return GoogleFonts.silkscreen(color: const Color(0xFFE2E8F0));
      case 'days_one':
        return GoogleFonts.daysOne(color: const Color(0xFFE2E8F0));
      case 'righteous':
      default:
        return GoogleFonts.righteous(color: const Color(0xFFE2E8F0));
    }
  }

  Widget _logoOption({
    required String label,
    required String? assetPath,
    required bool selected,
    required VoidCallback? onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: selected ? AppTheme.primaryBlue : const Color(0xFF334155),
        ),
      ),
      child: ListTile(
        title: Text(label),
        leading: assetPath == null
            ? const Icon(Icons.block, color: Color(0xFF94A3B8))
            : Container(
                width: 34,
                height: 34,
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Image.asset(assetPath, fit: BoxFit.contain),
              ),
        trailing: selected
            ? const Icon(
                Icons.check_circle_rounded,
                color: AppTheme.primaryBlue,
              )
            : null,
        onTap: onTap,
      ),
    );
  }

  Widget _receiptTemplatePreview({
    required String fontKey,
    required String? footerLogoAsset,
  }) {
    final sampleFont = _fontPreviewStyle(fontKey);
    final previewTitle = sampleFont.copyWith(color: const Color(0xFF0F172A));
    final previewBody = sampleFont.copyWith(
      fontSize: 10.5,
      color: const Color(0xFF1E293B),
    );
    final previewMuted = sampleFont.copyWith(
      fontSize: 10.3,
      color: const Color(0xFF475569),
    );

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Receipt Template Preview',
            style: TextStyle(
              color: Color(0xFFE2E8F0),
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 10),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 54,
                    height: 54,
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Image.asset(
                      'assets/images/app_logo.png',
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Center(
                  child: Text(
                    'BWISER POS RECEIPT',
                    style: previewTitle.copyWith(fontSize: 15),
                  ),
                ),
                const SizedBox(height: 8),
                Text('Station: Sample Station', style: previewBody),
                Text('Driver: Sample Driver', style: previewBody),
                const SizedBox(height: 10),
                Text('Voucher Code: BWV-1234', style: previewBody),
                Text('Amount: R 1200.00', style: previewBody),
                Text('Fuel: R 960.00', style: previewBody),
                Text('Airtime: R 240.00', style: previewBody),
                const SizedBox(height: 8),
                Text('Kiosk Split', style: previewTitle.copyWith(fontSize: 11)),
                Text('Fuel   R 900.00', style: previewMuted),
                Text('Snack  R 60.00', style: previewMuted),
                const SizedBox(height: 8),
                Text('Repayment', style: previewTitle.copyWith(fontSize: 11)),
                Text('Daily: R 42.00 · Due: 2026-03-31', style: previewMuted),
                Text('Remaining: R 840.00', style: previewMuted),
                const SizedBox(height: 12),
                if (footerLogoAsset != null)
                  Center(
                    child: Container(
                      width: 110,
                      height: 40,
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Image.asset(footerLogoAsset, fit: BoxFit.contain),
                    ),
                  ),
                if (footerLogoAsset != null) const SizedBox(height: 8),
                Center(
                  child: Container(
                    width: 96,
                    height: 96,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(
                      Icons.qr_code_2_rounded,
                      size: 72,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class StationRedeemPage extends StatefulWidget {
  const StationRedeemPage({
    super.key,
    required this.api,
    required this.receiptSettings,
  });
  final ApiClient api;
  final ReceiptTemplateSettings receiptSettings;

  @override
  State<StationRedeemPage> createState() => _StationRedeemPageState();
}

class _StationRedeemPageState extends State<StationRedeemPage>
    with SingleTickerProviderStateMixin {
  static const List<int> _bwiserSelectAidApdu = <int>[
    0x00,
    0xA4,
    0x04,
    0x00,
    0x05,
    0xF2,
    0x22,
    0x22,
    0x22,
    0x22,
    0x00,
  ];

  final inputCtrl = TextEditingController();
  final FocusNode scannerFocusNode = FocusNode();
  final FocusNode scannerTextFocusNode = FocusNode();
  Timer? _scanDebounce;
  Timer? _hardwareScanDebounce;
  Timer? _ussdPollTimer;
  bool submitting = false;
  bool nfcListening = false;
  bool ussdPolling = false;
  bool scanMode = false;
  bool ussdListening = false;
  bool stationLoading = true;
  bool approvedVoucherLoading = true;
  bool ussdConfigLoading = true;
  bool ussdLaunching = false;
  List<Map<String, dynamic>> stations = [];
  List<VoucherItem> approvedVouchers = [];
  int? selectedStationId;
  int? selectedUssdVoucherId;
  Printer? selectedPrinter;
  final Set<int> seenUssdEventIds = <int>{};
  late final AnimationController _laserController;
  String _hardwareScanBuffer = '';
  String _lastSubmittedScan = '';
  String? ussdServiceCode;
  DateTime _lastSubmittedAt = DateTime.fromMillisecondsSinceEpoch(0);

  @override
  void initState() {
    super.initState();
    _laserController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat();
    _loadStations();
    _loadApprovedVouchers();
    _loadUssdConfiguration();
    inputCtrl.addListener(_onInputControllerChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        _requestScannerFocus();
      }
    });
  }

  @override
  void dispose() {
    _scanDebounce?.cancel();
    _hardwareScanDebounce?.cancel();
    _ussdPollTimer?.cancel();
    _laserController.dispose();
    scannerFocusNode.dispose();
    scannerTextFocusNode.dispose();
    inputCtrl.dispose();
    super.dispose();
  }

  void _requestScannerFocus() {
    if (!mounted) return;
    scannerTextFocusNode.requestFocus();
  }

  Widget _laserScanIndicator() {
    return AnimatedBuilder(
      animation: _laserController,
      builder: (context, _) {
        final t = _laserController.value;
        final scanY = (t < 0.25 || (t >= 0.5 && t < 0.75)) ? 16.0 : 0.0;
        final clipTop = (t >= 0.25 && t < 0.5) ? 1.0 : 0.0;
        final clipBottom = (t >= 0.5 && t < 0.75) ? 1.0 : 0.0;

        return SizedBox(
          width: 84,
          height: 24,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Positioned.fill(
                child: Center(
                  child: ClipRect(
                    child: Align(
                      alignment: Alignment.center,
                      heightFactor:
                          1.0 - (clipTop + clipBottom).clamp(0.0, 1.0),
                      child: const Text(
                        'Scan',
                        style: TextStyle(
                          color: Color(0xFFF2FFF0),
                          fontSize: 18,
                          fontStyle: FontStyle.italic,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.2,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
              Positioned(
                left: 0,
                right: 0,
                top: scanY,
                child: Container(
                  height: 3,
                  decoration: BoxDecoration(
                    color: const Color(0xFFFF8282),
                    borderRadius: BorderRadius.circular(4),
                    boxShadow: const [
                      BoxShadow(
                        color: Color(0x91FF8282),
                        blurRadius: 10,
                        spreadRadius: 1,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String _normalizeScanInput(String value) {
    return value.replaceAll(RegExp(r'\s+'), '');
  }

  Future<void> _submitScannerInput() async {
    if (submitting) return;
    _scanDebounce?.cancel();
    final payload = _normalizeScanInput(inputCtrl.text);
    if (payload.isEmpty) return;
    final now = DateTime.now();
    if (payload == _lastSubmittedScan &&
        now.difference(_lastSubmittedAt) < const Duration(milliseconds: 1200)) {
      return;
    }
    _lastSubmittedScan = payload;
    _lastSubmittedAt = now;
    await redeem(payload);
    if (mounted) {
      _requestScannerFocus();
    }
  }

  void _queueScannerSubmit() {
    _scanDebounce?.cancel();
    _scanDebounce = Timer(const Duration(milliseconds: 220), () {
      if (!mounted) return;
      _submitScannerInput();
    });
  }

  void _onInputControllerChanged() {
    if (inputCtrl.text.isEmpty) return;
    _queueScannerSubmit();
  }

  KeyEventResult _handleScannerKeyEvent(FocusNode node, KeyEvent event) {
    if (event is! KeyDownEvent || submitting) {
      return KeyEventResult.ignored;
    }

    final key = event.logicalKey;
    if (key == LogicalKeyboardKey.enter ||
        key == LogicalKeyboardKey.numpadEnter ||
        key == LogicalKeyboardKey.tab) {
      if (_hardwareScanBuffer.isNotEmpty) {
        _submitHardwareScanBuffer();
        return KeyEventResult.handled;
      }
      _submitScannerInput();
      return KeyEventResult.handled;
    }

    final keyLabel = key.keyLabel;
    final character =
        event.character ?? (keyLabel.length == 1 ? keyLabel : null);
    if (character == null ||
        character.isEmpty ||
        character.length != 1 ||
        character.codeUnitAt(0) < 32) {
      return KeyEventResult.ignored;
    }

    _hardwareScanBuffer += character;
    _hardwareScanDebounce?.cancel();
    _hardwareScanDebounce = Timer(const Duration(milliseconds: 260), () {
      if (!mounted || _hardwareScanBuffer.isEmpty) return;
      _submitHardwareScanBuffer();
    });
    return KeyEventResult.handled;
  }

  void _submitHardwareScanBuffer() {
    final payload = _normalizeScanInput(_hardwareScanBuffer);
    _hardwareScanBuffer = '';
    _hardwareScanDebounce?.cancel();
    if (payload.isEmpty) return;
    inputCtrl.text = payload;
    inputCtrl.selection = TextSelection.collapsed(
      offset: inputCtrl.text.length,
    );
    unawaited(_submitScannerInput());
  }

  Future<void> _loadStations() async {
    setState(() => stationLoading = true);
    try {
      final list = await widget.api.stations();
      if (!mounted) return;
      setState(() {
        stations = list;
        if (selectedStationId == null && stations.isNotEmpty) {
          selectedStationId = int.tryParse('${stations.first['id']}');
        }
        _syncSelectedUssdVoucher();
      });
    } catch (_) {
      // Keep UI usable without hard failing.
    } finally {
      if (mounted) setState(() => stationLoading = false);
    }
  }

  Future<void> _loadApprovedVouchers() async {
    setState(() => approvedVoucherLoading = true);
    try {
      final list = await widget.api.stationApprovedVouchers();
      if (!mounted) return;
      setState(() {
        approvedVouchers = list
            .where((voucher) => voucher.status == 'approved')
            .toList();
        _syncSelectedUssdVoucher();
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        approvedVouchers = const [];
        selectedUssdVoucherId = null;
      });
    } finally {
      if (mounted) setState(() => approvedVoucherLoading = false);
    }
  }

  Future<void> _loadUssdConfiguration() async {
    setState(() => ussdConfigLoading = true);
    try {
      final settings = await widget.api.stationSettings();
      if (!mounted) return;
      setState(() {
        ussdServiceCode = (settings['ussd_service_code'] ?? '')
            .toString()
            .trim();
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => ussdServiceCode = '');
    } finally {
      if (mounted) setState(() => ussdConfigLoading = false);
    }
  }

  List<VoucherItem> get _filteredApprovedVouchers {
    if (selectedStationId == null) {
      return approvedVouchers;
    }

    final stationSpecific = approvedVouchers
        .where((voucher) => voucher.stationId == selectedStationId)
        .toList();
    if (stationSpecific.isNotEmpty) {
      return stationSpecific;
    }

    return approvedVouchers
        .where((voucher) => voucher.stationId == null)
        .toList();
  }

  VoucherItem? get _selectedUssdVoucher {
    if (selectedUssdVoucherId == null) return null;

    for (final voucher in _filteredApprovedVouchers) {
      if (voucher.id == selectedUssdVoucherId) {
        return voucher;
      }
    }

    return null;
  }

  void _syncSelectedUssdVoucher() {
    final options = _filteredApprovedVouchers;
    if (options.isEmpty) {
      selectedUssdVoucherId = null;
      return;
    }

    final stillAvailable = options.any(
      (voucher) => voucher.id == selectedUssdVoucherId,
    );
    if (!stillAvailable) {
      selectedUssdVoucherId = options.first.id;
    }
  }

  Future<void> _toggleUssdListener() async {
    if (selectedStationId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Select a station before listening for USSD.'),
        ),
      );
      return;
    }
    final nextListening = !ussdListening;
    setState(() => ussdListening = nextListening);
    if (nextListening) {
      await _primeUssdListener();
      _startUssdPolling();
    } else {
      _stopUssdPolling();
    }
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          ussdListening ? 'USSD listener enabled.' : 'USSD listener is off.',
        ),
      ),
    );
  }

  Future<void> _ensureUssdListening() async {
    if (ussdListening) return;
    setState(() => ussdListening = true);
    await _primeUssdListener();
    _startUssdPolling();
  }

  void _startUssdPolling() {
    _ussdPollTimer?.cancel();
    _ussdPollTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      _pollUssdEvents();
    });
    _pollUssdEvents();
  }

  void _stopUssdPolling() {
    _ussdPollTimer?.cancel();
    _ussdPollTimer = null;
  }

  Future<void> _primeUssdListener() async {
    try {
      final events = await widget.api.stationUssdEvents(perPage: 50);
      for (final event in events) {
        final id = int.tryParse('${event['id'] ?? ''}');
        if (id != null && id > 0) {
          seenUssdEventIds.add(id);
        }
      }
    } catch (_) {
      // Keep listener on even if initial sync fails.
    }
  }

  Future<void> _pollUssdEvents() async {
    if (!ussdListening || ussdPolling || selectedStationId == null) {
      return;
    }

    ussdPolling = true;
    try {
      final events = await widget.api.stationUssdEvents(perPage: 25);
      if (!mounted || !ussdListening) return;

      for (final event in events.reversed) {
        final id = int.tryParse('${event['id'] ?? ''}');
        if (id == null || id <= 0 || seenUssdEventIds.contains(id)) {
          continue;
        }
        seenUssdEventIds.add(id);

        final stationId = int.tryParse('${event['fuel_station_id'] ?? ''}');
        if (stationId == null || stationId != selectedStationId) {
          continue;
        }

        final status = (event['status'] ?? '').toString().toLowerCase();
        if (status != 'success') {
          continue;
        }

        final receipt = Map<String, dynamic>.from(
          (event['receipt_payload'] as Map?)?.cast<String, dynamic>() ??
              <String, dynamic>{},
        );
        if (receipt.isEmpty) {
          continue;
        }

        receipt['transaction_status'] =
            (receipt['transaction_status'] ?? 'successful').toString();
        final printable = await _prepareReceiptForPrint(receipt);

        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'USSD redemption received: ${(receipt['voucher_code'] ?? receipt['code'] ?? 'voucher').toString()}',
            ),
          ),
        );
        await _showPrintReceiptDialog(printable);
      }
    } catch (_) {
      // Keep listener running despite transient API failures.
    } finally {
      ussdPolling = false;
    }
  }

  String _selectedStationLabel() {
    final station = stations.firstWhere(
      (s) => '${s['id']}' == '$selectedStationId',
      orElse: () => <String, dynamic>{},
    );
    final name = (station['name'] ?? 'Station').toString();
    return '$name (Wallet)';
  }

  String _buildUssdDialCode(String serviceCode, int voucherId) {
    final compact = serviceCode.replaceAll(RegExp(r'\s+'), '');
    if (compact.isEmpty || !RegExp(r'^[0-9*#]+$').hasMatch(compact)) {
      throw Exception('Admin USSD service code is invalid.');
    }

    final base = compact.endsWith('#')
        ? compact.substring(0, compact.length - 1)
        : compact;
    if (base.isEmpty) {
      throw Exception('Admin USSD service code is invalid.');
    }

    return '$base*1*$voucherId*1#';
  }

  Future<bool> _confirmUssdLaunch(VoucherItem voucher, String dialCode) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Start USSD Payment'),
          content: Text(
            'Launch the secure USSD payment for voucher ${voucher.code} '
            '(voucher number ${voucher.id})?\n\n'
            'Dial string: $dialCode',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Start'),
            ),
          ],
        );
      },
    );

    return confirmed ?? false;
  }

  Future<void> _launchUssdPayment() async {
    final voucher = _selectedUssdVoucher;
    if (voucher == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Choose an approved voucher before starting USSD.'),
        ),
      );
      return;
    }

    final serviceCode = (ussdServiceCode ?? '').trim();
    if (serviceCode.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No admin USSD service code has been configured yet.'),
        ),
      );
      return;
    }

    try {
      final supported = await UssdCallBridge.isSupported();
      if (!supported) {
        throw Exception('This device cannot place USSD calls.');
      }

      final dialCode = _buildUssdDialCode(serviceCode, voucher.id);
      final confirmed = await _confirmUssdLaunch(voucher, dialCode);
      if (!confirmed || !mounted) return;

      setState(() => ussdLaunching = true);
      await _ensureUssdListening();

      var hasPermission = await UssdCallBridge.hasCallPermission();
      if (!hasPermission) {
        hasPermission = await UssdCallBridge.requestCallPermission();
      }
      if (!hasPermission) {
        throw Exception(
          'Phone call permission is required to start USSD payments.',
        );
      }

      await UssdCallBridge.launchUssdCall(dialCode);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'USSD started for ${voucher.code}. Return here for the receipt once the network completes the flow.',
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() => ussdLaunching = false);
        _requestScannerFocus();
      }
    }
  }

  Future<void> redeem(String value) async {
    if (submitting) return;
    final normalized = _normalizeScanInput(value);
    if (normalized.isEmpty) return;

    setState(() => submitting = true);
    try {
      final receiptData = await widget.api.stationRedeem(scanInput: normalized);
      unawaited(AppSfx.playWiserTone());
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Voucher redeemed successfully.')),
      );
      inputCtrl.clear();
      final printable = await _prepareReceiptForPrint({
        ...receiptData,
        'transaction_status': 'successful',
      });
      try {
        await _printReceipt(printable);
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Receipt sent to printer.')),
        );
      } catch (printError) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Redeemed, but print failed: ${printError.toString().replaceFirst('Exception: ', '')}',
            ),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
      inputCtrl.clear();
    } finally {
      if (mounted) {
        setState(() => submitting = false);
        _requestScannerFocus();
      }
    }
  }

  Future<pw.Font> _loadReceiptFont(String fontKey) async {
    switch (fontKey) {
      case 'goldman':
        return PdfGoogleFonts.goldmanRegular();
      case 'silkscreen':
        return PdfGoogleFonts.silkscreenRegular();
      case 'days_one':
        return PdfGoogleFonts.daysOneRegular();
      case 'righteous':
      default:
        return PdfGoogleFonts.righteousRegular();
    }
  }

  Future<pw.MemoryImage?> _tryLoadAssetImage(String assetPath) async {
    try {
      final data = await rootBundle.load(assetPath);
      return pw.MemoryImage(data.buffer.asUint8List());
    } catch (_) {
      return null;
    }
  }

  Future<Uint8List> _buildReceiptPdf(Map<String, dynamic> receipt) async {
    final doc = pw.Document();
    final stationAddress =
        (receipt['station'] is Map
                ? receipt['station']['address']
                : receipt['station_address'])
            ?.toString() ??
        '';
    final stationName =
        (receipt['station'] is Map
                ? (receipt['station']['name'] ?? receipt['station']['city'])
                : receipt['station_name'])
            ?.toString() ??
        'Station';
    final driverName =
        (receipt['driver'] is Map ? receipt['driver']['name'] : null)
            ?.toString() ??
        'Unknown';
    final voucherId = (receipt['voucher_id'] ?? '-').toString();
    final voucherCode = (receipt['voucher_code'] ?? receipt['code'] ?? '-')
        .toString();
    final voucherQrCode = (receipt['qr_code'] ?? voucherCode).toString();
    final amount = double.tryParse('${receipt['amount'] ?? 0}') ?? 0;
    final fuelAmount =
        double.tryParse('${receipt['redeemed_fuel_amount'] ?? ''}') ?? amount;
    final airtimeAmount =
        double.tryParse('${receipt['redeemed_airtime_amount'] ?? ''}') ?? 0;
    final airtimeStatus = (receipt['airtime_status'] ?? '').toString();
    final kioskItems = _parseKioskItems(receipt['kiosk_items']);
    final lease = Map<String, dynamic>.from(
      (receipt['lease'] as Map?)?.cast<String, dynamic>() ??
          <String, dynamic>{},
    );
    final voucherStatus = (receipt['status'] ?? 'unknown').toString();
    final txStatus = (receipt['transaction_status'] ?? 'unknown')
        .toString()
        .toUpperCase();
    final when =
        (receipt['redeemed_at'] ??
                receipt['issued_at'] ??
                DateTime.now().toIso8601String())
            .toString();
    final qrData = voucherQrCode;
    final upcomingRepayments = _parseRepayments(receipt['upcoming_repayments']);
    final receiptFont = await _loadReceiptFont(widget.receiptSettings.fontKey);
    final bwiserLogo = await _tryLoadAssetImage('assets/images/app_logo.png');
    final footerLogo = widget.receiptSettings.footerLogoAsset == null
        ? null
        : await _tryLoadAssetImage(widget.receiptSettings.footerLogoAsset!);

    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.roll80,
        margin: const pw.EdgeInsets.fromLTRB(14, 14, 14, 18),
        build: (context) => pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.start,
          children: [
            if (bwiserLogo != null)
              pw.Center(
                child: pw.Image(
                  bwiserLogo,
                  width: 56,
                  height: 56,
                  fit: pw.BoxFit.contain,
                ),
              ),
            pw.SizedBox(height: 6),
            pw.Center(
              child: pw.Text(
                'BWISER POS RECEIPT',
                style: pw.TextStyle(font: receiptFont, fontSize: 15),
              ),
            ),
            pw.SizedBox(height: 10),
            pw.Container(
              width: double.infinity,
              padding: const pw.EdgeInsets.all(8),
              decoration: pw.BoxDecoration(
                border: pw.Border.all(color: PdfColors.grey400, width: 0.6),
                borderRadius: pw.BorderRadius.circular(4),
              ),
              child: pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Text(
                    'Station: $stationName',
                    style: pw.TextStyle(font: receiptFont, fontSize: 10.5),
                  ),
                  if (stationAddress.isNotEmpty) ...[
                    pw.SizedBox(height: 2),
                    pw.Text(
                      'Address: $stationAddress',
                      style: pw.TextStyle(font: receiptFont, fontSize: 9.7),
                    ),
                  ],
                  pw.SizedBox(height: 4),
                  pw.Text(
                    'Driver: $driverName',
                    style: pw.TextStyle(font: receiptFont, fontSize: 10.5),
                  ),
                ],
              ),
            ),
            pw.SizedBox(height: 10),
            pw.Text(
              'Voucher Details',
              style: pw.TextStyle(font: receiptFont, fontSize: 11.5),
            ),
            pw.SizedBox(height: 6),
            _receiptLine(receiptFont, 'Voucher Number', voucherId),
            _receiptLine(receiptFont, 'Voucher Code', voucherCode),
            _receiptLine(receiptFont, 'Voucher QR Code', voucherQrCode),
            pw.Text(
              'Amount: R ${amount.toStringAsFixed(2)}',
              style: pw.TextStyle(font: receiptFont, fontSize: 11),
            ),
            pw.SizedBox(height: 4),
            if (fuelAmount > 0) ...[
              pw.SizedBox(height: 2),
              pw.Text(
                'Fuel: R ${fuelAmount.toStringAsFixed(2)}',
                style: pw.TextStyle(font: receiptFont, fontSize: 10.3),
              ),
            ],
            if (airtimeAmount > 0 || airtimeStatus == 'sent') ...[
              pw.SizedBox(height: 2),
              pw.Text(
                'Airtime: R ${airtimeAmount.toStringAsFixed(2)}',
                style: pw.TextStyle(font: receiptFont, fontSize: 10.3),
              ),
            ],
            if (kioskItems.isNotEmpty) ...[
              pw.SizedBox(height: 8),
              pw.Text(
                'Kiosk Split',
                style: pw.TextStyle(font: receiptFont, fontSize: 11),
              ),
              pw.SizedBox(height: 4),
              ...kioskItems.map((item) {
                final itemName = (item['name'] ?? 'Item').toString();
                final itemAmount =
                    double.tryParse('${item['amount'] ?? 0}') ?? 0;
                return _receiptLine(
                  receiptFont,
                  itemName,
                  'R ${itemAmount.toStringAsFixed(2)}',
                );
              }),
            ],
            pw.SizedBox(height: 4),
            _receiptLine(
              receiptFont,
              'Voucher Status',
              voucherStatus.toUpperCase(),
            ),
            _receiptLine(receiptFont, 'Transaction', txStatus),
            if (lease.isNotEmpty) ...[
              pw.SizedBox(height: 6),
              pw.Text(
                'Repayment',
                style: pw.TextStyle(font: receiptFont, fontSize: 11),
              ),
              pw.SizedBox(height: 4),
              _receiptLine(
                receiptFont,
                'Lease',
                (lease['id'] ?? '-').toString(),
              ),
              _receiptLine(
                receiptFont,
                'Frequency',
                (lease['repayment_frequency'] ?? 'daily').toString(),
              ),
              _receiptLine(
                receiptFont,
                'Daily',
                'R ${(double.tryParse('${lease['daily_repayment'] ?? 0}') ?? 0).toStringAsFixed(2)}',
              ),
              _receiptLine(
                receiptFont,
                'Remaining',
                'R ${(double.tryParse('${lease['remaining_balance'] ?? 0}') ?? 0).toStringAsFixed(2)}',
              ),
              _receiptLine(
                receiptFont,
                'Due Date',
                (lease['due_date'] ?? '-').toString(),
              ),
              if (upcomingRepayments.isNotEmpty) ...[
                pw.SizedBox(height: 8),
                pw.Text(
                  'Upcoming Repayments',
                  style: pw.TextStyle(font: receiptFont, fontSize: 10.8),
                ),
                pw.SizedBox(height: 4),
                ...upcomingRepayments.map((repayment) {
                  final repaymentId = (repayment['id'] ?? '-').toString();
                  final dueDate = (repayment['due_date'] ?? '-').toString();
                  final amountDue =
                      double.tryParse('${repayment['amount'] ?? 0}') ?? 0;
                  final payUrl = (repayment['pay_url'] ?? '').toString();

                  return pw.Padding(
                    padding: const pw.EdgeInsets.only(bottom: 8),
                    child: pw.Container(
                      width: double.infinity,
                      padding: const pw.EdgeInsets.all(6),
                      decoration: pw.BoxDecoration(
                        border: pw.Border.all(
                          color: PdfColors.grey400,
                          width: 0.5,
                        ),
                        borderRadius: pw.BorderRadius.circular(4),
                      ),
                      child: pw.Row(
                        crossAxisAlignment: pw.CrossAxisAlignment.start,
                        children: [
                          pw.Expanded(
                            child: pw.Column(
                              crossAxisAlignment: pw.CrossAxisAlignment.start,
                              children: [
                                pw.Text(
                                  "Repayment #$repaymentId",
                                  style: pw.TextStyle(
                                    font: receiptFont,
                                    fontSize: 9.8,
                                  ),
                                ),
                                pw.Text(
                                  "Due: $dueDate",
                                  style: pw.TextStyle(
                                    font: receiptFont,
                                    fontSize: 9.2,
                                  ),
                                ),
                                pw.Text(
                                  "Amount: R ${amountDue.toStringAsFixed(2)}",
                                  style: pw.TextStyle(
                                    font: receiptFont,
                                    fontSize: 9.2,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          if (payUrl.isNotEmpty)
                            pw.Column(
                              children: [
                                pw.BarcodeWidget(
                                  barcode: pw.Barcode.qrCode(),
                                  data: payUrl,
                                  width: 52,
                                  height: 52,
                                ),
                                pw.SizedBox(height: 2),
                                pw.Text(
                                  'Pay URL',
                                  style: pw.TextStyle(
                                    font: receiptFont,
                                    fontSize: 7.5,
                                  ),
                                ),
                              ],
                            ),
                        ],
                      ),
                    ),
                  );
                }),
              ],
            ],
            pw.Text(
              'When: $when',
              style: pw.TextStyle(font: receiptFont, fontSize: 10.3),
            ),
            pw.SizedBox(height: 12),
            if (footerLogo != null) ...[
              pw.Center(
                child: pw.Text(
                  'Franchise',
                  style: pw.TextStyle(font: receiptFont, fontSize: 9.5),
                ),
              ),
              pw.SizedBox(height: 4),
              pw.Center(
                child: pw.Image(
                  footerLogo,
                  width: 110,
                  height: 40,
                  fit: pw.BoxFit.contain,
                ),
              ),
              pw.SizedBox(height: 12),
            ],
            pw.Center(
              child: pw.BarcodeWidget(
                barcode: pw.Barcode.qrCode(),
                data: qrData,
                width: 130,
                height: 130,
              ),
            ),
            pw.SizedBox(height: 8),
            pw.Center(
              child: pw.Text(
                'Scan to verify voucher',
                style: pw.TextStyle(font: receiptFont, fontSize: 9.5),
              ),
            ),
            pw.SizedBox(height: 4),
            pw.Center(
              child: pw.Text(
                'www.bwiser.co.za',
                style: pw.TextStyle(font: receiptFont, fontSize: 9.5),
              ),
            ),
            pw.SizedBox(height: 6),
          ],
        ),
      ),
    );

    return doc.save();
  }

  List<Map<String, dynamic>> _parseRepayments(dynamic raw) {
    if (raw is! List) {
      return const [];
    }

    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e.cast<String, dynamic>()))
        .toList();
  }

  List<Map<String, dynamic>> _parseKioskItems(dynamic raw) {
    if (raw is! List) {
      return const [];
    }

    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e.cast<String, dynamic>()))
        .where((item) {
          final amount = double.tryParse('${item['amount'] ?? 0}') ?? 0;
          return amount > 0;
        })
        .toList();
  }

  Future<Map<String, dynamic>> _prepareReceiptForPrint(
    Map<String, dynamic> receipt,
  ) async {
    final prepared = Map<String, dynamic>.from(receipt);
    final existingKiosk = _parseKioskItems(prepared['kiosk_items']);
    if (existingKiosk.isNotEmpty) {
      return prepared;
    }

    final fuelAmount =
        double.tryParse(
          '${prepared['redeemed_fuel_amount'] ?? prepared['amount'] ?? 0}',
        ) ??
        0;
    if (fuelAmount <= 0 || !mounted) {
      return prepared;
    }

    final kioskItems = await _captureKioskSplitItems(fuelAmount);
    if (kioskItems == null || kioskItems.isEmpty) {
      return prepared;
    }

    prepared['kiosk_items'] = kioskItems;
    return prepared;
  }

  Future<List<Map<String, dynamic>>?> _captureKioskSplitItems(
    double fuelAmount,
  ) async {
    final rows = <Map<String, TextEditingController>>[
      {
        'name': TextEditingController(text: 'Fuel'),
        'amount': TextEditingController(text: fuelAmount.toStringAsFixed(2)),
      },
    ];

    final result = await showDialog<List<Map<String, dynamic>>>(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            double sum = 0;
            for (final row in rows) {
              sum += double.tryParse(row['amount']!.text.trim()) ?? 0;
            }
            final remaining = fuelAmount - sum;

            return AlertDialog(
              backgroundColor: const Color(0xFF0B1220),
              title: const Text(
                'Split Kiosk Items',
                style: TextStyle(color: Color(0xFFE2E8F0)),
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'Fuel allocation: R ${fuelAmount.toStringAsFixed(2)}',
                      style: const TextStyle(color: Color(0xFF94A3B8)),
                    ),
                    const SizedBox(height: 10),
                    ...rows.asMap().entries.map((entry) {
                      final index = entry.key;
                      final row = entry.value;
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Row(
                          children: [
                            Expanded(
                              child: TextField(
                                controller: row['name'],
                                decoration: const InputDecoration(
                                  labelText: 'Item',
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            SizedBox(
                              width: 110,
                              child: TextField(
                                controller: row['amount'],
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                      decimal: true,
                                    ),
                                decoration: const InputDecoration(
                                  labelText: 'Amount',
                                ),
                                onChanged: (_) => setDialogState(() {}),
                              ),
                            ),
                            if (rows.length > 1)
                              IconButton(
                                icon: const Icon(Icons.close_rounded),
                                onPressed: () {
                                  setDialogState(() {
                                    rows.removeAt(index);
                                  });
                                },
                              ),
                          ],
                        ),
                      );
                    }),
                    const SizedBox(height: 8),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: TextButton.icon(
                        onPressed: () {
                          setDialogState(() {
                            rows.add({
                              'name': TextEditingController(),
                              'amount': TextEditingController(),
                            });
                          });
                        },
                        icon: const Icon(Icons.add),
                        label: const Text('Add Item'),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Remaining: R ${remaining.toStringAsFixed(2)}',
                      style: TextStyle(
                        color: remaining.abs() < 0.01
                            ? const Color(0xFF22C55E)
                            : const Color(0xFFF97316),
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(dialogContext).pop(null),
                  child: const Text('Skip'),
                ),
                ElevatedButton(
                  onPressed: () {
                    final items = <Map<String, dynamic>>[];
                    double total = 0;
                    for (final row in rows) {
                      final name = row['name']!.text.trim();
                      final amount =
                          double.tryParse(row['amount']!.text.trim()) ?? 0;
                      if (name.isEmpty || amount <= 0) {
                        continue;
                      }
                      total += amount;
                      items.add({
                        'name': name,
                        'amount': double.parse(amount.toStringAsFixed(2)),
                      });
                    }

                    if (items.isEmpty || (total - fuelAmount).abs() > 0.05) {
                      ScaffoldMessenger.of(dialogContext).showSnackBar(
                        const SnackBar(
                          content: Text(
                            'Split must match fuel amount before saving.',
                          ),
                        ),
                      );
                      return;
                    }

                    Navigator.of(dialogContext).pop(items);
                  },
                  child: const Text('Save Split'),
                ),
              ],
            );
          },
        );
      },
    );

    for (final row in rows) {
      row['name']?.dispose();
      row['amount']?.dispose();
    }

    return result;
  }

  pw.Widget _receiptLine(pw.Font font, String label, String value) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 4),
      child: pw.Row(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.SizedBox(
            width: 76,
            child: pw.Text(
              '$label:',
              style: pw.TextStyle(font: font, fontSize: 10.2),
            ),
          ),
          pw.Expanded(
            child: pw.Text(
              value,
              style: pw.TextStyle(font: font, fontSize: 10.2),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _printReceipt(Map<String, dynamic> receipt) async {
    final bytes = await _buildReceiptPdf(receipt);
    if (!mounted) return;
    try {
      if (selectedPrinter != null) {
        await Printing.directPrintPdf(
          printer: selectedPrinter!,
          onLayout: (_) async => bytes,
        );
        return;
      }

      await Printing.layoutPdf(
        name:
            'Bwiser receipt ${(receipt['voucher_code'] ?? receipt['code'] ?? '').toString()}',
        onLayout: (_) async => bytes,
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => selectedPrinter = null);
      throw Exception(e.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> _showPrintReceiptDialog(Map<String, dynamic> receipt) async {
    if (!mounted) return;
    final txStatus = (receipt['transaction_status'] ?? 'unknown')
        .toString()
        .toUpperCase();
    final voucherCode = (receipt['voucher_code'] ?? receipt['code'] ?? '-')
        .toString();

    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFF0B1220),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Print POS Receipt',
                style: TextStyle(
                  color: Color(0xFFE2E8F0),
                  fontWeight: FontWeight.w700,
                  fontSize: 18,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Voucher: $voucherCode',
                style: const TextStyle(color: Color(0xFFCBD5E1)),
              ),
              Text(
                'Transaction: $txStatus',
                style: TextStyle(
                  color: txStatus == 'SUCCESSFUL'
                      ? const Color(0xFF22C55E)
                      : const Color(0xFFF97316),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'Template: ${_receiptFonts[widget.receiptSettings.fontKey] ?? 'Righteous'}',
                style: const TextStyle(color: Color(0xFF94A3B8)),
              ),
              const SizedBox(height: 10),
              FxButton(
                label: selectedPrinter == null
                    ? 'Select Android Printer'
                    : 'Printer: ${selectedPrinter!.name}',
                icon: Icons.print_rounded,
                fullWidth: true,
                onPressed: () async {
                  final navigator = Navigator.of(context);
                  final printer = await Printing.pickPrinter(context: context);
                  if (printer == null || !mounted) return;
                  setState(() => selectedPrinter = printer);
                  navigator.pop();
                  await _showPrintReceiptDialog(receipt);
                },
              ),
              const SizedBox(height: 8),
              FxButton(
                label: 'Print Receipt',
                icon: Icons.receipt_long_rounded,
                fullWidth: true,
                onPressed: () async {
                  final messenger = ScaffoldMessenger.of(context);
                  Navigator.of(context).pop();
                  try {
                    await _printReceipt(receipt);
                    if (!mounted) return;
                    messenger.showSnackBar(
                      const SnackBar(content: Text('Receipt sent to printer.')),
                    );
                  } catch (e) {
                    if (!mounted) return;
                    messenger.showSnackBar(
                      SnackBar(
                        content: Text(
                          'Print failed: ${e.toString().replaceFirst('Exception: ', '')}',
                        ),
                      ),
                    );
                  }
                },
              ),
            ],
          ),
        );
      },
    );
  }

  String _decodeNdefPayload(List<int> payload) {
    if (payload.isEmpty) return '';
    final status = payload.first;
    final languageCodeLength = status & 0x3F;
    final isUtf16 = (status & 0x80) != 0;
    final textBytes = payload.skip(1 + languageCodeLength).toList();
    if (textBytes.isEmpty) return '';
    if (isUtf16) {
      return const Utf8Decoder(allowMalformed: true).convert(textBytes);
    }
    return utf8.decode(textBytes, allowMalformed: true);
  }

  String _cleanNfcToken(String raw) {
    var token = raw.trim();
    if (token.endsWith('\u{9000}')) {
      token = token.substring(0, token.length - 1).trim();
    }
    return token.replaceAll(RegExp(r'[\u0000-\u001F\u007F]'), '').trim();
  }

  String _decodeNdefRecord(NdefRecord record) {
    if (record.payload.isEmpty) return '';
    final type = utf8.decode(record.type, allowMalformed: true);
    if (type == 'T') {
      return _decodeNdefPayload(record.payload);
    }
    return utf8.decode(record.payload, allowMalformed: true);
  }

  Future<String> _readNdefToken(NfcTag tag) async {
    final ndef = Ndef.from(tag);
    if (ndef == null) return '';

    final message = ndef.cachedMessage ?? await ndef.read();
    for (final record in message.records) {
      final raw = _cleanNfcToken(_decodeNdefRecord(record));
      if (raw.isNotEmpty) return raw;
    }

    return '';
  }

  Future<String> _readBwiserHceToken(NfcTag tag) async {
    final isoDep = IsoDep.from(tag);
    if (isoDep == null) return '';

    final response = await isoDep.transceive(
      data: Uint8List.fromList(_bwiserSelectAidApdu),
    );
    if (response.length < 2) return '';

    final sw1 = response[response.length - 2];
    final sw2 = response[response.length - 1];
    if (sw1 != 0x90 || sw2 != 0x00) return '';

    final payload = response.sublist(0, response.length - 2);
    return _cleanNfcToken(utf8.decode(payload, allowMalformed: true));
  }

  Future<void> scanNfcAndRedeem() async {
    if (submitting || nfcListening) return;
    setState(() => nfcListening = true);

    try {
      final available = await NfcManager.instance.isAvailable();
      if (!available) {
        throw Exception('NFC is not available on this device.');
      }

      await NfcManager.instance.startSession(
        onDiscovered: (tag) async {
          try {
            String raw = await _readNdefToken(tag);
            raw = raw.isNotEmpty ? raw : await _readBwiserHceToken(tag);
            if (raw.isEmpty) {
              throw Exception('No supported Bwiser NFC token was found.');
            }

            await NfcManager.instance.stopSession();
            await redeem(raw);
          } catch (e) {
            await NfcManager.instance.stopSession(errorMessage: e.toString());
            if (!mounted) return;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(e.toString().replaceFirst('Exception: ', '')),
              ),
            );
          } finally {
            if (mounted) {
              setState(() => nfcListening = false);
              _requestScannerFocus();
            }
          }
        },
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Hold POS near NFC token to redeem.')),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => nfcListening = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Focus(
      focusNode: scannerFocusNode,
      autofocus: true,
      onKeyEvent: _handleScannerKeyEvent,
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: _requestScannerFocus,
        child: Stack(
          children: [
            Positioned(
              left: 0,
              top: 0,
              width: 1,
              height: 1,
              child: Opacity(
                opacity: 0.01,
                child: TextField(
                  controller: inputCtrl,
                  focusNode: scannerTextFocusNode,
                  autofocus: true,
                  keyboardType: TextInputType.visiblePassword,
                  textInputAction: TextInputAction.done,
                  autocorrect: false,
                  enableSuggestions: false,
                  showCursor: false,
                  decoration: const InputDecoration(
                    border: InputBorder.none,
                    isCollapsed: true,
                    contentPadding: EdgeInsets.zero,
                  ),
                  onSubmitted: (_) => unawaited(_submitScannerInput()),
                ),
              ),
            ),
            ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<int>(
                                isExpanded: true,
                                initialValue:
                                    stations.any(
                                      (s) =>
                                          '${s['id']}' == '$selectedStationId',
                                    )
                                    ? selectedStationId
                                    : null,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                ),
                                iconEnabledColor: Colors.white,
                                decoration: const InputDecoration(
                                  labelText: 'Station (Wallet)',
                                  labelStyle: TextStyle(
                                    color: Color(0xFFE2E8F0),
                                    fontSize: 12,
                                  ),
                                  contentPadding: EdgeInsets.symmetric(
                                    horizontal: 16,
                                    vertical: 10,
                                  ),
                                ),
                                items: stations
                                    .map(
                                      (s) => DropdownMenuItem<int>(
                                        value: int.tryParse('${s['id']}'),
                                        child: Text(
                                          (s['name'] ?? 'Station').toString(),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontSize: 12,
                                          ),
                                        ),
                                      ),
                                    )
                                    .toList(),
                                onChanged: stationLoading
                                    ? null
                                    : (value) => setState(() {
                                        selectedStationId = value;
                                        _syncSelectedUssdVoucher();
                                        _requestScannerFocus();
                                      }),
                              ),
                            ),
                            const SizedBox(width: 10),
                            IconButton(
                              icon: const Icon(Icons.refresh_rounded),
                              onPressed: stationLoading ? null : _loadStations,
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        FxButton(
                          label: scanMode ? 'Close Scanner' : 'Open Scanner',
                          icon: Icons.qr_code_scanner,
                          fullWidth: true,
                          onPressed: () {
                            setState(() => scanMode = !scanMode);
                            _requestScannerFocus();
                          },
                        ),
                        const SizedBox(height: 8),
                        if (scanMode)
                          Align(
                            alignment: Alignment.centerRight,
                            child: _laserScanIndicator(),
                          ),
                        const SizedBox(height: 8),
                        FxButton(
                          label: nfcListening
                              ? 'Waiting for NFC...'
                              : 'Scan NFC Token',
                          icon: Icons.nfc_rounded,
                          fullWidth: true,
                          onPressed: (submitting || nfcListening)
                              ? null
                              : scanNfcAndRedeem,
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<int>(
                          isExpanded: true,
                          initialValue:
                              _filteredApprovedVouchers.any(
                                (voucher) =>
                                    voucher.id == selectedUssdVoucherId,
                              )
                              ? selectedUssdVoucherId
                              : null,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                          ),
                          iconEnabledColor: Colors.white,
                          decoration: const InputDecoration(
                            labelText: 'Approved Voucher for USSD',
                            labelStyle: TextStyle(
                              color: Color(0xFFE2E8F0),
                              fontSize: 12,
                            ),
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 10,
                            ),
                          ),
                          items: _filteredApprovedVouchers
                              .map(
                                (voucher) => DropdownMenuItem<int>(
                                  value: voucher.id,
                                  child: Text(
                                    '${voucher.id} • ${voucher.code} • ${voucher.stationName ?? 'Station'} • R ${voucher.amount.toStringAsFixed(2)}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 12,
                                    ),
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged:
                              approvedVoucherLoading ||
                                  _filteredApprovedVouchers.isEmpty
                              ? null
                              : (value) => setState(() {
                                  selectedUssdVoucherId = value;
                                  _requestScannerFocus();
                                }),
                        ),
                        const SizedBox(height: 8),
                        FxButton(
                          label: ussdLaunching
                              ? 'Starting USSD...'
                              : 'Start USSD Payment',
                          icon: Icons.call_rounded,
                          fullWidth: true,
                          onPressed:
                              (ussdLaunching ||
                                  approvedVoucherLoading ||
                                  ussdConfigLoading)
                              ? null
                              : _launchUssdPayment,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          ussdConfigLoading
                              ? 'Loading admin USSD configuration...'
                              : (ussdServiceCode ?? '').trim().isEmpty
                              ? 'No admin USSD service code is configured yet.'
                              : _selectedUssdVoucher == null
                              ? 'Choose an approved voucher to start the USSD payment flow.'
                              : 'USSD will call ${(ussdServiceCode ?? '').trim()} for voucher number ${_selectedUssdVoucher!.id}.',
                          style: const TextStyle(color: Color(0xFF94A3B8)),
                        ),
                        const SizedBox(height: 8),
                        FxButton(
                          label: ussdListening
                              ? 'Stop USSD Listener'
                              : 'Listen for USSD',
                          icon: Icons.podcasts_rounded,
                          fullWidth: true,
                          onPressed: _toggleUssdListener,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          selectedStationId == null
                              ? 'Select a station before listening for USSD.'
                              : (ussdListening
                                    ? 'USSD listener is active on ${_selectedStationLabel()}.'
                                    : 'USSD listener is off.'),
                          style: const TextStyle(color: Color(0xFF94A3B8)),
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
                                  inputCtrl.text = code;
                                  inputCtrl.selection = TextSelection.collapsed(
                                    offset: inputCtrl.text.length,
                                  );
                                  _submitScannerInput();
                                },
                              ),
                            ),
                          ),
                        if (scanMode) const SizedBox(height: 12),
                        submitting
                            ? const SizedBox(
                                height: 54,
                                child: Center(
                                  child: AppLoader(size: 28, showText: false),
                                ),
                              )
                            : const Text(
                                'Auto redeem is active.',
                                style: TextStyle(color: Color(0xFF94A3B8)),
                              ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
