import 'package:flutter/material.dart';

class StatusBadge extends StatelessWidget {
  final String status;
  final bool isSmall;

  const StatusBadge({super.key, required this.status, this.isSmall = false});

  @override
  Widget build(BuildContext context) {
    final isBn = Localizations.localeOf(context).languageCode == 'bn';
    final (color, label) = _getStyle(isBn);
    return Container(
      padding: EdgeInsets.symmetric(horizontal: isSmall ? 6 : 10, vertical: isSmall ? 2 : 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Text(label, style: TextStyle(color: color, fontSize: isSmall ? 10 : 12, fontWeight: FontWeight.w600)),
    );
  }

  (Color, String) _getStyle(bool isBn) {
    final s = status.toLowerCase();
    return switch (s) {
      'paid' || 'completed' => (const Color(0xFF16A34A), isBn ? 'পরিশোধিত' : 'PAID'),
      'received' => (const Color(0xFF16A34A), isBn ? 'গৃহীত' : 'RECEIVED'),
      'approved' => (const Color(0xFF16A34A), isBn ? 'অনুমোদিত' : 'APPROVED'),
      'active' => (const Color(0xFF16A34A), isBn ? 'সক্রিয়' : 'ACTIVE'),
      'partial' => (const Color(0xFF2563EB), isBn ? 'আংশিক আদায়' : 'PARTIAL'),
      'due' => (const Color(0xFFEA580C), isBn ? 'বকেয়া' : 'DUE'),
      'overdue' => (const Color(0xFFDC2626), isBn ? 'খেলাপি' : 'OVERDUE'),
      'written_off' => (const Color(0xFFDC2626), isBn ? 'অবলোপন' : 'WRITTEN OFF'),
      'pending' => (const Color(0xFFD97706), isBn ? 'অপেক্ষমাণ' : 'PENDING'),
      'submitted' => (const Color(0xFFD97706), isBn ? 'জমা হয়েছে' : 'SUBMITTED'),
      'cancelled' || 'rejected' => (const Color(0xFFDC2626), isBn ? 'বাতিল' : 'CANCELLED'),
      'inactive' => (Colors.grey.shade600, isBn ? 'নিষ্ক্রিয়' : 'INACTIVE'),
      'closed' => (Colors.grey.shade600, isBn ? 'বন্ধ' : 'CLOSED'),
      _ => (Colors.grey.shade700, status.toUpperCase()),
    };
  }
}
