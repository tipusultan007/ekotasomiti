import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_endpoints.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/widgets/app_drawer.dart';

final settlementsProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.read(apiClientProvider);
  try {
    final response = await api.dio.get(ApiEndpoints.settlements);
    final rawData = (response.data['data'] as List?) ?? [];
    return rawData.map((s) => Map<String, dynamic>.from(s as Map)).toList();
  } catch (e) {
    debugPrint('settlementsProvider error: $e');
    return [];
  }
});

double _parseAmount(dynamic val) {
  if (val == null) return 0.0;
  if (val is num) return val.toDouble();
  return double.tryParse(val.toString()) ?? 0.0;
}

class SettlementScreen extends ConsumerStatefulWidget {
  const SettlementScreen({super.key});

  @override
  ConsumerState<SettlementScreen> createState() => _SettlementScreenState();
}

class _SettlementScreenState extends ConsumerState<SettlementScreen> {
  String _selectedStatus = 'all'; // 'all', 'submitted', 'received', 'rejected'

  @override
  Widget build(BuildContext context) {
    final isBn = context.isBn;
    final settlementsAsync = ref.watch(settlementsProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(
          isBn ? 'ক্যাশ সেটেলমেন্ট ও ক্লোজিং' : 'Cash Settlement & Closing',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: isBn ? 'রিফ্রেশ করুন' : 'Refresh',
            onPressed: () => ref.invalidate(settlementsProvider),
          ),
        ],
      ),
      drawer: const AppDrawer(),
      body: Column(
        children: [
          _buildStatusBar(isBn),
          Expanded(
            child: settlementsAsync.when(
              loading: () => Center(
                child: AppLoadingIndicator(message: isBn ? 'সেটেলমেন্ট লোড হচ্ছে...' : 'Loading settlements...'),
              ),
              error: (e, _) => Center(
                child: AppErrorWidget(
                  message: isBn ? 'সেটেলমেন্ট লোড করা যায়নি: $e' : 'Failed to load settlements: $e',
                  onRetry: () => ref.invalidate(settlementsProvider),
                ),
              ),
              data: (settlements) {
                final filtered = _selectedStatus == 'all'
                    ? settlements
                    : settlements.where((s) => (s['status'] ?? '').toString().toLowerCase() == _selectedStatus).toList();

                if (filtered.isEmpty) {
                  return RefreshIndicator(
                    onRefresh: () async => ref.invalidate(settlementsProvider),
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: [
                        Padding(
                          padding: const EdgeInsets.only(top: 80),
                          child: EmptyState(
                            icon: Icons.account_balance_wallet_outlined,
                            message: isBn ? 'এই ফিল্টারে কোনো ক্যাশ সেটেলমেন্ট পাওয়া যায়নি।' : 'No cash settlements found for this filter.',
                          ),
                        ),
                      ],
                    ),
                  );
                }

                return RefreshIndicator(
                  onRefresh: () async => ref.invalidate(settlementsProvider),
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 80),
                    itemCount: filtered.length,
                    itemBuilder: (context, index) => _buildSettlementCard(filtered[index], isBn),
                  ),
                );
              },
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openCreateSettlementModal(context),
        backgroundColor: const Color(0xFF0F172A),
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded, size: 20),
        label: Text(isBn ? 'নতুন সেটেলমেন্ট' : 'New Settlement', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
        elevation: 3,
      ),
    );
  }

  Widget _buildStatusBar(bool isBn) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 10),
      child: Row(
        children: [
          _buildFilterChip('all', isBn ? 'সকল' : 'All'),
          const SizedBox(width: 8),
          _buildFilterChip('submitted', isBn ? 'অপেক্ষমাণ' : 'Pending'),
          const SizedBox(width: 8),
          _buildFilterChip('received', isBn ? 'গৃহীত ও সমাপ্ত' : 'Received & Closed'),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String status, String label) {
    final isSelected = _selectedStatus == status;
    return Expanded(
      child: InkWell(
        onTap: () => setState(() => _selectedStatus = status),
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 7),
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

  Widget _buildSettlementCard(Map<String, dynamic> s, bool isBn) {
    final total = _parseAmount(s['total_collection']);
    final submitted = _parseAmount(s['cash_submitted']);
    final remaining = _parseAmount(s['remaining_cash']);
    final savings = _parseAmount(s['savings_collection']);
    final loans = _parseAmount(s['loan_collection']);
    final status = (s['status'] ?? 'submitted').toString();
    final isReceived = status == 'received';

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
          onTap: () => _showSettlementDetailSheet(s, isBn),
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
                        color: isReceived ? const Color(0xFFECFDF5) : const Color(0xFFFFFBEB),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        isReceived ? Icons.verified_outlined : Icons.pending_actions_outlined,
                        color: isReceived ? const Color(0xFF059669) : const Color(0xFFD97706),
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            s['settlement_no'] ?? 'STL-UNKNOWN',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                          ),
                          const SizedBox(height: 2),
                          Row(
                            children: [
                              const Icon(Icons.calendar_today_outlined, size: 12, color: Color(0xFF64748B)),
                              const SizedBox(width: 4),
                              Text(
                                DateFormatter.human(s['settlement_date']),
                                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    StatusBadge(status: status),
                  ],
                ),

                const Divider(height: 24, color: Color(0xFFF1F5F9)),

                Row(
                  children: [
                    Expanded(
                      child: _buildMetricTile(
                        label: isBn ? 'মোট আদায়' : 'Total Collection',
                        amount: total,
                        color: const Color(0xFF0F172A),
                      ),
                    ),
                    Container(width: 1, height: 32, color: const Color(0xFFE2E8F0)),
                    Expanded(
                      child: _buildMetricTile(
                        label: isBn ? 'ক্যাশ জমা' : 'Submitted',
                        amount: submitted,
                        color: const Color(0xFF059669),
                        isBold: true,
                      ),
                    ),
                    if (remaining > 0) ...[
                      Container(width: 1, height: 32, color: const Color(0xFFE2E8F0)),
                      Expanded(
                        child: _buildMetricTile(
                          label: isBn ? 'অবশিষ্ট হাতে' : 'Remaining',
                          amount: remaining,
                          color: const Color(0xFFDC2626),
                        ),
                      ),
                    ],
                  ],
                ),

                const SizedBox(height: 12),

                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Wrap(
                      spacing: 6,
                      children: [
                        _buildSubPill(isBn ? 'সঞ্চয়' : 'Savings', savings, const Color(0xFF2563EB)),
                        _buildSubPill(isBn ? 'লোন' : 'Loans', loans, const Color(0xFF0D9488)),
                      ],
                    ),
                    if (isReceived && s['receiver'] != null)
                      Text(
                        isBn ? 'গ্রহণ করেছেন: ${s['receiver']['name'] ?? 'ক্যাশিয়ার'}' : 'Received by: ${s['receiver']['name'] ?? 'Cashier'}',
                        style: const TextStyle(fontSize: 10, color: Color(0xFF059669), fontWeight: FontWeight.bold),
                      )
                    else
                      Text(
                        isBn ? 'যাচাইয়ের অপেক্ষায়' : 'Awaiting verification',
                        style: const TextStyle(fontSize: 10, color: Color(0xFFD97706), fontWeight: FontWeight.w500),
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

  Widget _buildMetricTile({required String label, required double amount, required Color color, bool isBold = false}) {
    return Column(
      children: [
        Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w500)),
        const SizedBox(height: 2),
        Text(
          CurrencyFormatter.simple(amount),
          style: TextStyle(
            fontSize: 13,
            fontWeight: isBold ? FontWeight.w900 : FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }

  Widget _buildSubPill(String label, double amount, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        '$label: ${CurrencyFormatter.simple(amount)}',
        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: color),
      ),
    );
  }

  void _showSettlementDetailSheet(Map<String, dynamic> s, bool isBn) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(s['settlement_no'] ?? '', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 2),
                    Text(DateFormatter.human(s['settlement_date']), style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                  ],
                ),
                StatusBadge(status: s['status'] ?? ''),
              ],
            ),
            const Divider(height: 24),
            _detailRow(isBn ? 'সঞ্চয় আদায়' : 'Savings Collection', CurrencyFormatter.simple(_parseAmount(s['savings_collection']))),
            _detailRow(isBn ? 'লোন কিস্তি আদায়' : 'Loan Installments', CurrencyFormatter.simple(_parseAmount(s['loan_collection']))),
            _detailRow(isBn ? 'অন্যান্য আদায়' : 'Other Collection', CurrencyFormatter.simple(_parseAmount(s['other_collection']))),
            const Divider(height: 16),
            _detailRow(isBn ? 'মোট কালেকশন' : 'Total Collection', CurrencyFormatter.simple(_parseAmount(s['total_collection'])), bold: true),
            _detailRow(isBn ? 'ক্যাশ জমা দেওয়া হয়েছে' : 'Cash Submitted', CurrencyFormatter.simple(_parseAmount(s['cash_submitted'])), bold: true, color: const Color(0xFF059669)),
            _detailRow(isBn ? 'হাতে অবশিষ্ট ক্যাশ' : 'Remaining Variance', CurrencyFormatter.simple(_parseAmount(s['remaining_cash'])), color: const Color(0xFFDC2626)),
            if (s['notes'] != null && s['notes'].toString().isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(10)),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(isBn ? 'মন্তব্য / নোট:' : 'Notes:', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF475569))),
                    const SizedBox(height: 4),
                    Text(s['notes'].toString(), style: const TextStyle(fontSize: 12, color: Color(0xFF0F172A))),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () => Navigator.pop(ctx),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: Text(isBn ? 'বন্ধ করুন' : 'Close'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _detailRow(String label, String value, {bool bold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 13, color: const Color(0xFF475569), fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          Text(value, style: TextStyle(fontSize: 13, fontWeight: bold ? FontWeight.w900 : FontWeight.bold, color: color ?? const Color(0xFF0F172A))),
        ],
      ),
    );
  }

  void _openCreateSettlementModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => const _CreateSettlementSheet(),
    );
  }
}

class _CreateSettlementSheet extends ConsumerStatefulWidget {
  const _CreateSettlementSheet();

  @override
  ConsumerState<_CreateSettlementSheet> createState() => _CreateSettlementSheetState();
}

class _CreateSettlementSheetState extends ConsumerState<_CreateSettlementSheet> {
  final _formKey = GlobalKey<FormState>();

  DateTime _settlementDate = DateTime.now();

  final _savingsController = TextEditingController(text: '0.00');
  final _loanController = TextEditingController(text: '0.00');
  final _otherController = TextEditingController(text: '0.00');
  final _cashSubmittedController = TextEditingController(text: '0.00');
  final _notesController = TextEditingController();

  bool _loadingPreview = false;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _fetchLivePreview();
    _cashSubmittedController.addListener(() => setState(() {}));
    _otherController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _savingsController.dispose();
    _loanController.dispose();
    _otherController.dispose();
    _cashSubmittedController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _fetchLivePreview() async {
    setState(() => _loadingPreview = true);
    try {
      final api = ref.read(apiClientProvider);
      final dateStr = DateFormatter.api(_settlementDate);
      final res = await api.dio.get(ApiEndpoints.settlementPreview, queryParameters: {'date': dateStr});
      final data = Map<String, dynamic>.from(res.data as Map);

      final savings = _parseAmount(data['savings_collection']);
      final loans = _parseAmount(data['loan_collection']);
      final total = _parseAmount(data['total_collection']);

      if (mounted) {
        setState(() {
          _savingsController.text = savings.toStringAsFixed(2);
          _loanController.text = loans.toStringAsFixed(2);
          _cashSubmittedController.text = total.toStringAsFixed(2);
        });
      }
    } catch (e) {
      debugPrint('Failed to load settlement preview: $e');
    } finally {
      if (mounted) setState(() => _loadingPreview = false);
    }
  }

  double get _computedTotal {
    final s = double.tryParse(_savingsController.text) ?? 0.0;
    final l = double.tryParse(_loanController.text) ?? 0.0;
    final o = double.tryParse(_otherController.text) ?? 0.0;
    return s + l + o;
  }

  double get _cashSubmitted => double.tryParse(_cashSubmittedController.text) ?? 0.0;
  double get _remainingVariance => _computedTotal - _cashSubmitted;

  Future<void> _submitSettlement() async {
    if (!_formKey.currentState!.validate()) return;
    final isBn = context.isBn;

    if (_cashSubmitted <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isBn ? 'জমা দেওয়া ক্যাশের পরিমাণ ০ এর বেশি হতে হবে' : 'Cash submitted must be greater than 0'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _submitting = true);
    final messenger = ScaffoldMessenger.of(context);
    final nav = Navigator.of(context);

    try {
      final api = ref.read(apiClientProvider);
      final res = await api.dio.post(ApiEndpoints.settlements, data: {
        'settlement_date': DateFormatter.api(_settlementDate),
        'savings_collection': double.tryParse(_savingsController.text) ?? 0.0,
        'loan_collection': double.tryParse(_loanController.text) ?? 0.0,
        'other_collection': double.tryParse(_otherController.text) ?? 0.0,
        'cash_submitted': _cashSubmitted,
        'notes': _notesController.text.trim(),
      });

      ref.invalidate(settlementsProvider);

      if (mounted) {
        nav.pop();
        final stlNo = res.data['settlement']?['settlement_no'] ?? '';
        messenger.showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    isBn ? 'সেটেলমেন্ট $stlNo সফলভাবে ব্রাঞ্চে জমা দেওয়া হয়েছে!' : 'Settlement $stlNo submitted successfully to branch vault!',
                  ),
                ),
              ],
            ),
            backgroundColor: const Color(0xFF059669),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isBn ? 'সেটেলমেন্ট সাবমিট করা যায়নি: $e' : 'Failed to submit settlement: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isBn = context.isBn;

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        top: 16,
        left: 20,
        right: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.9),
      child: Form(
        key: _formKey,
        child: ListView(
          shrinkWrap: true,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 16),

            Row(
              children: [
                const Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF2563EB), size: 22),
                const SizedBox(width: 8),
                Text(
                  isBn ? 'দৈনিক ক্যাশ সেটেলমেন্ট' : 'Daily Cash Settlement',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A)),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              isBn ? 'আদায়কৃত টাকা ব্রাঞ্চ ক্যাশিয়ার / ভল্টে জমা দিন ও হিসাব ক্লোজ করুন।' : 'Deposit collected cash to branch cashier / vault and close daily accounts.',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 16),

            InkWell(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: _settlementDate,
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now(),
                );
                if (picked != null) {
                  setState(() => _settlementDate = picked);
                  _fetchLivePreview();
                }
              },
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.event_note_outlined, size: 20, color: Color(0xFF2563EB)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(isBn ? 'সেটেলমেন্ট তারিখ' : 'Settlement Date', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                          const SizedBox(height: 2),
                          Text(
                            DateFormatter.display(_settlementDate),
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          ),
                        ],
                      ),
                    ),
                    Text(isBn ? 'তারিখ পরিবর্তন' : 'Change Date', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF2563EB))),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            Container(
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E3A8A)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(isBn ? 'মোট আদায় (স্বয়ংক্রিয় হিসাব)' : 'Total Collections (Auto Calculated)', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                      if (_loadingPreview)
                        const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      else
                        IconButton(
                          icon: const Icon(Icons.refresh_rounded, size: 16, color: Colors.white70),
                          onPressed: _fetchLivePreview,
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                          tooltip: isBn ? 'পুনরায় গণনা' : 'Recalculate',
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    CurrencyFormatter.simple(_computedTotal),
                    style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w900),
                  ),
                  const Divider(color: Colors.white12, height: 20),
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(isBn ? 'সঞ্চয় জমা' : 'Savings', style: const TextStyle(color: Colors.white60, fontSize: 10)),
                            const SizedBox(height: 2),
                            Text(
                              CurrencyFormatter.simple(double.tryParse(_savingsController.text) ?? 0),
                              style: const TextStyle(color: Color(0xFF93C5FD), fontSize: 13, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ),
                      Container(width: 1, height: 24, color: Colors.white12),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(isBn ? 'লোন কিস্তি' : 'Loan Installments', style: const TextStyle(color: Colors.white60, fontSize: 10)),
                            const SizedBox(height: 2),
                            Text(
                              CurrencyFormatter.simple(double.tryParse(_loanController.text) ?? 0),
                              style: const TextStyle(color: Color(0xFF6EE7B7), fontSize: 13, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            TextFormField(
              controller: _cashSubmittedController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              decoration: InputDecoration(
                labelText: isBn ? 'ভল্টে ক্যাশ জমা দেওয়া হচ্ছে *' : 'Cash Submitted to Vault *',
                prefixText: '৳ ',
                hintText: '0.00',
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5)),
                suffixIcon: TextButton(
                  onPressed: () => setState(() => _cashSubmittedController.text = _computedTotal.toStringAsFixed(2)),
                  child: Text(isBn ? 'সম্পূর্ণ টাকা' : 'Exact Amount', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                ),
              ),
              validator: (v) {
                if (v == null || v.trim().isEmpty) return isBn ? 'জমা দেওয়া টাকার পরিমাণ লিখুন' : 'Please enter amount';
                final val = double.tryParse(v);
                if (val == null || val <= 0) return isBn ? 'সঠিক পরিমাণ লিখুন' : 'Enter a valid amount';
                return null;
              },
            ),

            const SizedBox(height: 10),

            if (_remainingVariance == 0)
              _buildVarianceCard(
                icon: Icons.check_circle_rounded,
                color: const Color(0xFF059669),
                bgColor: const Color(0xFFECFDF5),
                title: isBn ? 'হিসাব সম্পন্ন (Exact Match)' : 'Balanced (Exact Match)',
                subtitle: isBn ? 'আদায়কৃত টাকার সাথে জমা দেওয়া ক্যাশ সম্পূর্ণ মিলে গেছে।' : 'Submitted cash matches collection amount perfectly.',
              )
            else if (_remainingVariance > 0)
              _buildVarianceCard(
                icon: Icons.info_outline_rounded,
                color: const Color(0xFFD97706),
                bgColor: const Color(0xFFFFFBEB),
                title: isBn ? 'অবশিষ্ট ক্যাশ: ${CurrencyFormatter.simple(_remainingVariance)}' : 'Remaining Cash: ${CurrencyFormatter.simple(_remainingVariance)}',
                subtitle: isBn ? 'এই টাকা আপনার ব্যক্তিগত দায়িত্বে থাকবে।' : 'This balance will remain in your personal custody.',
              )
            else
              _buildVarianceCard(
                icon: Icons.add_circle_outline_rounded,
                color: const Color(0xFF2563EB),
                bgColor: const Color(0xFFEFF6FF),
                title: isBn ? 'অতিরিক্ত ক্যাশ: ${CurrencyFormatter.simple(-_remainingVariance)}' : 'Surplus Cash: ${CurrencyFormatter.simple(-_remainingVariance)}',
                subtitle: isBn ? 'আদায়কৃত রেকর্ডের চেয়ে বেশি ক্যাশ জমা দেওয়া হচ্ছে।' : 'Submitting more cash than collections recorded.',
              ),

            const SizedBox(height: 14),

            TextFormField(
              controller: _notesController,
              maxLines: 2,
              style: const TextStyle(fontSize: 13),
              decoration: InputDecoration(
                labelText: isBn ? 'মন্তব্য / নোট (ঐচ্ছিক)' : 'Notes (Optional)',
                hintText: isBn ? 'যেমন: সমিতি ১২ এর ক্যাশ হস্তান্তর' : 'e.g., Handed over center collection',
                hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 12),
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
              ),
            ),

            const SizedBox(height: 20),

            ElevatedButton(
              onPressed: _submitting ? null : _submitSettlement,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 2,
              ),
              child: _submitting
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : Text(isBn ? 'ভল্টে সেটেলমেন্ট সাবমিট করুন' : 'Submit Settlement to Vault', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
            ),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }

  Widget _buildVarianceCard({
    required IconData icon,
    required Color color,
    required Color bgColor,
    required String title,
    required String subtitle,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color)),
                const SizedBox(height: 1),
                Text(subtitle, style: TextStyle(fontSize: 11, color: color.withValues(alpha: 0.85))),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
