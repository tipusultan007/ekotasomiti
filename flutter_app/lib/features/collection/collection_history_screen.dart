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
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/helpers/error_handler.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/app_drawer.dart';

class CollectionHistoryFilter {
  final String dateFrom;
  final String dateTo;
  final String type;

  const CollectionHistoryFilter({
    required this.dateFrom,
    required this.dateTo,
    this.type = 'both',
  });

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CollectionHistoryFilter &&
          runtimeType == other.runtimeType &&
          dateFrom == other.dateFrom &&
          dateTo == other.dateTo &&
          type == other.type;

  @override
  int get hashCode => Object.hash(dateFrom, dateTo, type);
}

final collectionHistoryProvider =
    FutureProvider.family<Map<String, dynamic>, CollectionHistoryFilter>((ref, filter) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.offlineCache);
  final cacheKey = 'history_${filter.dateFrom}_${filter.dateTo}_${filter.type}';

  try {
    final response = await api.dio.get(
      ApiEndpoints.reportsCollections,
      queryParameters: {
        'date_from': filter.dateFrom,
        'date_to': filter.dateTo,
        'type': filter.type,
      },
    );
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put(cacheKey, data);
    return data;
  } catch (e) {
    final cached = box.get(cacheKey);
    if (cached != null) return Map<String, dynamic>.from(cached as Map);
    rethrow;
  }
});

class CollectionHistoryScreen extends ConsumerStatefulWidget {
  const CollectionHistoryScreen({super.key});

  @override
  ConsumerState<CollectionHistoryScreen> createState() => _CollectionHistoryScreenState();
}

class _CollectionHistoryScreenState extends ConsumerState<CollectionHistoryScreen> {
  DateTime _dateFrom = DateTime.now().subtract(const Duration(days: 30));
  DateTime _dateTo = DateTime.now();
  String _selectedType = 'both'; // 'both', 'savings', 'loan'

  CollectionHistoryFilter get _currentFilter => CollectionHistoryFilter(
        dateFrom: DateFormatter.api(_dateFrom),
        dateTo: DateFormatter.api(_dateTo),
        type: _selectedType,
      );

  void _setDatePreset(int days) {
    setState(() {
      _dateTo = DateTime.now();
      _dateFrom = days == 0
          ? DateTime.now()
          : DateTime.now().subtract(Duration(days: days));
    });
  }

  @override
  Widget build(BuildContext context) {
    final isBn = context.isBn;
    final filter = _currentFilter;
    final historyAsync = ref.watch(collectionHistoryProvider(filter));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        leading: const BackButton(),
        title: Text(
          isBn ? 'কালেকশন হিস্ট্রি' : 'Collection History',
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
      ),
      drawer: const AppDrawer(),
      body: Column(
        children: [
          const ConnectivityBanner(),
          _buildFilterBar(isBn),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => ref.invalidate(collectionHistoryProvider(filter)),
              child: historyAsync.when(
                loading: () => Center(
                  child: AppLoadingIndicator(
                    message: isBn ? 'কালেকশন রেকর্ড লোড হচ্ছে...' : 'Loading collection logs...',
                  ),
                ),
                error: (e, _) => Center(
                  child: AppErrorWidget(
                    message: AppErrorHandler.getMessage(e, isBn: isBn),
                    onRetry: () => ref.invalidate(collectionHistoryProvider(filter)),
                  ),
                ),
                data: (data) => _buildContent(data, isBn),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterBar(bool isBn) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Column(
        children: [
          // Type filter chips
          Row(
            children: [
              _buildTypeChip('both', isBn ? 'সকল কালেকশন' : 'All Collections'),
              const SizedBox(width: 8),
              _buildTypeChip('savings', isBn ? 'সঞ্চয় আদায়' : 'Savings'),
              const SizedBox(width: 8),
              _buildTypeChip('loan', isBn ? 'লোন আদায়' : 'Loans'),
            ],
          ),
          const SizedBox(height: 10),

          // Date buttons
          Row(
            children: [
              Expanded(
                child: _buildDateButton(isBn ? 'শুরু' : 'From', _dateFrom, (d) => setState(() => _dateFrom = d)),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildDateButton(isBn ? 'শেষ' : 'To', _dateTo, (d) => setState(() => _dateTo = d)),
              ),
            ],
          ),
          const SizedBox(height: 8),

          // Quick presets
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildPresetChip(isBn ? 'আজকের' : 'Today', 0),
                const SizedBox(width: 6),
                _buildPresetChip(isBn ? '৭ দিন' : '7 Days', 7),
                const SizedBox(width: 6),
                _buildPresetChip(isBn ? '৩০ দিন' : '30 Days', 30),
                const SizedBox(width: 6),
                _buildPresetChip(isBn ? '৯০ দিন' : '90 Days', 90),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTypeChip(String type, String label) {
    final isSelected = _selectedType == type;
    return Expanded(
      child: InkWell(
        onTap: () => setState(() => _selectedType = type),
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(10),
          ),
          alignment: Alignment.center,
          child: Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
              color: isSelected ? Colors.white : const Color(0xFF475569),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPresetChip(String label, int days) {
    return InkWell(
      onTap: () => _setDatePreset(days),
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Text(label, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
      ),
    );
  }

  Widget _buildDateButton(String prefix, DateTime date, ValueChanged<DateTime> onPicked) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: date,
          firstDate: DateTime(2020),
          lastDate: DateTime.now().add(const Duration(days: 1)),
        );
        if (picked != null) onPicked(picked);
      },
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFCBD5E1)),
        ),
        child: Row(
          children: [
            const Icon(Icons.calendar_today_outlined, size: 14, color: Color(0xFF64748B)),
            const SizedBox(width: 6),
            Expanded(
              child: Text(
                '$prefix: ${DateFormatter.display(date)}',
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContent(Map<String, dynamic> data, bool isBn) {
    final summary = data['summary'] != null ? Map<String, dynamic>.from(data['summary'] as Map) : <String, dynamic>{};
    final rawSavings = (data['savings_transactions'] as List?) ?? [];
    final savingsTxns = rawSavings.map((t) => Map<String, dynamic>.from(t as Map)).toList();
    final rawLoans = (data['loan_transactions'] as List?) ?? [];
    final loanTxns = rawLoans.map((t) => Map<String, dynamic>.from(t as Map)).toList();

    final grandTotal = (summary['grand_total'] as num?)?.toDouble() ?? 0;
    final savingsTotal = (summary['savings_total'] as num?)?.toDouble() ?? 0;
    final loanTotal = (summary['loan_total'] as num?)?.toDouble() ?? 0;

    final totalCount = savingsTxns.length + loanTxns.length;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Summary Hero Card
        _buildHeroSummaryCard(
          grandTotal: grandTotal,
          savingsTotal: savingsTotal,
          loanTotal: loanTotal,
          count: totalCount,
          isBn: isBn,
        ),
        const SizedBox(height: 20),

        // Savings Section
        if (savingsTxns.isNotEmpty && (_selectedType == 'both' || _selectedType == 'savings')) ...[
          _buildSectionHeader(
            isBn ? 'সঞ্চয় আদায় (${savingsTxns.length})' : 'Savings Collections (${savingsTxns.length})',
            const Color(0xFF2563EB),
          ),
          const SizedBox(height: 8),
          ...savingsTxns.map((t) => _buildTransactionItem(t, isSavings: true, isBn: isBn)),
          const SizedBox(height: 16),
        ],

        // Loans Section
        if (loanTxns.isNotEmpty && (_selectedType == 'both' || _selectedType == 'loan')) ...[
          _buildSectionHeader(
            isBn ? 'লোন কিস্তি আদায় (${loanTxns.length})' : 'Loan Installments (${loanTxns.length})',
            const Color(0xFF0D9488),
          ),
          const SizedBox(height: 8),
          ...loanTxns.map((t) => _buildTransactionItem(t, isSavings: false, isBn: isBn)),
          const SizedBox(height: 16),
        ],

        if (savingsTxns.isEmpty && loanTxns.isEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 40),
            child: EmptyState(
              icon: Icons.history_rounded,
              message: isBn
                  ? 'নির্বাচিত তারিখ ও ফিল্টারে কোনো কালেকশন তথ্য পাওয়া যায়নি।'
                  : 'No collection records found for the selected date & filter.',
            ),
          ),
      ],
    );
  }

  Widget _buildHeroSummaryCard({
    required double grandTotal,
    required double savingsTotal,
    required double loanTotal,
    required int count,
    required bool isBn,
  }) {
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.15),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                isBn ? 'মোট কালেকশন' : 'Total Collection',
                style: const TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w500),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  isBn ? '$count টি লেনদেন' : '$count transactions',
                  style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            CurrencyFormatter.simple(grandTotal),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 26,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.5,
            ),
          ),
          const Divider(color: Colors.white12, height: 24),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(isBn ? 'সঞ্চয় আদায়' : 'Savings Total', style: const TextStyle(color: Colors.white60, fontSize: 11)),
                    const SizedBox(height: 3),
                    Text(
                      CurrencyFormatter.simple(savingsTotal),
                      style: const TextStyle(color: Color(0xFF93C5FD), fontSize: 14, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
              Container(width: 1, height: 28, color: Colors.white12),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(isBn ? 'লোন আদায়' : 'Loans Total', style: const TextStyle(color: Colors.white60, fontSize: 11)),
                    const SizedBox(height: 3),
                    Text(
                      CurrencyFormatter.simple(loanTotal),
                      style: const TextStyle(color: Color(0xFF6EE7B7), fontSize: 14, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title, Color color) {
    return Row(
      children: [
        Container(width: 3, height: 12, decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(2))),
        const SizedBox(width: 6),
        Text(
          title,
          style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color, letterSpacing: 0.5),
        ),
      ],
    );
  }

  Widget _buildTransactionItem(Map<String, dynamic> txn, {required bool isSavings, required bool isBn}) {
    final amount = (txn['amount'] as num?)?.toDouble() ?? 0;
    final dateStr = txn['date']?.toString() ?? '';
    final memberName = txn['member_name']?.toString() ?? (isBn ? 'সদস্য' : 'Member');
    final memberNo = txn['member_no']?.toString() ?? '';
    final txnNo = txn['txn_no']?.toString() ?? '';
    final programOrProduct = (isSavings ? txn['program'] : txn['product'])?.toString() ?? '';
    final method = txn['payment_method']?.toString() ?? 'cash';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.015),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: isSavings ? const Color(0xFFEFF6FF) : const Color(0xFFECFDF5),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(
              isSavings ? Icons.savings_outlined : Icons.monetization_on_outlined,
              color: isSavings ? const Color(0xFF2563EB) : const Color(0xFF059669),
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        memberName,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    Text(
                      CurrencyFormatter.simple(amount),
                      style: TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 14,
                        color: isSavings ? const Color(0xFF2563EB) : const Color(0xFF059669),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 3),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      '${memberNo.isNotEmpty ? '$memberNo • ' : ''}$programOrProduct',
                      style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                    ),
                    Text(
                      DateFormatter.human(dateStr),
                      style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isBn ? 'লেনদেন নং: $txnNo' : 'Txn No: $txnNo',
                      style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        method == 'cash' ? (isBn ? 'নগদ' : 'CASH') : method.toUpperCase(),
                        style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF475569)),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
