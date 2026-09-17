import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../core/offline/sync_service.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/error_handler.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/app_drawer.dart';

final loanSheetProvider = FutureProvider.family<Map<String, dynamic>, String>((ref, cacheKey) async {
  final api = ref.read(apiClientProvider);
  final parts = cacheKey.split('_');
  final frequency = parts[0];
  final date = parts.sublist(1).join('_');
  try {
    final response = await api.dio.get('${ApiEndpoints.collectionLoans}/$frequency', queryParameters: {
      'date': date,
    });
    final box = Hive.box(HiveBoxes.loans);
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put('loan_sheet_$cacheKey', data);
    return data;
  } catch (e, st) {
    debugPrint('loanSheetProvider error: $e\n$st');
    final box = Hive.box(HiveBoxes.loans);
    final cached = box.get('loan_sheet_$cacheKey');
    if (cached != null) return Map<String, dynamic>.from(cached as Map);
    rethrow;
  }
});

class LoanCollectionScreen extends ConsumerStatefulWidget {
  final String frequency;
  const LoanCollectionScreen({super.key, required this.frequency});

  @override
  ConsumerState<LoanCollectionScreen> createState() => _LoanCollectionScreenState();
}

class _LoanCollectionScreenState extends ConsumerState<LoanCollectionScreen> {
  late String _frequency;
  String _date = DateFormatter.today();
  final Map<int, TextEditingController> _amountControllers = {};
  final Set<int> _submittingIds = {};
  final Set<int> _forceShowInputIds = {};
  String _searchQuery = '';
  String _statusFilter = 'all'; // 'all', 'due', 'paid', 'overdue'

  static const Color _loanPrimary = Color(0xFF0D9488); // Teal 600
  static const Color _loanDark = Color(0xFF0F766E); // Teal 700
  static const Color _loanLight = Color(0xFFF0FDFA); // Teal 50

  @override
  void initState() {
    super.initState();
    _frequency = widget.frequency;
  }

  @override
  void dispose() {
    for (final c in _amountControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _refresh() {
    final key = '${_frequency}_$_date';
    ref.invalidate(loanSheetProvider(key));
  }

  void _changeDate(int offsetDays) {
    final current = DateTime.tryParse(_date) ?? DateTime.now();
    final newDate = current.add(Duration(days: offsetDays));
    setState(() {
      _date = DateFormatter.api(newDate);
    });
    _refresh();
  }

  Future<void> _pickDate() async {
    final current = DateTime.tryParse(_date) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 30)),
    );
    if (picked != null) {
      setState(() => _date = DateFormatter.api(picked));
      _refresh();
    }
  }

  void _updateLocalLoanSheetCache(int loanId, double amount) {
    try {
      final key = '${_frequency}_$_date';
      final box = Hive.box(HiveBoxes.loans);
      final raw = box.get('loan_sheet_$key');
      if (raw != null) {
        final data = Map<String, dynamic>.from(raw);
        final rows = List<Map<String, dynamic>>.from(
          (data['rows'] as List).map((r) => Map<String, dynamic>.from(r)),
        );
        for (int i = 0; i < rows.length; i++) {
          if (rows[i]['loan_id'] == loanId) {
            final prevCollected = (rows[i]['collected'] ?? 0).toDouble();
            final dueToday = (rows[i]['due_today'] ?? 0).toDouble();
            final overdue = (rows[i]['overdue'] ?? 0).toDouble();
            final newCollected = prevCollected + amount;
            final newTotalDue = (dueToday + overdue - newCollected).clamp(0.0, double.infinity);
            final newOutstanding = ((rows[i]['outstanding'] ?? 0).toDouble() - amount).clamp(0.0, double.infinity);

            rows[i]['collected'] = newCollected;
            rows[i]['total_due'] = newTotalDue;
            rows[i]['outstanding'] = newOutstanding;
            rows[i]['status'] = newTotalDue <= 0 ? 'paid' : (newCollected > 0 ? 'partial' : 'due');
            break;
          }
        }
        data['rows'] = rows;
        if (data['totals'] != null) {
          final totals = Map<String, dynamic>.from(data['totals']);
          totals['collected'] = (totals['collected'] ?? 0).toDouble() + amount;
          totals['total_due'] = ((totals['total_due'] ?? 0).toDouble() - amount).clamp(0.0, double.infinity);
          data['totals'] = totals;
        }
        box.put('loan_sheet_$key', data);
      }
    } catch (e) {
      debugPrint('Error updating local loan cache: $e');
    }
  }

  Future<void> _submitRepayment(int loanId) async {
    if (_submittingIds.contains(loanId)) return;
    final isBn = context.isBn;

    final controller = _amountControllers[loanId];
    final amount = double.tryParse(controller?.text.trim() ?? '') ?? 0;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isBn ? 'সঠিক কিস্তির পরিমাণ লিখুন' : 'Please enter a valid installment amount'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() => _submittingIds.add(loanId));
    final messenger = ScaffoldMessenger.of(context);

    try {
      bool isSavedOffline = false;
      final payload = {
        'loan_id': loanId,
        'amount': amount,
        'collection_date': _date,
        'txn_date': _date,
        'payment_date': _date,
        'payment_method': 'cash',
      };

      final api = ref.read(apiClientProvider);
      final syncNotifier = ref.read(syncServiceProvider.notifier);

      try {
        await api.dio.post(
          ApiEndpoints.loanRepay,
          data: payload,
          options: Options(
            sendTimeout: const Duration(seconds: 10),
            receiveTimeout: const Duration(seconds: 10),
          ),
        );
        syncNotifier.setOnline(true);
      } catch (postError) {
        bool isNetwork = false;
        if (postError is DioException) {
          if (postError.type == DioExceptionType.connectionError ||
              postError.type == DioExceptionType.connectionTimeout ||
              postError.type == DioExceptionType.sendTimeout ||
              postError.type == DioExceptionType.receiveTimeout ||
              (postError.response?.statusCode != null && postError.response!.statusCode! >= 500)) {
            isNetwork = true;
          }
        }
        if (isNetwork) {
          syncNotifier.setOnline(false);
          await syncNotifier.addToPending(
            endpoint: ApiEndpoints.loanRepay,
            data: payload,
            title: isBn ? 'কিস্তি আদায় (লোন #$loanId: ৳$amount)' : 'Loan Repay (Loan #$loanId: ৳$amount)',
          );
          isSavedOffline = true;
        } else {
          rethrow;
        }
      }

      _updateLocalLoanSheetCache(loanId, amount);
      _forceShowInputIds.remove(loanId);
      _refresh();

      if (mounted) {
        messenger.showSnackBar(
          SnackBar(
            content: Row(
              children: [
                Icon(
                  isSavedOffline ? Icons.cloud_off_rounded : Icons.check_circle_rounded,
                  color: Colors.white,
                  size: 20,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    isSavedOffline
                        ? (isBn ? 'অফলাইনে সংরক্ষিত হয়েছে (${CurrencyFormatter.simple(amount)})' : 'Saved offline (${CurrencyFormatter.simple(amount)})')
                        : (isBn ? 'কিস্তি আদায় সফল: ${CurrencyFormatter.simple(amount)}' : 'Repayment recorded: ${CurrencyFormatter.simple(amount)}'),
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            backgroundColor: isSavedOffline ? const Color(0xFFD97706) : const Color(0xFF0F766E),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(AppErrorHandler.getMessage(e, isBn: isBn)),
            backgroundColor: Colors.red.shade700,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _submittingIds.remove(loanId));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isBn = context.isBn;
    final cacheKey = '${_frequency}_$_date';
    final sheetAsync = ref.watch(loanSheetProvider(cacheKey));
    final freqTitle = _frequency == 'daily'
        ? (isBn ? 'দৈনিক' : 'Daily')
        : (_frequency == 'weekly' ? (isBn ? 'সাপ্তাহিক' : 'Weekly') : (isBn ? 'মাসিক' : 'Monthly'));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(
          isBn ? '$freqTitle লোন কিস্তি কালেকশন' : '$freqTitle Loan Collection',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          const ConnectivityBanner(compact: true),
          IconButton(
            icon: const Icon(Icons.calendar_month_outlined),
            tooltip: isBn ? 'তারিখ নির্বাচন' : 'Choose Date',
            onPressed: _pickDate,
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: isBn ? 'রিফ্রেশ করুন' : 'Refresh',
            onPressed: _refresh,
          ),
        ],
      ),
      drawer: const AppDrawer(),
      body: Column(
        children: [
          const ConnectivityBanner(),
          _buildTopBar(isBn),
          Expanded(
            child: sheetAsync.when(
              loading: () => AppLoadingIndicator(
                message: isBn ? 'লোন কালেকশন তালিকা লোড হচ্ছে...' : 'Loading loan collection list...',
              ),
              error: (e, _) => AppErrorWidget(
                message: AppErrorHandler.getMessage(e, isBn: isBn),
                onRetry: _refresh,
              ),
              data: (data) => _buildBody(data, isBn),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTopBar(bool isBn) {
    final isToday = _date == DateFormatter.today();
    final parsedDate = DateTime.tryParse(_date) ?? DateTime.now();

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
      child: Column(
        children: [
          Container(
            height: 38,
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
            ),
            padding: const EdgeInsets.all(3),
            child: Row(
              children: [
                _buildFreqTab('daily', isBn ? 'দৈনিক' : 'Daily'),
                _buildFreqTab('weekly', isBn ? 'সাপ্তাহিক' : 'Weekly'),
                _buildFreqTab('monthly', isBn ? 'মাসিক' : 'Monthly'),
              ],
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              InkWell(
                onTap: () => _changeDate(-1),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.chevron_left_rounded, size: 20),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: InkWell(
                  onTap: _pickDate,
                  borderRadius: BorderRadius.circular(8),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: isToday ? _loanLight : const Color(0xFFF8FAFC),
                      border: Border.all(
                        color: isToday ? _loanPrimary : Colors.grey.shade300,
                      ),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.event_outlined,
                          size: 16,
                          color: isToday ? _loanPrimary : Colors.grey.shade700,
                        ),
                        const SizedBox(width: 6),
                        Text(
                          DateFormatter.display(parsedDate),
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: isToday ? _loanDark : Colors.grey.shade800,
                          ),
                        ),
                        if (isToday) ...[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                            decoration: BoxDecoration(
                              color: _loanPrimary,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              isBn ? 'আজ' : 'TODAY',
                              style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              InkWell(
                onTap: () => _changeDate(1),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.chevron_right_rounded, size: 20),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFreqTab(String value, String label) {
    final isSelected = _frequency == value;
    return Expanded(
      child: GestureDetector(
        onTap: () {
          if (_frequency != value) {
            setState(() => _frequency = value);
            _refresh();
          }
        },
        child: Container(
          decoration: BoxDecoration(
            color: isSelected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(8),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.06),
                      blurRadius: 3,
                      offset: const Offset(0, 1),
                    )
                  ]
                : null,
          ),
          alignment: Alignment.center,
          child: Text(
            label,
            style: TextStyle(
              fontSize: 13,
              fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
              color: isSelected ? _loanDark : const Color(0xFF64748B),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBody(Map<String, dynamic> data, bool isBn) {
    final rawRows = (data['rows'] as List?) ?? [];
    final rows = rawRows.map((r) => Map<String, dynamic>.from(r as Map)).toList();
    final totals = data['totals'] != null ? Map<String, dynamic>.from(data['totals'] as Map) : <String, dynamic>{};

    final filteredRows = rows.where((row) {
      final status = (row['status'] ?? 'due').toString().toLowerCase();
      final dueToday = (row['due_today'] ?? 0).toDouble();
      final overdue = (row['overdue'] ?? 0).toDouble();
      final collected = (row['collected'] ?? 0).toDouble();
      final totalDue = (row['total_due'] ?? (dueToday + overdue - collected)).toDouble();
      final isPaid = status == 'paid' || (totalDue <= 0 && (dueToday > 0 || overdue > 0 || collected > 0));

      if (_statusFilter == 'due' && isPaid) return false;
      if (_statusFilter == 'paid' && !isPaid) return false;
      if (_statusFilter == 'overdue' && overdue <= 0) return false;

      if (_searchQuery.isNotEmpty) {
        final query = _searchQuery.toLowerCase();
        final name = (row['member_name'] ?? '').toString().toLowerCase();
        final loanNo = (row['loan_no'] ?? '').toString().toLowerCase();
        final phone = (row['phone'] ?? row['mobile'] ?? '').toString().toLowerCase();
        final memberNo = (row['member_no'] ?? '').toString().toLowerCase();
        return name.contains(query) || loanNo.contains(query) || phone.contains(query) || memberNo.contains(query);
      }

      return true;
    }).toList();

    final dueCount = rows.where((r) {
      final s = (r['status'] ?? 'due').toString().toLowerCase();
      final d = (r['due_today'] ?? 0).toDouble();
      final o = (r['overdue'] ?? 0).toDouble();
      final c = (r['collected'] ?? 0).toDouble();
      final td = (r['total_due'] ?? (d + o - c)).toDouble();
      return !(s == 'paid' || (td <= 0 && (d > 0 || o > 0 || c > 0)));
    }).length;

    final paidCount = rows.length - dueCount;
    final overdueCount = rows.where((r) => ((r['overdue'] ?? 0).toDouble()) > 0).length;

    return RefreshIndicator(
      onRefresh: () async => _refresh(),
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        children: [
          _buildSummaryCard(totals, rows.length, paidCount, isBn),
          const SizedBox(height: 12),
          _buildSearchAndFilters(rows.length, dueCount, paidCount, overdueCount, isBn),
          const SizedBox(height: 12),
          if (rows.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              alignment: Alignment.center,
              child: Column(
                children: [
                  Icon(Icons.assignment_outlined, size: 48, color: Colors.grey.shade400),
                  const SizedBox(height: 8),
                  Text(
                    _frequency == 'monthly'
                        ? (isBn ? 'এই মাসে কোনো লোন কিস্তি কালেকশন নির্ধারিত নেই' : 'No monthly loan installments scheduled for this month')
                        : (isBn ? 'নির্বাচিত তারিখে কোনো লোন কিস্তি কালেকশন নির্ধারিত নেই' : 'No loan installments scheduled for collection on this date'),
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            )
          else if (filteredRows.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              alignment: Alignment.center,
              child: Column(
                children: [
                  Icon(Icons.search_off_rounded, size: 48, color: Colors.grey.shade400),
                  const SizedBox(height: 8),
                  Text(
                    isBn ? 'ফিল্টারের সাথে কোনো লোন মেলেনি' : 'No loans match your filter',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
                  ),
                ],
              ),
            )
          else
            ...filteredRows.map((row) => _buildRow(row, isBn)),
          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildSummaryCard(Map<String, dynamic> totals, int totalCount, int paidCount, bool isBn) {
    final dueToday = (totals['due_today'] ?? 0).toDouble();
    final overdue = (totals['overdue'] ?? 0).toDouble();
    final collected = (totals['collected'] ?? 0).toDouble();
    final totalExpected = dueToday + overdue;
    final percent = totalExpected > 0 ? (collected / totalExpected).clamp(0.0, 1.0) : 0.0;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        children: [
          Row(
            children: [
              _buildStatItem(
                _frequency == 'monthly'
                    ? (isBn ? 'চলতি মাসের কিস্তি' : 'This Month')
                    : (isBn ? 'আজকের কিস্তি' : 'Due Today'),
                CurrencyFormatter.simple(dueToday),
                const Color(0xFFEA580C),
                Icons.event_available,
              ),
              Container(width: 1, height: 36, color: Colors.grey.shade200),
              _buildStatItem(isBn ? 'আদায় হয়েছে' : 'Collected', CurrencyFormatter.simple(collected), _loanPrimary, Icons.check_circle_outline),
              Container(width: 1, height: 36, color: Colors.grey.shade200),
              _buildStatItem(isBn ? 'বকেয়া / খেলাপি' : 'Overdue', CurrencyFormatter.simple(overdue), const Color(0xFFDC2626), Icons.warning_amber_rounded),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: percent,
                    minHeight: 6,
                    backgroundColor: const Color(0xFFE2E8F0),
                    valueColor: const AlwaysStoppedAnimation<Color>(_loanPrimary),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                isBn
                    ? '$paidCount/$totalCount আদায় সম্পন্ন (${(percent * 100).toStringAsFixed(0)}%)'
                    : '$paidCount/$totalCount Collected (${(percent * 100).toStringAsFixed(0)}%)',
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF475569),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem(String label, String value, Color color, IconData icon) {
    return Expanded(
      child: Column(
        children: [
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 15,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchAndFilters(int total, int due, int paid, int overdue, bool isBn) {
    return Column(
      children: [
        Container(
          height: 40,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: TextField(
            onChanged: (val) => setState(() => _searchQuery = val.trim()),
            style: const TextStyle(fontSize: 13),
            decoration: InputDecoration(
              hintText: isBn ? 'সদস্যের নাম, লোন নং বা মোবাইল দিয়ে খুঁজুন...' : 'Search member, loan number, phone...',
              hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
              prefixIcon: Icon(Icons.search, size: 18, color: Colors.grey.shade500),
              suffixIcon: _searchQuery.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear, size: 16),
                      onPressed: () => setState(() => _searchQuery = ''),
                    )
                  : null,
              contentPadding: const EdgeInsets.symmetric(vertical: 8),
              border: InputBorder.none,
            ),
          ),
        ),
        const SizedBox(height: 8),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: [
              _buildFilterChip('all', isBn ? 'সকল ($total)' : 'All ($total)'),
              const SizedBox(width: 6),
              _buildFilterChip('due', isBn ? 'বকেয়া ($due)' : 'Due ($due)'),
              const SizedBox(width: 6),
              _buildFilterChip('paid', isBn ? 'আদায়কৃত ($paid)' : 'Collected ($paid)'),
              if (overdue > 0) ...[
                const SizedBox(width: 6),
                _buildFilterChip('overdue', isBn ? 'খেলাপি ($overdue)' : 'Overdue ($overdue)'),
              ],
            ],
          ),
        ),
      ],
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
          color: isSelected ? _loanDark : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? _loanDark : Colors.grey.shade300,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
            color: isSelected ? Colors.white : const Color(0xFF475569),
          ),
        ),
      ),
    );
  }

  Widget _buildRow(Map<String, dynamic> row, bool isBn) {
    final loanId = row['loan_id'] as int;
    final status = (row['status'] ?? 'due').toString().toLowerCase();
    final dueToday = (row['due_today'] ?? 0).toDouble();
    final overdue = (row['overdue'] ?? 0).toDouble();
    final collected = (row['collected'] ?? 0).toDouble();
    final installment = (row['installment_amount'] ?? 0).toDouble();
    final totalDue = (row['total_due'] ?? (dueToday + overdue - collected)).toDouble();
    final outstanding = (row['outstanding'] ?? 0).toDouble();
    final memberName = row['member_name'] ?? (isBn ? 'সদস্য' : 'Member');
    final loanNo = (row['loan_no'] ?? '').toString();
    final phone = (row['phone'] ?? row['mobile'] ?? '').toString();

    final isPaid = (status == 'paid' || (totalDue <= 0 && (dueToday > 0 || overdue > 0 || collected > 0))) &&
        !_forceShowInputIds.contains(loanId);
    final isSubmittingThis = _submittingIds.contains(loanId);

    final suggestedAmount = totalDue > 0 ? totalDue : (dueToday > 0 ? dueToday : installment);
    _amountControllers.putIfAbsent(
      loanId,
      () => TextEditingController(
        text: isPaid ? '' : '${suggestedAmount.toInt()}',
      ),
    );

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isPaid ? const Color(0xFFBBF7D0) : const Color(0xFFE2E8F0),
          width: isPaid ? 1.5 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: isPaid ? const Color(0xFF22C55E).withValues(alpha: 0.04) : Colors.black.withValues(alpha: 0.02),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: isPaid ? const Color(0xFFDCFCE7) : _loanLight,
                child: Text(
                  memberName.isNotEmpty ? memberName[0].toUpperCase() : 'L',
                  style: TextStyle(
                    color: isPaid ? const Color(0xFF16A34A) : _loanDark,
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      memberName,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Text(
                          loanNo,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: _loanDark,
                            letterSpacing: 0.3,
                          ),
                        ),
                        if (phone.isNotEmpty) ...[
                          const Text(' • ', style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
                          Icon(Icons.phone_outlined, size: 12, color: Colors.grey.shade600),
                          const SizedBox(width: 3),
                          Text(
                            phone,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                              color: Colors.grey.shade700,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              StatusBadge(status: isPaid ? 'paid' : (overdue > 0 ? 'overdue' : status)),
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
              children: [
                _buildCardStat(isBn ? 'কিস্তির পরিমাণ' : 'Installment', CurrencyFormatter.simple(installment), const Color(0xFF334155)),
                _buildCardStat(isBn ? 'আজকের প্রদেয়' : 'Due Today', CurrencyFormatter.simple(dueToday), const Color(0xFFEA580C)),
                _buildCardStat(isBn ? 'অবশিষ্ট ঋণ স্থিতি' : 'Outstanding', CurrencyFormatter.simple(outstanding), const Color(0xFF6366F1)),
              ],
            ),
          ),
          if (overdue > 0) ...[
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF2F2),
                borderRadius: BorderRadius.circular(6),
                border: Border.all(color: const Color(0xFFFECACA)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.error_outline, size: 14, color: Color(0xFFDC2626)),
                  const SizedBox(width: 6),
                  Text(
                    isBn ? 'বকেয়া কিস্তি: ${CurrencyFormatter.simple(overdue)}' : 'Past Overdue: ${CurrencyFormatter.simple(overdue)}',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFFB91C1C),
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 10),
          if (isPaid) ...[
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFF86EFAC)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 18),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      isBn
                          ? 'আদায়কৃত ${CurrencyFormatter.simple(collected)} • কিস্তি পরিশোধ সম্পন্ন'
                          : 'Collected ${CurrencyFormatter.simple(collected)} • Paid in Full',
                      style: const TextStyle(
                        color: Color(0xFF15803D),
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  InkWell(
                    onTap: () {
                      setState(() => _forceShowInputIds.add(loanId));
                    },
                    borderRadius: BorderRadius.circular(4),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      child: Text(
                        isBn ? '+ আরও জমা' : '+ Add More',
                        style: const TextStyle(
                          color: Color(0xFF16A34A),
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ] else ...[
            Row(
              children: [
                Expanded(
                  child: Container(
                    height: 40,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: TextField(
                      controller: _amountControllers[loanId],
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      decoration: InputDecoration(
                        hintText: isBn ? 'পরিমাণ' : 'Amount',
                        prefixText: '৳ ',
                        prefixStyle: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        border: InputBorder.none,
                        isDense: true,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                SizedBox(
                  height: 40,
                  child: ElevatedButton(
                    onPressed: isSubmittingThis ? null : () => _submitRepayment(loanId),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _loanDark,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(horizontal: 18),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: isSubmittingThis
                        ? const SizedBox(
                            height: 16,
                            width: 16,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.check, size: 16),
                              const SizedBox(width: 4),
                              Text(isBn ? 'কিস্তি নিন' : 'Collect', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            ],
                          ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildCardStat(String label, String value, Color valueColor) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
          const SizedBox(height: 1),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 12,
              color: valueColor,
            ),
          ),
        ],
      ),
    );
  }
}
