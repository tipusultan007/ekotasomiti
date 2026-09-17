import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../features/settings/locale_provider.dart';

class LanguageSwitchButton extends ConsumerWidget {
  final bool isCompact;
  const LanguageSwitchButton({super.key, this.isCompact = false});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentLocale = ref.watch(localeProvider).locale;
    final isBn = currentLocale.languageCode == 'bn';

    if (isCompact) {
      return InkWell(
        onTap: () {
          ref.read(localeProvider.notifier).toggle();
        },
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          decoration: BoxDecoration(
            color: const Color(0xFFEFF6FF),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: const Color(0xFF3B82F6).withValues(alpha: 0.3)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.language_rounded, size: 14, color: Color(0xFF2563EB)),
              const SizedBox(width: 4),
              Text(
                isBn ? 'বাং' : 'EN',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1D4ED8),
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF1F5F9),
        borderRadius: BorderRadius.circular(10),
      ),
      padding: const EdgeInsets.all(3),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildPill(
            context,
            ref,
            label: 'বাংলা',
            isSelected: isBn,
            locale: const Locale('bn'),
          ),
          _buildPill(
            context,
            ref,
            label: 'English',
            isSelected: !isBn,
            locale: const Locale('en'),
          ),
        ],
      ),
    );
  }

  Widget _buildPill(
    BuildContext context,
    WidgetRef ref, {
    required String label,
    required bool isSelected,
    required Locale locale,
  }) {
    return GestureDetector(
      onTap: () {
        if (!isSelected) {
          ref.read(localeProvider.notifier).setLocale(locale);
        }
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
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
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
            color: isSelected ? const Color(0xFF0F172A) : const Color(0xFF64748B),
          ),
        ),
      ),
    );
  }
}
