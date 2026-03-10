import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;

import '../core/session_store.dart';
import 'models.dart';

class ApiClient {
  ApiClient(this._store);

  final SessionStore _store;

  static const bool mockMode = false;

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
    {
      'id': 1,
      'name': 'Shell Sandton',
      'city': 'Johannesburg',
      'address': 'Rivonia Rd, Sandton, Johannesburg',
      'latitude': -26.1076,
      'longitude': 28.0567,
      'is_partner': true,
      'wallet_balance': 25000,
    },
    {
      'id': 2,
      'name': 'BP Midrand',
      'city': 'Midrand',
      'address': 'Old Pretoria Rd, Midrand',
      'latitude': -25.9978,
      'longitude': 28.1266,
      'is_partner': true,
      'wallet_balance': 14000,
    },
    {
      'id': 3,
      'name': 'Engen Rosebank',
      'city': 'Johannesburg',
      'address': 'Jan Smuts Ave, Rosebank, Johannesburg',
      'latitude': -26.1458,
      'longitude': 28.0417,
      'is_partner': false,
      'wallet_balance': 12000,
    },
    {
      'id': 4,
      'name': 'Sasol Pretoria East',
      'city': 'Pretoria',
      'address': 'Atterbury Rd, Pretoria East',
      'latitude': -25.7892,
      'longitude': 28.3146,
      'is_partner': true,
      'wallet_balance': 0,
    },
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
    String? role,
  }) async {
    final requestedRole = (role ?? '').toLowerCase().trim();
    if (mockMode) {
      final user = {
        'id': requestedRole == 'station' ? 2001 : 1001,
        'name': requestedRole == 'station'
            ? 'Mock Station Attendant'
            : 'Mock Driver',
        'phone': phone.isEmpty ? '+27000000000' : phone,
        'email': requestedRole == 'station'
            ? 'station@mock.local'
            : 'driver@mock.local',
        'autopay_enabled': false,
      };
      _mockUser = Map<String, dynamic>.from(user);
      final appRole = requestedRole == 'station' ? 'station' : 'driver';
      await _store.saveSession(
        token: 'mock-token-${DateTime.now().millisecondsSinceEpoch}',
        user: user,
        role: appRole,
      );
      return user;
    }

    final response = await _request(
      method: 'POST',
      path: '/auth/login',
      body: {
        'phone': phone,
        'password': password,
        'device_id': 'flutter-${DateTime.now().millisecondsSinceEpoch}',
        'device_name': 'Bwiser Flutter',
        'device_type': 'android',
      },
      auth: false,
    );

    final data = _extractData(response.body);
    final token = (data['token'] ?? '').toString();
    final user = Map<String, dynamic>.from(
      (data['user'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{},
    );
    final roles =
        (data['roles'] as List?)?.map((e) => '$e').toList() ?? const [];

    if (token.isEmpty || user.isEmpty) {
      throw Exception('Login response missing token or user payload.');
    }

    final appRole = roles.any((r) => r == 'merchant' || r == 'station')
        ? 'station'
        : roles.contains('driver')
        ? 'driver'
        : '';
    if (appRole.isEmpty) {
      throw Exception(
        'This account is not assigned to Driver or Station role.',
      );
    }

    await _store.saveSession(token: token, user: user, role: appRole);
    return user;
  }

  Future<Map<String, dynamic>> quickLogin({required String role}) async {
    if (mockMode) {
      return login(
        phone: '',
        password: '',
        role: role == 'merchant' ? 'station' : role,
      );
    }

    final quickRole = role == 'station' ? 'merchant' : role;
    final appRole = role == 'merchant' ? 'station' : role;
    final response = await _request(
      method: 'POST',
      path: '/auth/quick-login',
      body: {'role': quickRole},
      auth: false,
    );

    final data = _extractData(response.body);
    final token = (data['token'] ?? '').toString();
    final user = Map<String, dynamic>.from(
      (data['user'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{},
    );

    if (token.isEmpty || user.isEmpty) {
      throw Exception('Quick login response missing token or user payload.');
    }

    await _store.saveSession(token: token, user: user, role: appRole);
    return user;
  }

  Future<Map<String, dynamic>> forgotPassword({
    String? phone,
    String? email,
    String channel = 'phone',
  }) async {
    final payload = <String, dynamic>{
      'channel': channel,
      if (phone != null && phone.trim().isNotEmpty) 'phone': phone.trim(),
      if (email != null && email.trim().isNotEmpty) 'email': email.trim(),
    };
    final response = await _request(
      method: 'POST',
      path: '/auth/forgot-password',
      body: payload,
      auth: false,
    );
    return _extractData(response.body);
  }

  Future<Map<String, dynamic>> resetPassword({
    String? phone,
    String? email,
    String channel = 'phone',
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    final payload = <String, dynamic>{
      'channel': channel,
      'code': code.trim(),
      'password': password,
      'password_confirmation': passwordConfirmation,
      if (phone != null && phone.trim().isNotEmpty) 'phone': phone.trim(),
      if (email != null && email.trim().isNotEmpty) 'email': email.trim(),
    };
    final response = await _request(
      method: 'POST',
      path: '/auth/reset-password',
      body: payload,
      auth: false,
    );
    return _extractData(response.body);
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

    final response = await _request(method: 'GET', path: '/auth/profile');
    final data = _extractData(response.body);
    final user = (data['user'] as Map?)?.cast<String, dynamic>();
    return user ?? data;
  }

  Future<void> setAutopay({
    required bool enabled,
    String method = 'paystack',
    String? paystackAuthorizationCode,
    String? paystackEmail,
  }) async {
    if (mockMode) {
      final current = await profile();
      current['autopay_enabled'] = enabled;
      current['autopay_gateway'] = 'paystack';
      current['autopay_has_token'] = enabled;
      current['autopay_ready'] = enabled;
      _mockUser = Map<String, dynamic>.from(current);
      final token = await _store.token() ?? 'mock-token';
      final role = await _store.role() ?? 'driver';
      await _store.saveSession(token: token, user: current, role: role);
      return;
    }

    final payload = <String, dynamic>{
      'enabled': enabled,
      if (enabled) ...{
        'payment_method': method,
        'threshold_days': 2,
        'max_amount': 50000,
      },
      if (enabled && method == 'paystack' && paystackAuthorizationCode != null)
        'authorization_code': paystackAuthorizationCode,
      if (enabled && method == 'paystack' && paystackEmail != null)
        'paystack_email': paystackEmail,
    };

    await _request(
      method: 'POST',
      path: '/repayments/setup-auto-payment',
      body: payload,
    );

    final user = await profile();
    final token = await _store.token();
    final role = await _store.role();
    if (token != null && role != null) {
      await _store.saveSession(token: token, user: user, role: role);
    }
  }

  Future<Map<String, dynamic>> initializeAutopayPaystack({
    String? email,
  }) async {
    if (mockMode) {
      return {
        'reference': 'AUTOSETUP-MOCK-${DateTime.now().millisecondsSinceEpoch}',
        'authorization_url': 'https://paystack.com/pay/mock-autopay',
        'access_code': 'mock-access-code',
        'probe_amount': 5.0,
      };
    }

    final response = await _request(
      method: 'POST',
      path: '/repayments/autopay/paystack/initialize',
      body: {
        if (email != null && email.trim().isNotEmpty) 'email': email.trim(),
      },
    );
    return _extractData(response.body);
  }

  Future<Map<String, dynamic>> verifyAutopayPaystack({
    required String reference,
  }) async {
    if (mockMode) {
      final current = await profile();
      current['autopay_enabled'] = true;
      current['autopay_gateway'] = 'paystack';
      current['autopay_has_token'] = true;
      current['autopay_ready'] = true;
      _mockUser = Map<String, dynamic>.from(current);
      final token = await _store.token() ?? 'mock-token';
      final role = await _store.role() ?? 'driver';
      await _store.saveSession(token: token, user: current, role: role);
      return {'reference': reference, 'autopay_ready': true};
    }

    final response = await _request(
      method: 'POST',
      path: '/repayments/autopay/paystack/verify',
      body: {'reference': reference},
    );
    return _extractData(response.body);
  }

  Future<List<VoucherItem>> driverVouchers() async {
    if (mockMode) {
      return List<VoucherItem>.from(_mockDriverVouchers);
    }

    try {
      final response = await _request(
        method: 'GET',
        path: '/vouchers?limit=50',
      );
      final data = _extractData(response.body);
      final vouchersNode =
          (data['vouchers'] as Map?)?.cast<String, dynamic>() ??
          <String, dynamic>{};
      final rows = _asList(vouchersNode['data'] ?? data['data'] ?? data);
      return rows.map(VoucherItem.fromDriverMap).toList();
    } catch (_) {
      // Fallback if paginator/query variants differ.
      final response = await _request(method: 'GET', path: '/vouchers');
      final data = _extractData(response.body);
      final vouchersNode =
          (data['vouchers'] as Map?)?.cast<String, dynamic>() ??
          <String, dynamic>{};
      final rows = _asList(vouchersNode['data'] ?? data['data'] ?? data);
      return rows.map(VoucherItem.fromDriverMap).toList();
    }
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

    await _request(
      method: 'POST',
      path: '/vouchers/request',
      body: {
        'fuel_station_id': stationId,
        'amount': amount,
        'fuel_type': fuelType,
        'payment_type': 'bnpl',
      },
    );
  }

  Future<List<Map<String, dynamic>>> stations() async {
    if (mockMode) {
      return List<Map<String, dynamic>>.from(_mockStations);
    }

    try {
      final response = await _request(
        method: 'GET',
        path: '/merchant/developer/stations',
      );
      final data = _extractData(response.body);
      final rows = _asList(data['data'] ?? data['stations'] ?? data);
      final mapped = rows
          .map(
            (e) => {
              'id': _toInt(e['id']),
              'name': (e['name'] ?? 'Station').toString(),
              'city': (e['city'] ?? '').toString(),
              'address':
                  (e['address'] ?? e['street_address'] ?? e['location'] ?? '')
                      .toString(),
              'latitude':
                  e['latitude'] ??
                  e['lat'] ??
                  e['station_latitude'] ??
                  e['device_latitude'],
              'longitude':
                  e['longitude'] ??
                  e['lng'] ??
                  e['lon'] ??
                  e['station_longitude'] ??
                  e['device_longitude'],
              'status': (e['status'] ?? '').toString(),
              'wallet_balance':
                  e['wallet_balance'] ??
                  e['available_balance'] ??
                  e['balance'] ??
                  e['prefunded_balance'] ??
                  e['funded_amount'] ??
                  0,
              'total_settlements': e['total_settlements'] ?? 0,
            },
          )
          .where((e) => (e['id'] as int) > 0)
          .toList();
      if (mapped.isNotEmpty) return mapped;
    } catch (_) {
      // Try generic public/search endpoint below.
    }

    var searchFailed = false;
    try {
      final response = await _request(
        method: 'GET',
        path: '/stations/search?query=station',
      );
      final data = _extractData(response.body);
      final rows = _asList(data['stations'] ?? data['data'] ?? data);
      final mapped = rows
          .map(
            (e) => {
              'id': _toInt(e['id']),
              'name': (e['name'] ?? 'Station').toString(),
              'city': (e['city'] ?? '').toString(),
              'address':
                  (e['address'] ?? e['street_address'] ?? e['location'] ?? '')
                      .toString(),
              'latitude':
                  e['latitude'] ??
                  e['lat'] ??
                  e['station_latitude'] ??
                  e['device_latitude'],
              'longitude':
                  e['longitude'] ??
                  e['lng'] ??
                  e['lon'] ??
                  e['station_longitude'] ??
                  e['device_longitude'],
              'status': (e['status'] ?? '').toString(),
              'wallet_balance':
                  e['wallet_balance'] ??
                  e['available_balance'] ??
                  e['balance'] ??
                  e['prefunded_balance'] ??
                  e['funded_amount'] ??
                  0,
              'total_settlements': e['total_settlements'] ?? 0,
            },
          )
          .where((e) => (e['id'] as int) > 0)
          .toList();
      if (mapped.isNotEmpty) return mapped;
    } catch (_) {
      searchFailed = true;
    }

    if (searchFailed) {
      throw Exception(
        'Unable to load stations from API right now. Please try again later.',
      );
    }
    throw Exception('No stations returned by API.');
  }

  Future<List<Map<String, dynamic>>> nearbyStations({
    required double latitude,
    required double longitude,
    double radiusKm = 35,
    int limit = 50,
  }) async {
    if (mockMode) {
      return List<Map<String, dynamic>>.from(_mockStations);
    }

    final response = await _request(
      method: 'GET',
      path:
          '/stations/nearby?latitude=$latitude&longitude=$longitude&radius=$radiusKm&limit=$limit',
    );
    final data = _extractData(response.body);
    final rows = _asList(data['stations'] ?? data['data'] ?? data);
    return rows
        .map(
          (e) => {
            'id': _toInt(e['id']),
            'name': (e['name'] ?? 'Station').toString(),
            'city': (e['city'] ?? '').toString(),
            'address':
                (e['address'] ?? e['street_address'] ?? e['location'] ?? '')
                    .toString(),
            'latitude':
                e['latitude'] ??
                e['lat'] ??
                e['station_latitude'] ??
                e['device_latitude'],
            'longitude':
                e['longitude'] ??
                e['lng'] ??
                e['lon'] ??
                e['station_longitude'] ??
                e['device_longitude'],
            'status': (e['status'] ?? '').toString(),
            'wallet_balance':
                e['wallet_balance'] ??
                e['available_balance'] ??
                e['balance'] ??
                e['prefunded_balance'] ??
                e['funded_amount'] ??
                0,
            'total_settlements': e['total_settlements'] ?? 0,
            'distance': e['distance'],
          },
        )
        .where((e) => (e['id'] as int) > 0)
        .toList();
  }

  Future<List<Map<String, dynamic>>> searchStations(String query) async {
    final q = query.trim();
    if (q.length < 2) return const [];

    if (mockMode) {
      final lower = q.toLowerCase();
      return _mockStations.where((s) {
        final name = (s['name'] ?? '').toString().toLowerCase();
        final city = (s['city'] ?? '').toString().toLowerCase();
        final address = (s['address'] ?? '').toString().toLowerCase();
        return name.contains(lower) ||
            city.contains(lower) ||
            address.contains(lower);
      }).toList();
    }

    final response = await _request(
      method: 'GET',
      path: '/stations/search?query=${Uri.encodeQueryComponent(q)}',
    );
    final data = _extractData(response.body);
    final rows = _asList(data['stations'] ?? data['data'] ?? data);
    return rows
        .map(
          (e) => {
            'id': _toInt(e['id']),
            'name': (e['name'] ?? 'Station').toString(),
            'city': (e['city'] ?? '').toString(),
            'address':
                (e['address'] ?? e['street_address'] ?? e['location'] ?? '')
                    .toString(),
            'latitude':
                e['latitude'] ??
                e['lat'] ??
                e['station_latitude'] ??
                e['device_latitude'],
            'longitude':
                e['longitude'] ??
                e['lng'] ??
                e['lon'] ??
                e['station_longitude'] ??
                e['device_longitude'],
            'status': (e['status'] ?? '').toString(),
            'wallet_balance':
                e['wallet_balance'] ??
                e['available_balance'] ??
                e['balance'] ??
                e['prefunded_balance'] ??
                e['funded_amount'] ??
                0,
            'total_settlements': e['total_settlements'] ?? 0,
          },
        )
        .where((e) => (e['id'] as int) > 0)
        .toList();
  }

  Future<List<VoucherItem>> stationApprovedVouchers() async {
    if (mockMode) {
      return _mockDriverVouchers.where((v) => v.status == 'approved').toList();
    }

    try {
      final response = await _request(
        method: 'GET',
        path: '/merchant/developer/vouchers?status=approved&latest=20',
      );
      final data = _extractData(response.body);
      final rows = _asList(data['data'] ?? data);
      return rows.map(VoucherItem.fromMerchantMap).toList();
    } catch (_) {
      // Fallback to general voucher feed if developer endpoint is not enabled.
      final response = await _request(
        method: 'GET',
        path: '/vouchers?status=issued&limit=20',
      );
      final data = _extractData(response.body);
      final vouchersNode =
          (data['vouchers'] as Map?)?.cast<String, dynamic>() ??
          <String, dynamic>{};
      final rows = _asList(vouchersNode['data'] ?? data['data'] ?? data);
      return rows.map(VoucherItem.fromDriverMap).toList();
    }
  }

  Future<List<VoucherItem>> stationVoucherHistory({int limit = 10}) async {
    if (mockMode) {
      final list = List<VoucherItem>.from(_mockDriverVouchers);
      list.sort((a, b) => b.id.compareTo(a.id));
      return list.take(limit).toList();
    }

    final safeLimit = limit.clamp(1, 50);
    final response = await _request(
      method: 'GET',
      path: '/merchant/developer/vouchers?latest=$safeLimit',
    );
    final data = _extractData(response.body);
    final rows = _asList(data['data'] ?? data);
    return rows.map(VoucherItem.fromMerchantMap).toList();
  }

  Future<List<Map<String, dynamic>>> stationUssdEvents({
    int perPage = 20,
  }) async {
    if (mockMode) {
      return const [];
    }

    final safePerPage = perPage.clamp(1, 100);
    final response = await _request(
      method: 'GET',
      path: '/merchant/developer/ussd/events?per_page=$safePerPage',
    );
    final data = _extractData(response.body);
    return _asList(data['data'] ?? data);
  }

  Future<Map<String, dynamic>> stationRedeem({
    required String scanInput,
  }) async {
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
      return {
        'voucher_id': existing.id,
        'voucher_code': existing.code,
        'qr_code': existing.qrCode,
        'amount': existing.amount,
        'status': 'redeemed',
        'fuel_type': existing.fuelType,
        'station': {'name': existing.stationName ?? 'Station'},
        'driver': {'name': 'Mock Driver'},
        'redeemed_at': DateTime.now().toIso8601String(),
        'transaction_status': 'successful',
      };
    }

    final parsedCode = _extractVoucherCode(scanInput);
    final location = await _currentDeviceLocation();
    final redemptionBody = <String, dynamic>{
      'scan_input': scanInput,
      'voucher_code': parsedCode,
      'code': parsedCode,
      if (location != null) ...location,
    };

    try {
      final response = await _request(
        method: 'POST',
        path: '/merchant/developer/vouchers/redeem',
        body: redemptionBody,
      );
      final data = _extractData(response.body);
      final payload = Map<String, dynamic>.from(
        (data['data'] as Map?)?.cast<String, dynamic>() ?? data,
      );
      final ok = payload['success'] == null ? true : payload['success'] == true;
      if (!ok) {
        throw Exception(
          (payload['message'] ?? 'Voucher redemption failed.').toString(),
        );
      }
      payload['transaction_status'] = 'successful';
      return payload;
    } catch (_) {
      final response = await _request(
        method: 'POST',
        path: '/merchant/redeem-voucher',
        body: redemptionBody,
      );
      final data = _extractData(response.body);
      final payload = Map<String, dynamic>.from(
        (data['voucher'] as Map?)?.cast<String, dynamic>() ??
            (data['data'] as Map?)?.cast<String, dynamic>() ??
            data,
      );
      payload['transaction_status'] = 'successful';
      return payload;
    }
  }

  Future<List<RepaymentItem>> driverRepayments() async {
    if (mockMode) {
      return List<RepaymentItem>.from(_mockRepayments)
        ..sort((a, b) => a.dueDate.compareTo(b.dueDate));
    }

    List<Map<String, dynamic>> upcomingRows = const [];
    List<Map<String, dynamic>> overdueRows = const [];

    try {
      final upcomingRes = await _request(
        method: 'GET',
        path: '/repayments/upcoming?limit=100',
      );
      final upcomingData = _extractData(upcomingRes.body);
      upcomingRows = _asList(
        ((upcomingData['repayments'] as Map?)?.cast<String, dynamic>() ??
                <String, dynamic>{})['data'] ??
            upcomingData['data'] ??
            upcomingData,
      );
    } catch (_) {
      // Non-fatal: upcoming repayments endpoint may be unavailable in some envs.
    }

    try {
      final overdueRes = await _request(
        method: 'GET',
        path: '/repayments/overdue',
      );
      final overdueData = _extractData(overdueRes.body);
      overdueRows = _asList(
        ((overdueData['repayments'] as Map?)?.cast<String, dynamic>() ??
                <String, dynamic>{})['data'] ??
            overdueData['repayments'] ??
            overdueData['data'] ??
            overdueData,
      );
    } catch (_) {
      // Non-fatal: overdue endpoint may fail when schema is inconsistent.
    }

    if (upcomingRows.isEmpty && overdueRows.isEmpty) {
      // Final fallback: history endpoint, then filter in-app.
      try {
        final historyRes = await _request(
          method: 'GET',
          path: '/repayments/history?limit=120',
        );
        final historyData = _extractData(historyRes.body);
        final historyRows = _asList(
          ((historyData['repayments'] as Map?)?.cast<String, dynamic>() ??
                  <String, dynamic>{})['data'] ??
              historyData['data'] ??
              historyData,
        );
        upcomingRows = historyRows
            .where(
              (row) =>
                  ((row['status'] ?? '').toString().toLowerCase() == 'pending'),
            )
            .toList();
        overdueRows = historyRows
            .where(
              (row) =>
                  ((row['status'] ?? '').toString().toLowerCase() == 'overdue'),
            )
            .toList();
      } catch (_) {
        // keep empty list rather than hard-failing app screens
      }
    }

    final merged = <int, RepaymentItem>{};
    for (final row in [...upcomingRows, ...overdueRows]) {
      final item = _repaymentFromMap(row);
      if (item.id > 0) {
        merged[item.id] = item;
      }
    }

    final list = merged.values.toList()
      ..sort((a, b) => a.dueDate.compareTo(b.dueDate));
    return list;
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

    await _request(
      method: 'POST',
      path: '/repayments/make-payment',
      body: {
        'repayment_ids': [repaymentId],
        'payment_method': 'wallet',
      },
    );
  }

  Future<Map<String, dynamic>> initializePaystackRepayment(
    int repaymentId,
  ) async {
    final response = await _request(
      method: 'POST',
      path: '/repayments/$repaymentId/paystack/initialize',
      body: {},
    );
    return _extractData(response.body);
  }

  Future<void> verifyPaystackRepayment({
    required int repaymentId,
    required String reference,
  }) async {
    await _request(
      method: 'POST',
      path: '/repayments/paystack/verify',
      body: {'repayment_id': repaymentId, 'reference': reference},
    );
  }

  Future<String> driverTapToken(int voucherId) async {
    if (mockMode) {
      final match = _mockDriverVouchers.firstWhere(
        (v) => v.id == voucherId,
        orElse: () => _mockDriverVouchers.first,
      );
      return buildHmacTapToken(voucherId: match.id, voucherCode: match.code);
    }

    final response = await _request(
      method: 'GET',
      path: '/vouchers/$voucherId/tap-token',
    );
    final data = _extractData(response.body);
    final token = (data['token'] ?? '').toString();
    if (token.isEmpty) {
      throw Exception('Tap token was not returned by server.');
    }
    return token;
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

  RepaymentItem _repaymentFromMap(Map<String, dynamic> row) {
    final lease =
        (row['lease'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
    return RepaymentItem(
      id: _toInt(row['id']),
      voucherCode: (row['voucher_code'] ?? lease['id'] ?? 'Lease').toString(),
      amount: _toDouble(row['amount']),
      dueDate: DateTime.tryParse('${row['due_date'] ?? ''}') ?? DateTime.now(),
      status: (row['status'] ?? 'pending').toString(),
    );
  }

  String _extractVoucherCode(String input) {
    var normalized = input.trim();
    if (normalized.isEmpty) return normalized;

    try {
      final decoded = jsonDecode(normalized);
      if (decoded is Map<String, dynamic>) {
        return (decoded['code'] ??
                decoded['voucher_code'] ??
                decoded['qr_code'] ??
                decoded['voucher_id'] ??
                normalized)
            .toString();
      }
    } catch (_) {
      // raw token or code
    }

    if (normalized.contains(':')) {
      final parts = normalized.split(':');
      if (parts.length >= 2 && parts[0].trim().isNotEmpty) {
        return parts[1].trim();
      }
    }

    return normalized;
  }

  Future<http.Response> _request({
    required String method,
    required String path,
    Map<String, dynamic>? body,
    bool auth = true,
  }) async {
    final base = await _store.baseUrl();
    final uri = Uri.parse('$base$path');
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    if (auth) {
      final token = await _store.token();
      if (token == null || token.isEmpty) {
        throw Exception('Authentication required. Please login again.');
      }
      headers['Authorization'] = 'Bearer $token';
    }

    late http.Response response;
    try {
      switch (method.toUpperCase()) {
        case 'POST':
          response = await http.post(
            uri,
            headers: headers,
            body: jsonEncode(body ?? {}),
          );
          break;
        case 'PUT':
          response = await http.put(
            uri,
            headers: headers,
            body: jsonEncode(body ?? {}),
          );
          break;
        case 'DELETE':
          response = await http.delete(
            uri,
            headers: headers,
            body: jsonEncode(body ?? {}),
          );
          break;
        default:
          response = await http.get(uri, headers: headers);
      }
    } catch (e) {
      final message = e.toString().toLowerCase();
      final isFetchFailure =
          message.contains('failed to fetch') ||
          message.contains('xmlhttprequest error') ||
          message.contains('connection refused') ||
          message.contains('connection closed');
      if (isFetchFailure) {
        throw Exception(
          'Cannot reach API at $base. Ensure https://www.bwiser.co.za is reachable.',
        );
      }
      rethrow;
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return response;
    }

    throw Exception(_extractError(response));
  }

  Map<String, dynamic> _extractData(String body) {
    final decoded = jsonDecode(body);
    if (decoded is Map<String, dynamic>) {
      if (decoded['data'] is Map<String, dynamic>) {
        return Map<String, dynamic>.from(decoded['data'] as Map);
      }
      if (decoded['data'] is List) {
        return {'data': decoded['data']};
      }
      return decoded;
    }
    return <String, dynamic>{};
  }

  String _extractError(http.Response response) {
    try {
      final decoded = jsonDecode(response.body);
      final parsed = _stringifyErrorNode(decoded);
      if (parsed.isNotEmpty) {
        return parsed;
      }
    } catch (_) {
      // ignore parse errors
    }

    return 'Request failed (${response.statusCode}).';
  }

  String _stringifyErrorNode(dynamic node) {
    if (node == null) return '';
    if (node is String) {
      final text = node.trim();
      if (text.isEmpty) return '';
      // Suppress noisy framework internals from end users.
      if (text.toLowerCase().contains('could not be converted to string')) {
        return 'A server validation error occurred.';
      }
      return text;
    }
    if (node is num || node is bool) return node.toString();

    if (node is List) {
      for (final item in node) {
        final parsed = _stringifyErrorNode(item);
        if (parsed.isNotEmpty) return parsed;
      }
      return '';
    }

    if (node is Map) {
      final map = node.map((key, value) => MapEntry(key.toString(), value));

      // Prefer common API message keys first.
      const priorityKeys = <String>[
        'message',
        'error',
        'detail',
        'description',
        'title',
      ];
      for (final key in priorityKeys) {
        final parsed = _stringifyErrorNode(map[key]);
        if (parsed.isNotEmpty) return parsed;
      }

      // Laravel style field errors: { errors: { phone: ["..."] } }
      final errorsNode = map['errors'];
      final parsedErrors = _stringifyErrorNode(errorsNode);
      if (parsedErrors.isNotEmpty) return parsedErrors;

      // Fallback: scan all values recursively.
      for (final value in map.values) {
        final parsed = _stringifyErrorNode(value);
        if (parsed.isNotEmpty) return parsed;
      }
    }

    return '';
  }

  List<Map<String, dynamic>> _asList(dynamic node) {
    if (node is List) {
      return node
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e.cast<String, dynamic>()))
          .toList();
    }
    return const [];
  }

  int _toInt(dynamic value) => int.tryParse('$value') ?? 0;

  double _toDouble(dynamic value) => double.tryParse('$value') ?? 0;

  Future<Map<String, double>?> _currentDeviceLocation() async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) return null;

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        return null;
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      return {
        'device_latitude': position.latitude,
        'device_longitude': position.longitude,
      };
    } catch (_) {
      return null;
    }
  }
}
