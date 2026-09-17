import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/models/member.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../shared/widgets/member_avatar.dart';
import '../../shared/widgets/app_drawer.dart';
import 'member_detail_screen.dart';
import 'members_screen.dart';

class MemberEditScreen extends ConsumerStatefulWidget {
  final Member member;
  const MemberEditScreen({super.key, required this.member});

  @override
  ConsumerState<MemberEditScreen> createState() => _MemberEditScreenState();
}

class _MemberEditScreenState extends ConsumerState<MemberEditScreen> {
  final _formKey = GlobalKey<FormState>();

  late TextEditingController _nameController;
  late TextEditingController _nameBnController;
  late TextEditingController _mobileController;
  late TextEditingController _nidController;
  late TextEditingController _addressController;
  late TextEditingController _fatherHusbandController;
  late TextEditingController _motherController;
  late TextEditingController _occupationController;
  late TextEditingController _notesController;

  late String _gender;
  late String _status;
  DateTime? _dob;
  XFile? _selectedImage;
  bool _isSaving = false;

  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    final m = widget.member;
    _nameController = TextEditingController(text: m.name);
    _nameBnController = TextEditingController(text: m.nameBn ?? '');
    _mobileController = TextEditingController(text: m.mobile ?? '');
    _nidController = TextEditingController(text: m.nid ?? '');
    _addressController = TextEditingController(text: m.address ?? '');
    _fatherHusbandController = TextEditingController(text: m.fatherHusbandName ?? '');
    _motherController = TextEditingController(text: m.motherName ?? '');
    _occupationController = TextEditingController(text: m.occupation ?? '');
    _notesController = TextEditingController(text: m.notes ?? '');

    _gender = (m.gender != null && ['male', 'female', 'other'].contains(m.gender!.toLowerCase()))
        ? m.gender!.toLowerCase()
        : 'male';
    _status = (['active', 'inactive', 'suspended', 'closed'].contains(m.status.toLowerCase()))
        ? m.status.toLowerCase()
        : 'active';

    if (m.dob != null && m.dob!.isNotEmpty) {
      _dob = DateTime.tryParse(m.dob!);
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _nameBnController.dispose();
    _mobileController.dispose();
    _nidController.dispose();
    _addressController.dispose();
    _fatherHusbandController.dispose();
    _motherController.dispose();
    _occupationController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picked = await _picker.pickImage(
        source: source,
        maxWidth: 1024,
        maxHeight: 1024,
        imageQuality: 85,
      );
      if (picked != null) {
        setState(() => _selectedImage = picked);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('ছবি নির্বাচন করা যায়নি: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _showImagePickerSheet() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
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
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const Text(
                'সদস্যের প্রোফাইল ছবি',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.photo_camera_rounded, color: Color(0xFF2563EB)),
                ),
                title: const Text('ক্যামেরা দিয়ে ছবি তুলুন', style: TextStyle(fontWeight: FontWeight.w600)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickImage(ImageSource.camera);
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFECFDF5),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.photo_library_rounded, color: Color(0xFF059669)),
                ),
                title: const Text('গ্যালারি থেকে নির্বাচন করুন', style: TextStyle(fontWeight: FontWeight.w600)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickImage(ImageSource.gallery);
                },
              ),
              if (_selectedImage != null)
                ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.delete_outline_rounded, color: Color(0xFFDC2626)),
                  ),
                  title: const Text('নির্বাচিত ছবি বাতিল করুন', style: TextStyle(color: Color(0xFFDC2626), fontWeight: FontWeight.w600)),
                  onTap: () {
                    Navigator.pop(ctx);
                    setState(() => _selectedImage = null);
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    final messenger = ScaffoldMessenger.of(context);

    try {
      final api = ref.read(apiClientProvider);

      // Prepare member text update payload
      final payload = <String, dynamic>{
        'name': _nameController.text.trim(),
        'name_bn': _nameBnController.text.trim(),
        'mobile': _mobileController.text.trim(),
        'nid': _nidController.text.trim(),
        'address': _addressController.text.trim(),
        'father_husband_name': _fatherHusbandController.text.trim(),
        'mother_name': _motherController.text.trim(),
        'occupation': _occupationController.text.trim(),
        'gender': _gender,
        'status': _status,
        'notes': _notesController.text.trim(),
      };

      if (_dob != null) {
        payload['dob'] = DateFormatter.api(_dob!);
      }

      // Update text profile details
      await api.dio.put('${ApiEndpoints.members}/${widget.member.id}', data: payload);

      // If a new photo was picked, upload it via multipart
      if (_selectedImage != null) {
        final formData = FormData.fromMap({
          'photo': await MultipartFile.fromFile(
            _selectedImage!.path,
            filename: 'photo_${widget.member.id}.jpg',
          ),
        });
        await api.dio.post('${ApiEndpoints.members}/${widget.member.id}/photo', data: formData);
      }

      // Invalidate relevant providers to update all member screens
      ref.invalidate(memberDetailProvider(widget.member.id));
      ref.invalidate(membersProvider(''));

      if (mounted) {
        messenger.showSnackBar(
          const SnackBar(
            content: Row(
              children: [
                Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
                SizedBox(width: 8),
                Expanded(child: Text('সদস্যের প্রোফাইল সফলভাবে আপডেট করা হয়েছে')),
              ],
            ),
            backgroundColor: Color(0xFF059669),
          ),
        );
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(
            content: Text('আপডেট করা যায়নি: $e'),
            backgroundColor: const Color(0xFFDC2626),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      drawer: const AppDrawer(),
      appBar: AppBar(
        leading: const BackButton(),
        title: Text(
          '${widget.member.name} - সম্পাদন',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17),
        ),
        elevation: 0,
        actions: [
          TextButton.icon(
            onPressed: _isSaving ? null : _save,
            icon: _isSaving
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.check_rounded, color: Colors.white, size: 20),
            label: const Text('সংরক্ষণ', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          children: [
            // Profile Photo Header Section
            _buildPhotoPickerCard(),
            const SizedBox(height: 16),

            // Member ID & Center Header Banner
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFBFDBFE)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.badge_outlined, color: Color(0xFF2563EB), size: 22),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'সদস্য নম্বর: ${widget.member.memberNo}',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                            color: Color(0xFF1E40AF),
                          ),
                        ),
                        if (widget.member.area != null)
                          Text(
                            'সমিতি / এলাকা: ${widget.member.area!.name}',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF3B82F6)),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Section 1: Basic Information
            _buildSectionCard(
              title: 'মৌলিক তথ্য',
              icon: Icons.person_outline_rounded,
              children: [
                _buildTextField(
                  controller: _nameController,
                  label: 'পূর্ণ নাম (ইংরেজি) *',
                  hint: 'সদস্যের নাম লিখুন',
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'নাম আবশ্যক' : null,
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  controller: _nameBnController,
                  label: 'নাম (বাংলা)',
                  hint: 'বাংলায় নাম লিখুন',
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildDropdown<String>(
                        label: 'লিঙ্গ',
                        value: _gender,
                        items: const [
                          DropdownMenuItem(value: 'male', child: Text('পুরুষ')),
                          DropdownMenuItem(value: 'female', child: Text('মহিলা')),
                          DropdownMenuItem(value: 'other', child: Text('অন্যান্য')),
                        ],
                        onChanged: (v) => setState(() => _gender = v ?? 'male'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildDropdown<String>(
                        label: 'অবস্থা',
                        value: _status,
                        items: const [
                          DropdownMenuItem(value: 'active', child: Text('সক্রিয়')),
                          DropdownMenuItem(value: 'inactive', child: Text('নিষ্ক্রিয়')),
                          DropdownMenuItem(value: 'suspended', child: Text('স্থগিত')),
                          DropdownMenuItem(value: 'closed', child: Text('বন্ধ')),
                        ],
                        onChanged: (v) => setState(() => _status = v ?? 'active'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                InkWell(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _dob ?? DateTime(1995, 1, 1),
                      firstDate: DateTime(1940),
                      lastDate: DateTime.now(),
                    );
                    if (picked != null) setState(() => _dob = picked);
                  },
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('জন্ম তারিখ', style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                            const SizedBox(height: 2),
                            Text(
                              _dob != null ? DateFormatter.display(_dob!) : 'জন্ম তারিখ নির্বাচন করুন',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: _dob != null ? const Color(0xFF0F172A) : Colors.grey.shade400,
                              ),
                            ),
                          ],
                        ),
                        Icon(Icons.calendar_month_outlined, size: 20, color: Colors.grey.shade600),
                      ],
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Section 2: Contact & Identification
            _buildSectionCard(
              title: 'যোগাযোগ ও পরিচয়পত্র',
              icon: Icons.contact_phone_outlined,
              children: [
                _buildTextField(
                  controller: _mobileController,
                  label: 'মোবাইল নম্বর',
                  hint: '০১XXXXXXXXX',
                  keyboardType: TextInputType.phone,
                  prefixIcon: const Icon(Icons.phone_outlined, size: 18),
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  controller: _nidController,
                  label: 'জাতীয় পরিচয়পত্র (NID)',
                  hint: 'জাতীয় পরিচয়পত্র নম্বর লিখুন',
                  prefixIcon: const Icon(Icons.credit_card_outlined, size: 18),
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  controller: _occupationController,
                  label: 'পেশা',
                  hint: 'যেমন: ব্যবসা, শিক্ষকতা, কৃষি',
                  prefixIcon: const Icon(Icons.work_outline_rounded, size: 18),
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  controller: _addressController,
                  label: 'ঠিকানা',
                  hint: 'গ্রাম, ডাকঘর, উপজেলা, জেলা',
                  maxLines: 2,
                  prefixIcon: const Icon(Icons.location_on_outlined, size: 18),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Section 3: Family & Notes
            _buildSectionCard(
              title: 'পারিবারিক তথ্য ও মন্তব্য',
              icon: Icons.family_restroom_outlined,
              children: [
                _buildTextField(
                  controller: _fatherHusbandController,
                  label: 'পিতা / স্বামীর নাম',
                  hint: 'পিতা বা স্বামীর নাম লিখুন',
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  controller: _motherController,
                  label: 'মাতার নাম',
                  hint: 'মাতার নাম লিখুন',
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  controller: _notesController,
                  label: 'মন্তব্য / নোট',
                  hint: 'সদস্য সম্পর্কিত অতিরিক্ত তথ্য...',
                  maxLines: 3,
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Save Button
            ElevatedButton(
              onPressed: _isSaving ? null : _save,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 2,
              ),
              child: _isSaving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text(
                      'পরিবর্তন সংরক্ষণ করুন',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                    ),
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildPhotoPickerCard() {
    final hasLocalImage = _selectedImage != null;
    final photoUrl = widget.member.photoUrl;

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
      child: Row(
        children: [
          // Avatar display
          GestureDetector(
            onTap: _showImagePickerSheet,
            child: Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: const Color(0xFFEFF6FF),
                    border: Border.all(color: const Color(0xFF2563EB), width: 2),
                    image: hasLocalImage
                        ? DecorationImage(
                            image: FileImage(File(_selectedImage!.path)),
                            fit: BoxFit.cover,
                          )
                        : (photoUrl != null && photoUrl.isNotEmpty
                            ? DecorationImage(
                                image: NetworkImage(MemberAvatar.resolveUrl(photoUrl)!),
                                fit: BoxFit.cover,
                                onError: (exception, stackTrace) {},
                              )
                            : null),
                  ),
                  alignment: Alignment.center,
                  child: (!hasLocalImage && (photoUrl == null || photoUrl.isEmpty))
                      ? Text(
                          widget.member.name.isNotEmpty ? widget.member.name[0].toUpperCase() : 'স',
                          style: const TextStyle(
                            fontSize: 28,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF2563EB),
                          ),
                        )
                      : null,
                ),
                Positioned(
                  bottom: -2,
                  right: -2,
                  child: Container(
                    padding: const EdgeInsets.all(5),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A),
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                    ),
                    child: const Icon(Icons.camera_alt_rounded, size: 13, color: Colors.white),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'প্রোফাইল ছবি',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 4),
                Text(
                  hasLocalImage
                      ? 'নতুন ছবি নির্বাচন করা হয়েছে'
                      : (photoUrl != null ? 'ছবি পরিবর্তন করতে ট্যাপ করুন' : 'এখনও কোনো ছবি আপলোড করা হয়নি'),
                  style: TextStyle(
                    fontSize: 12,
                    color: hasLocalImage ? const Color(0xFF059669) : Colors.grey.shade600,
                    fontWeight: hasLocalImage ? FontWeight.w600 : FontWeight.normal,
                  ),
                ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: _showImagePickerSheet,
                  icon: const Icon(Icons.photo_camera_outlined, size: 14),
                  label: Text(
                    hasLocalImage || photoUrl != null ? 'ছবি পরিবর্তন করুন' : 'ছবি আপলোড করুন',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required IconData icon,
    required List<Widget> children,
  }) {
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
              Icon(icon, size: 18, color: const Color(0xFF2563EB)),
              const SizedBox(width: 8),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ...children,
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    String? hint,
    TextInputType? keyboardType,
    int maxLines = 1,
    Widget? prefixIcon,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      validator: validator,
      style: const TextStyle(fontSize: 13, color: Color(0xFF0F172A)),
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
        prefixIcon: prefixIcon,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
        ),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      ),
    );
  }

  Widget _buildDropdown<T>({
    required String label,
    required T value,
    required List<DropdownMenuItem<T>> items,
    required ValueChanged<T?> onChanged,
  }) {
    return DropdownButtonFormField<T>(
      initialValue: value,
      items: items,
      onChanged: onChanged,
      style: const TextStyle(fontSize: 13, color: Color(0xFF0F172A)),
      decoration: InputDecoration(
        labelText: label,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
        ),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      ),
    );
  }
}
