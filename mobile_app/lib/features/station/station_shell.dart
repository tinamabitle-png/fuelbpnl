import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:nfc_manager/nfc_manager.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

import '../../core/fx_button.dart';
import '../../core/logo_mark.dart';
import '../../core/pro545_printer_bridge.dart';
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
      margin: const EdgeInsets.fromLTRB(12, 0, 12, 12),
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Row(
        children: [
          _item(0, Icons.history_rounded, 'History'),
          Container(width: 1, height: 44, color: const Color(0xFF334155)),
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
      final list = await widget.api.stationVoucherHistory(limit: 10);
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
        itemCount: items.length,
        itemBuilder: (context, i) {
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
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            v.status.toUpperCase(),
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFFE2E8F0),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'R ${v.amount.toStringAsFixed(2)} • ${v.fuelType.toUpperCase()}',
                      style: const TextStyle(color: AppTheme.slate),
                    ),
                    if (v.stationName != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 3),
                        child: Text(
                          v.stationName!,
                          style: const TextStyle(color: Color(0xFF94A3B8)),
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
  final FocusNode scannerFocusNode = FocusNode();
  Timer? _scanDebounce;
  bool submitting = false;
  bool nfcListening = false;
  bool scanMode = false;
  Printer? selectedPrinter;
  String printerMode = 'android';
  final networkIpCtrl = TextEditingController();
  final networkPortCtrl = TextEditingController(text: '9100');

  @override
  void initState() {
    super.initState();
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
    networkIpCtrl.dispose();
    networkPortCtrl.dispose();
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

  Future<Uint8List> _buildReceiptPdf(Map<String, dynamic> receipt) async {
    final doc = pw.Document();
    final stationName =
        (receipt['station'] is Map
                ? (receipt['station']['name'] ?? receipt['station']['city'])
                : receipt['station_name'])?.toString() ??
            'Station';
    final driverName =
        (receipt['driver'] is Map ? receipt['driver']['name'] : null)
            ?.toString() ??
        'Unknown';
    final voucherId = (receipt['voucher_id'] ?? '-').toString();
    final voucherCode =
        (receipt['voucher_code'] ?? receipt['code'] ?? '-').toString();
    final amount = double.tryParse('${receipt['amount'] ?? 0}') ?? 0;
    final voucherStatus = (receipt['status'] ?? 'unknown').toString();
    final txStatus =
        (receipt['transaction_status'] ?? 'unknown').toString().toUpperCase();
    final when = (receipt['redeemed_at'] ??
            receipt['issued_at'] ??
            DateTime.now().toIso8601String())
        .toString();
    final qrData = (receipt['qr_code'] ?? voucherCode).toString();

    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.roll80,
        build: (context) => pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.start,
          children: [
            pw.Text('BWISER POS RECEIPT',
                style: pw.TextStyle(
                  fontWeight: pw.FontWeight.bold,
                  fontSize: 14,
                )),
            pw.SizedBox(height: 8),
            pw.Text('Station: $stationName'),
            pw.Text('Driver: $driverName'),
            pw.Divider(),
            pw.Text('Voucher ID: $voucherId'),
            pw.Text('Voucher Code: $voucherCode'),
            pw.Text('Amount: R ${amount.toStringAsFixed(2)}'),
            pw.Text('Voucher Status: ${voucherStatus.toUpperCase()}'),
            pw.Text('Transaction: $txStatus'),
            pw.Text('When: $when'),
            pw.SizedBox(height: 10),
            pw.Center(
              child: pw.BarcodeWidget(
                barcode: pw.Barcode.qrCode(),
                data: qrData,
                width: 130,
                height: 130,
              ),
            ),
          ],
        ),
      ),
    );

    return doc.save();
  }

  Future<Uint8List> _buildEscPosBytes(
    Map<String, dynamic> receipt, {
    required bool is58mm,
  }) async {
    final stationName =
        (receipt['station'] is Map
                ? (receipt['station']['name'] ?? receipt['station']['city'])
                : receipt['station_name'])?.toString() ??
            'Station';
    final driverName =
        (receipt['driver'] is Map ? receipt['driver']['name'] : null)
            ?.toString() ??
        'Unknown';
    final voucherId = (receipt['voucher_id'] ?? '-').toString();
    final voucherCode =
        (receipt['voucher_code'] ?? receipt['code'] ?? '-').toString();
    final amount = double.tryParse('${receipt['amount'] ?? 0}') ?? 0;
    final voucherStatus = (receipt['status'] ?? 'unknown').toString();
    final txStatus =
        (receipt['transaction_status'] ?? 'unknown').toString().toUpperCase();
    final when = (receipt['redeemed_at'] ??
            receipt['issued_at'] ??
            DateTime.now().toIso8601String())
        .toString();
    final qrData = (receipt['qr_code'] ?? voucherCode).toString();

    final profile = await CapabilityProfile.load();
    final generator = Generator(
      is58mm ? PaperSize.mm58 : PaperSize.mm80,
      profile,
    );

    final bytes = <int>[];
    bytes.addAll(generator.reset());
    bytes.addAll(
      generator.text(
        'BWISER POS RECEIPT',
        styles: const PosStyles(
          align: PosAlign.center,
          bold: true,
          height: PosTextSize.size2,
          width: PosTextSize.size2,
        ),
      ),
    );
    bytes.addAll(generator.hr());
    bytes.addAll(generator.text('Station: $stationName'));
    bytes.addAll(generator.text('Driver: $driverName'));
    bytes.addAll(generator.text('Voucher ID: $voucherId'));
    bytes.addAll(generator.text('Voucher Code: $voucherCode'));
    bytes.addAll(generator.text('Amount: R ${amount.toStringAsFixed(2)}'));
    bytes.addAll(generator.text('Status: ${voucherStatus.toUpperCase()}'));
    bytes.addAll(generator.text('Transaction: $txStatus'));
    bytes.addAll(generator.text('When: $when'));
    bytes.addAll(generator.hr());
    bytes.addAll(generator.qrcode(qrData, size: QRSize.size6));
    bytes.addAll(generator.feed(2));
    bytes.addAll(generator.cut());
    return Uint8List.fromList(bytes);
  }

  Future<void> _printEscPosNetwork(Map<String, dynamic> receipt) async {
    final host = networkIpCtrl.text.trim();
    final port = int.tryParse(networkPortCtrl.text.trim()) ?? 9100;
    if (host.isEmpty) {
      throw Exception('Enter POS printer IP address for ESC/POS mode.');
    }

    final bytes = await _buildEscPosBytes(
      receipt,
      is58mm: printerMode == 'escpos58',
    );
    final socket = await Socket.connect(
      host,
      port,
      timeout: const Duration(seconds: 5),
    );
    socket.add(bytes);
    await socket.flush();
    await socket.close();
  }

  Future<void> _printReceipt(Map<String, dynamic> receipt) async {
    if (printerMode == 'pro545') {
      final available = await Pro545PrinterBridge.isAvailable();
      if (!available) {
        throw Exception('PRO545 PrinterService not available.');
      }
      await Pro545PrinterBridge.printReceipt(receipt);
      return;
    }

    if (printerMode == 'escpos58' || printerMode == 'escpos80') {
      await _printEscPosNetwork(receipt);
      return;
    }

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
    final voucherCode =
        (receipt['voucher_code'] ?? receipt['code'] ?? '-').toString();

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
              SegmentedButton<String>(
                showSelectedIcon: false,
                segments: const [
                  ButtonSegment(
                    value: 'android',
                    icon: Icon(Icons.print_rounded, size: 16),
                    label: Text('Android'),
                  ),
                  ButtonSegment(
                    value: 'escpos58',
                    icon: Icon(Icons.receipt_long_rounded, size: 16),
                    label: Text('ESC/POS 58'),
                  ),
                  ButtonSegment(
                    value: 'escpos80',
                    icon: Icon(Icons.receipt_rounded, size: 16),
                    label: Text('ESC/POS 80'),
                  ),
                  ButtonSegment(
                    value: 'pro545',
                    icon: Icon(Icons.point_of_sale_rounded, size: 16),
                    label: Text('PRO545 SDK'),
                  ),
                ],
                selected: {printerMode},
                onSelectionChanged: (v) => setState(() => printerMode = v.first),
              ),
              const SizedBox(height: 10),
              if (printerMode == 'escpos58' || printerMode == 'escpos80') ...[
                TextField(
                  controller: networkIpCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Printer IP',
                    hintText: '192.168.0.120',
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: networkPortCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Printer Port',
                    hintText: '9100',
                  ),
                ),
                const SizedBox(height: 8),
              ],
              FxButton(
                label: selectedPrinter == null
                    ? 'Select POS Printer'
                    : 'Printer: ${selectedPrinter!.name}',
                icon: Icons.print_rounded,
                fullWidth: true,
                onPressed: printerMode == 'android'
                    ? () async {
                        final navigator = Navigator.of(context);
                        final printer = await Printing.pickPrinter(context: context);
                        if (printer == null || !mounted) return;
                        setState(() => selectedPrinter = printer);
                        navigator.pop();
                        await _showPrintReceiptDialog(receipt);
                      }
                    : null,
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
                content: Text(
                  e.toString().replaceFirst('Exception: ', ''),
                ),
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
                  FxButton(
                    label: scanMode ? 'Close Scanner' : 'Open Scanner',
                    icon: Icons.qr_code_scanner,
                    fullWidth: true,
                    onPressed: () => setState(() => scanMode = !scanMode),
                  ),
                  const SizedBox(height: 8),
                  FxButton(
                    label: nfcListening ? 'Waiting for NFC...' : 'Scan NFC Token',
                    icon: Icons.nfc_rounded,
                    fullWidth: true,
                    onPressed: (submitting || nfcListening)
                        ? null
                        : scanNfcAndRedeem,
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
