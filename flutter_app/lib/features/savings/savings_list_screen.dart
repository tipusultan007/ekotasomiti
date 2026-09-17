import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/app_drawer.dart';
import 'savings_detail_screen.dart';

final savingsAccountsProvider = FutureProvider.family<Map<String, dynamic>, String>((ref, frequency) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.savingsAccounts);
  try {
    final response = await api.dio.get(ApiEndpoints.savingsAccounts, queryParameters: {
      'frequency': frequency,
      'per_page': '100',
    });
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put('savings_list_$frequency', data);
    return data;
  } catch (e) {
    final cached = box.get('savings_list_$frequency');
    if (cached != null) return Map<String, dynamic>.from(cached as Map);
    rethrow;
  }
});

class SavingsListScreen extends ConsumerStatefulWidget {
  const SavingsListScreen({super.key});

  @override
  ConsumerState<SavingsListScreen> createState() => _SavingsListScreenState();
}

class _SavingsListScreenState extends ConsumerState<SavingsListScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  String _search = '';
  String _statusFilter = 'all'; // 'all', 'active', 'inactive'

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
          isBn ? 'সঞ্চয় হিসাবসমূহ' : 'Savings Accounts',
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
                Tab(text: isBn ? 'দৈনিক সঞ্চয়' : 'Daily'),
                Tab(text: isBn ? 'সাপ্তাহিক সঞ্চয়' : 'Weekly'),
                Tab(text: isBn ? 'মাসিক সঞ্চয়' : 'Monthly'),
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
                      hintText: isBn ? 'হিসাব নং, সদস্যের নাম বা মোবাইল দিয়ে খুঁজুন...' : 'Search by account, member name or phone...',
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
                    _buildFilterChip('all', isBn ? 'সকল হিসাব' : 'All Accounts'),
                    const SizedBox(width: 8),
                    _buildFilterChip('active', isBn ? 'সক্রিয়' : 'Active'),
                    const SizedBox(width: 8),
                    _buildFilterChip('inactive', isBn ? 'নিষ্ক্রিয় / বন্ধ' : 'Inactive / Closed'),
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
    final accountsAsync = ref.watch(savingsAccountsProvider(frequency));

    return accountsAsync.when(
      loading: () => AppLoadingIndicator(
        message: isBn ? 'সঞ্চয় হিসাব লোড হচ্ছে...' : 'Loading savings accounts...',
      ),
      error: (e, _) => AppErrorWidget(
        message: isBn ? 'হিসাব লোড করা যায়নি: $e' : 'Failed to load accounts: $e',
        onRetry: () => ref.invalidate(savingsAccountsProvider(frequency)),
      ),
      data: (data) {
        final rawAccounts = (data['data'] as List?) ?? [];
        var accounts = rawAccounts.map((a) => Map<String, dynamic>.from(a as Map)).toList();

        if (_search.isNotEmpty) {
          final query = _search.toLowerCase();
          accounts = accounts.where((a) {
            final accountNo = (a['account_no'] ?? '').toString().toLowerCase();
            final memberName = (a['member']?['name'] ?? a['member_name'] ?? '').toString().toLowerCase();
            final memberNo = (a['member']?['member_no'] ?? a['member_no'] ?? '').toString().toLowerCase();
            final phone = (a['member']?['mobile'] ?? a['mobile'] ?? '').toString().toLowerCase();
            return accountNo.contains(query) ||
                memberName.contains(query) ||
                memberNo.contains(query) ||
                phone.contains(query);
          }).toList();
        }

        if (_statusFilter != 'all') {
          accounts = accounts.where((a) {
            final status = (a['status'] ?? 'active').toString().toLowerCase();
            if (_statusFilter == 'active') return status == 'active';
            if (_statusFilter == 'inactive') return status != 'active';
            return true;
          }).toList();
        }

        if (accounts.isEmpty) {
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(savingsAccountsProvider(frequency)),
            child: ListView(
              children: [
                const SizedBox(height: 60),
                EmptyState(
                  message: _search.isNotEmpty
                      ? (isBn ? '"$_search" এর সাথে কোনো হিসাব মেলেনি' : 'No accounts matching "$_search"')
                      : (isBn ? 'কোনো সঞ্চয় হিসাব পাওয়া যায়নি' : 'No savings accounts found'),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () async => ref.invalidate(savingsAccountsProvider(frequency)),
          child: ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            itemCount: accounts.length,
            itemBuilder: (context, index) => _buildAccountCard(accounts[index], isBn),
          ),
        );
      },
    );
  }

  Widget _buildAccountCard(Map<String, dynamic> a, bool isBn) {
    final accountId = a['id'] is int ? a['id'] as int : int.tryParse(a['id'].toString()) ?? 0;
    final accountNo = a['account_no'] ?? '';
    final program = a['program'] != null ? Map<String, dynamic>.from(a['program'] as Map) : null;
    final programName = program?['name'] ?? a['program_name'] ?? (isBn ? 'সঞ্চয় স্কিম' : 'Savings Scheme');
    final member = a['member'] != null ? Map<String, dynamic>.from(a['member'] as Map) : null;
    final memberName = member?['name'] ?? a['member_name'] ?? (isBn ? 'সদস্য' : 'Member');
    final memberNo = member?['member_no'] ?? a['member_no'] ?? '';
    final memberPhone = member?['mobile'] ?? a['mobile'] ?? '';
    final balance = double.tryParse('${a['current_balance'] ?? 0}') ?? 0.0;
    final status = (a['status'] ?? 'active').toString();

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
          onTap: () {
            if (accountId > 0) {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => SavingsDetailScreen(accountId: accountId),
                ),
              );
            }
          },
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
                        color: const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF2563EB), size: 22),
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
                            '$accountNo ${memberNo.isNotEmpty ? '• $memberNo' : ''}',
                            style: const TextStyle(fontSize: 12, color: Color(0xFF2563EB), fontWeight: FontWeight.w600),
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
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            programName,
                            style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          if (memberPhone.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                Icon(Icons.phone_outlined, size: 12, color: Colors.grey.shade500),
                                const SizedBox(width: 4),
                                Text(
                                  memberPhone,
                                  style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          CurrencyFormatter.simple(balance),
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF059669),
                          ),
                        ),
                        Text(
                          isBn ? 'সঞ্চয় স্থিতি' : 'Current Balance',
                          style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                        ),
                      ],
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
