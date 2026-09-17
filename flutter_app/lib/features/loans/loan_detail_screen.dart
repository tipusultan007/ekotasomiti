import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../features/auth/auth_provider.dart';
import '../../core/models/loan.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/app_drawer.dart';

final loanDetailProvider = FutureProvider.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.loans);
  try {
    final response = await api.dio.get('${ApiEndpoints.loans}/$id');
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put('loan_detail_$id', data);
    return data;
  } catch (e) {
    final cached = box.get('loan_detail_$id');
    if (cached != null) return Map<String, dynamic>.from(cached as Map);
    rethrow;
  }
});

class LoanDetailScreen extends ConsumerWidget {
  final int loanId;
  const LoanDetailScreen({super.key, required this.loanId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final loanAsync = ref.watch(loanDetailProvider(loanId));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      drawer: const AppDrawer(),
      appBar: AppBar(
        leading: const BackButton(),
        title: const Text(
          'ঋণ / লোন বিস্তারিত',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          const ConnectivityBanner(compact: true),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'রিফ্রেশ করুন',
            onPressed: () => ref.invalidate(loanDetailProvider(loanId)),
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
      body: loanAsync.when(
        loading: () => const AppLoadingIndicator(message: 'লোন বিবরণ লোড হচ্ছে...'),
        error: (e, _) => AppErrorWidget(
          message: 'বিবরণ লোড করা যায়নি: $e',
          onRetry: () => ref.invalidate(loanDetailProvider(loanId)),
        ),
        data: (data) {
          final loan = Loan.fromJson(data);
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(loanDetailProvider(loanId)),
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              children: [
                _buildHeroCard(context, loan),
                const SizedBox(height: 16),
                _buildSummaryCard(context, loan),
                if (loan.schedules != null && loan.schedules!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _buildSchedule(context, loan.schedules!),
                ],
                if (loan.transactions != null && loan.transactions!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _buildTransactions(context, loan.transactions!),
                ],
                const SizedBox(height: 32),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeroCard(BuildContext context, Loan loan) {
    final memberName = loan.memberName ?? 'সদস্য';

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF0F766E)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F766E).withValues(alpha: 0.25),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: Colors.white.withValues(alpha: 0.15),
                child: Text(
                  memberName.isNotEmpty ? memberName[0].toUpperCase() : 'ল',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      memberName,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'লোন নম্বর: ${loan.loanNo}',
                      style: const TextStyle(
                        color: Color(0xFF99F6E4),
                        fontWeight: FontWeight.w600,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
              StatusBadge(status: loan.status, isSmall: true),
            ],
          ),

          const Divider(color: Colors.white12, height: 28),

          const Text(
            'অবশিষ্ট বকেয়া (আদায়যোগ্য)',
            style: TextStyle(
              color: Colors.white70,
              fontSize: 11,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            CurrencyFormatter.simple(loan.outstanding),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 26,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.5,
            ),
          ),

          const SizedBox(height: 16),

          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _buildHeroStat('মূল ঋণ', CurrencyFormatter.simple(loan.principalAmount)),
                Container(width: 1, height: 24, color: Colors.white12),
                _buildHeroStat('কিস্তি পরিমাণ', CurrencyFormatter.simple(loan.installmentAmount)),
                Container(width: 1, height: 24, color: Colors.white12),
                _buildHeroStat('মোট পরিশোধ', CurrencyFormatter.simple(loan.totalPaid)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeroStat(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(color: Colors.white60, fontSize: 10, fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
        ),
      ],
    );
  }

  Widget _buildSummaryCard(BuildContext context, Loan loan) {
    final progress = loan.principalAmount > 0 ? loan.totalPaid / loan.principalAmount : 0.0;
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 6, offset: const Offset(0, 2)),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'পরিশোধের অগ্রগতি',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: progress.clamp(0, 1),
              minHeight: 10,
              backgroundColor: const Color(0xFFE2E8F0),
              valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF0D9488)),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${(progress * 100).toStringAsFixed(1)}% পরিশোধ সম্পন্ন',
                style: const TextStyle(color: Color(0xFF0D9488), fontWeight: FontWeight.bold, fontSize: 12),
              ),
              Text(
                'প্রোডাক্ট: ${loan.productName ?? '-'}',
                style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSchedule(BuildContext context, List<LoanSchedule> schedules) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 6, offset: const Offset(0, 2)),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'কিস্তির সময়সূচি (Repayment Schedule)',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 12),
          Table(
            columnWidths: const {
              0: FlexColumnWidth(0.8),
              1: FlexColumnWidth(1.6),
              2: FlexColumnWidth(1.2),
              3: FlexColumnWidth(1.2),
              4: FlexColumnWidth(1.2),
            },
            children: [
              TableRow(
                decoration: const BoxDecoration(
                  border: Border(bottom: BorderSide(color: Color(0xFFCBD5E1))),
                ),
                children: [
                  _tableHeader('নং'),
                  _tableHeader('তারিখ'),
                  _tableHeader('কিস্তি'),
                  _tableHeader('জমা'),
                  _tableHeader('অবস্থা'),
                ],
              ),
              ...schedules.map((s) => TableRow(
                    decoration: const BoxDecoration(
                      border: Border(bottom: BorderSide(color: Color(0xFFF1F5F9))),
                    ),
                    children: [
                      _tableCell('${s.installmentNo}'),
                      _tableCell(DateFormatter.display(s.dueDate)),
                      _tableCell(CurrencyFormatter.simple(s.total)),
                      _tableCell(CurrencyFormatter.simple(s.paid)),
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 4),
                        child: StatusBadge(status: s.status, isSmall: true),
                      ),
                    ],
                  )),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTransactions(BuildContext context, List<LoanTransaction> txns) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 6, offset: const Offset(0, 2)),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'লোন লেনদেন বিবরণী (${txns.length})',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 10),
          ...txns.map((t) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: t.type == 'repayment' ? const Color(0xFFECFDF5) : const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(
                        t.type == 'repayment' ? Icons.check_circle_outline_rounded : Icons.monetization_on_outlined,
                        color: t.type == 'repayment' ? const Color(0xFF059669) : const Color(0xFF2563EB),
                        size: 18,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${t.txnNo} • ${t.type == 'repayment' ? 'কিস্তি আদায়' : 'ঋণ বিতরণ'}',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                          ),
                          Text(
                            DateFormatter.human(t.date, showTime: true),
                            style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                          ),
                        ],
                      ),
                    ),
                    Text(
                      CurrencyFormatter.simple(t.amount),
                      style: TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 13,
                        color: t.type == 'repayment' ? const Color(0xFF059669) : const Color(0xFF2563EB),
                      ),
                    ),
                  ],
                ),
              )),
        ],
      ),
    );
  }

  Widget _tableHeader(String text) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 2),
        child: Text(text, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF475569))),
      );

  Widget _tableCell(String text) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 2),
        child: Text(text, style: const TextStyle(fontSize: 11, color: Color(0xFF0F172A))),
      );
}
