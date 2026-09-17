import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'app_logo.dart';
import 'language_switch_button.dart';
import 'pending_sync_sheet.dart';
import '../../shared/helpers/app_strings.dart';
import '../../features/auth/auth_provider.dart';
import '../../features/navigation/main_navigation_screen.dart';
import '../../features/savings/savings_list_screen.dart';
import '../../features/loans/loans_list_screen.dart';
import '../../features/collection/collection_history_screen.dart';
import '../../features/collection_sheet/collection_sheet_screen.dart';
import '../../features/loan_application/loan_application_screen.dart';
import '../../shared/helpers/date_formatter.dart';
import '../../core/offline/sync_service.dart';

class AppDrawer extends ConsumerWidget {
  const AppDrawer({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;
    final l10n = context.l10n;
    final userName = user?.name ?? l10n.fieldOfficer;
    final userPhone = user?.phone ?? '';

    return Drawer(
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(20),
          bottomRight: Radius.circular(20),
        ),
      ),
      child: Column(
        children: [
          // Executive Gradient Header
          _buildHeader(context, userName, userPhone, l10n),
          // Scrollable Menu List
          Expanded(
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
              children: [
                _buildSectionHeader(l10n.mainMenu),
                _buildDrawerItem(
                  context,
                  title: l10n.dashboard,
                  icon: Icons.dashboard_rounded,
                  color: const Color(0xFF2563EB),
                  onTap: () => _selectTab(context, ref, 0),
                ),
                _buildDrawerItem(
                  context,
                  title: l10n.memberList,
                  icon: Icons.people_alt_rounded,
                  color: const Color(0xFF4F46E5),
                  onTap: () => _selectTab(context, ref, 3),
                ),

                const SizedBox(height: 12),
                _buildSectionHeader(l10n.collectionAndRecovery),
                _buildDrawerItem(
                  context,
                  title: l10n.savingsCollection,
                  icon: Icons.savings_rounded,
                  color: const Color(0xFF2563EB),
                  badge: l10n.daily,
                  onTap: () => _selectTab(context, ref, 1),
                ),
                _buildDrawerItem(
                  context,
                  title: l10n.loanCollection,
                  icon: Icons.monetization_on_rounded,
                  color: const Color(0xFF0D9488),
                  badge: l10n.daily,
                  onTap: () => _selectTab(context, ref, 2),
                ),
                _buildDrawerItem(
                  context,
                  title: l10n.collectionSheet,
                  icon: Icons.list_alt_rounded,
                  color: const Color(0xFF6366F1),
                  onTap: () => _push(context, const CollectionSheetScreen()),
                ),
                _buildDrawerItem(
                  context,
                  title: l10n.collectionHistory,
                  icon: Icons.history_rounded,
                  color: const Color(0xFF7C3AED),
                  onTap: () => _push(context, const CollectionHistoryScreen()),
                ),
                _buildDrawerItem(
                  context,
                  title: l10n.cashSettlement,
                  icon: Icons.receipt_long_rounded,
                  color: const Color(0xFFD97706),
                  onTap: () => _selectTab(context, ref, 4),
                ),

                const SizedBox(height: 12),
                _buildSectionHeader(l10n.accountManagement),
                _buildDrawerItem(
                  context,
                  title: l10n.savingsAccounts,
                  icon: Icons.account_balance_wallet_outlined,
                  color: const Color(0xFF0284C7),
                  onTap: () => _push(context, const SavingsListScreen()),
                ),
                _buildDrawerItem(
                  context,
                  title: l10n.activeLoans,
                  icon: Icons.assessment_outlined,
                  color: const Color(0xFFEA580C),
                  onTap: () => _push(context, const LoansListScreen()),
                ),
                _buildDrawerItem(
                  context,
                  title: l10n.newLoanApplication,
                  icon: Icons.post_add_rounded,
                  color: const Color(0xFF059669),
                  onTap: () => _push(context, const LoanApplicationScreen()),
                ),

                const SizedBox(height: 12),
                // Language Switch Row inside drawer
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.translate_rounded, size: 18, color: Color(0xFF2563EB)),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          l10n.language,
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                        ),
                      ),
                      const LanguageSwitchButton(),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Footer with App Version & Logout
          _buildFooter(context, ref, l10n),
        ],
      ),
    );
  }

  Widget _buildHeader(BuildContext context, String userName, String userPhone, AppStrings l10n) {
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF1E3A8A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      padding: EdgeInsets.fromLTRB(
        18,
        MediaQuery.of(context).padding.top + 16,
        18,
        18,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // App Logo & Organization Title
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const AppLogo(size: 32, borderRadius: 8, showShadow: false),
                  const SizedBox(width: 10),
                  Text(
                    l10n.appName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                      fontSize: 16,
                      letterSpacing: 1.0,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF3B82F6).withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFF3B82F6).withValues(alpha: 0.4)),
                ),
                child: Text(
                  l10n.fieldOfficer,
                  style: const TextStyle(color: Color(0xFF93C5FD), fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          // Officer Avatar & Info
          Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: const Color(0xFF2563EB),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.3), width: 2),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.2),
                      blurRadius: 6,
                    ),
                  ],
                ),
                alignment: Alignment.center,
                child: Text(
                  userName.isNotEmpty ? userName[0].toUpperCase() : 'O',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      userName,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    if (userPhone.isNotEmpty)
                      Row(
                        children: [
                          const Icon(Icons.phone_outlined, size: 12, color: Colors.white70),
                          const SizedBox(width: 4),
                          Text(
                            userPhone,
                            style: const TextStyle(color: Colors.white70, fontSize: 12),
                          ),
                        ],
                      )
                    else
                      Text(
                        DateFormatter.display(DateTime.now()),
                        style: const TextStyle(color: Colors.white60, fontSize: 11),
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

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 8, top: 4, bottom: 6),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w800,
          color: Color(0xFF94A3B8),
          letterSpacing: 0.5,
        ),
      ),
    );
  }

  Widget _buildDrawerItem(
    BuildContext context, {
    required String title,
    required IconData icon,
    required Color color,
    String? badge,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(10),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: color, size: 18),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1E293B),
                  ),
                ),
              ),
              if (badge != null) ...[
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    badge,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: color,
                    ),
                  ),
                ),
                const SizedBox(width: 4),
              ],
              const Icon(Icons.chevron_right_rounded, size: 18, color: Color(0xFFCBD5E1)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFooter(BuildContext context, WidgetRef ref, AppStrings l10n) {
    final syncState = ref.watch(syncServiceProvider);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: const BoxDecoration(
        border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
      ),
      child: Column(
        children: [
          // Connection & Sync Section
          InkWell(
            onTap: () {
              if (syncState.pendingCount > 0) {
                Navigator.pop(context);
                PendingSyncSheet.show(context);
              } else {
                ref.read(syncServiceProvider.notifier).checkConnection().then((online) {
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(online
                            ? 'ইন্টারনেট সংযোগ চালু রয়েছে।'
                            : 'সার্ভারের সাথে সংযোগ নেই (অফলাইন)'),
                        backgroundColor: online ? const Color(0xFF16A34A) : const Color(0xFFB45309),
                        behavior: SnackBarBehavior.floating,
                      ),
                    );
                  }
                });
              }
            },
            borderRadius: BorderRadius.circular(10),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: syncState.isOnline ? const Color(0xFFF0FDF4) : const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: syncState.isOnline ? const Color(0xFFBBF7D0) : const Color(0xFFFDE68A),
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    syncState.isOnline ? Icons.wifi_rounded : Icons.wifi_off_rounded,
                    size: 16,
                    color: syncState.isOnline ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      syncState.isOnline ? l10n.onlineMode : l10n.offlineMode,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: syncState.isOnline ? const Color(0xFF15803D) : const Color(0xFFB45309),
                      ),
                    ),
                  ),
                  if (syncState.pendingCount > 0)
                    InkWell(
                      onTap: syncState.isSyncing
                          ? null
                          : () async {
                              final messenger = ScaffoldMessenger.of(context);
                              final result = await ref.read(syncServiceProvider.notifier).syncPendingTransactions();
                              messenger.showSnackBar(
                                SnackBar(
                                  content: Text(result.message),
                                  backgroundColor: result.synced > 0 ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                                  behavior: SnackBarBehavior.floating,
                                ),
                              );
                            },
                      borderRadius: BorderRadius.circular(6),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFF2563EB),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (syncState.isSyncing)
                              const SizedBox(
                                width: 10,
                                height: 10,
                                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                              )
                            else
                              const Icon(Icons.sync_rounded, size: 12, color: Colors.white),
                            const SizedBox(width: 4),
                            Text(
                              'Sync (${syncState.pendingCount})',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: () {
                    Navigator.pop(context);
                    ref.read(authProvider.notifier).logout();
                  },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFFECACA)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.logout_rounded, size: 16, color: Color(0xFFDC2626)),
                        const SizedBox(width: 6),
                        Text(
                          l10n.logout,
                          style: const TextStyle(
                            color: Color(0xFFDC2626),
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _selectTab(BuildContext context, WidgetRef ref, int index) {
    Navigator.pop(context);
    Navigator.of(context).popUntil((route) => route.isFirst);
    ref.read(bottomNavIndexProvider.notifier).state = index;
  }

  void _push(BuildContext context, Widget page) {
    Navigator.pop(context);
    Navigator.of(context).popUntil((route) => route.isFirst);
    Navigator.push(context, MaterialPageRoute(builder: (_) => page));
  }
}
