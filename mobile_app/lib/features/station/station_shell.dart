import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:nfc_manager/nfc_manager.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../core/fx_button.dart';
import '../../core/logo_mark.dart';
import '../../core/theme.dart';
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
      StationHomePage(api: widget.api),
      StationRedeemPage(api: widget.api, receiptSettings: receiptSettings),
      StationReceiptSettingsPage(
        settings: receiptSettings,
        onChanged: _saveReceiptSettings,
      ),
    ];

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
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(16),
          topRight: Radius.circular(16),
        ),
        border: const Border(top: BorderSide(color: Color(0xFF334155))),
      ),
      child: Row(
        children: [
          _item(0, Icons.history_rounded, 'History'),
          Container(width: 1, height: 48, color: const Color(0xFF334155)),
          _item(1, Icons.qr_code_scanner, 'Redeem'),
          Container(width: 1, height: 48, color: const Color(0xFF334155)),
          _item(2, Icons.receipt_long_rounded, 'Receipt'),
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
  const StationHomePage({super.key, required this.api});
  final ApiClient api;

  @override
  State<StationHomePage> createState() => _StationHomePageState();
}

class _StationHomePageState extends State<StationHomePage> {
  bool loading = true;
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
    if (loading) return const Center(child: CircularProgressIndicator());
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
              child: _voucherCard(v),
            ),
          ),
        ],
      ),
    );
  }

  Widget _voucherCard(VoucherItem v) {
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
          ],
        ),
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

class _StationRedeemPageState extends State<StationRedeemPage> {
  final inputCtrl = TextEditingController();
  final FocusNode scannerFocusNode = FocusNode();
  Timer? _scanDebounce;
  bool submitting = false;
  bool nfcListening = false;
  bool scanMode = false;
  bool ussdListening = false;
  bool stationLoading = true;
  List<Map<String, dynamic>> stations = [];
  int? selectedStationId;
  Printer? selectedPrinter;

  @override
  void initState() {
    super.initState();
    _loadStations();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        scannerFocusNode.requestFocus();
      }
    });
  }

  @override
  void dispose() {
    _scanDebounce?.cancel();
    scannerFocusNode.dispose();
    inputCtrl.dispose();
    super.dispose();
  }

  String _normalizeScanInput(String value) {
    return value.replaceAll(RegExp(r'\s+'), '');
  }

  Future<void> _submitScannerInput() async {
    if (submitting) return;
    final payload = _normalizeScanInput(inputCtrl.text);
    if (payload.isEmpty) return;
    await redeem(payload);
    if (mounted) {
      scannerFocusNode.requestFocus();
    }
  }

  void _onScannerChanged(String _) {
    _scanDebounce?.cancel();
    _scanDebounce = Timer(const Duration(milliseconds: 180), () {
      if (!mounted) return;
      _submitScannerInput();
    });
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
      });
    } catch (_) {
      // Keep UI usable without hard failing.
    } finally {
      if (mounted) setState(() => stationLoading = false);
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
    setState(() => ussdListening = !ussdListening);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          ussdListening ? 'USSD listener enabled.' : 'USSD listener is off.',
        ),
      ),
    );
  }

  String _selectedStationLabel() {
    final station = stations.firstWhere(
      (s) => '${s['id']}' == '$selectedStationId',
      orElse: () => <String, dynamic>{},
    );
    final name = (station['name'] ?? 'Station').toString();
    return '$name (Wallet)';
  }

  Future<void> redeem(String value) async {
    if (submitting) return;
    final normalized = _normalizeScanInput(value);
    if (normalized.isEmpty) return;

    setState(() => submitting = true);
    try {
      final receiptData = await widget.api.stationRedeem(scanInput: normalized);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Voucher redeemed successfully.')),
      );
      inputCtrl.clear();
      await _showPrintReceiptDialog({
        ...receiptData,
        'transaction_status': 'successful',
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
      final failureCode = _extractCodeFromScan(value);
      await _showPrintReceiptDialog({
        'voucher_id': null,
        'voucher_code': failureCode,
        'qr_code': failureCode,
        'amount': 0,
        'status': 'failed',
        'driver': {'name': 'Unknown'},
        'station': {'name': 'Station'},
        'redeemed_at': DateTime.now().toIso8601String(),
        'transaction_status': 'failed',
      });
    } finally {
      if (mounted) setState(() => submitting = false);
    }
  }

  String _extractCodeFromScan(String input) {
    final normalized = _normalizeScanInput(input);
    try {
      final decoded = jsonDecode(normalized);
      if (decoded is Map<String, dynamic>) {
        return (decoded['voucher_code'] ??
                decoded['code'] ??
                decoded['qr_code'] ??
                decoded['voucher_id'] ??
                normalized)
            .toString();
      }
    } catch (_) {
      // keep raw value
    }
    return normalized;
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
    final amount = double.tryParse('${receipt['amount'] ?? 0}') ?? 0;
    final voucherStatus = (receipt['status'] ?? 'unknown').toString();
    final txStatus = (receipt['transaction_status'] ?? 'unknown')
        .toString()
        .toUpperCase();
    final when =
        (receipt['redeemed_at'] ??
                receipt['issued_at'] ??
                DateTime.now().toIso8601String())
            .toString();
    final qrData = (receipt['qr_code'] ?? voucherCode).toString();
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
            _receiptLine(receiptFont, 'Voucher ID', voucherId),
            _receiptLine(receiptFont, 'Voucher Code', voucherCode),
            pw.Text(
              'Amount: R ${amount.toStringAsFixed(2)}',
              style: pw.TextStyle(font: receiptFont, fontSize: 11),
            ),
            pw.SizedBox(height: 4),
            _receiptLine(
              receiptFont,
              'Voucher Status',
              voucherStatus.toUpperCase(),
            ),
            _receiptLine(receiptFont, 'Transaction', txStatus),
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
            pw.SizedBox(height: 6),
          ],
        ),
      ),
    );

    return doc.save();
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

      final chosen = await Printing.pickPrinter(context: context);
      if (chosen == null) {
        // Fallback: open system print/share dialog when no printer is selected.
        await Printing.layoutPdf(onLayout: (_) async => bytes);
        return;
      }
      if (!mounted) return;
      setState(() => selectedPrinter = chosen);
      await Printing.directPrintPdf(
        printer: chosen,
        onLayout: (_) async => bytes,
      );
    } catch (e) {
      if (!mounted) return;
      // Printer can disconnect or not support direct mode; fallback to print dialog.
      setState(() => selectedPrinter = null);
      await Printing.layoutPdf(onLayout: (_) async => bytes);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Direct POS print failed. Opened system print dialog. '
            '${e.toString().replaceFirst('Exception: ', '')}',
          ),
        ),
      );
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
            final ndef = Ndef.from(tag);
            if (ndef == null) {
              throw Exception('NFC tag not supported.');
            }

            final message = ndef.cachedMessage ?? await ndef.read();
            if (message.records.isEmpty) {
              throw Exception('No NFC payload found.');
            }

            String raw = '';
            for (final record in message.records) {
              if (record.payload.isEmpty) continue;
              raw = _decodeNdefPayload(record.payload).trim();
              if (raw.isNotEmpty) break;
            }

            if (raw.isEmpty) {
              throw Exception('Empty NFC payload.');
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
              scannerFocusNode.requestFocus();
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
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => scannerFocusNode.requestFocus(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          SizedBox(
            height: 0,
            width: 0,
            child: TextField(
              controller: inputCtrl,
              focusNode: scannerFocusNode,
              autofocus: true,
              enableSuggestions: false,
              autocorrect: false,
              keyboardType: TextInputType.visiblePassword,
              onChanged: _onScannerChanged,
              onSubmitted: (_) => _submitScannerInput(),
            ),
          ),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<int>(
                          initialValue:
                              stations.any(
                                (s) => '${s['id']}' == '$selectedStationId',
                              )
                              ? selectedStationId
                              : null,
                          decoration: const InputDecoration(
                            labelText: 'Station (Wallet)',
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
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged: stationLoading
                              ? null
                              : (value) =>
                                    setState(() => selectedStationId = value),
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
                    onPressed: () => setState(() => scanMode = !scanMode),
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
                            setState(() => scanMode = false);
                            redeem(code);
                          },
                        ),
                      ),
                    ),
                  if (scanMode) const SizedBox(height: 12),
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
                          label: 'Redeem Voucher',
                          icon: Icons.check_circle_outline,
                          fullWidth: true,
                          onPressed: _submitScannerInput,
                        ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
