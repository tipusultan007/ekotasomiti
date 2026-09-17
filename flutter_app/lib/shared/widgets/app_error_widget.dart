import 'package:flutter/material.dart';
import '../helpers/error_handler.dart';

class AppErrorWidget extends StatelessWidget {
  final dynamic error;
  final String? message;
  final VoidCallback? onRetry;
  final Future<void> Function()? onRefresh;

  const AppErrorWidget({
    super.key,
    this.error,
    this.message,
    this.onRetry,
    this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    final displayMessage = message != null && message!.isNotEmpty
        ? AppErrorHandler.getMessage(message, isBn: true)
        : AppErrorHandler.getMessage(error, isBn: true);

    final isNetwork = displayMessage.contains('ইন্টারনেট') ||
        displayMessage.contains('নেটওয়ার্ক') ||
        displayMessage.contains('সংযোগ') ||
        displayMessage.contains('Timeout');

    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isNetwork ? const Color(0xFFFFFBEB) : const Color(0xFFFEF2F2),
                shape: BoxShape.circle,
              ),
              child: Icon(
                isNetwork ? Icons.wifi_off_rounded : Icons.error_outline_rounded,
                size: 48,
                color: isNetwork ? const Color(0xFFD97706) : const Color(0xFFEF4444),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              isNetwork ? 'সংযোগ সমস্যা' : 'তথ্য লোড করা যায়নি',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1E293B),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              displayMessage,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Color(0xFF64748B),
                fontSize: 13,
                height: 1.4,
              ),
            ),
            if (onRetry != null || onRefresh != null) ...[
              const SizedBox(height: 20),
              ElevatedButton.icon(
                onPressed: () {
                  if (onRetry != null) {
                    onRetry!();
                  } else if (onRefresh != null) {
                    onRefresh!();
                  }
                },
                icon: const Icon(Icons.refresh_rounded, size: 18),
                label: const Text('পুনরায় চেষ্টা করুন', style: TextStyle(fontWeight: FontWeight.bold)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  elevation: 0,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
