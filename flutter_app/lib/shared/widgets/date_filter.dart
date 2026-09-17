import 'package:flutter/material.dart';
import '../helpers/date_formatter.dart';

class DateFilter extends StatelessWidget {
  final String startDate;
  final String endDate;
  final ValueChanged<String> onStartChanged;
  final ValueChanged<String> onEndChanged;

  const DateFilter({
    super.key,
    required this.startDate,
    required this.endDate,
    required this.onStartChanged,
    required this.onEndChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: OutlinedButton.icon(
            onPressed: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: DateTime.tryParse(startDate) ?? DateTime.now(),
                firstDate: DateTime(2020),
                lastDate: DateTime.now(),
              );
              if (picked != null) onStartChanged(DateFormatter.api(picked));
            },
            icon: const Icon(Icons.calendar_today, size: 16),
            label: Text('From: ${DateFormatter.display(startDate)}'),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: OutlinedButton.icon(
            onPressed: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: DateFormatter.parse(endDate) ?? DateTime.now(),
                firstDate: DateTime(2020),
                lastDate: DateTime.now(),
              );
              if (picked != null) onEndChanged(DateFormatter.api(picked));
            },
            icon: const Icon(Icons.calendar_today, size: 16),
            label: Text('To: ${DateFormatter.display(endDate)}'),
          ),
        ),
      ],
    );
  }
}
