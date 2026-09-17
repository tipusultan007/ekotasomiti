import 'package:flutter/material.dart';

class SearchField extends StatelessWidget {
  final String hintText;
  final ValueChanged<String> onChanged;
  final TextEditingController? controller;

  const SearchField({
    super.key,
    required this.onChanged,
    this.hintText = 'Search...',
    this.controller,
  });

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      onChanged: onChanged,
      decoration: InputDecoration(
        hintText: hintText,
        prefixIcon: const Icon(Icons.search),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        filled: true,
        fillColor: Theme.of(context).colorScheme.surfaceContainerHighest.withValues(alpha: 0.3),
      ),
    );
  }
}
