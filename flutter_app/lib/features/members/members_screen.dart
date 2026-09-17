import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/offline/hive_boxes.dart';
import '../../features/auth/auth_provider.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/status_badge.dart';
import '../../shared/widgets/app_loading_indicator.dart';
import '../../shared/widgets/app_error_widget.dart';
import '../../shared/helpers/error_handler.dart';
import '../../shared/widgets/connectivity_banner.dart';
import '../../shared/widgets/member_avatar.dart';
import '../../shared/widgets/app_drawer.dart';
import 'member_detail_screen.dart';

final membersProvider = FutureProvider.family<Map<String, dynamic>, String>((ref, search) async {
  final api = ref.read(apiClientProvider);
  final box = Hive.box(HiveBoxes.members);
  final params = <String, dynamic>{'per_page': '100'};
  if (search.isNotEmpty) params['search'] = search;
  try {
    final response = await api.dio.get(ApiEndpoints.members, queryParameters: params);
    final data = Map<String, dynamic>.from(response.data as Map);
    if (search.isEmpty) {
      await box.put('all_members', data);
    }
    return data;
  } catch (e) {
    final cached = box.get('all_members');
    if (cached != null) {
      final cachedMap = Map<String, dynamic>.from(cached as Map);
      if (search.isNotEmpty) {
        final rawList = (cachedMap['data'] as List?) ?? [];
        final list = rawList.map((m) => Map<String, dynamic>.from(m as Map)).toList();
        final q = search.toLowerCase();
        final filtered = list.where((m) {
          final name = (m['name'] ?? '').toString().toLowerCase();
          final no = (m['member_no'] ?? '').toString().toLowerCase();
          final mobile = (m['mobile'] ?? '').toString().toLowerCase();
          return name.contains(q) || no.contains(q) || mobile.contains(q);
        }).toList();
        return {'data': filtered, 'total': filtered.length};
      }
      return cachedMap;
    }
    rethrow;
  }
});

class MembersScreen extends ConsumerStatefulWidget {
  const MembersScreen({super.key});

  @override
  ConsumerState<MembersScreen> createState() => _MembersScreenState();
}

class _MembersScreenState extends ConsumerState<MembersScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _search = '';
  String _statusFilter = 'all'; // 'all', 'active', 'inactive'

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _refresh() {
    ref.invalidate(membersProvider(_search));
  }

  @override
  Widget build(BuildContext context) {
    final isBn = context.isBn;
    final membersAsync = ref.watch(membersProvider(_search));

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(
          isBn ? 'সদস্যবৃন্দ' : 'Members',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
        actions: [
          const ConnectivityBanner(compact: true),
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
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Column(
              children: [
                Container(
                  height: 42,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: TextField(
                    controller: _searchController,
                    onChanged: (v) => setState(() => _search = v.trim()),
                    style: const TextStyle(fontSize: 13),
                    decoration: InputDecoration(
                      hintText: isBn ? 'নাম, সদস্য নম্বর বা মোবাইল নম্বর দিয়ে খুঁজুন...' : 'Search by name, member no or phone...',
                      hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
                      prefixIcon: Icon(Icons.search_rounded, size: 18, color: Colors.grey.shade500),
                      suffixIcon: _search.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 16),
                              onPressed: () {
                                _searchController.clear();
                                setState(() => _search = '');
                              },
                            )
                          : null,
                      contentPadding: const EdgeInsets.symmetric(vertical: 8),
                      border: InputBorder.none,
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    _buildFilterChip('all', isBn ? 'সকল সদস্য' : 'All Members'),
                    const SizedBox(width: 6),
                    _buildFilterChip('active', isBn ? 'সক্রিয়' : 'Active'),
                    const SizedBox(width: 6),
                    _buildFilterChip('inactive', isBn ? 'নিষ্ক্রিয়' : 'Inactive'),
                  ],
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => _refresh(),
              child: membersAsync.when(
                loading: () => AppLoadingIndicator(
                  message: isBn ? 'সদস্য তালিকা লোড হচ্ছে...' : 'Loading member list...',
                ),
                error: (e, _) => AppErrorWidget(
                  message: AppErrorHandler.getMessage(e, isBn: isBn),
                  onRetry: _refresh,
                ),
                data: (data) {
                  final rawData = (data['data'] as List?) ?? [];
                  final allMembers = rawData.map((m) => Map<String, dynamic>.from(m as Map)).toList();
                  if (allMembers.isEmpty) {
                    return EmptyState(message: isBn ? 'কোনো সদস্য পাওয়া যায়নি' : 'No members found');
                  }

                  final members = allMembers.where((m) {
                    final status = (m['status'] ?? 'active').toString().toLowerCase();
                    if (_statusFilter == 'active' && status != 'active') return false;
                    if (_statusFilter == 'inactive' && status == 'active') return false;
                    return true;
                  }).toList();

                  if (members.isEmpty) {
                    return Container(
                      padding: const EdgeInsets.all(32),
                      alignment: Alignment.center,
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.person_search_rounded, size: 48, color: Colors.grey.shade400),
                          const SizedBox(height: 8),
                          Text(
                            isBn ? 'অনুসন্ধানের সাথে কোনো সদস্য মেলেনি' : 'No members match your search criteria',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    itemCount: members.length,
                    itemBuilder: (context, index) {
                      final m = members[index];
                      return _buildMemberCard(context, m, isBn);
                    },
                  );
                },
              ),
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

  Widget _buildMemberCard(BuildContext context, Map<String, dynamic> m, bool isBn) {
    final name = m['name'] ?? (isBn ? 'অজ্ঞাত' : 'Unknown');
    final memberNo = (m['member_no'] ?? '').toString();
    final mobile = (m['mobile'] ?? '').toString();
    final areaName = m['area']?['name'] ?? '';
    final status = (m['status'] ?? 'active').toString();
    final isActive = status.toLowerCase() == 'active';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
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
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => MemberDetailScreen(memberId: m['id'])),
          ),
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                MemberAvatar(
                  name: name,
                  photoUrl: m['photo_url'] ?? m['photo_path'],
                  radius: 22,
                  fontSize: 16,
                  backgroundColor: isActive ? const Color(0xFFEFF6FF) : const Color(0xFFF1F5F9),
                  textColor: isActive ? const Color(0xFF2563EB) : const Color(0xFF64748B),
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
                              name,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                                color: Color(0xFF0F172A),
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          StatusBadge(status: status, isSmall: true),
                        ],
                      ),
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          Text(
                            memberNo,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF1D4ED8),
                            ),
                          ),
                          if (mobile.isNotEmpty) ...[
                            const Text(' • ', style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
                            Icon(Icons.phone_outlined, size: 12, color: Colors.grey.shade600),
                            const SizedBox(width: 3),
                            Text(
                              mobile,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w500,
                                color: Colors.grey.shade700,
                              ),
                            ),
                          ],
                        ],
                      ),
                      if (areaName.isNotEmpty) ...[
                        const SizedBox(height: 3),
                        Row(
                          children: [
                            Icon(Icons.location_on_outlined, size: 12, color: Colors.grey.shade500),
                            const SizedBox(width: 3),
                            Text(
                              areaName,
                              style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                const Icon(Icons.chevron_right_rounded, color: Color(0xFFCBD5E1), size: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
