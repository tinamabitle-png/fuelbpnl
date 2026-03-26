// Central place for currency display formatting in the app.
//
// Backend uses ISO code "ZAR", but South African users expect the "R" symbol.

String currencySymbol([String? currencyCode]) {
  final code = (currencyCode ?? 'ZAR').trim().toUpperCase();
  if (code == 'ZAR') return 'R';
  return code.isEmpty ? 'R' : code;
}

String formatMoney(num amount, {String? currencyCode}) {
  final symbol = currencySymbol(currencyCode);
  final v = amount.isFinite ? amount : 0;
  return '$symbol ${v.toStringAsFixed(2)}';
}

