import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/helpers/error_handler.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/app_drawer.dart';
import 'loan_detail_screen.dart';

final loansProvider = FutureProvider.family<Map<String, dynamic>, String>((ref, frequency) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.loans);
  try {
    final response = await api.dio.get(ApiEndpoints.loans, queryParameters: {
      'frequency': frequency,
      'per_page': '100',
    });
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put('loans_list_$frequency', data);
    return data;
  } catch (e) {
    final cached = box.get('loans_list_$frequency');
    if (cached != null) return Map<String, dynamic>.from(cached as Map);
    rethrow;
  }
});

class LoansListScreen extends ConsumerStatefulWidget {
  const LoansListScreen({super.key});

  @override
  ConsumerState<LoansListScreen> createState() => _LoansListScreenState();
}

class _LoansListScreenState extends ConsumerState<LoansListScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  String _search = '';
  String _statusFilter = 'all';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isBn = context.isBn;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      drawer: const AppDrawer(),
      appBar: AppBar(
        leading: const BackButton(),
        title: Text(
          isBn ? 'ঋণ / লোন হিসাবসমূহ' : 'Loan Accounts',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          const ConnectivityBanner(compact: true),
          Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu_rounded),
              tooltip: isBn ? 'মেনু' : 'Menu',
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: TabBar(
              controller: _tabController,
              indicator: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(10),
              ),
              indicatorSize: TabBarIndicatorSize.tab,
              labelColor: Colors.white,
              unselectedLabelColor: const Color(0xFF64748B),
              labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 13),
              dividerColor: Colors.transparent,
              tabs: [
                Tab(text: isBn ? 'দৈনিক কিস্তি' : 'Daily'),
                Tab(text: isBn ? 'সাপ্তাহিক কিস্তি' : 'Weekly'),
                Tab(text: isBn ? 'মাসিক কিস্তি' : 'Monthly'),
              ],
            ),
          ),
        ),
      ),
      body: Column(
        children: [
          const ConnectivityBanner(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
            child: Column(
              children: [
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.02),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: TextField(
                    controller: _searchController,
                    onChanged: (v) => setState(() => _search = v.trim()),
                    decoration: InputDecoration(
                      hintText: isBn ? 'লোন নম্বর, সদস্যের নাম বা মোবাইল দিয়ে খুঁজুন...' : 'Search by loan no, member name or phone...',
                      hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
                      prefixIcon: Icon(Icons.search_rounded, color: Colors.grey.shade500, size: 20),
                      suffixIcon: _search.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear_rounded, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                setState(() => _search = '');
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    _buildFilterChip('all', isBn ? 'সকল লোন' : 'All Loans'),
                    const SizedBox(width: 8),
                    _buildFilterChip('active', isBn ? 'চলমান' : 'Active'),
                    const SizedBox(width: 8),
                    _buildFilterChip('completed', isBn ? 'পরিশোধিত' : 'Closed / Settled'),
                  ],
                ),
              ],
            ),
          ),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildList('daily', isBn),
                _buildList('weekly', isBn),
                _buildList('monthly', isBn),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String key, String label) {
    final isSelected = _statusFilter == key;
    return InkWell(
      onTap: () => setState(() => _statusFilter = key),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF0F172A) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFF0F172A) : Colors.grey.shade300,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
            color: isSelected ? Colors.white : const Color(0xFF475569),
          ),
        ),
      ),
    );
  }

  Widget _buildList(String frequency, bool isBn) {
    final loansAsync = ref.watch(loansProvider(frequency));

    return loansAsync.when(
      loading: () => AppLoadingIndicator(message: isBn ? 'লোন তথ্য লোড হচ্ছে...' : 'Loading loan details...'),
      error: (e, _) => AppErrorWidget(
        message: AppErrorHandler.getMessage(e, isBn: isBn),
        onRetry: () => ref.invalidate(loansProvider(frequency)),
      ),
      data: (data) {
        final rawLoans = (data['data'] as List?) ?? [];
        var loans = rawLoans.map((l) => Map<String, dynamic>.from(l as Map)).toList();

        if (_search.isNotEmpty) {
          loans = loans.where((l) {
            final search = _search.toLowerCase();
            final loanNo = (l['loan_no'] ?? '').toLowerCase();
            final memberName = (l['member']?['name'] ?? '').toLowerCase();
            final memberNo = (l['member']?['member_no'] ?? '').toLowerCase();
            return loanNo.contains(search) || memberName.contains(search) || memberNo.contains(search);
          }).toList();
        }

        if (_statusFilter != 'all') {
          loans = loans.where((l) {
            final status = (l['status'] ?? 'active').toString().toLowerCase();
            if (_statusFilter == 'active') return status == 'active' || status == 'disbursed';
            if (_statusFilter == 'completed') return status == 'closed' || status == 'completed';
            return true;
          }).toList();
        }

        if (loans.isEmpty) {
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(loansProvider(frequency)),
            child: ListView(
              children: [
                const SizedBox(height: 60),
                EmptyState(
                  message: _search.isNotEmpty
                      ? (isBn ? '"$_search" এর সাথে কোনো লোন মেলেনি' : 'No loans matching "$_search"')
                      : (isBn ? 'কোনো লোন রেকর্ড পাওয়া যায়নি' : 'No loan records found'),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () async => ref.invalidate(loansProvider(frequency)),
          child: ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            itemCount: loans.length,
            itemBuilder: (context, index) => _buildLoanCard(loans[index], isBn),
          ),
        );
      },
    );
  }

  Widget _buildLoanCard(Map<String, dynamic> l, bool isBn) {
    final memberName = l['member']?['name'] ?? (isBn ? 'সদস্য' : 'Member');
    final memberNo = l['member']?['member_no'] ?? '';
    final loanNo = l['loan_no'] ?? '';
    final productName = l['product']?['name'] ?? (isBn ? 'সাধারণ লোন' : 'General Loan');
    final outstanding = double.tryParse('${l['outstanding'] ?? 0}') ?? 0.0;
    final installment = double.tryParse('${l['installment_amount'] ?? 0}') ?? 0.0;
    final status = l['status'] ?? 'active';

    final latestRepayment = l['latest_repayment'] != null ? Map<String, dynamic>.from(l['latest_repayment'] as Map) : null;
    final lastPaymentDate = latestRepayment?['txn_date'] ?? l['last_payment_date'] ?? l['last_payment']?['txn_date'] ?? l['last_payment_at'];
    final lastPaymentAmount = (latestRepayment?['amount'] as num?)?.toDouble() ?? (l['last_payment_amount'] as num?)?.toDouble();

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
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
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => LoanDetailScreen(loanId: l['id'])),
          ),
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF0FDFA),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.monetization_on_rounded, color: Color(0xFF0D9488), size: 22),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            memberName,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '$loanNo ${memberNo.isNotEmpty ? '• $memberNo' : ''}',
                            style: const TextStyle(fontSize: 12, color: Color(0xFF0D9488), fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    ),
                    StatusBadge(status: status, isSmall: true),
                  ],
                ),

                const Divider(height: 20, color: Color(0xFFF1F5F9)),

                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          productName,
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${isBn ? 'কিস্তি' : 'Installment'}: ${CurrencyFormatter.simple(installment)}',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF475569), fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          CurrencyFormatter.simple(outstanding),
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF0D9488),
                          ),
                        ),
                        Text(
                          isBn ? 'অবশিষ্ট বকেয়া' : 'Outstanding Balance',
                          style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                        ),
                      ],
                    ),
                  ],
                ),

                const SizedBox(height: 10),

                // Last Payment Date strip
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFF1F5F9)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Icon(
                            Icons.history_rounded,
                            size: 13,
                            color: lastPaymentDate != null ? const Color(0xFF0D9488) : const Color(0xFF94A3B8),
                          ),
                          const SizedBox(width: 5),
                          Text(
                            isBn ? 'সর্বশেষ কিস্তি পরিশোধ:' : 'Last Payment:',
                            style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                      Text(
                        lastPaymentDate != null
                            ? '${DateFormatter.display(lastPaymentDate)}${lastPaymentAmount != null && lastPaymentAmount > 0 ? ' (${CurrencyFormatter.simple(lastPaymentAmount)})' : ''}'
                            : (isBn ? 'কোনো জমা হয়নি' : 'No payment yet'),
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: lastPaymentDate != null ? const Color(0xFF0F172A) : const Color(0xFF94A3B8),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
