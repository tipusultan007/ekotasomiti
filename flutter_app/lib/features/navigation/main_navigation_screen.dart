import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/widgets/app_logo.dart';
import '../dashboard/dashboard_screen.dart';
import '../collection/savings_collection_screen.dart';
import '../collection/loan_collection_screen.dart';
import '../members/members_screen.dart';
import '../settlement/settlement_screen.dart';

final bottomNavIndexProvider = StateProvider<int>((ref) => 0);

class MainNavigationScreen extends ConsumerWidget {
  const MainNavigationScreen({super.key});

  Future<bool?> _showExitConfirmDialog(BuildContext context) {
    final l10n = context.l10n;
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        backgroundColor: Colors.white,
        contentPadding: const EdgeInsets.fromLTRB(24, 24, 24, 16),
        actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const AppLogo(size: 64, borderRadius: 16),
            const SizedBox(height: 16),
            Text(
              l10n.exitAppTitle,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF0F172A),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              l10n.exitAppMsg,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey.shade600,
                height: 1.4,
              ),
            ),
          ],
        ),
        actions: [
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => Navigator.of(ctx).pop(false),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    side: BorderSide(color: Colors.grey.shade300),
                  ),
                  child: Text(l10n.cancel, style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF475569))),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: () => Navigator.of(ctx).pop(true),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFDC2626),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                  child: Text(l10n.exitApp, style: const TextStyle(fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentIndex = ref.watch(bottomNavIndexProvider);
    final l10n = context.l10n;

    final List<Widget> screens = [
      const DashboardScreen(),
      const SavingsCollectionScreen(frequency: 'daily'),
      const LoanCollectionScreen(frequency: 'daily'),
      const MembersScreen(),
      const SettlementScreen(),
    ];

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        if (currentIndex != 0) {
          ref.read(bottomNavIndexProvider.notifier).state = 0;
        } else {
          final shouldExit = await _showExitConfirmDialog(context);
          if (shouldExit == true) {
            await SystemNavigator.pop();
          }
        }
      },
      child: Scaffold(
        body: IndexedStack(
          index: currentIndex,
          children: screens,
        ),
        bottomNavigationBar: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 16,
                offset: const Offset(0, -4),
              ),
            ],
            border: const Border(
              top: BorderSide(color: Color(0xFFF1F5F9), width: 1.5),
            ),
          ),
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildNavItem(
                    context,
                    ref: ref,
                    index: 0,
                    currentIndex: currentIndex,
                    icon: Icons.dashboard_outlined,
                    activeIcon: Icons.dashboard_rounded,
                    label: l10n.navHome,
                    activeColor: const Color(0xFF2563EB),
                  ),
                  _buildNavItem(
                    context,
                    ref: ref,
                    index: 1,
                    currentIndex: currentIndex,
                    icon: Icons.savings_outlined,
                    activeIcon: Icons.savings_rounded,
                    label: l10n.navSavings,
                    activeColor: const Color(0xFF2563EB),
                  ),
                  _buildNavItem(
                    context,
                    ref: ref,
                    index: 2,
                    currentIndex: currentIndex,
                    icon: Icons.monetization_on_outlined,
                    activeIcon: Icons.monetization_on_rounded,
                    label: l10n.navLoans,
                    activeColor: const Color(0xFF0D9488),
                  ),
                  _buildNavItem(
                    context,
                    ref: ref,
                    index: 3,
                    currentIndex: currentIndex,
                    icon: Icons.people_outline_rounded,
                    activeIcon: Icons.people_alt_rounded,
                    label: l10n.navMembers,
                    activeColor: const Color(0xFF4F46E5),
                  ),
                  _buildNavItem(
                    context,
                    ref: ref,
                    index: 4,
                    currentIndex: currentIndex,
                    icon: Icons.receipt_long_outlined,
                    activeIcon: Icons.receipt_long_rounded,
                    label: l10n.navClosing,
                    activeColor: const Color(0xFFD97706),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem(
    BuildContext context, {
    required WidgetRef ref,
    required int index,
    required int currentIndex,
    required IconData icon,
    required IconData activeIcon,
    required String label,
    required Color activeColor,
  }) {
    final isSelected = index == currentIndex;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () {
          ref.read(bottomNavIndexProvider.notifier).state = index;
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: isSelected ? activeColor.withValues(alpha: 0.12) : Colors.transparent,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Icon(
                  isSelected ? activeIcon : icon,
                  color: isSelected ? activeColor : const Color(0xFF64748B),
                  size: 22,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                  color: isSelected ? activeColor : const Color(0xFF64748B),
                  letterSpacing: isSelected ? -0.2 : 0,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
