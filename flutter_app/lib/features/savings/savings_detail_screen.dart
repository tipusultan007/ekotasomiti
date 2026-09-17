import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/models/savings.dart';
import '../../core/offline/hive_boxes.dart';
import '../../features/auth/auth_provider.dart';
import '../../features/members/member_detail_screen.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_drawer.dart';

final savingsDetailProvider = FutureProvider.family<SavingsAccount, int>((ref, id) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.savingsAccounts);
  try {
    final response = await api.dio.get('${ApiEndpoints.savingsAccounts}/$id');
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put('savings_detail_$id', data);
    return SavingsAccount.fromJson(data);
  } catch (e) {
    final cached = box.get('savings_detail_$id');
    if (cached != null) {
      return SavingsAccount.fromJson(Map<String, dynamic>.from(cached as Map));
    }
    rethrow;
  }
});

class SavingsDetailScreen extends ConsumerWidget {
  final int accountId;
  const SavingsDetailScreen({super.key, required this.accountId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final accountAsync = ref.watch(savingsDetailProvider(accountId));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      drawer: const AppDrawer(),
      appBar: AppBar(
        leading: const BackButton(),
        title: const Text(
          'সঞ্চয় হিসাব বিবরণী',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          const ConnectivityBanner(compact: true),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'রিফ্রেশ করুন',
            onPressed: () => ref.invalidate(savingsDetailProvider(accountId)),
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
      body: accountAsync.when(
        loading: () => const AppLoadingIndicator(message: 'হিসাবের বিবরণ লোড হচ্ছে...'),
        error: (e, _) => AppErrorWidget(
          message: 'বিবরণ লোড করা যায়নি: $e',
          onRetry: () => ref.invalidate(savingsDetailProvider(accountId)),
        ),
        data: (account) {
          final transactions = account.transactions ?? [];

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(savingsDetailProvider(accountId)),
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              children: [
                _buildHeroCard(context, account),
                const SizedBox(height: 16),
                _buildInfoCard(context, account),
                const SizedBox(height: 16),
                _buildTransactionsSection(context, transactions),
                const SizedBox(height: 32),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeroCard(BuildContext context, SavingsAccount account) {
    final memberName = account.memberName ?? 'সদস্য';

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF1E3A8A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1E3A8A).withValues(alpha: 0.25),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Top Row: Avatar + Member info & status
          Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: Colors.white.withValues(alpha: 0.15),
                child: Text(
                  memberName.isNotEmpty ? memberName[0].toUpperCase() : 'স',
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
                    InkWell(
                      onTap: account.memberId != null
                          ? () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => MemberDetailScreen(memberId: account.memberId!),
                                ),
                              )
                          : null,
                      child: Row(
                        children: [
                          Flexible(
                            child: Text(
                              memberName,
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (account.memberId != null) ...[
                            const SizedBox(width: 4),
                            Icon(Icons.open_in_new_rounded, size: 13, color: Colors.white.withValues(alpha: 0.6)),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'হিসাব নং: ${account.accountNo}',
                      style: const TextStyle(
                        color: Color(0xFF93C5FD),
                        fontWeight: FontWeight.w600,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
              StatusBadge(status: account.status, isSmall: true),
            ],
          ),

          const Divider(color: Colors.white12, height: 28),

          // Current Balance Label & Value
          const Text(
            'বর্তমান সঞ্চয় স্থিতি (ব্যালেন্স)',
            style: TextStyle(
              color: Colors.white70,
              fontSize: 11,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            CurrencyFormatter.simple(account.currentBalance),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 26,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.5,
            ),
          ),

          const SizedBox(height: 16),

          // Quick metrics row
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
                _buildHeroStat('কিস্তির পরিমাণ', CurrencyFormatter.simple(account.expectedDeposit)),
                Container(width: 1, height: 24, color: Colors.white12),
                _buildHeroStat('সর্বনিম্ন জমা', CurrencyFormatter.simple(account.minDeposit)),
                if (account.programName != null) ...[
                  Container(width: 1, height: 24, color: Colors.white12),
                  _buildHeroStat('স্কিম', account.programName!),
                ],
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

  Widget _buildInfoCard(BuildContext context, SavingsAccount account) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'হিসাবের মৌলিক তথ্য',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 12),
          if (account.memberPhone != null && account.memberPhone!.isNotEmpty)
            _buildDetailRow(
              icon: Icons.phone_outlined,
              label: 'মোবাইল নম্বর',
              value: account.memberPhone!,
              isHighlight: true,
            ),
          if (account.memberNo != null && account.memberNo!.isNotEmpty)
            _buildDetailRow(
              icon: Icons.badge_outlined,
              label: 'সদস্য নম্বর',
              value: account.memberNo!,
            ),
          if (account.areaName != null && account.areaName!.isNotEmpty)
            _buildDetailRow(
              icon: Icons.location_on_outlined,
              label: 'সমিতি / এলাকা',
              value: account.areaName!,
            ),
          if (account.fieldOfficerName != null && account.fieldOfficerName!.isNotEmpty)
            _buildDetailRow(
              icon: Icons.person_outline_rounded,
              label: 'দায়িত্বপ্রাপ্ত অফিসার',
              value: account.fieldOfficerName!,
            ),
          if (account.openingDate != null && account.openingDate!.isNotEmpty)
            _buildDetailRow(
              icon: Icons.calendar_today_outlined,
              label: 'হিসাব খোলার তারিখ',
              value: DateFormatter.full(account.openingDate),
            ),
          if (account.openingBalance != null && account.openingBalance! > 0)
            _buildDetailRow(
              icon: Icons.account_balance_wallet_outlined,
              label: 'প্রারম্ভিক ব্যালেন্স',
              value: CurrencyFormatter.simple(account.openingBalance!),
            ),
        ],
      ),
    );
  }

  Widget _buildDetailRow({
    required IconData icon,
    required String label,
    required String value,
    bool isHighlight = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        children: [
          Icon(icon, size: 16, color: const Color(0xFF64748B)),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: isHighlight ? const Color(0xFF1D4ED8) : const Color(0xFF0F172A),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTransactionsSection(BuildContext context, List<SavingsTransaction> txns) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'সাম্প্রতিক লেনদেনসমূহ',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: Color(0xFF0F172A),
              ),
            ),
            Text(
              '${txns.length}টি লেনদেন',
              style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
            ),
          ],
        ),
        const SizedBox(height: 10),
        if (txns.isEmpty)
          Container(
            padding: const EdgeInsets.all(24),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              children: [
                Icon(Icons.receipt_long_outlined, size: 36, color: Colors.grey.shade400),
                const SizedBox(height: 6),
                Text(
                  'কোনো লেনদেন রেকর্ড পাওয়া যায়নি',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                ),
              ],
            ),
          )
        else
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.02),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: txns.length,
              separatorBuilder: (context, index) => const Divider(height: 1, indent: 52, color: Color(0xFFF1F5F9)),
              itemBuilder: (context, index) {
                final t = txns[index];
                final isDeposit = t.type.toLowerCase() == 'deposit' || t.type.toLowerCase() == 'account_opening';
                final typeTitle = t.type.toLowerCase() == 'deposit'
                    ? 'সঞ্চয় জমা'
                    : (t.type.toLowerCase() == 'account_opening'
                        ? 'হিসাব খোলার ফি'
                        : (t.type.toLowerCase() == 'withdraw' ? 'সঞ্চয় উত্তোলন' : t.type));

                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: isDeposit ? const Color(0xFFECFDF5) : const Color(0xFFFEF2F2),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(
                          isDeposit ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
                          color: isDeposit ? const Color(0xFF059669) : const Color(0xFFDC2626),
                          size: 16,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '${t.txnNo} • $typeTitle',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              DateFormatter.human(t.date, showTime: true),
                              style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                            ),
                          ],
                        ),
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            '${isDeposit ? '+' : '-'}${CurrencyFormatter.simple(t.amount)}',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 13,
                              color: isDeposit ? const Color(0xFF059669) : const Color(0xFFDC2626),
                            ),
                          ),
                          if (t.balanceAfter != null) ...[
                            const SizedBox(height: 2),
                            Text(
                              'স্থিতি: ${CurrencyFormatter.simple(t.balanceAfter!)}',
                              style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
      ],
    );
  }
}
