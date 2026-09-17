import 'package:flutter/material.dart';
import '../helpers/currency_formatter.dart';

class CurrencyText extends StatelessWidget {
  final double amount;
  final TextStyle? style;
  final bool showSign;

  const CurrencyText(this.amount, {super.key, this.style, this.showSign = false});

  @override
  Widget build(BuildContext context) {
    final formatted = CurrencyFormatter.simple(amount);
    final display = showSign && amount > 0 ? '+$formatted' : formatted;
    return Text(display, style: style);
  }
}
