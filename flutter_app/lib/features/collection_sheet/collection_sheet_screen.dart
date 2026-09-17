import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../core/offline/sync_service.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/error_handler.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/app_drawer.dart';

final collectionSheetProvider = FutureProvider.family<Map<String, dynamic>, String>((ref, cacheKey) async {
  final api = ref.read(apiClientProvider);
  final parts = cacheKey.split('_');
  final frequency = parts[0];
  final date = parts.sublist(1).join('_');
  try {
    final response = await api.dio.get('${ApiEndpoints.collectionSavings}/$frequency', queryParameters: {
      'date': date,
    });
    final box = Hive.box(HiveBoxes.savingsAccounts);
    final data = Map<String, dynamic>.from(response.data as Map);
    await box.put('collection_sheet_$cacheKey', data);
    return data;
  } catch (e, st) {
    debugPrint('collectionSheetProvider error: $e\n$st');
    final box = Hive.box(HiveBoxes.savingsAccounts);
    final cached = box.get('collection_sheet_$cacheKey');
    if (cached != null) return Map<String, dynamic>.from(cached as Map);
    rethrow;
  }
});

class CollectionSheetScreen extends ConsumerStatefulWidget {
  const CollectionSheetScreen({super.key});

  @override
  ConsumerState<CollectionSheetScreen> createState() => _CollectionSheetScreenState();
}

class _CollectionSheetScreenState extends ConsumerState<CollectionSheetScreen> {
  String _frequency = 'daily';
  String _date = DateFormatter.today();
  final Map<int, TextEditingController> _amountControllers = {};
  final Set<int> _submittingIds = {};
  final Set<int> _forceShowInputIds = {};
  bool _submittingAll = false;
  String _searchQuery = '';
  String _statusFilter = 'all';

  @override
  void dispose() {
    for (final c in _amountControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _refresh() {
    final cacheKey = '${_frequency}_$_date';
    ref.invalidate(collectionSheetProvider(cacheKey));
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

  void _updateLocalSavingsCache(int accountId, double amount) {
    try {
      final key = '${_frequency}_$_date';
      final box = Hive.box(HiveBoxes.savingsAccounts);
      final raw = box.get('collection_sheet_$key');
      if (raw != null) {
        final data = Map<String, dynamic>.from(raw);
        final rows = List<Map<String, dynamic>>.from(
          (data['rows'] as List).map((r) => Map<String, dynamic>.from(r)),
        );
        for (int i = 0; i < rows.length; i++) {
          if (rows[i]['account_id'] == accountId) {
            final prevCollected = (rows[i]['collected'] ?? 0).toDouble();
            final expected = (rows[i]['expected'] ?? 0).toDouble();
            final newCollected = prevCollected + amount;
            final newDue = (expected - newCollected).clamp(0.0, double.infinity);
            final currentBal = (rows[i]['current_balance'] ?? 0).toDouble() + amount;

            rows[i]['collected'] = newCollected;
            rows[i]['due'] = newDue;
            rows[i]['current_balance'] = currentBal;
            rows[i]['status'] = newCollected >= expected ? 'paid' : (newCollected > 0 ? 'partial' : 'due');
            break;
          }
        }
        data['rows'] = rows;
        if (data['totals'] != null) {
          final totals = Map<String, dynamic>.from(data['totals']);
          totals['collected'] = (totals['collected'] ?? 0).toDouble() + amount;
          totals['due'] = ((totals['due'] ?? 0).toDouble() - amount).clamp(0.0, double.infinity);
          data['totals'] = totals;
        }
        box.put('collection_sheet_$key', data);
      }
    } catch (e) {
      debugPrint('Error updating local collection sheet cache: $e');
    }
  }

  Future<void> _submitSingle(int accountId, double expected) async {
    if (_submittingIds.contains(accountId)) return;

    final controller = _amountControllers[accountId];
    final amount = double.tryParse(controller?.text.trim() ?? '') ?? 0;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('সঠিক জমার পরিমাণ লিখুন'), backgroundColor: Colors.orange),
      );
      return;
    }

    setState(() => _submittingIds.add(accountId));
    bool isSavedOffline = false;

    try {
      final api = ref.read(apiClientProvider);
      final syncService = ref.read(syncServiceProvider.notifier);
      final data = {
        'account_id': accountId,
        'amount': amount,
        'txn_date': _date,
        'collection_date': _date,
        'payment_date': _date,
        'payment_method': 'cash',
      };

      try {
        await api.dio.post(
          ApiEndpoints.savingsDeposit,
          data: data,
          options: Options(
            sendTimeout: const Duration(seconds: 10),
            receiveTimeout: const Duration(seconds: 10),
          ),
        );
        syncService.setOnline(true);
      } catch (e) {
        bool isNetwork = false;
        if (e is DioException) {
          if (e.type == DioExceptionType.connectionError ||
              e.type == DioExceptionType.connectionTimeout ||
              e.type == DioExceptionType.sendTimeout ||
              e.type == DioExceptionType.receiveTimeout ||
              (e.response?.statusCode != null && e.response!.statusCode! >= 500)) {
            isNetwork = true;
          }
        }
        if (isNetwork) {
          syncService.setOnline(false);
          await syncService.addToPending(
            endpoint: ApiEndpoints.savingsDeposit,
            data: data,
            title: 'সঞ্চয় জমা (হিসাব #$accountId: ৳$amount)',
          );
          isSavedOffline = true;
        } else {
          rethrow;
        }
      }

      _updateLocalSavingsCache(accountId, amount);
      _forceShowInputIds.remove(accountId);
      controller?.clear();
      _refresh();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSavedOffline
                  ? 'অফলাইনে সংরক্ষিত হয়েছে (${CurrencyFormatter.simple(amount)})'
                  : 'সঞ্চয় জমা সফল: ${CurrencyFormatter.simple(amount)}',
            ),
            backgroundColor: isSavedOffline ? const Color(0xFFD97706) : const Color(0xFF2E7D32),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(AppErrorHandler.getMessage(e, isBn: true)), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _submittingIds.remove(accountId));
      }
    }
  }

  Future<void> _submitAll(List<Map<String, dynamic>> rows) async {
    setState(() => _submittingAll = true);
    int successCount = 0;
    int offlineCount = 0;
    try {
      final api = ref.read(apiClientProvider);
      final syncService = ref.read(syncServiceProvider.notifier);
      for (final row in rows) {
        final accountId = row['account_id'] as int;
        final status = (row['status'] ?? 'due').toString().toLowerCase();
        final expected = (row['expected'] ?? 0).toDouble();
        final collected = (row['collected'] ?? 0).toDouble();
        final due = (row['due'] ?? 0).toDouble();
        final isPaid = (status == 'paid' || (expected > 0 && collected >= expected) || (due <= 0 && collected > 0)) &&
            !_forceShowInputIds.contains(accountId);

        if (isPaid) continue;

        final controller = _amountControllers[accountId];
        final amount = double.tryParse(controller?.text.trim() ?? '') ?? 0;
        if (amount <= 0) continue;

        final data = {
          'account_id': accountId,
          'amount': amount,
          'txn_date': _date,
          'collection_date': _date,
          'payment_date': _date,
          'payment_method': 'cash',
        };

        try {
          await api.dio.post(ApiEndpoints.savingsDeposit, data: data);
          successCount++;
        } catch (e) {
          await syncService.addToPending(
            endpoint: ApiEndpoints.savingsDeposit,
            data: data,
            title: 'সঞ্চয় জমা (হিসাব #$accountId: ৳$amount)',
          );
          _updateLocalSavingsCache(accountId, amount);
          offlineCount++;
        }
        controller?.clear();
      }
      _refresh();
      if (mounted) {
        final totalDone = successCount + offlineCount;
        if (totalDone > 0) {
          final msg = offlineCount > 0
              ? 'মোট $totalDone টি কালেকশন সম্পন্ন ($offlineCount টি অফলাইনে সংরক্ষিত)'
              : 'সকল $totalDone টি কালেকশন সফলভাবে সম্পন্ন হয়েছে!';
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(msg),
              backgroundColor: offlineCount > 0 ? const Color(0xFFD97706) : const Color(0xFF2E7D32),
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('কালেকশন সাবমিট করা যায়নি'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _submittingAll = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final cacheKey = '${_frequency}_$_date';
    final sheetAsync = ref.watch(collectionSheetProvider(cacheKey));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        leading: const BackButton(),
        title: const Text(
          'ফিল্ড কালেকশন শিট',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          const ConnectivityBanner(compact: true),
          IconButton(
            icon: const Icon(Icons.calendar_month_outlined),
            tooltip: 'তারিখ নির্বাচন',
            onPressed: _pickDate,
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'রিফ্রেশ করুন',
            onPressed: _refresh,
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
      drawer: const AppDrawer(),
      body: Column(
        children: [
          const ConnectivityBanner(),
          _buildTopBar(),
          Expanded(
            child: sheetAsync.when(
              loading: () => const AppLoadingIndicator(message: 'কালেকশন শিট লোড হচ্ছে...'),
              error: (e, _) => AppErrorWidget(message: AppErrorHandler.getMessage(e, isBn: true), onRetry: _refresh),
              data: (data) => _buildBody(data),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTopBar() {
    final isToday = _date == DateFormatter.today();
    final parsedDate = DateTime.tryParse(_date) ?? DateTime.now();

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
      child: Column(
        children: [
          // Frequency Selector Pills
          Container(
            height: 38,
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
            ),
            padding: const EdgeInsets.all(3),
            child: Row(
              children: [
                _buildFreqTab('daily', 'দৈনিক'),
                _buildFreqTab('weekly', 'সাপ্তাহিক'),
                _buildFreqTab('monthly', 'মাসিক'),
              ],
            ),
          ),
          const SizedBox(height: 10),
          // Date Stepper
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
                      color: isToday ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                      border: Border.all(
                        color: isToday ? const Color(0xFF3B82F6) : Colors.grey.shade300,
                      ),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.event_outlined,
                          size: 16,
                          color: isToday ? const Color(0xFF2563EB) : Colors.grey.shade700,
                        ),
                        const SizedBox(width: 6),
                        Text(
                          DateFormatter.display(parsedDate),
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: isToday ? const Color(0xFF1D4ED8) : Colors.grey.shade800,
                          ),
                        ),
                        if (isToday) ...[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                            decoration: BoxDecoration(
                              color: const Color(0xFF2563EB),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text(
                              'আজ',
                              style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
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
              color: isSelected ? const Color(0xFF0F172A) : const Color(0xFF64748B),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBody(Map<String, dynamic> data) {
    final rawRows = (data['rows'] as List?) ?? [];
    final rows = rawRows.map((r) => Map<String, dynamic>.from(r as Map)).toList();
    final totals = data['totals'] != null ? Map<String, dynamic>.from(data['totals'] as Map) : <String, dynamic>{};

    if (rows.isEmpty) {
      return const EmptyState(message: 'নির্বাচিত তারিখে কোনো শিট এন্ট্রি পাওয়া যায়নি');
    }

    final filteredRows = rows.where((row) {
      final status = (row['status'] ?? 'due').toString().toLowerCase();
      final expected = (row['expected'] ?? 0).toDouble();
      final collected = (row['collected'] ?? 0).toDouble();
      final overdue = (row['overdue'] ?? 0).toDouble();
      final due = (row['due'] ?? 0).toDouble();
      final isPaid = status == 'paid' || (expected > 0 && collected >= expected) || (due <= 0 && collected > 0);

      if (_statusFilter == 'due' && isPaid) return false;
      if (_statusFilter == 'paid' && !isPaid) return false;
      if (_statusFilter == 'overdue' && overdue <= 0) return false;

      if (_searchQuery.isNotEmpty) {
        final query = _searchQuery.toLowerCase();
        final name = (row['member_name'] ?? '').toString().toLowerCase();
        final accNo = (row['account_no'] ?? '').toString().toLowerCase();
        final phone = (row['phone'] ?? row['mobile'] ?? '').toString().toLowerCase();
        return name.contains(query) || accNo.contains(query) || phone.contains(query);
      }

      return true;
    }).toList();

    final dueRows = rows.where((r) {
      final s = (r['status'] ?? 'due').toString().toLowerCase();
      final c = (r['collected'] ?? 0).toDouble();
      final e = (r['expected'] ?? 0).toDouble();
      final d = (r['due'] ?? 0).toDouble();
      return !(s == 'paid' || (e > 0 && c >= e) || (d <= 0 && c > 0));
    }).toList();

    final dueCount = dueRows.length;
    final paidCount = rows.length - dueCount;
    final overdueCount = rows.where((r) => ((r['overdue'] ?? 0).toDouble()) > 0).length;

    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async => _refresh(),
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              children: [
                _buildSummaryCard(totals, rows.length, paidCount),
                const SizedBox(height: 12),
                _buildSearchAndFilters(rows.length, dueCount, paidCount, overdueCount),
                const SizedBox(height: 12),
                if (filteredRows.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(32),
                    alignment: Alignment.center,
                    child: Column(
                      children: [
                        Icon(Icons.search_off_rounded, size: 48, color: Colors.grey.shade400),
                        const SizedBox(height: 8),
                        Text('অনুসন্ধানের সাথে কোনো রেকর্ড মেলেনি', style: TextStyle(color: Colors.grey.shade600, fontSize: 14)),
                      ],
                    ),
                  )
                else
                  ...filteredRows.map((row) => _buildRow(row)),
                const SizedBox(height: 16),
              ],
            ),
          ),
        ),
        if (dueRows.isNotEmpty) _buildSubmitBar(dueRows),
      ],
    );
  }

  Widget _buildSummaryCard(Map<String, dynamic> totals, int totalCount, int paidCount) {
    final expected = (totals['expected'] ?? 0).toDouble();
    final collected = (totals['collected'] ?? 0).toDouble();
    final due = (totals['due'] ?? 0).toDouble();
    final percent = expected > 0 ? (collected / expected).clamp(0.0, 1.0) : 0.0;

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
              _buildStatItem('লক্ষ্যমাত্রা', CurrencyFormatter.simple(expected), const Color(0xFF0F172A)),
              Container(width: 1, height: 36, color: Colors.grey.shade200),
              _buildStatItem('আদায় হয়েছে', CurrencyFormatter.simple(collected), const Color(0xFF16A34A)),
              Container(width: 1, height: 36, color: Colors.grey.shade200),
              _buildStatItem('বকেয়া', CurrencyFormatter.simple(due), const Color(0xFFEA580C)),
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
                    valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF16A34A)),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                '$paidCount/$totalCount আদায় সম্পন্ন (${(percent * 100).toStringAsFixed(0)}%)',
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem(String label, String value, Color color) {
    return Expanded(
      child: Column(
        children: [
          Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: color)),
          const SizedBox(height: 2),
          Text(label, style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  Widget _buildSearchAndFilters(int total, int due, int paid, int overdue) {
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
              hintText: 'সদস্যের নাম বা হিসাব নম্বর দিয়ে খুঁজুন...',
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
              _buildFilterChip('all', 'সকল ($total)'),
              const SizedBox(width: 6),
              _buildFilterChip('due', 'বকেয়া ($due)'),
              const SizedBox(width: 6),
              _buildFilterChip('paid', 'আদায়কৃত ($paid)'),
              if (overdue > 0) ...[
                const SizedBox(width: 6),
                _buildFilterChip('overdue', 'খেলাপি ($overdue)'),
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
          color: isSelected ? const Color(0xFF0F172A) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? const Color(0xFF0F172A) : Colors.grey.shade300),
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

  Widget _buildRow(Map<String, dynamic> row) {
    final accountId = row['account_id'] as int;
    final status = (row['status'] ?? 'due').toString().toLowerCase();
    final collected = (row['collected'] ?? 0).toDouble();
    final expected = (row['expected'] ?? 0).toDouble();
    final due = (row['due'] ?? 0).toDouble();
    final balance = (row['current_balance'] ?? 0).toDouble();
    final memberName = row['member_name'] ?? 'সদস্য';
    final accountNo = (row['account_no'] ?? '').toString();
    final phone = (row['phone'] ?? row['mobile'] ?? '').toString();

    final isPaid = (status == 'paid' || (expected > 0 && collected >= expected) || (due <= 0 && collected > 0)) &&
        !_forceShowInputIds.contains(accountId);
    final isSubmittingThis = _submittingIds.contains(accountId);

    final suggestedAmount = due > 0 ? due : expected;
    _amountControllers.putIfAbsent(
      accountId,
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
                backgroundColor: isPaid ? const Color(0xFFDCFCE7) : const Color(0xFFEFF6FF),
                child: Text(
                  memberName.isNotEmpty ? memberName[0].toUpperCase() : 'স',
                  style: TextStyle(
                    color: isPaid ? const Color(0xFF16A34A) : const Color(0xFF2563EB),
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
                          accountNo,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF1D4ED8),
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
              StatusBadge(status: isPaid ? 'paid' : status),
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
                _buildCardStat('কিস্তির পরিমাণ', CurrencyFormatter.simple(expected), const Color(0xFF334155)),
                _buildCardStat('আদায় হয়েছে', CurrencyFormatter.simple(collected), const Color(0xFF16A34A)),
                _buildCardStat('মোট ব্যালেন্স', CurrencyFormatter.simple(balance), const Color(0xFF0284C7)),
              ],
            ),
          ),
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
                      'আদায়কৃত ${CurrencyFormatter.simple(collected)} • পরিশোধ সম্পন্ন',
                      style: const TextStyle(color: Color(0xFF15803D), fontSize: 12, fontWeight: FontWeight.w600),
                    ),
                  ),
                  InkWell(
                    onTap: () => setState(() => _forceShowInputIds.add(accountId)),
                    child: const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      child: Text('+ আরও জমা', style: TextStyle(color: Color(0xFF16A34A), fontSize: 11, fontWeight: FontWeight.bold)),
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
                      controller: _amountControllers[accountId],
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      decoration: const InputDecoration(
                        hintText: 'পরিমাণ',
                        prefixText: '৳ ',
                        prefixStyle: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
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
                    onPressed: isSubmittingThis ? null : () => _submitSingle(accountId, expected),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
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
                        : const Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.check, size: 16),
                              SizedBox(width: 4),
                              Text('জমা নিন', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
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
          Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: valueColor)),
        ],
      ),
    );
  }

  Widget _buildSubmitBar(List<Map<String, dynamic>> dueRows) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey.shade200)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 6,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SizedBox(
        width: double.infinity,
        height: 46,
        child: ElevatedButton.icon(
          onPressed: _submittingAll ? null : () => _submitAll(dueRows),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF0F172A),
            foregroundColor: Colors.white,
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
          icon: _submittingAll
              ? const SizedBox(
                  height: 18,
                  width: 18,
                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                )
              : const Icon(Icons.send_rounded, size: 18),
          label: Text(
            _submittingAll ? 'কালেকশন সাবমিট হচ্ছে...' : 'একসাথে সকল বকেয়া জমা দিন (${dueRows.length})',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
          ),
        ),
      ),
    );
  }
}
