import 'dart:convert';

import 'package:crypto/crypto.dart';

import '../core/session_store.dart';
import 'models.dart';

class ApiClient {
  ApiClient(this._store);

  final SessionStore _store;

  static const bool mockMode = true;

  static Map<String, dynamic>? _mockUser;
  static final List<VoucherItem> _mockDriverVouchers = [
    VoucherItem(
      id: 4101,
      code: 'BWV-4101-APR',
      status: 'approved',
      amount: 1200,
      fuelType: 'diesel',
      qrCode: 'QR-4101',
      stationName: 'Shell Sandton',
    ),
    VoucherItem(
      id: 4102,
      code: 'BWV-4102-APR',
      status: 'approved',
      amount: 850,
      fuelType: 'petrol',
      qrCode: 'QR-4102',
      stationName: 'BP Midrand',
    ),
    VoucherItem(
      id: 4103,
      code: 'BWV-4103-ISS',
      status: 'issued',
      amount: 600,
      fuelType: 'super',
      qrCode: 'QR-4103',
      stationName: 'Engen Rosebank',
    ),
  ];

  static final List<Map<String, dynamic>> _mockStations = [
    {'id': 1, 'name': 'Shell Sandton', 'city': 'Johannesburg'},
    {'id': 2, 'name': 'BP Midrand', 'city': 'Midrand'},
    {'id': 3, 'name': 'Engen Rosebank', 'city': 'Johannesburg'},
    {'id': 4, 'name': 'Sasol Pretoria East', 'city': 'Pretoria'},
  ];

  static final List<RepaymentItem> _mockRepayments = [
    RepaymentItem(
      id: 9001,
      voucherCode: 'BWV-4101-APR',
      amount: 140.00,
      dueDate: DateTime.now().add(const Duration(days: 1)),
      status: 'pending',
    ),
    RepaymentItem(
      id: 9002,
      voucherCode: 'BWV-4102-APR',
      amount: 120.00,
      dueDate: DateTime.now().add(const Duration(days: 2)),
      status: 'pending',
    ),
    RepaymentItem(
      id: 9003,
      voucherCode: 'BWV-3999-APR',
      amount: 95.00,
      dueDate: DateTime.now().subtract(const Duration(days: 1)),
      status: 'overdue',
    ),
  ];

  Future<Map<String, dynamic>> login({
    required String phone,
    required String password,
    required String role,
  }) async {
    if (mockMode) {
      final user = {
        'id': role == 'driver' ? 1001 : 2001,
        'name': role == 'driver' ? 'Mock Driver' : 'Mock Station Attendant',
        'phone': phone.isEmpty ? '+27000000000' : phone,
        'email': role == 'driver' ? 'driver@mock.local' : 'station@mock.local',
        'autopay_enabled': false,
      };
      _mockUser = Map<String, dynamic>.from(user);
      await _store.saveSession(
        token: 'mock-token-${DateTime.now().millisecondsSinceEpoch}',
        user: user,
        role: role,
      );
      return user;
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<Map<String, dynamic>> profile() async {
    if (mockMode) {
      if (_mockUser != null) return Map<String, dynamic>.from(_mockUser!);
      final stored = await _store.user();
      if (stored != null) {
        stored['autopay_enabled'] = stored['autopay_enabled'] ?? false;
        _mockUser = Map<String, dynamic>.from(stored);
        return stored;
      }
      return {
        'id': 1001,
        'name': 'Mock User',
        'phone': '+27000000000',
        'autopay_enabled': false,
      };
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<void> setAutopay({required bool enabled}) async {
    if (mockMode) {
      final current = await profile();
      current['autopay_enabled'] = enabled;
      _mockUser = Map<String, dynamic>.from(current);
      final token = await _store.token() ?? 'mock-token';
      final role = await _store.role() ?? 'driver';
      await _store.saveSession(token: token, user: current, role: role);
      return;
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<List<VoucherItem>> driverVouchers() async {
    if (mockMode) {
      return List<VoucherItem>.from(_mockDriverVouchers);
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<void> applyVoucher({
    required int stationId,
    required double amount,
    required String fuelType,
  }) async {
    if (mockMode) {
      final station = _mockStations.firstWhere(
        (s) => (s['id'] as int) == stationId,
        orElse: () => _mockStations.first,
      );
      final id = DateTime.now().millisecondsSinceEpoch ~/ 1000;
      _mockDriverVouchers.insert(
        0,
        VoucherItem(
          id: id,
          code: 'BWV-$id-APR',
          status: 'approved',
          amount: amount,
          fuelType: fuelType,
          qrCode: 'QR-$id',
          stationName: station['name'].toString(),
        ),
      );
      return;
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<List<Map<String, dynamic>>> stations() async {
    if (mockMode) {
      return List<Map<String, dynamic>>.from(_mockStations);
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<List<VoucherItem>> stationApprovedVouchers() async {
    if (mockMode) {
      return _mockDriverVouchers.where((v) => v.status == 'approved').toList();
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<void> stationRedeem({required String scanInput}) async {
    if (mockMode) {
      if (scanInput.isEmpty) {
        throw Exception('Provide a QR payload, code, or token.');
      }

      String code = scanInput.trim();
      try {
        final decoded = jsonDecode(scanInput);
        if (decoded is Map<String, dynamic>) {
          code =
              (decoded['code'] ??
                      decoded['voucher_code'] ??
                      decoded['qr_code'] ??
                      code)
                  .toString();
        }
      } catch (_) {
        // keep raw input
      }

      final index = _mockDriverVouchers.indexWhere(
        (v) => v.code == code || v.qrCode == code || scanInput.contains(v.code),
      );

      if (index < 0) {
        throw Exception('Voucher not found in mock data.');
      }

      final existing = _mockDriverVouchers[index];
      _mockDriverVouchers[index] = VoucherItem(
        id: existing.id,
        code: existing.code,
        status: 'redeemed',
        amount: existing.amount,
        fuelType: existing.fuelType,
        qrCode: existing.qrCode,
        stationName: existing.stationName,
        expiresAt: existing.expiresAt,
      );
      return;
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<List<RepaymentItem>> driverRepayments() async {
    if (mockMode) {
      return List<RepaymentItem>.from(_mockRepayments)
        ..sort((a, b) => a.dueDate.compareTo(b.dueDate));
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  Future<void> payRepayment(int repaymentId) async {
    if (mockMode) {
      final i = _mockRepayments.indexWhere((r) => r.id == repaymentId);
      if (i < 0) throw Exception('Repayment not found.');
      final current = _mockRepayments[i];
      _mockRepayments[i] = RepaymentItem(
        id: current.id,
        voucherCode: current.voucherCode,
        amount: current.amount,
        dueDate: current.dueDate,
        status: 'paid',
      );
      return;
    }

    throw UnimplementedError('Live backend mode is disabled for now.');
  }

  String buildHmacTapToken({
    required int voucherId,
    required String voucherCode,
  }) {
    final payload =
        '$voucherId:$voucherCode:${DateTime.now().toUtc().millisecondsSinceEpoch}';
    const secret = 'BWISER_MOBILE_HMAC';
    final digest = Hmac(
      sha256,
      utf8.encode(secret),
    ).convert(utf8.encode(payload));
    return '$payload:${digest.toString().substring(0, 24)}';
  }
}
