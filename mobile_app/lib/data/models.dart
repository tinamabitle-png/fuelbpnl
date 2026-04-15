class VoucherItem {
  VoucherItem({
    required this.id,
    required this.code,
    required this.status,
    required this.amount,
    required this.fuelType,
    required this.qrCode,
    this.stationId,
    this.stationName,
    this.driverName,
    this.expiresAt,
  });

  final int id;
  final String code;
  final String status;
  final double amount;
  final String fuelType;
  final String qrCode;
  final int? stationId;
  final String? stationName;
  final String? driverName;
  final DateTime? expiresAt;

  factory VoucherItem.fromDriverMap(Map<String, dynamic> map) {
    return VoucherItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      code: (map['code'] ?? '').toString(),
      status: (map['status'] ?? '').toString(),
      amount: double.tryParse('${map['amount'] ?? 0}') ?? 0,
      fuelType: (map['fuel_type'] ?? 'petrol').toString(),
      qrCode: (map['qr_code'] ?? '').toString(),
      stationId: int.tryParse(
        '${map['fuel_station_id'] ?? map['station_id'] ?? ''}',
      ),
      stationName:
          (map['fuel_station']?['name'] ?? map['fuel_station']?['city'])
              ?.toString(),
      driverName: (map['user']?['name'] ?? map['driver']?['name'])?.toString(),
      expiresAt: map['expires_at'] == null
          ? null
          : DateTime.tryParse(map['expires_at'].toString()),
    );
  }

  factory VoucherItem.fromMerchantMap(Map<String, dynamic> map) {
    return VoucherItem(
      id: int.tryParse('${map['voucher_id'] ?? map['id'] ?? 0}') ?? 0,
      code: (map['voucher_code'] ?? map['code'] ?? '').toString(),
      status: (map['status'] ?? '').toString(),
      amount: double.tryParse('${map['amount'] ?? 0}') ?? 0,
      fuelType: (map['fuel_type'] ?? 'petrol').toString(),
      qrCode: (map['qr_code'] ?? '').toString(),
      stationId: int.tryParse(
        '${map['station']?['id'] ?? map['fuelStation']?['id'] ?? map['station_id'] ?? map['fuel_station_id'] ?? ''}',
      ),
      stationName: (map['station']?['name'] ?? map['fuelStation']?['name'])
          ?.toString(),
      driverName: (map['driver']?['name'] ?? map['user']?['name'])?.toString(),
      expiresAt: map['expires_at'] == null
          ? null
          : DateTime.tryParse(map['expires_at'].toString()),
    );
  }
}

class RepaymentItem {
  RepaymentItem({
    required this.id,
    required this.voucherCode,
    required this.amount,
    required this.dueDate,
    required this.status,
  });

  final int id;
  final String voucherCode;
  final double amount;
  final DateTime dueDate;
  final String status;
}

class VirtualCardItem {
  VirtualCardItem({
    required this.id,
    required this.status,
    required this.currency,
    required this.allocatedAmount,
    required this.brand,
    this.maskedPan,
    this.expiryMonth,
    this.expiryYear,
    this.cardScheme,
    this.label,
    this.provider,
  });

  final int id;
  final String status;
  final String currency;
  final double allocatedAmount;
  final String brand;
  final String? maskedPan;
  final int? expiryMonth;
  final int? expiryYear;
  final String? cardScheme;
  final String? label;
  final String? provider;

  factory VirtualCardItem.fromMap(Map<String, dynamic> map) {
    return VirtualCardItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      status: (map['status'] ?? 'active').toString(),
      currency: (map['currency'] ?? 'ZAR').toString(),
      allocatedAmount: double.tryParse('${map['allocated_amount'] ?? 0}') ?? 0,
      brand: (map['brand'] ?? 'generic').toString(),
      maskedPan: (map['masked_pan'] ?? '').toString().trim().isEmpty
          ? null
          : map['masked_pan'].toString(),
      expiryMonth: int.tryParse('${map['expiry_month'] ?? ''}'),
      expiryYear: int.tryParse('${map['expiry_year'] ?? ''}'),
      cardScheme: (map['card_scheme'] ?? '').toString().trim().isEmpty
          ? null
          : map['card_scheme'].toString(),
      label: (map['label'] ?? '').toString().trim().isEmpty
          ? null
          : map['label'].toString(),
      provider: (map['provider'] ?? '').toString().trim().isEmpty
          ? null
          : map['provider'].toString(),
    );
  }
}

class RetailBrandItem {
  RetailBrandItem({required this.slug, required this.name, this.logoUrl});

  final String slug;
  final String name;
  final String? logoUrl;

  factory RetailBrandItem.fromMap(Map<String, dynamic> map) {
    return RetailBrandItem(
      slug: (map['slug'] ?? '').toString(),
      name: (map['name'] ?? '').toString(),
      logoUrl: (map['logo_url'] ?? map['logo'] ?? '').toString().trim().isEmpty
          ? null
          : (map['logo_url'] ?? map['logo']).toString(),
    );
  }
}
