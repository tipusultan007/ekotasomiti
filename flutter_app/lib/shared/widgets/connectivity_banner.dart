import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/offline/sync_service.dart';
import 'pending_sync_sheet.dart';

class ConnectivityBanner extends ConsumerWidget {
  final bool compact;
  const ConnectivityBanner({super.key, this.compact = false});

  Future<void> _handleSync(BuildContext context, WidgetRef ref) async {
    final messenger = ScaffoldMessenger.of(context);
    final result = await ref.read(syncServiceProvider.notifier).syncPendingTransactions();

    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(
              result.synced > 0 ? Icons.check_circle_rounded : Icons.info_outline_rounded,
              color: Colors.white,
              size: 20,
            ),
            const SizedBox(width: 8),
            Expanded(child: Text(result.message)),
          ],
        ),
        backgroundColor: result.synced > 0 ? const Color(0xFF16A34A) : const Color(0xFFD97706),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final syncState = ref.watch(syncServiceProvider);

    if (syncState.isOnline && syncState.pendingCount == 0 && !syncState.isSyncing) {
      return const SizedBox.shrink();
    }

    if (compact) {
      return _buildCompactBadge(context, ref, syncState);
    }

    return _buildFullBanner(context, ref, syncState);
  }

  Widget _buildCompactBadge(BuildContext context, WidgetRef ref, SyncStatusState syncState) {
    if (syncState.isSyncing) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: const Color(0xFFEFF6FF),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFF3B82F6).withValues(alpha: 0.3)),
        ),
        child: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              width: 12,
              height: 12,
              child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)),
            ),
            SizedBox(width: 6),
            Text(
              'সিঙ্ক হচ্ছে...',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF2563EB)),
            ),
          ],
        ),
      );
    }

    if (!syncState.isOnline) {
      return InkWell(
        onTap: () {
          if (syncState.pendingCount > 0) {
            PendingSyncSheet.show(context);
          } else {
            ref.read(syncServiceProvider.notifier).checkConnection().then((online) {
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(online
                        ? 'ইন্টারনেট সংযোগ চালু রয়েছে।'
                        : 'অফলাইন মোড: সার্ভারের সাথে সংযোগ পাওয়া যায়নি।'),
                    backgroundColor: online ? const Color(0xFF16A34A) : const Color(0xFFB45309),
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              }
            });
          }
        },
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: const Color(0xFFFFFBEB),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: const Color(0xFFF59E0B).withValues(alpha: 0.4)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off_rounded, size: 13, color: Color(0xFFD97706)),
              const SizedBox(width: 4),
              Text(
                syncState.pendingCount > 0 ? 'অফলাইন (${syncState.pendingCount})' : 'অফলাইন',
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFB45309)),
              ),
            ],
          ),
        ),
      );
    }

    // Online but has pending items
    return InkWell(
      onTap: () => _handleSync(context, ref),
      onLongPress: () => PendingSyncSheet.show(context),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: const Color(0xFFECFDF5),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.4)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.sync_rounded, size: 14, color: Color(0xFF059669)),
            const SizedBox(width: 4),
            Text(
              'সিঙ্ক (${syncState.pendingCount})',
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF047857)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFullBanner(BuildContext context, WidgetRef ref, SyncStatusState syncState) {
    if (!syncState.isOnline) {
      return InkWell(
        onTap: () {
          if (syncState.pendingCount > 0) {
            PendingSyncSheet.show(context);
          } else {
            ref.read(syncServiceProvider.notifier).checkConnection();
          }
        },
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: const BoxDecoration(
            color: Color(0xFFFEF3C7),
            border: Border(bottom: BorderSide(color: Color(0xFFFDE68A))),
          ),
          child: Row(
            children: [
              const Icon(Icons.cloud_off_rounded, size: 16, color: Color(0xFFB45309)),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  syncState.pendingCount > 0
                      ? 'অফলাইনে কাজ করছেন • ${syncState.pendingCount} টি কালেকশন সিঙ্কের অপেক্ষায় (দেখতে চাপুন)'
                      : 'অফলাইনে কাজ করছেন • ডেটা নিরাপদে ডিভাইসে সংরক্ষিত হবে',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF92400E)),
                ),
              ),
              TextButton(
                onPressed: () => ref.read(syncServiceProvider.notifier).checkConnection(),
                style: TextButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: const Text('চেক করুন', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFB45309))),
              ),
            ],
          ),
        ),
      );
    }

    if (syncState.isSyncing) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: const BoxDecoration(
          color: Color(0xFFEFF6FF),
          border: Border(bottom: BorderSide(color: Color(0xFFBFDBFE))),
        ),
        child: const Row(
          children: [
            SizedBox(
              width: 14,
              height: 14,
              child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)),
            ),
            SizedBox(width: 10),
            Expanded(
              child: Text(
                'সার্ভারে অফলাইন কালেকশন সিঙ্ক হচ্ছে...',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF1D4ED8)),
              ),
            ),
          ],
        ),
      );
    }

    if (syncState.pendingCount > 0) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: const BoxDecoration(
          color: Color(0xFFF0FDF4),
          border: Border(bottom: BorderSide(color: Color(0xFFBBF7D0))),
        ),
        child: Row(
          children: [
            const Icon(Icons.cloud_upload_outlined, size: 16, color: Color(0xFF15803D)),
            const SizedBox(width: 8),
            Expanded(
              child: InkWell(
                onTap: () => PendingSyncSheet.show(context),
                child: Text(
                  '${syncState.pendingCount} টি অফলাইন লেনদেন সিঙ্কের জন্য প্রস্তুত (তালিকা দেখুন)',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF166534)),
                ),
              ),
            ),
            TextButton(
              onPressed: () => _handleSync(context, ref),
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                foregroundColor: const Color(0xFF166534),
              ),
              child: const Text('এখনই সিঙ্ক করুন', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            ),
          ],
        ),
      );
    }

    return const SizedBox.shrink();
  }
}
