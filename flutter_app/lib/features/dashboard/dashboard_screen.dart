import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/app_drawer.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../auth/auth_provider.dart';
import '../settings/locale_provider.dart';
import '../members/members_screen.dart';
import '../collection/savings_collection_screen.dart';
import '../collection/loan_collection_screen.dart';
import '../collection/collection_history_screen.dart';
import '../settlement/settlement_screen.dart';
import '../collection_sheet/collection_sheet_screen.dart';
import '../loan_application/loan_application_screen.dart';
import '../savings/savings_list_screen.dart';
import '../loans/loans_list_screen.dart';

final dashboardProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.dashboard);
  try {
    final response = await api.dio.get(ApiEndpoints.dashboard);
    await box.put('summary', response.data);
    return response.data;
  } catch (e) {
    final cached = box.get('summary');
    if (cached != null) {
      return Map<String, dynamic>.from(cached);
    }
    if (e is DioException) {
      final msg = e.response?.data is Map ? (e.response!.data['message'] ?? e.message) : e.message;
      throw Exception(msg ?? 'Dashboard failed to load');
    }
    throw Exception('Dashboard failed to load: $e');
  }
});

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final dashboardAsync = ref.watch(dashboardProvider);
    final isBn = context.isBn;
    final userName = authState.user?.name ?? (isBn ? 'ফিল্ড অফিসার' : 'Field Officer');

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: const Color(0xFF2563EB).withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.account_balance_rounded, color: Color(0xFF2563EB), size: 20),
            ),
            const SizedBox(width: 8),
            Text(
              isBn ? 'একতা' : 'Ekota',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A)),
            ),
          ],
        ),
        elevation: 0,
        backgroundColor: Colors.white,
        iconTheme: const IconThemeData(color: Color(0xFF0F172A)),
        actions: [
          const ConnectivityBanner(compact: true),
          IconButton(
            icon: const Icon(Icons.language_rounded, size: 22),
            tooltip: isBn ? 'ভাষা পরিবর্তন' : 'Change Language',
            onPressed: () => ref.read(localeProvider.notifier).toggle(),
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, size: 22),
            tooltip: isBn ? 'রিফ্রেশ করুন' : 'Refresh',
            onPressed: () => ref.invalidate(dashboardProvider),
          ),
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: PopupMenuButton<String>(
              icon: CircleAvatar(
                radius: 16,
                backgroundColor: const Color(0xFFEFF6FF),
                child: Text(
                  userName.isNotEmpty ? userName[0].toUpperCase() : 'O',
                  style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2563EB), fontSize: 14),
                ),
              ),
              offset: const Offset(0, 45),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              onSelected: (value) {
                if (value == 'logout') {
                  ref.read(authProvider.notifier).logout();
                }
              },
              itemBuilder: (context) => [
                PopupMenuItem(
                  enabled: false,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(userName, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      Text(authState.user?.email ?? (isBn ? 'ফিল্ড অফিসার' : 'Field Officer'), style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                      const Divider(),
                    ],
                  ),
                ),
                PopupMenuItem(
                  value: 'logout',
                  child: Row(
                    children: [
                      const Icon(Icons.logout_rounded, size: 18, color: Colors.red),
                      const SizedBox(width: 8),
                      Text(isBn ? 'লগআউট' : 'Logout', style: const TextStyle(color: Colors.red, fontWeight: FontWeight.w600)),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
      drawer: const AppDrawer(),
      body: dashboardAsync.when(
        loading: () => const Center(
          child: CircularProgressIndicator(color: Color(0xFF2563EB)),
        ),
        error: (e, _) {
          final msg = e.toString().replaceFirst('Exception: ', '');
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(Icons.cloud_off_rounded, size: 48, color: Colors.red.shade600),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    isBn ? 'ড্যাশবোর্ড লোড করা সম্ভব হয়নি' : 'Failed to load dashboard',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey.shade800),
                  ),
                  const SizedBox(height: 8),
                  Text(msg, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                  const SizedBox(height: 20),
                  ElevatedButton.icon(
                    onPressed: () => ref.invalidate(dashboardProvider),
                    icon: const Icon(Icons.refresh),
                    label: Text(isBn ? 'আবার চেষ্টা করুন' : 'Try Again'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
        data: (data) => Column(
          children: [
            const ConnectivityBanner(),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async => ref.invalidate(dashboardProvider),
                color: const Color(0xFF2563EB),
                child: ListView(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  children: [
                    _buildHeroOfficerCard(context, userName, data, isBn),
                    const SizedBox(height: 16),
                    _buildCollectionHub(context, isBn),
                    const SizedBox(height: 16),
                    _buildKpiMetricsGrid(context, data, isBn),
                    const SizedBox(height: 16),
                    _buildManagementSection(context, isBn),
                    const SizedBox(height: 16),
                    _buildWeeklyChart(context, data, isBn),
                    const SizedBox(height: 32),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeroOfficerCard(BuildContext context, String officerName, Map<String, dynamic> data, bool isBn) {
    final todayCollected = (data['today_collection'] ?? 0).toDouble();
    final expectedToday = (data['expected_today'] ?? 0).toDouble();
    final cashInHand = (data['cash_in_hand'] ?? 0).toDouble();
    final percent = expectedToday > 0 ? (todayCollected / expectedToday).clamp(0.0, 1.0) : 0.0;

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
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Greeting & Date Row
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    isBn ? 'স্বাগতম,' : 'Welcome,',
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12),
                  ),
                  Text(
                    officerName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      letterSpacing: -0.2,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.today_rounded, color: Colors.white70, size: 13),
                    const SizedBox(width: 4),
                    Text(
                      DateFormatter.display(DateTime.now()),
                      style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Main Collected Amount Banner
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isBn ? "আজকের মোট আদায়" : "Today's Total Collection",
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.8),
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.5,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.4)),
                      ),
                      child: Text(
                        isBn ? 'লক্ষ্যমাত্রার ${(percent * 100).toStringAsFixed(0)}%' : '${(percent * 100).toStringAsFixed(0)}% of Target',
                        style: const TextStyle(color: Color(0xFF34D399), fontSize: 10, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  CurrencyFormatter.simple(todayCollected),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 26,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -0.5,
                  ),
                ),
                const SizedBox(height: 10),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: percent,
                    minHeight: 5,
                    backgroundColor: Colors.white.withValues(alpha: 0.15),
                    valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF10B981)),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          // Two sub-metrics: Cash in Hand & Expected Today
          Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF59E0B).withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.account_balance_wallet_rounded, color: Color(0xFFFBBF24), size: 16),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isBn ? 'হাতে থাকা ক্যাশ' : 'Cash in Hand',
                              style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 10),
                            ),
                            Text(
                              CurrencyFormatter.simple(cashInHand),
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: const Color(0xFF38BDF8).withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.track_changes_rounded, color: Color(0xFF38BDF8), size: 16),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isBn ? 'আজকের সম্ভাব্য আদায়' : 'Expected Today',
                              style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 10),
                            ),
                            Text(
                              CurrencyFormatter.simple(expectedToday),
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
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

  Widget _buildCollectionHub(BuildContext context, bool isBn) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                const Icon(Icons.flash_on_rounded, size: 18, color: Color(0xFF2563EB)),
                const SizedBox(width: 6),
                Text(
                  isBn ? 'কালেকশন হাব' : 'Collection Hub',
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
              ],
            ),
            InkWell(
              onTap: () => _navigate(context, const CollectionHistoryScreen()),
              child: Text(
                isBn ? 'হিস্ট্রি ›' : 'History ›',
                style: const TextStyle(color: Color(0xFF2563EB), fontSize: 13, fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildCollectionCategoryCard(
                context,
                title: isBn ? 'সঞ্চয় আদায়' : 'Savings Collection',
                subtitle: isBn ? 'সঞ্চয় জমা নিন' : 'Collect deposit',
                icon: Icons.savings_rounded,
                primaryColor: const Color(0xFF2563EB),
                bgColor: const Color(0xFFEFF6FF),
                frequencies: ['daily', 'weekly', 'monthly'],
                isBn: isBn,
                onFrequencyTap: (freq) => _navigate(context, SavingsCollectionScreen(frequency: freq)),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildCollectionCategoryCard(
                context,
                title: isBn ? 'লোন কিস্তি আদায়' : 'Loan Collection',
                subtitle: isBn ? 'কিস্তি গ্রহণ করুন' : 'Collect installment',
                icon: Icons.monetization_on_rounded,
                primaryColor: const Color(0xFF0D9488),
                bgColor: const Color(0xFFF0FDFA),
                frequencies: ['daily', 'weekly', 'monthly'],
                isBn: isBn,
                onFrequencyTap: (freq) => _navigate(context, LoanCollectionScreen(frequency: freq)),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildQuickActionStrip(
                context,
                title: isBn ? 'কালেকশন শিট' : 'Collection Sheet',
                subtitle: isBn ? 'একসাথে শিট এন্ট্রি' : 'Bulk center entry',
                icon: Icons.list_alt_rounded,
                iconColor: const Color(0xFF4F46E5),
                bgColor: const Color(0xFFEEF2FF),
                onTap: () => _navigate(context, const CollectionSheetScreen()),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildQuickActionStrip(
                context,
                title: isBn ? 'ক্যাশ সেটেলমেন্ট' : 'Cash Settlement',
                subtitle: isBn ? 'ভল্টে ক্যাশ জমা' : 'Deposit vault cash',
                icon: Icons.receipt_long_rounded,
                iconColor: const Color(0xFFD97706),
                bgColor: const Color(0xFFFFFBEB),
                onTap: () => _navigate(context, const SettlementScreen()),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildCollectionCategoryCard(
    BuildContext context, {
    required String title,
    required String subtitle,
    required IconData icon,
    required Color primaryColor,
    required Color bgColor,
    required List<String> frequencies,
    required bool isBn,
    required Function(String) onFrequencyTap,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: bgColor,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: primaryColor, size: 20),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                    ),
                    Text(
                      subtitle,
                      style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 8),
          Row(
            children: frequencies.map((freq) {
              final label = freq == 'daily'
                  ? (isBn ? 'দৈনিক' : 'Daily')
                  : (freq == 'weekly' ? (isBn ? 'সাপ্তাহিক' : 'Weekly') : (isBn ? 'মাসিক' : 'Monthly'));
              return Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 2),
                  child: InkWell(
                    onTap: () => onFrequencyTap(freq),
                    borderRadius: BorderRadius.circular(6),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      decoration: BoxDecoration(
                        color: bgColor,
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        label,
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: primaryColor,
                        ),
                      ),
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActionStrip(
    BuildContext context, {
    required String title,
    required String subtitle,
    required IconData icon,
    required Color iconColor,
    required Color bgColor,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.02),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: bgColor,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: iconColor, size: 20),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                  ),
                  Text(
                    subtitle,
                    style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildKpiMetricsGrid(BuildContext context, Map<String, dynamic> data, bool isBn) {
    final assignedMembers = data['assigned_members'] ?? 0;
    final activeSavings = data['active_savings_accounts'] ?? 0;
    final overdueLoans = data['overdue_loans'] ?? 0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          isBn ? 'পোর্টফোলিও একনজরে' : 'Portfolio Overview',
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildMetricTile(
                context,
                title: isBn ? 'সদস্যবৃন্দ' : 'Members',
                value: '$assignedMembers',
                subtitle: isBn ? 'নিবন্ধিত সদস্য' : 'Assigned Members',
                icon: Icons.people_alt_rounded,
                color: const Color(0xFF2563EB),
                bgColor: const Color(0xFFEFF6FF),
                onTap: () => _navigate(context, const MembersScreen()),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildMetricTile(
                context,
                title: isBn ? 'সঞ্চয় হিসাব' : 'Savings Accs',
                value: '$activeSavings',
                subtitle: isBn ? 'সক্রিয় একাউন্ট' : 'Active Accounts',
                icon: Icons.account_balance_wallet_rounded,
                color: const Color(0xFF059669),
                bgColor: const Color(0xFFECFDF5),
                onTap: () => _navigate(context, const SavingsListScreen()),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildMetricTile(
                context,
                title: isBn ? 'খেলাপি লোন' : 'Overdue Loans',
                value: '$overdueLoans',
                subtitle: overdueLoans > 0
                    ? (isBn ? 'জরুরি ব্যবস্থা নিন' : 'Action Required')
                    : (isBn ? 'সকল লোন নিয়মিত' : 'All loans on track'),
                icon: Icons.warning_amber_rounded,
                color: overdueLoans > 0 ? const Color(0xFFDC2626) : const Color(0xFF64748B),
                bgColor: overdueLoans > 0 ? const Color(0xFFFEF2F2) : const Color(0xFFF1F5F9),
                onTap: () => _navigate(context, const LoansListScreen()),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildMetricTile(
                context,
                title: isBn ? 'নতুন আবেদন' : 'Loan Apply',
                value: '+',
                subtitle: isBn ? 'সদস্য লোন আবেদন' : 'Apply for member',
                icon: Icons.post_add_rounded,
                color: const Color(0xFF7C3AED),
                bgColor: const Color(0xFFF5F3FF),
                onTap: () => _navigate(context, const LoanApplicationScreen()),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildMetricTile(
    BuildContext context, {
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color color,
    required Color bgColor,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.02),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(8)),
                  child: Icon(icon, color: color, size: 18),
                ),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
            ),
            Text(
              subtitle,
              style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildManagementSection(BuildContext context, bool isBn) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          isBn ? 'দ্রুত নেভিগেশন' : 'Quick Navigation',
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
        ),
        const SizedBox(height: 10),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            children: [
              _buildNavRow(
                context,
                title: isBn ? 'সদস্য তালিকা' : 'Member Directory',
                subtitle: isBn ? 'সদস্য অনুসন্ধান ও তথ্য দেখুন' : 'Search members & details',
                icon: Icons.person_search_rounded,
                iconColor: const Color(0xFF2563EB),
                onTap: () => _navigate(context, const MembersScreen()),
              ),
              const Divider(height: 1, indent: 56, color: Color(0xFFF1F5F9)),
              _buildNavRow(
                context,
                title: isBn ? 'সঞ্চয় হিসাবসমূহ' : 'Savings Accounts',
                subtitle: isBn ? 'সক্রিয় সঞ্চয় হিসাবসমূহ দেখুন' : 'View all savings accounts',
                icon: Icons.account_balance_wallet_outlined,
                iconColor: const Color(0xFF0D9488),
                onTap: () => _navigate(context, const SavingsListScreen()),
              ),
              const Divider(height: 1, indent: 56, color: Color(0xFFF1F5F9)),
              _buildNavRow(
                context,
                title: isBn ? 'চলমান লোন তালিকা' : 'Active Loans List',
                subtitle: isBn ? 'লোনের কিস্তি ও পরিশোধ বিবরণী' : 'Repayment & installment details',
                icon: Icons.monetization_on_outlined,
                iconColor: const Color(0xFFEA580C),
                onTap: () => _navigate(context, const LoansListScreen()),
              ),
              const Divider(height: 1, indent: 56, color: Color(0xFFF1F5F9)),
              _buildNavRow(
                context,
                title: isBn ? 'কালেকশন হিস্ট্রি ও রশিদ' : 'Collection History & Receipts',
                subtitle: isBn ? 'কালেকশন রেকর্ড ও মানি রিসিট দেখুন' : 'View logs & money receipts',
                icon: Icons.history_rounded,
                iconColor: const Color(0xFF7C3AED),
                onTap: () => _navigate(context, const CollectionHistoryScreen()),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildNavRow(
    BuildContext context, {
    required String title,
    required String subtitle,
    required IconData icon,
    required Color iconColor,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: iconColor, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                  Text(subtitle, style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded, color: Color(0xFF94A3B8), size: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildWeeklyChart(BuildContext context, Map<String, dynamic> data, bool isBn) {
    final labels = List<String>.from(data['week_labels'] ?? []);
    final savings = List<num>.from(data['week_savings'] ?? []).map((e) => e.toDouble()).toList();
    final loans = List<num>.from(data['week_loans'] ?? []).map((e) => e.toDouble()).toList();

    if (labels.isEmpty) return const SizedBox();

    final maxVal = [...savings, ...loans].fold<double>(0, (a, b) => a > b ? a : b);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.bar_chart_rounded, size: 18, color: Color(0xFF2563EB)),
                  const SizedBox(width: 6),
                  Text(
                    isBn ? 'সাপ্তাহিক ট্রেন্ড' : 'Weekly Trend',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              Row(
                children: [
                  _buildLegendItem(isBn ? 'সঞ্চয়' : 'Savings', const Color(0xFF2563EB)),
                  const SizedBox(width: 10),
                  _buildLegendItem(isBn ? 'লোন' : 'Loans', const Color(0xFF0D9488)),
                ],
              ),
            ],
          ),
          const SizedBox(height: 18),
          SizedBox(
            height: 130,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: List.generate(labels.length, (i) {
                final savingsH = (maxVal > 0 ? (savings[i] / maxVal) * 90 : 0.0).clamp(4.0, 90.0);
                final loansH = (maxVal > 0 ? (loans[i] / maxVal) * 90 : 0.0).clamp(4.0, 90.0);

                return Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Container(
                            width: 8,
                            height: savingsH,
                            decoration: BoxDecoration(
                              color: const Color(0xFF2563EB),
                              borderRadius: BorderRadius.circular(4),
                            ),
                          ),
                          const SizedBox(width: 3),
                          Container(
                            width: 8,
                            height: loansH,
                            decoration: BoxDecoration(
                              color: const Color(0xFF0D9488),
                              borderRadius: BorderRadius.circular(4),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        labels[i],
                        style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                );
              }),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem(String label, Color color) {
    return Row(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 4),
        Text(label, style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
      ],
    );
  }

  void _navigate(BuildContext context, Widget page) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => page));
  }
}
