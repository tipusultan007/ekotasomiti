import 'package:intl/intl.dart';

class DateFormatter {
  static final DateFormat _displayFormat = DateFormat('dd MMM yyyy');
  static final DateFormat _fullFormat = DateFormat('dd MMMM yyyy');
  static final DateFormat _apiFormat = DateFormat('yyyy-MM-dd');
  static final DateFormat _dayMonth = DateFormat('dd MMM');
  static final DateFormat _timeFormat = DateFormat('hh:mm a');
  static final DateFormat _dateTimeFormat = DateFormat('dd MMM yyyy, hh:mm a');

  static DateTime? parse(dynamic input) {
    if (input == null) return null;
    if (input is DateTime) return input;
    if (input is String) {
      final str = input.trim();
      if (str.isEmpty) return null;
      try {
        return DateTime.parse(str);
      } catch (_) {
        try {
          return _apiFormat.parse(str);
        } catch (_) {
          return null;
        }
      }
    }
    return null;
  }

  static DateTime? parseApi(String? dateStr) => parse(dateStr);

  static String display(dynamic input, {String fallback = '—'}) {
    final dt = parse(input);
    if (dt == null) return (input?.toString().isNotEmpty == true) ? input.toString() : fallback;
    return _displayFormat.format(dt);
  }

  static String full(dynamic input, {String fallback = '—'}) {
    final dt = parse(input);
    if (dt == null) return (input?.toString().isNotEmpty == true) ? input.toString() : fallback;
    return _fullFormat.format(dt);
  }

  static String api(DateTime date) => _apiFormat.format(date);

  static String dayMonth(dynamic input, {String fallback = '—'}) {
    final dt = parse(input);
    if (dt == null) return (input?.toString().isNotEmpty == true) ? input.toString() : fallback;
    return _dayMonth.format(dt);
  }

  static String time(dynamic input, {String fallback = '—'}) {
    final dt = parse(input);
    if (dt == null) return (input?.toString().isNotEmpty == true) ? input.toString() : fallback;
    return _timeFormat.format(dt.toLocal());
  }

  static String dateTime(dynamic input, {String fallback = '—'}) {
    final dt = parse(input);
    if (dt == null) return (input?.toString().isNotEmpty == true) ? input.toString() : fallback;
    return _dateTimeFormat.format(dt.toLocal());
  }

  /// Returns friendly human format:
  /// - "Today, 02:30 PM" or "Today, 02 Sep 2026"
  /// - "Yesterday, 01 Sep 2026"
  /// - "02 Sep 2026 • 02:30 PM" (if showTime) or "02 Sep 2026"
  static String human(dynamic input, {bool showTime = false, String fallback = '—'}) {
    final dt = parse(input)?.toLocal();
    if (dt == null) return (input?.toString().isNotEmpty == true) ? input.toString() : fallback;

    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final inputDate = DateTime(dt.year, dt.month, dt.day);
    final diffDays = today.difference(inputDate).inDays;

    final hasTime = (dt.hour != 0 || dt.minute != 0 || dt.second != 0);
    final timeStr = _timeFormat.format(dt);

    if (diffDays == 0) {
      return hasTime ? 'Today, $timeStr' : 'Today, ${_displayFormat.format(dt)}';
    } else if (diffDays == 1) {
      return hasTime ? 'Yesterday, $timeStr' : 'Yesterday, ${_displayFormat.format(dt)}';
    } else if (diffDays == -1) {
      return hasTime ? 'Tomorrow, $timeStr' : 'Tomorrow, ${_displayFormat.format(dt)}';
    }

    if (showTime && hasTime) {
      return '${_displayFormat.format(dt)} • $timeStr';
    }

    return _displayFormat.format(dt);
  }

  static String today() => _apiFormat.format(DateTime.now());
  static String tomorrow() => _apiFormat.format(DateTime.now().add(const Duration(days: 1)));
}
