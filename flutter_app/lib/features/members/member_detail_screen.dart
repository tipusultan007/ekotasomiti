import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../features/auth/auth_provider.dart';
import '../../core/models/member.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/member_avatar.dart';
import '../../shared/widgets/app_drawer.dart';
import 'member_edit_screen.dart';

final memberDetailProvider = FutureProvider.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.members);
  try {
    final response = await api.dio.get('${ApiEndpoints.members}/$id');
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put('member_detail_$id', data);
    return data;
  } catch (e) {
    final cached = box.get('member_detail_$id');
    if (cached != null) return Map<String, dynamic>.from(cached as Map);
    rethrow;
  }
});

class MemberDetailScreen extends ConsumerWidget {
  final int memberId;
  const MemberDetailScreen({super.key, required this.memberId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final memberAsync = ref.watch(memberDetailProvider(memberId));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      drawer: const AppDrawer(),
      appBar: AppBar(
        leading: const BackButton(),
        title: const Text(
          'সদস্যের বিস্তারিত প্রোফাইল',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          const ConnectivityBanner(compact: true),
          if (memberAsync.hasValue && memberAsync.value != null)
            IconButton(
              icon: const Icon(Icons.edit_outlined),
              tooltip: 'প্রোফাইল সম্পাদন',
              onPressed: () {
                final m = Member.fromJson(Map<String, dynamic>.from(memberAsync.value!['member'] as Map));
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MemberEditScreen(member: m)),
                );
              },
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'রিফ্রেশ করুন',
            onPressed: () => ref.invalidate(memberDetailProvider(memberId)),
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
      body: memberAsync.when(
        loading: () => const AppLoadingIndicator(message: 'সদস্যের তথ্য লোড হচ্ছে...'),
        error: (e, _) => AppErrorWidget(
          message: 'তথ্য লোড করা যায়নি: $e',
          onRetry: () => ref.invalidate(memberDetailProvider(memberId)),
        ),
        data: (data) {
          final m = Member.fromJson(Map<String, dynamic>.from(data['member'] as Map));
          final rawTxns = (data['recent_transactions'] as List?) ?? [];
          final transactions = rawTxns.map((t) => Map<String, dynamic>.from(t as Map)).toList();

          final totalSavings = m.savingsAccounts?.fold<double>(0, (sum, a) => sum + a.currentBalance) ?? 0.0;
          final totalLoanOutstanding = m.loans?.fold<double>(0, (sum, l) => sum + l.outstanding) ?? 0.0;

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(memberDetailProvider(memberId)),
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              children: [
                _buildHeroProfile(context, m),
                const SizedBox(height: 14),
                _buildFinancialSummary(totalSavings, totalLoanOutstanding),
                const SizedBox(height: 16),
                _buildPersonalInfoCard(context, m),
                if (m.savingsAccounts != null && m.savingsAccounts!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _buildSavingsSection(context, m.savingsAccounts!),
                ],
                if (m.loans != null && m.loans!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _buildLoansSection(context, m.loans!),
                ],
                if (transactions.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _buildTransactionsSection(context, transactions),
                ],
                const SizedBox(height: 32),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeroProfile(BuildContext context, Member m) {
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
            color: const Color(0xFF1E3A8A).withValues(alpha: 0.2),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(18),
      child: Column(
        children: [
          Row(
            children: [
              // Large Avatar with Member Image & Camera Badge
              MemberAvatar(
                name: m.name,
                photoUrl: m.photoUrl,
                radius: 29,
                fontSize: 22,
                backgroundColor: const Color(0xFF2563EB),
                border: Border.all(color: Colors.white.withValues(alpha: 0.35), width: 2.5),
                showCameraBadge: true,
                onCameraTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MemberEditScreen(member: m)),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      m.name,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            m.memberNo,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),
                        if (m.area != null) ...[
                          const SizedBox(width: 8),
                          Text(
                            m.area!.name,
                            style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              StatusBadge(status: m.status),
            ],
          ),
          const Divider(color: Colors.white12, height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              if (m.mobile != null && m.mobile!.isNotEmpty)
                Row(
                  children: [
                    const Icon(Icons.phone_outlined, size: 13, color: Colors.white70),
                    const SizedBox(width: 4),
                    Text(
                      m.mobile!,
                      style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w600),
                    ),
                  ],
                )
              else
                const SizedBox.shrink(),
              InkWell(
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MemberEditScreen(member: m)),
                ),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.white24),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.edit_outlined, size: 13, color: Colors.white),
                      SizedBox(width: 4),
                      Text('প্রোফাইল পরিবর্তন', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFinancialSummary(double totalSavings, double totalLoans) {
    return Row(
      children: [
        // Total Savings Box
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFE2E8F0)),
              boxShadow: [
                BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 6, offset: const Offset(0, 2)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(5),
                      decoration: BoxDecoration(
                        color: const Color(0xFFECFDF5),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Icon(Icons.savings_outlined, color: Color(0xFF059669), size: 16),
                    ),
                    const SizedBox(width: 6),
                    Text('মোট সঞ্চয় জমা', style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  CurrencyFormatter.simple(totalSavings),
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                    color: Color(0xFF059669),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(width: 10),
        // Total Loans Box
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFE2E8F0)),
              boxShadow: [
                BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 6, offset: const Offset(0, 2)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(5),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF0FDFA),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Icon(Icons.monetization_on_outlined, color: Color(0xFF0D9488), size: 16),
                    ),
                    const SizedBox(width: 6),
                    Text('অবশিষ্ট লোন স্থিতি', style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  CurrencyFormatter.simple(totalLoans),
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                    color: Color(0xFF0D9488),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildPersonalInfoCard(BuildContext context, Member m) {
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'ব্যক্তিগত তথ্য',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
              ),
              InkWell(
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MemberEditScreen(member: m)),
                ),
                borderRadius: BorderRadius.circular(6),
                child: const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.edit_outlined, size: 13, color: Color(0xFF2563EB)),
                      SizedBox(width: 3),
                      Text(
                        'সম্পাদন',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF2563EB),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (m.nameBn != null && m.nameBn!.isNotEmpty) ...[
            _buildInfoRow(Icons.translate_rounded, 'বাংলা নাম', m.nameBn!),
            const Divider(height: 16, color: Color(0xFFF1F5F9)),
          ],
          _buildInfoRow(Icons.phone_outlined, 'মোবাইল নম্বর', m.mobile ?? 'দেওয়া হয়নি', isPhone: true),
          const Divider(height: 16, color: Color(0xFFF1F5F9)),
          _buildInfoRow(Icons.badge_outlined, 'জাতীয় পরিচয়পত্র (NID)', m.nid ?? '-'),
          const Divider(height: 16, color: Color(0xFFF1F5F9)),
          if (m.gender != null && m.gender!.isNotEmpty) ...[
            _buildInfoRow(Icons.wc_outlined, 'লিঙ্গ', m.gender == 'female' ? 'মহিলা' : (m.gender == 'male' ? 'পুরুষ' : m.gender!)),
            const Divider(height: 16, color: Color(0xFFF1F5F9)),
          ],
          if (m.dob != null && m.dob!.isNotEmpty) ...[
            _buildInfoRow(Icons.cake_outlined, 'জন্ম তারিখ', DateFormatter.display(m.dob!)),
            const Divider(height: 16, color: Color(0xFFF1F5F9)),
          ],
          if (m.occupation != null && m.occupation!.isNotEmpty) ...[
            _buildInfoRow(Icons.work_outline_rounded, 'পেশা', m.occupation!),
            const Divider(height: 16, color: Color(0xFFF1F5F9)),
          ],
          if (m.fatherHusbandName != null && m.fatherHusbandName!.isNotEmpty) ...[
            _buildInfoRow(Icons.family_restroom_outlined, 'পিতা / স্বামীর নাম', m.fatherHusbandName!),
            const Divider(height: 16, color: Color(0xFFF1F5F9)),
          ],
          _buildInfoRow(Icons.location_on_outlined, 'ঠিকানা', m.address ?? '-'),
          const Divider(height: 16, color: Color(0xFFF1F5F9)),
          _buildInfoRow(Icons.map_outlined, 'সমিতি / এলাকা', m.area?.name ?? '-'),
          const Divider(height: 16, color: Color(0xFFF1F5F9)),
          _buildInfoRow(Icons.person_pin_outlined, 'দায়িত্বপ্রাপ্ত অফিসার', m.fieldOfficer?.name ?? '-'),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value, {bool isPhone = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: const Color(0xFF64748B)),
        const SizedBox(width: 10),
        SizedBox(
          width: 120,
          child: Text(
            label,
            style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: isPhone ? const Color(0xFF1D4ED8) : const Color(0xFF0F172A),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSavingsSection(BuildContext context, List<SavingsAccountInfo> accounts) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'সঞ্চয় হিসাবসমূহ',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '${accounts.length}টি সক্রিয়',
                style: const TextStyle(color: Color(0xFF2563EB), fontSize: 11, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        ...accounts.map((a) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF2563EB), size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          a.accountNo,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1D4ED8)),
                        ),
                        Text(
                          a.programName ?? 'সঞ্চয় স্কিম',
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        CurrencyFormatter.simple(a.currentBalance),
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF059669)),
                      ),
                      Text('ব্যালেন্স', style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
                    ],
                  ),
                ],
              ),
            )),
      ],
    );
  }

  Widget _buildLoansSection(BuildContext context, List<LoanInfo> loans) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'লোন হিসাব বিবরণী',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDFA),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '${loans.length}টি লোন',
                style: const TextStyle(color: Color(0xFF0D9488), fontSize: 11, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        ...loans.map((l) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF0FDFA),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.monetization_on_rounded, color: Color(0xFF0D9488), size: 20),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              l.loanNo,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F766E)),
                            ),
                            Text(
                              l.productName ?? 'লোন প্রোডাক্ট',
                              style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                            ),
                          ],
                        ),
                      ),
                      StatusBadge(status: l.status, isSmall: true),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _buildLoanSubStat('মূল ঋণ', CurrencyFormatter.simple(l.principalAmount)),
                        _buildLoanSubStat('কিস্তির পরিমাণ', CurrencyFormatter.simple(l.installmentAmount)),
                        _buildLoanSubStat('অবশিষ্ট বকেয়া', CurrencyFormatter.simple(l.outstanding), isPrimary: true),
                      ],
                    ),
                  ),
                ],
              ),
            )),
      ],
    );
  }

  Widget _buildLoanSubStat(String label, String value, {bool isPrimary = false}) {
    return Column(
      children: [
        Text(label, style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
        const SizedBox(height: 1),
        Text(
          value,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: isPrimary ? const Color(0xFF0D9488) : const Color(0xFF0F172A),
          ),
        ),
      ],
    );
  }

  Widget _buildTransactionsSection(BuildContext context, List<Map<String, dynamic>> transactions) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'সাম্প্রতিক লেনদেনসমূহ',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
        ),
        const SizedBox(height: 10),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            children: transactions.asMap().entries.map((entry) {
              final idx = entry.key;
              final t = entry.value;
              final isDepositOrRepay = t['type'] == 'deposit' || t['type'] == 'repayment';
              final amount = double.tryParse('${t['amount'] ?? 0}') ?? 0;
              final typeName = t['type'] == 'deposit'
                  ? 'সঞ্চয় জমা'
                  : (t['type'] == 'repayment' ? 'লোন কিস্তি' : (t['type'] == 'withdraw' ? 'সঞ্চয় উত্তোলন' : t['type']));

              return Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(7),
                          decoration: BoxDecoration(
                            color: isDepositOrRepay ? const Color(0xFFECFDF5) : const Color(0xFFFEF2F2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Icon(
                            isDepositOrRepay ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
                            color: isDepositOrRepay ? const Color(0xFF059669) : const Color(0xFFDC2626),
                            size: 16,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${t['txn_no'] ?? ''} • $typeName',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                              ),
                              Text(
                                DateFormatter.human(t['date'] ?? t['txn_date'] ?? t['created_at'], showTime: true),
                                style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                              ),
                            ],
                          ),
                        ),
                        Text(
                          '${isDepositOrRepay ? '+' : '-'}${CurrencyFormatter.simple(amount)}',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                            color: isDepositOrRepay ? const Color(0xFF059669) : const Color(0xFFDC2626),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (idx < transactions.length - 1)
                    const Divider(height: 1, indent: 48, color: Color(0xFFF1F5F9)),
                ],
              );
            }).toList(),
          ),
        ),
      ],
    );
  }
}
