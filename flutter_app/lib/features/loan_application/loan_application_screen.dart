import 'dart:async';
import 'dart:io';
import 'dart:math' as math;
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/models/loan.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/currency_formatter.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/member_avatar.dart';
import '../../shared/widgets/app_drawer.dart';

final loanProductsProvider = FutureProvider<List<LoanProduct>>((ref) async {
  final api = ref.read(apiClientProvider);
  try {
    final response = await api.dio.get(ApiEndpoints.loanProducts);
    final rawData = (response.data as List?) ?? [];
    return rawData.map((p) => LoanProduct.fromJson(Map<String, dynamic>.from(p as Map))).toList();
  } catch (e) {
    debugPrint('loanProductsProvider error: $e');
    return [];
  }
});

final membersSearchQueryProvider = StateProvider<String>((ref) => '');

final membersSearchProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  final search = ref.watch(membersSearchQueryProvider);
  if (search.trim().isEmpty) return [];
  final api = ref.read(apiClientProvider);
  try {
    final response = await api.dio.get(
      ApiEndpoints.members,
      queryParameters: {'search': search, 'per_page': '20', 'status': 'active'},
    );
    final rawData = (response.data['data'] as List?) ?? [];
    return rawData.map((m) => Map<String, dynamic>.from(m as Map)).toList();
  } catch (e) {
    return [];
  }
});

class GuarantorFieldGroup {
  final TextEditingController name = TextEditingController();
  final TextEditingController relation = TextEditingController();
  final TextEditingController nid = TextEditingController();
  final TextEditingController mobile = TextEditingController();
  final TextEditingController address = TextEditingController();

  void dispose() {
    name.dispose();
    relation.dispose();
    nid.dispose();
    mobile.dispose();
    address.dispose();
  }

  Map<String, dynamic> toMap() => {
        'name': name.text.trim(),
        if (relation.text.trim().isNotEmpty) 'relationship': relation.text.trim(),
        if (nid.text.trim().isNotEmpty) 'nid': nid.text.trim(),
        if (mobile.text.trim().isNotEmpty) 'mobile': mobile.text.trim(),
        if (address.text.trim().isNotEmpty) 'address': address.text.trim(),
      };
}

class DocumentFieldGroup {
  String type = 'nid';
  final TextEditingController title = TextEditingController();
  XFile? file;

  void dispose() {
    title.dispose();
  }
}

class LoanApplicationScreen extends ConsumerStatefulWidget {
  final int? initialMemberId;
  const LoanApplicationScreen({super.key, this.initialMemberId});

  @override
  ConsumerState<LoanApplicationScreen> createState() => _LoanApplicationScreenState();
}

class _LoanApplicationScreenState extends ConsumerState<LoanApplicationScreen> {
  final _formKey = GlobalKey<FormState>();

  final _amountController = TextEditingController();
  final _termController = TextEditingController();
  final _installmentController = TextEditingController();
  final _purposeController = TextEditingController();
  final _remarksController = TextEditingController();
  final _searchController = TextEditingController();

  bool _isInstallmentManuallyEdited = false;
  Timer? _debounce;

  int? _selectedMemberId;
  String _selectedMemberName = '';
  String _selectedMemberNo = '';
  String? _selectedMemberPhone;
  String? _selectedMemberPhoto;

  LoanProduct? _selectedProduct;

  DateTime _applicationDate = DateTime.now();
  DateTime _disbursementDate = DateTime.now();
  DateTime _firstDueDate = DateTime.now().add(const Duration(days: 7));

  String _paymentMethod = 'cash';
  bool _submitting = false;

  final List<GuarantorFieldGroup> _guarantors = [];
  final List<DocumentFieldGroup> _documents = [];

  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _amountController.addListener(_onAmountOrTermChanged);
    _termController.addListener(_onAmountOrTermChanged);
    _installmentController.addListener(() => setState(() {}));

    if (widget.initialMemberId != null) {
      _selectedMemberId = widget.initialMemberId;
    }
  }

  void _onAmountOrTermChanged() {
    if (!_isInstallmentManuallyEdited) {
      _autoCalculateInstallment();
    }
    setState(() {});
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _amountController.dispose();
    _termController.dispose();
    _installmentController.dispose();
    _purposeController.dispose();
    _remarksController.dispose();
    _searchController.dispose();
    for (final g in _guarantors) {
      g.dispose();
    }
    for (final d in _documents) {
      d.dispose();
    }
    super.dispose();
  }

  void _addGuarantor() {
    if (_guarantors.length >= 5) return;
    setState(() {
      _guarantors.add(GuarantorFieldGroup());
    });
  }

  void _removeGuarantor(int index) {
    setState(() {
      final removed = _guarantors.removeAt(index);
      removed.dispose();
    });
  }

  void _addDocument() {
    if (_documents.length >= 10) return;
    setState(() {
      _documents.add(DocumentFieldGroup());
    });
  }

  void _removeDocument(int index) {
    setState(() {
      final removed = _documents.removeAt(index);
      removed.dispose();
    });
  }

  Future<void> _pickDocumentImage(int index, ImageSource source) async {
    try {
      final picked = await _picker.pickImage(
        source: source,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 85,
      );
      if (picked != null) {
        setState(() {
          _documents[index].file = picked;
          if (_documents[index].title.text.trim().isEmpty) {
            _documents[index].title.text = _getDocumentTypeLabel(_documents[index].type);
          }
        });
      }
    } catch (e) {
      if (mounted) {
        _showErrorSnackBar('ফাইল নির্বাচন করা যায়নি: $e');
      }
    }
  }

  void _showDocumentPickerModal(int index) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      backgroundColor: Colors.white,
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
              ),
              const Text('ডকুমেন্ট ছবি আপলোড করুন', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.photo_camera_rounded, color: Color(0xFF2563EB)),
                ),
                title: const Text('ক্যামেরা দিয়ে ছবি তুলুন', style: TextStyle(fontWeight: FontWeight.w600)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickDocumentImage(index, ImageSource.camera);
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.photo_library_rounded, color: Color(0xFF059669)),
                ),
                title: const Text('গ্যালারি / ফাইল থেকে নির্বাচন করুন', style: TextStyle(fontWeight: FontWeight.w600)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickDocumentImage(index, ImageSource.gallery);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _getDocumentTypeLabel(String type) {
    switch (type) {
      case 'nid':
        return 'জাতীয় পরিচয়পত্র (NID)';
      case 'utility_bill':
        return 'বিদ্যুৎ / ইউটিলিটি বিল';
      case 'agreement':
        return 'লোন চুক্তিপত্র / স্ট্যাম্প';
      case 'income_proof':
        return 'আয়ের প্রমাণপত্র';
      case 'guarantor_doc':
        return 'জামিনদারের ডকুমেন্ট';
      default:
        return 'অন্যান্য ডকুমেন্ট';
    }
  }

  double _calculateDefaultInstallment() {
    final amount = double.tryParse(_amountController.text) ?? 0.0;
    final term = int.tryParse(_termController.text) ?? 0;
    if (amount <= 0 || term <= 0 || _selectedProduct == null) return 0.0;

    final rate = _selectedProduct!.interestRate;
    final isReducing = _selectedProduct!.interestType.toLowerCase() == 'reducing';
    final freq = _selectedProduct!.frequency.toLowerCase();

    if (isReducing) {
      double periodDivider = 12.0;
      if (freq == 'daily') {
        periodDivider = 365.0;
      } else if (freq == 'weekly') {
        periodDivider = 52.0;
      }
      final periodRate = (rate / periodDivider) / 100.0;
      if (periodRate > 0 && term > 0) {
        final factor = math.pow(1.0 + periodRate, term).toDouble();
        return (amount * periodRate * factor) / (factor - 1.0);
      }
    }

    final totalInterest = amount * (rate / 100.0);
    final totalPayable = amount + totalInterest;
    return totalPayable / term;
  }

  void _autoCalculateInstallment() {
    final calculated = _calculateDefaultInstallment();
    if (calculated > 0) {
      _installmentController.text = calculated.toStringAsFixed(2);
    } else {
      _installmentController.clear();
    }
  }

  void _onProductSelected(LoanProduct? p) {
    setState(() {
      _selectedProduct = p;
      _isInstallmentManuallyEdited = false;
      if (p != null) {
        if (_termController.text.isEmpty || int.tryParse(_termController.text) == 0) {
          _termController.text = '${p.minTerm}';
        }
        final now = DateTime.now();
        switch (p.frequency.toLowerCase()) {
          case 'daily':
            _firstDueDate = now.add(const Duration(days: 1));
            break;
          case 'weekly':
            _firstDueDate = now.add(const Duration(days: 7));
            break;
          case 'monthly':
            _firstDueDate = DateTime(now.year, now.month + 1, now.day);
            break;
          default:
            _firstDueDate = now.add(const Duration(days: 7));
        }
      }
      _autoCalculateInstallment();
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedMemberId == null) {
      _showErrorSnackBar('অনুগ্রহ করে একজন সদস্য নির্বাচন করুন');
      return;
    }
    if (_selectedProduct == null) {
      _showErrorSnackBar('অনুগ্রহ করে একটি লোন প্রোডাক্ট নির্বাচন করুন');
      return;
    }

    final requestedAmount = double.tryParse(_amountController.text) ?? 0;
    final requestedTerm = int.tryParse(_termController.text) ?? 0;
    final requestedInstallment = double.tryParse(_installmentController.text) ?? 0;

    if (requestedAmount <= 0) {
      _showErrorSnackBar('লোনের পরিমাণ ০ এর বেশি হতে হবে');
      return;
    }
    if (_selectedProduct!.minAmount > 0 && requestedAmount < _selectedProduct!.minAmount) {
      _showErrorSnackBar('সর্বনিম্ন লোনের পরিমাণ ${CurrencyFormatter.simple(_selectedProduct!.minAmount)}');
      return;
    }
    if (_selectedProduct!.maxAmount > 0 && requestedAmount > _selectedProduct!.maxAmount) {
      _showErrorSnackBar('সর্বোচ্চ লোনের পরিমাণ ${CurrencyFormatter.simple(_selectedProduct!.maxAmount)}');
      return;
    }
    if (requestedTerm < _selectedProduct!.minTerm || requestedTerm > _selectedProduct!.maxTerm) {
      _showErrorSnackBar('কিস্তির সংখ্যা ${_selectedProduct!.minTerm} থেকে ${_selectedProduct!.maxTerm} এর মধ্যে হতে হবে');
      return;
    }
    if (requestedInstallment <= 0) {
      _showErrorSnackBar('অনুগ্রহ করে সঠিক কিস্তির পরিমাণ লিখুন');
      return;
    }

    final validGuarantors = _guarantors
        .where((g) => g.name.text.trim().isNotEmpty)
        .map((g) => g.toMap())
        .toList();

    final validDocuments = _documents.where((d) => d.file != null).toList();

    setState(() => _submitting = true);
    final messenger = ScaffoldMessenger.of(context);
    final nav = Navigator.of(context);

    try {
      final api = ref.read(apiClientProvider);

      // Package multipart FormData
      final formData = FormData();
      formData.fields.addAll([
        MapEntry('member_id', _selectedMemberId.toString()),
        MapEntry('loan_product_id', _selectedProduct!.id.toString()),
        MapEntry('requested_amount', requestedAmount.toString()),
        MapEntry('requested_term', requestedTerm.toString()),
        MapEntry('installment_amount', requestedInstallment.toStringAsFixed(2)),
        MapEntry('purpose', _purposeController.text.trim()),
        MapEntry('remarks', _remarksController.text.trim()),
        MapEntry('application_date', DateFormatter.api(_applicationDate)),
        MapEntry('disbursement_date', DateFormatter.api(_disbursementDate)),
        MapEntry('first_due_date', DateFormatter.api(_firstDueDate)),
        MapEntry('payment_method', _paymentMethod),
      ]);

      // Add guarantors
      for (int i = 0; i < validGuarantors.length; i++) {
        final g = validGuarantors[i];
        formData.fields.add(MapEntry('guarantors[$i][name]', g['name'] ?? ''));
        if (g['relationship'] != null) formData.fields.add(MapEntry('guarantors[$i][relationship]', g['relationship']!));
        if (g['nid'] != null) formData.fields.add(MapEntry('guarantors[$i][nid]', g['nid']!));
        if (g['mobile'] != null) formData.fields.add(MapEntry('guarantors[$i][mobile]', g['mobile']!));
        if (g['address'] != null) formData.fields.add(MapEntry('guarantors[$i][address]', g['address']!));
      }

      // Add uploaded documents
      for (int i = 0; i < validDocuments.length; i++) {
        final doc = validDocuments[i];
        formData.files.add(MapEntry(
          'documents[]',
          await MultipartFile.fromFile(
            doc.file!.path,
            filename: doc.file!.name,
          ),
        ));
        formData.fields.add(MapEntry('document_types[]', doc.type));
        formData.fields.add(MapEntry(
          'document_titles[]',
          doc.title.text.trim().isNotEmpty ? doc.title.text.trim() : _getDocumentTypeLabel(doc.type),
        ));
      }

      final response = await api.dio.post(ApiEndpoints.loanApplications, data: formData);
      final loanNo = response.data['loan_no'] ?? '';
      final appNo = response.data['application_no'] ?? '';

      if (mounted) {
        messenger.showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text('লোন $loanNo ($appNo) সফলভাবে অনুমোদিত ও বিতরণ করা হয়েছে!'),
                ),
              ],
            ),
            backgroundColor: const Color(0xFF059669),
            duration: const Duration(seconds: 4),
          ),
        );
        nav.pop(true);
      }
    } catch (e) {
      if (mounted) {
        _showErrorSnackBar('আবেদন সম্পন্ন করা যায়নি: $e');
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.error_outline_rounded, color: Colors.white, size: 18),
            const SizedBox(width: 8),
            Expanded(child: Text(message)),
          ],
        ),
        backgroundColor: const Color(0xFFDC2626),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      drawer: const AppDrawer(),
      appBar: AppBar(
        leading: const BackButton(),
        title: const Text(
          'নতুন লোন আবেদন ও বিতরণ',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu_rounded),
              tooltip: 'মেনু',
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          children: [
            // Section 1: Member Selection
            _buildMemberSection(),
            const SizedBox(height: 16),

            // Section 2: Loan Product Selection
            _buildProductSection(),
            const SizedBox(height: 16),

            // Section 3: Amount, Term & Live Calculations
            _buildCalculationSection(),
            const SizedBox(height: 16),

            // Section 4: Disbursement & Schedule
            _buildDisbursementSection(),
            const SizedBox(height: 16),

            // Section 5: Guarantors
            _buildGuarantorsSection(),
            const SizedBox(height: 16),

            // Section 6: Loan Documents
            _buildDocumentsSection(),
            const SizedBox(height: 16),

            // Section 7: Purpose & Remarks
            _buildPurposeSection(),
            const SizedBox(height: 24),

            // Submit Button
            ElevatedButton(
              onPressed: _submitting ? null : _submit,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 2,
              ),
              child: _submitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text(
                      'লোন তৈরি ও বিতরণ করুন',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                    ),
            ),
            const SizedBox(height: 36),
          ],
        ),
      ),
    );
  }

  Widget _buildMemberSection() {
    final hasSelectedMember = _selectedMemberId != null && _selectedMemberName.isNotEmpty;

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
          Row(
            children: [
              const Icon(Icons.person_outline_rounded, color: Color(0xFF2563EB), size: 18),
              const SizedBox(width: 8),
              const Text(
                'সদস্য তথ্য *',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
              ),
              const Spacer(),
              if (hasSelectedMember)
                TextButton(
                  onPressed: () => setState(() {
                    _selectedMemberId = null;
                    _selectedMemberName = '';
                    _selectedMemberNo = '';
                    _selectedMemberPhone = null;
                    _selectedMemberPhoto = null;
                  }),
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                  child: const Text('পরিবর্তন', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                ),
            ],
          ),
          const SizedBox(height: 12),

          if (hasSelectedMember) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFBFDBFE)),
              ),
              child: Row(
                children: [
                  MemberAvatar(
                    name: _selectedMemberName,
                    photoUrl: _selectedMemberPhoto,
                    radius: 20,
                    fontSize: 15,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _selectedMemberName,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E40AF)),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'সদস্য নং: $_selectedMemberNo${_selectedMemberPhone != null ? ' • $_selectedMemberPhone' : ''}',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF3B82F6)),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.check_circle_rounded, color: Color(0xFF2563EB), size: 20),
                ],
              ),
            ),
          ] else ...[
            TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'নাম, মোবাইল নম্বর বা সদস্য নম্বর দিয়ে খুঁজুন...',
                hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
                prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Color(0xFF64748B)),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 16),
                        onPressed: () {
                          _searchController.clear();
                          ref.read(membersSearchQueryProvider.notifier).state = '';
                        },
                      )
                    : null,
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              ),
              onChanged: (v) {
                _debounce?.cancel();
                _debounce = Timer(const Duration(milliseconds: 350), () {
                  ref.read(membersSearchQueryProvider.notifier).state = v.trim();
                });
              },
            ),
            Consumer(builder: (context, ref, _) {
              final query = ref.watch(membersSearchQueryProvider);
              if (query.trim().isEmpty) return const SizedBox.shrink();

              final searchAsync = ref.watch(membersSearchProvider);
              return searchAsync.when(
                loading: () => const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Center(child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))),
                ),
                error: (e, _) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Text('ত্রুটি: $e', style: const TextStyle(color: Colors.red, fontSize: 12)),
                ),
                data: (members) {
                  if (members.isEmpty) {
                    return const Padding(
                      padding: EdgeInsets.symmetric(vertical: 10),
                      child: Text('কোনো সক্রিয় সদস্য মেলেনি', style: TextStyle(color: Colors.grey, fontSize: 12)),
                    );
                  }
                  return Container(
                    margin: const EdgeInsets.only(top: 8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: members.take(5).length,
                      separatorBuilder: (context, index) => Divider(height: 1, color: Colors.grey.shade100),
                      itemBuilder: (context, index) {
                        final m = members[index];
                        final mName = m['name'] ?? 'অজ্ঞাত';
                        final mNo = m['member_no'] ?? '';
                        final mPhone = m['mobile'] ?? '';

                        return ListTile(
                          dense: true,
                          leading: MemberAvatar(name: mName, photoUrl: m['photo_url'] ?? m['photo_path'], radius: 16, fontSize: 12),
                          title: Text(mName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                          subtitle: Text('$mNo${mPhone.isNotEmpty ? ' • $mPhone' : ''}', style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                          trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 12, color: Colors.grey),
                          onTap: () {
                            setState(() {
                              _selectedMemberId = m['id'];
                              _selectedMemberName = mName;
                              _selectedMemberNo = mNo;
                              _selectedMemberPhone = mPhone.isNotEmpty ? mPhone : null;
                              _selectedMemberPhoto = m['photo_url'] ?? m['photo_path'];
                            });
                            _searchController.clear();
                            ref.read(membersSearchQueryProvider.notifier).state = '';
                          },
                        );
                      },
                    ),
                  );
                },
              );
            }),
          ],
        ],
      ),
    );
  }

  Widget _buildProductSection() {
    final productsAsync = ref.watch(loanProductsProvider);

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
          const Row(
            children: [
              Icon(Icons.category_outlined, color: Color(0xFF2563EB), size: 18),
              SizedBox(width: 8),
              Text(
                'লোন প্রোডাক্ট স্কিম *',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          productsAsync.when(
            loading: () => const Center(
              child: Padding(
                padding: EdgeInsets.all(12),
                child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)),
              ),
            ),
            error: (e, _) => Text('প্রোডাক্ট লোড করা যায়নি: $e', style: const TextStyle(color: Colors.red, fontSize: 12)),
            data: (products) {
              if (products.isEmpty) {
                return const Text('কোনো সক্রিয় লোন প্রোডাক্ট পাওয়া যায়নি।', style: TextStyle(color: Colors.grey, fontSize: 12));
              }

              return DropdownButtonFormField<LoanProduct>(
                isExpanded: true,
                initialValue: _selectedProduct,
                decoration: InputDecoration(
                  labelText: 'প্রোডাক্ট নির্বাচন করুন',
                  hintText: 'লোন স্কিম বাছুন',
                  filled: true,
                  fillColor: const Color(0xFFF8FAFC),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                  focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                ),
                items: products.map((p) {
                  final freqBn = p.frequency == 'daily' ? 'দৈনিক' : (p.frequency == 'weekly' ? 'সাপ্তাহিক' : 'মাসিক');
                  return DropdownMenuItem<LoanProduct>(
                    value: p,
                    child: Text(
                      '${p.name} ($freqBn • ${p.interestRate.toStringAsFixed(1)}%)',
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
                      overflow: TextOverflow.ellipsis,
                    ),
                  );
                }).toList(),
                onChanged: _onProductSelected,
                validator: (v) => v == null ? 'অনুগ্রহ করে লোন প্রোডাক্ট নির্বাচন করুন' : null,
              );
            },
          ),
          if (_selectedProduct != null) ...[
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: 8,
                    runSpacing: 6,
                    children: [
                      _buildProductTag(
                        icon: Icons.percent_rounded,
                        text: '${_selectedProduct!.interestRate}% (${_selectedProduct!.interestType == 'flat' ? 'ফ্ল্যাট' : 'হ্রাসমান'})',
                      ),
                      _buildProductTag(
                        icon: Icons.repeat_rounded,
                        text: '${_selectedProduct!.frequency == 'daily' ? 'দৈনিক' : (_selectedProduct!.frequency == 'weekly' ? 'সাপ্তাহিক' : 'মাসিক')} (${_selectedProduct!.minTerm}-${_selectedProduct!.maxTerm} কিস্তি)',
                      ),
                      if (_selectedProduct!.minAmount > 0 || _selectedProduct!.maxAmount > 0)
                        _buildProductTag(
                          icon: Icons.account_balance_wallet_outlined,
                          text: '${CurrencyFormatter.simple(_selectedProduct!.minAmount)} - ${CurrencyFormatter.simple(_selectedProduct!.maxAmount)}',
                        ),
                    ],
                  ),
                  if (_selectedProduct!.description != null && _selectedProduct!.description!.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Text(
                      _selectedProduct!.description!,
                      style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildProductTag({required IconData icon, required String text}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: const Color(0xFF2563EB)),
          const SizedBox(width: 4),
          Text(
            text,
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
          ),
        ],
      ),
    );
  }

  Widget _buildCalculationSection() {
    final amount = double.tryParse(_amountController.text) ?? 0.0;
    final term = int.tryParse(_termController.text) ?? 0;
    final rate = _selectedProduct?.interestRate ?? 0.0;
    final frequency = _selectedProduct?.frequency ?? 'monthly';
    final freqLabel = frequency == 'daily' ? 'দিন' : (frequency == 'weekly' ? 'সপ্তাহ' : 'মাস');

    final manualInstallment = double.tryParse(_installmentController.text) ?? 0.0;
    double totalRepayable = 0.0;
    double totalInterest = 0.0;
    double finalInstallment = 0.0;

    if (manualInstallment > 0) {
      finalInstallment = manualInstallment;
      totalRepayable = term > 0 ? (manualInstallment * term) : amount;
      totalInterest = math.max(0.0, totalRepayable - amount);
    } else {
      final defaultInst = _calculateDefaultInstallment();
      finalInstallment = defaultInst;
      if (_selectedProduct?.interestType.toLowerCase() == 'reducing' && term > 0 && defaultInst > 0) {
        totalRepayable = defaultInst * term;
        totalInterest = math.max(0.0, totalRepayable - amount);
      } else {
        totalInterest = amount * (rate / 100);
        totalRepayable = amount + totalInterest;
      }
    }

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
          const Row(
            children: [
              Icon(Icons.calculate_outlined, color: Color(0xFF2563EB), size: 18),
              SizedBox(width: 8),
              Text(
                'লোনের পরিমাণ ও কিস্তির বিবরণ *',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                flex: 3,
                child: TextFormField(
                  controller: _amountController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  decoration: InputDecoration(
                    labelText: 'কাঙ্ক্ষিত লোনের পরিমাণ *',
                    hintText: '0.00',
                    prefixText: '৳ ',
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  ),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return 'পরিমাণ আবশ্যক';
                    final val = double.tryParse(v);
                    if (val == null || val <= 0) return 'সঠিক পরিমাণ লিখুন';
                    return null;
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: TextFormField(
                  controller: _termController,
                  keyboardType: TextInputType.number,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  decoration: InputDecoration(
                    labelText: 'মেয়াদ (কিস্তি) *',
                    hintText: 'যেমন: ১২',
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  ),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return 'কিস্তি আবশ্যক';
                    final val = int.tryParse(v);
                    if (val == null || val <= 0) return 'সঠিক সংখ্যা লিখুন';
                    return null;
                  },
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),

          // Manual Installment Amount Field
          TextFormField(
            controller: _installmentController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1D4ED8)),
            decoration: InputDecoration(
              labelText: 'প্রতি কিস্তির পরিমাণ (ম্যানুয়াল নির্ধারণযোগ্য) *',
              hintText: '0.00',
              prefixText: '৳ ',
              prefixStyle: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8)),
              suffixIcon: _isInstallmentManuallyEdited
                  ? TextButton.icon(
                      onPressed: () {
                        setState(() {
                          _isInstallmentManuallyEdited = false;
                          _autoCalculateInstallment();
                        });
                      },
                      icon: const Icon(Icons.refresh_rounded, size: 14, color: Color(0xFF2563EB)),
                      label: const Text('স্বয়ংক্রিয়', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF2563EB))),
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 8),
                      ),
                    )
                  : const Padding(
                      padding: EdgeInsets.only(right: 12),
                      child: Tooltip(
                        message: 'স্বয়ংক্রিয়ভাবে হিসাবকৃত',
                        child: Icon(Icons.auto_awesome_rounded, size: 18, color: Color(0xFF2563EB)),
                      ),
                    ),
              filled: true,
              fillColor: const Color(0xFFEFF6FF),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFBFDBFE))),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFBFDBFE))),
              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              helperText: 'ফিল্ড অফিসার চাইলে এই কিস্তির পরিমাণ ম্যানুয়ালি পরিবর্তন করতে পারেন',
              helperStyle: TextStyle(fontSize: 11, color: Colors.grey.shade600),
            ),
            onChanged: (val) {
              setState(() {
                _isInstallmentManuallyEdited = true;
              });
            },
            validator: (v) {
              if (v == null || v.trim().isEmpty) return 'কিস্তির পরিমাণ আবশ্যক';
              final val = double.tryParse(v);
              if (val == null || val <= 0) return 'সঠিক কিস্তির পরিমাণ লিখুন';
              return null;
            },
          ),

          const SizedBox(height: 14),

          // Live Calculation Breakdown
          Container(
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF0F172A), Color(0xFF1E3A8A)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(14),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF1E3A8A).withValues(alpha: 0.2),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            padding: const EdgeInsets.all(14),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('মোট পরিশোধযোগ্য টাকা', style: TextStyle(color: Colors.white70, fontSize: 12)),
                    Text(
                      CurrencyFormatter.simple(totalRepayable),
                      style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w900),
                    ),
                  ],
                ),
                const Divider(color: Colors.white12, height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('মুনাফা / সুদ ($rate%)', style: const TextStyle(color: Colors.white60, fontSize: 10)),
                        const SizedBox(height: 2),
                        Text(
                          CurrencyFormatter.simple(totalInterest),
                          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text('প্রতি $freqLabel কিস্তির পরিমাণ', style: const TextStyle(color: Colors.white60, fontSize: 10)),
                        const SizedBox(height: 2),
                        Text(
                          CurrencyFormatter.simple(finalInstallment),
                          style: const TextStyle(color: Color(0xFF6EE7B7), fontSize: 13, fontWeight: FontWeight.w900),
                        ),
                      ],
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

  Widget _buildDisbursementSection() {
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
          const Row(
            children: [
              Icon(Icons.calendar_month_outlined, color: Color(0xFF2563EB), size: 18),
              SizedBox(width: 8),
              Text(
                'তারিখ ও পেমেন্ট মাধ্যম *',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildDatePicker(
                  label: 'আবেদনের তারিখ',
                  date: _applicationDate,
                  onChanged: (d) => setState(() => _applicationDate = d),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildDatePicker(
                  label: 'বিতরণের তারিখ',
                  date: _disbursementDate,
                  onChanged: (d) => setState(() => _disbursementDate = d),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          _buildDatePicker(
            label: 'প্রথম কিস্তির তারিখ',
            date: _firstDueDate,
            onChanged: (d) => setState(() => _firstDueDate = d),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: _paymentMethod,
            decoration: InputDecoration(
              labelText: 'পেমেন্ট মাধ্যম *',
              filled: true,
              fillColor: const Color(0xFFF8FAFC),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            ),
            items: const [
              DropdownMenuItem(value: 'cash', child: Text('নগদ (Cash)')),
              DropdownMenuItem(value: 'bank', child: Text('ব্যাংক ট্রান্সফার')),
              DropdownMenuItem(value: 'bkash', child: Text('বিকাশ (bKash)')),
              DropdownMenuItem(value: 'nagad', child: Text('নগদ (Nagad)')),
              DropdownMenuItem(value: 'other', child: Text('অন্যান্য')),
            ],
            onChanged: (v) => setState(() => _paymentMethod = v ?? 'cash'),
          ),
        ],
      ),
    );
  }

  Widget _buildDatePicker({
    required String label,
    required DateTime date,
    required ValueChanged<DateTime> onChanged,
  }) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: date,
          firstDate: DateTime(2020),
          lastDate: DateTime(2035),
        );
        if (picked != null) onChanged(picked);
      },
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.shade300),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
            const SizedBox(height: 3),
            Row(
              children: [
                Expanded(
                  child: Text(
                    DateFormatter.display(date),
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                ),
                Icon(Icons.calendar_today_outlined, size: 14, color: Colors.grey.shade500),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGuarantorsSection() {
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.people_outline_rounded, color: Color(0xFF2563EB), size: 18),
                  SizedBox(width: 8),
                  Text(
                    'জামিনদারগণ (ঐচ্ছিক)',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              if (_guarantors.length < 5)
                TextButton.icon(
                  onPressed: _addGuarantor,
                  icon: const Icon(Icons.add_circle_outline_rounded, size: 14),
                  label: const Text('জামিনদার যোগ করুন', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                ),
            ],
          ),
          if (_guarantors.isEmpty) ...[
            const SizedBox(height: 8),
            Text(
              'এখনও কোনো জামিনদার যোগ করা হয়নি। প্রয়োজনে "জামিনদার যোগ করুন" বোতামে ট্যাপ করুন।',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
            ),
          ],
          for (int i = 0; i < _guarantors.length; i++) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'জামিনদার #${i + 1}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF1E3A8A)),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close_rounded, size: 16, color: Color(0xFFDC2626)),
                        onPressed: () => _removeGuarantor(i),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                        tooltip: 'মুছুন',
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _guarantors[i].name,
                          style: const TextStyle(fontSize: 12),
                          decoration: _inputDeco('পূর্ণ নাম *', 'জামিনদারের নাম'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextFormField(
                          controller: _guarantors[i].relation,
                          style: const TextStyle(fontSize: 12),
                          decoration: _inputDeco('সম্পর্ক', 'যেমন: ভাই, চাচা, প্রতিবেশী'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _guarantors[i].mobile,
                          keyboardType: TextInputType.phone,
                          style: const TextStyle(fontSize: 12),
                          decoration: _inputDeco('মোবাইল নম্বর', '০১XXXXXXXXX'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextFormField(
                          controller: _guarantors[i].nid,
                          style: const TextStyle(fontSize: 12),
                          decoration: _inputDeco('জাতীয় পরিচয়পত্র (NID)', 'NID নম্বর'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildDocumentsSection() {
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.attach_file_rounded, color: Color(0xFF2563EB), size: 18),
                  SizedBox(width: 8),
                  Text(
                    'লোন ডকুমেন্টস / কাগজপত্র (ঐচ্ছিক)',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              if (_documents.length < 10)
                TextButton.icon(
                  onPressed: _addDocument,
                  icon: const Icon(Icons.add_circle_outline_rounded, size: 14),
                  label: const Text('ডকুমেন্ট যোগ করুন', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                ),
            ],
          ),
          if (_documents.isEmpty) ...[
            const SizedBox(height: 8),
            Text(
              'এখনও কোনো ফাইল যুক্ত করা হয়নি। NID, চুক্তিপত্র, বা বিলের ছবি আপলোড করতে "ডকুমেন্ট যোগ করুন" চাপুন।',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
            ),
          ],
          for (int i = 0; i < _documents.length; i++) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        'ডকুমেন্ট #${i + 1}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF1E3A8A)),
                      ),
                      const Spacer(),
                      IconButton(
                        icon: const Icon(Icons.close_rounded, size: 16, color: Color(0xFFDC2626)),
                        onPressed: () => _removeDocument(i),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                        tooltip: 'মুছুন',
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    initialValue: _documents[i].type,
                    decoration: _inputDeco('ডকুমেন্টের ধরণ', 'নির্বাচন করুন'),
                    items: const [
                      DropdownMenuItem(value: 'nid', child: Text('জাতীয় পরিচয়পত্র (NID)')),
                      DropdownMenuItem(value: 'utility_bill', child: Text('বিদ্যুৎ / ইউটিলিটি বিল')),
                      DropdownMenuItem(value: 'agreement', child: Text('লোন চুক্তিপত্র / স্ট্যাম্প')),
                      DropdownMenuItem(value: 'income_proof', child: Text('আয়ের প্রমাণপত্র')),
                      DropdownMenuItem(value: 'guarantor_doc', child: Text('জামিনদারের ডকুমেন্ট')),
                      DropdownMenuItem(value: 'other', child: Text('অন্যান্য')),
                    ],
                    onChanged: (v) => setState(() => _documents[i].type = v ?? 'other'),
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _documents[i].title,
                    style: const TextStyle(fontSize: 12),
                    decoration: _inputDeco('ডকুমেন্টের শিরোনাম (ঐচ্ছিক)', 'যেমন: আবেদনকারীর এনআইডি সামনের অংশ'),
                  ),
                  const SizedBox(height: 10),
                  // File picker preview
                  InkWell(
                    onTap: () => _showDocumentPickerModal(i),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: _documents[i].file != null ? const Color(0xFF10B981) : const Color(0xFFCBD5E1),
                          style: BorderStyle.solid,
                        ),
                      ),
                      child: Row(
                        children: [
                          if (_documents[i].file != null) ...[
                            ClipRRect(
                              borderRadius: BorderRadius.circular(6),
                              child: Image.file(
                                File(_documents[i].file!.path),
                                width: 36,
                                height: 36,
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stackTrace) => const Icon(Icons.description_rounded, size: 24, color: Color(0xFF059669)),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _documents[i].file!.name,
                                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const Text('ফাইল যুক্ত হয়েছে • পরিবর্তন করতে ট্যাপ করুন', style: TextStyle(fontSize: 10, color: Color(0xFF059669))),
                                ],
                              ),
                            ),
                            const Icon(Icons.check_circle_rounded, color: Color(0xFF10B981), size: 20),
                          ] else ...[
                            const Icon(Icons.cloud_upload_outlined, color: Color(0xFF2563EB), size: 22),
                            const SizedBox(width: 10),
                            const Expanded(
                              child: Text(
                                'ডকুমেন্টের ছবি তুলতে বা ফাইল নির্বাচন করতে ট্যাপ করুন',
                                style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                              ),
                            ),
                            const Icon(Icons.add_a_photo_outlined, size: 18, color: Color(0xFF2563EB)),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildPurposeSection() {
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
          const Row(
            children: [
              Icon(Icons.notes_outlined, color: Color(0xFF2563EB), size: 18),
              SizedBox(width: 8),
              Text(
                'উদ্দেশ্য ও মন্তব্য',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _purposeController,
            style: const TextStyle(fontSize: 13),
            decoration: _inputDeco('লোন গ্রহণের উদ্দেশ্য', 'যেমন: মুদি দোকান, গবাদি পশু পালন, কৃষি চাষাবাদ'),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _remarksController,
            maxLines: 2,
            style: const TextStyle(fontSize: 13),
            decoration: _inputDeco('অতিরিক্ত মন্তব্য / নোট', 'আবেদন সম্পর্কে কোনো বিশেষ তথ্য থাকলে লিখুন...'),
          ),
        ],
      ),
    );
  }

  InputDecoration _inputDeco(String label, String hint) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 12),
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.grey.shade300)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.grey.shade300)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
    );
  }
}
