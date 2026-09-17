import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';
import '../../core/api/api_endpoints.dart';
import '../../shared/helpers/error_handler.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/app_drawer.dart';

final receiptProvider = FutureProvider.family<Map<String, dynamic>?, Map<String, String>>((ref, params) async {
  final api = ref.read(apiClientProvider);
  try {
    final response = await api.dio.get('${ApiEndpoints.receipts}/${params['type']}/${params['id']}');
    return response.data;
  } catch (e) {
    return null;
  }
});

class ReceiptScreen extends ConsumerWidget {
  final String type;
  final int id;
  const ReceiptScreen({super.key, required this.type, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final receiptAsync = ref.watch(receiptProvider({'type': type, 'id': '$id'}));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      drawer: const AppDrawer(),
      appBar: AppBar(
        leading: const BackButton(),
        title: const Text('মানি রিসিট / রশিদ', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        elevation: 0,
        actions: [
          receiptAsync.when(
            loading: () => const SizedBox(),
            error: (_, _) => const SizedBox(),
            data: (data) => data != null
                ? IconButton(
                    icon: const Icon(Icons.share_rounded),
                    tooltip: 'শেয়ার করুন',
                    onPressed: () => _shareReceipt(data),
                  )
                : const SizedBox(),
          ),
          Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu_rounded),
              tooltip: 'মেনু',
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
        ],
      ),
      body: receiptAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(AppErrorHandler.getMessage(e, isBn: true), textAlign: TextAlign.center))),
        data: (data) {
          if (data == null) return const Center(child: Text('কোনো রশিদ পাওয়া যায়নি'));
          return _buildReceipt(context, data);
        },
      ),
    );
  }

  void _shareReceipt(Map<String, dynamic> data) {
    final buffer = StringBuffer();
    final grossAmount = (data['gross_amount'] as num?)?.toDouble() ?? ((data['amount'] as num?)?.toDouble() ?? 0);
    final fundAmount = (data['fund_amount'] as num?)?.toDouble() ?? 0.0;
    final fundName = data['fund_name']?.toString() ?? 'কল্যাণ তহবিল';
    final netAmount = (data['amount'] as num?)?.toDouble() ?? 0.0;

    buffer.writeln('--- জমার রশিদ ---');
    buffer.writeln('${data['type_label'] ?? 'কালেকশন'} রশিদ');
    buffer.writeln('রশিদ নং: ${data['receipt_no'] ?? ''}');
    buffer.writeln('তারিখ: ${DateFormatter.human(data['date'], showTime: true)}');
    buffer.writeln('সদস্য: ${data['member_name'] ?? ''} (${data['member_no'] ?? ''})');
    buffer.writeln('আদায়কৃত মোট টাকা: ${CurrencyFormatter.simple(grossAmount > 0 ? grossAmount : netAmount)}');
    if (fundAmount > 0) {
      buffer.writeln('$fundName: ${CurrencyFormatter.simple(fundAmount)}');
      buffer.writeln('সঞ্চয় জমা: ${CurrencyFormatter.simple(netAmount)}');
    }
    if (data['principal_paid'] != null) buffer.writeln('মূল পরিশোধ: ${CurrencyFormatter.simple((data['principal_paid'] as num?)?.toDouble() ?? 0)}');
    if (data['interest_paid'] != null) buffer.writeln('মুনাফা: ${CurrencyFormatter.simple((data['interest_paid'] as num?)?.toDouble() ?? 0)}');
    buffer.writeln('পেমেন্ট মাধ্যম: ${data['payment_method'] ?? 'নগদ'}');
    buffer.writeln('বর্তমান ব্যালেন্স: ${CurrencyFormatter.simple((data['balance_after'] as num?)?.toDouble() ?? 0)}');
    buffer.writeln('---');
    buffer.writeln('${data['org_name'] ?? 'একতা সঞ্চয় ও ঋণদান সমবায় সমিতি'}');
    buffer.writeln('${data['org_address'] ?? ''}');
    SharePlus.instance.share(
      ShareParams(
        text: buffer.toString(),
        subject: 'মানি রিসিট ${data['receipt_no'] ?? ''}',
      ),
    );
  }

  Widget _buildReceipt(BuildContext context, Map<String, dynamic> data) {
    final grossAmount = (data['gross_amount'] as num?)?.toDouble() ?? ((data['amount'] as num?)?.toDouble() ?? 0);
    final fundAmount = (data['fund_amount'] as num?)?.toDouble() ?? 0.0;
    final fundName = data['fund_name']?.toString() ?? 'কল্যাণ তহবিল';
    final netAmount = (data['amount'] as num?)?.toDouble() ?? 0.0;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.03),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            Text(data['org_name'] ?? 'একতা সঞ্চয় ও ঋণদান সমিতি', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
            if (data['org_address'] != null) Text(data['org_address'], style: TextStyle(color: Colors.grey[600], fontSize: 12)),
            if (data['org_phone'] != null) Text(data['org_phone'], style: TextStyle(color: Colors.grey[600], fontSize: 12)),
            const Divider(thickness: 1.5, height: 30),
            Text('${data['type_label'] ?? 'কালেকশন'} রশিদ', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF2563EB))),
            const SizedBox(height: 2),
            Text('রশিদ নং: ${data['receipt_no'] ?? ''}', style: TextStyle(color: Colors.grey[600], fontSize: 12, fontWeight: FontWeight.w600)),
            const SizedBox(height: 16),
            _row('তারিখ ও সময়', DateFormatter.human(data['date'], showTime: true)),
            _row('সদস্যের নাম', '${data['member_name'] ?? ''} (${data['member_no'] ?? ''})'),
            _row('হিসাব নম্বর', '${data['account_no'] ?? ''}'),
            if (data['program'] != null) _row('স্কিম / প্রোগ্রাম', '${data['program']}'),
            const Divider(height: 20),
            _row('আদায়কৃত মোট টাকা', CurrencyFormatter.simple(grossAmount > 0 ? grossAmount : netAmount), bold: true, color: const Color(0xFF059669)),
            if (fundAmount > 0) ...[
              _row(fundName, CurrencyFormatter.simple(fundAmount), color: const Color(0xFFD97706)),
              _row('সঞ্চয় হিসাবে জমা', CurrencyFormatter.simple(netAmount)),
            ],
            if (data['principal_paid'] != null) _row('মূল কিস্তি', CurrencyFormatter.simple((data['principal_paid'] as num?)?.toDouble() ?? 0)),
            if (data['interest_paid'] != null) _row('মুনাফা / সুদ', CurrencyFormatter.simple((data['interest_paid'] as num?)?.toDouble() ?? 0)),
            _row('পেমেন্ট মাধ্যম', data['payment_method'] == 'cash' ? 'নগদ' : '${data['payment_method']}'),
            _row('পূর্ববর্তী স্থিতি', CurrencyFormatter.simple((data['previous_balance'] as num?)?.toDouble() ?? 0)),
            _row('বর্তমান স্থিতি (ব্যালেন্স)', CurrencyFormatter.simple((data['balance_after'] as num?)?.toDouble() ?? 0), bold: true),
            const Divider(height: 20),
            _row('ফিল্ড অফিসার', '${data['field_officer'] ?? '-'}'),
            _row('আদায়কারী / গ্রহণকারী', '${data['received_by'] ?? '-'}'),
            if (data['notes'] != null && data['notes'].toString().isNotEmpty) _row('মন্তব্য', '${data['notes']}'),
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value, {bool bold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Colors.grey[700], fontSize: 13, fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          Text(value, style: TextStyle(fontSize: 13, fontWeight: bold ? FontWeight.bold : FontWeight.w600, color: color ?? const Color(0xFF0F172A))),
        ],
      ),
    );
  }
}
