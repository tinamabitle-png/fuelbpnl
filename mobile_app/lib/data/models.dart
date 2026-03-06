class VoucherItem {
  VoucherItem({
    required this.id,
    required this.code,
    required this.status,
    required this.amount,
    required this.fuelType,
    required this.qrCode,
    this.stationName,
    this.expiresAt,
  });

  final int id;
  final String code;
  final String status;
  final double amount;
  final String fuelType;
  final String qrCode;
  final String? stationName;
  final DateTime? expiresAt;

  factory VoucherItem.fromDriverMap(Map<String, dynamic> map) {
    return VoucherItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      code: (map['code'] ?? '').toString(),
      status: (map['status'] ?? '').toString(),
      amount: double.tryParse('${map['amount'] ?? 0}') ?? 0,
      fuelType: (map['fuel_type'] ?? 'petrol').toString(),
      qrCode: (map['qr_code'] ?? '').toString(),
      stationName:
          (map['fuel_station']?['name'] ?? map['fuel_station']?['city'])
              ?.toString(),
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
      stationName: (map['station']?['name'] ?? map['fuelStation']?['name'])
          ?.toString(),
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
