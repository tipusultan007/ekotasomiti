import 'package:intl/intl.dart';

class CurrencyFormatter {
  static final NumberFormat _format = NumberFormat.currency(
    locale: 'bn_BD',
    symbol: '\u09F3',
    decimalDigits: 0,
  );

  static final NumberFormat _formatWithDecimal = NumberFormat.currency(
    locale: 'bn_BD',
    symbol: '\u09F3',
    decimalDigits: 2,
  );

  static String format(num amount) => _format.format(amount);
  static String formatDecimal(num amount) => _formatWithDecimal.format(amount);
  static String simple(num amount) => '৳${amount.toStringAsFixed(0)}';
}
