import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/offline/sync_service.dart';
import '../helpers/currency_formatter.dart';
import '../helpers/date_formatter.dart';

class PendingSyncSheet extends ConsumerStatefulWidget {
  const PendingSyncSheet({super.key});

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => const PendingSyncSheet(),
    );
  }

  @override
  ConsumerState<PendingSyncSheet> createState() => _PendingSyncSheetState();
}

class _PendingSyncSheetState extends ConsumerState<PendingSyncSheet> {
  bool _isSyncing = false;

  Future<void> _performSync() async {
    setState(() => _isSyncing = true);
    final syncService = ref.read(syncServiceProvider.notifier);
    final messenger = ScaffoldMessenger.of(context);

    try {
      final result = await syncService.syncPendingTransactions();
      if (mounted) {
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
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(
            content: Text('সিঙ্ক ব্যর্থ হয়েছে: $e'),
            backgroundColor: Colors.red,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isSyncing = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syncState = ref.watch(syncServiceProvider);
    final syncService = ref.read(syncServiceProvider.notifier);
    final pendingItems = syncService.getPendingItems();

    return Container(
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.75),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Drag handle
          Center(
            child: Container(
              margin: const EdgeInsets.only(top: 12, bottom: 8),
              width: 38,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),

          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'অফলাইন কালেকশন তালিকা',
                      style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${pendingItems.length} টি লেনদেন সার্ভারে সিঙ্কের অপেক্ষায়',
                      style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          const Divider(height: 1),

          // List
          if (pendingItems.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 40, horizontal: 24),
              child: Column(
                children: [
                  Icon(Icons.cloud_done_rounded, size: 56, color: Colors.green.shade400),
                  const SizedBox(height: 12),
                  const Text(
                    'কোনো অফলাইন লেনদেন অপেক্ষমাণ নেই',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF1E293B)),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'সকল লেনদেন সফলভাবে সার্ভারে সংরক্ষিত রয়েছে।',
                    style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                  ),
                ],
              ),
            )
          else
            Flexible(
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                shrinkWrap: true,
                itemCount: pendingItems.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (context, index) {
                  final item = pendingItems[index];
                  final data = item['data'] as Map? ?? {};
                  final amount = (data['amount'] as num?)?.toDouble() ?? 0.0;
                  final title = item['title']?.toString() ?? 'কালেকশন';
                  final dateStr = (data['txn_date'] ?? data['collection_date'])?.toString() ?? '';
                  final key = item['_key'];

                  return Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF3C7),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.cloud_off_rounded, color: Color(0xFFD97706), size: 18),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                title,
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 2),
                              Text(
                                dateStr.isNotEmpty ? DateFormatter.display(dateStr) : 'আজকের কালেকশন',
                                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                              ),
                            ],
                          ),
                        ),
                        Text(
                          CurrencyFormatter.simple(amount),
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                        ),
                        const SizedBox(width: 4),
                        IconButton(
                          icon: const Icon(Icons.delete_outline_rounded, size: 18, color: Colors.grey),
                          tooltip: 'মুছে ফেলুন',
                          onPressed: () async {
                            final confirm = await showDialog<bool>(
                              context: context,
                              builder: (ctx) => AlertDialog(
                                title: const Text('নিশ্চিত করুন'),
                                content: const Text('আপনি কি এই অফলাইন লেনদেনটি মুছে ফেলতে চান?'),
                                actions: [
                                  TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('না')),
                                  ElevatedButton(
                                    style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                                    onPressed: () => Navigator.pop(ctx, true),
                                    child: const Text('মুছে ফেলুন'),
                                  ),
                                ],
                              ),
                            );
                            if (confirm == true) {
                              await syncService.removePendingItem(key);
                              setState(() {});
                            }
                          },
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),

          // Bottom Action
          Padding(
            padding: EdgeInsets.fromLTRB(16, 10, 16, MediaQuery.of(context).padding.bottom + 12),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () async {
                      final online = await syncService.checkConnection();
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(online ? 'ইন্টারনেট সংযোগ চালু রয়েছে।' : 'সার্ভারে সংযোগ পাওয়া যায়নি।'),
                            backgroundColor: online ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                            behavior: SnackBarBehavior.floating,
                          ),
                        );
                      }
                    },
                    icon: const Icon(Icons.network_check_rounded, size: 18),
                    label: const Text('কানেকশন চেক'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ),
                if (pendingItems.isNotEmpty) ...[
                  const SizedBox(width: 10),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton.icon(
                      onPressed: (_isSyncing || syncState.isSyncing) ? null : _performSync,
                      icon: (_isSyncing || syncState.isSyncing)
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.sync_rounded, size: 18),
                      label: Text(
                        (_isSyncing || syncState.isSyncing) ? 'সিঙ্ক হচ্ছে...' : 'এখনই সিঙ্ক করুন (${pendingItems.length})',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF2563EB),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        elevation: 0,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
