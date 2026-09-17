import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/offline/hive_boxes.dart';

class LocaleNotifier extends ChangeNotifier {
  final Box _box = Hive.box(HiveBoxes.auth);
  Locale _locale = const Locale('bn');

  Locale get locale => _locale;

  LocaleNotifier() {
    final saved = _box.get('locale', defaultValue: 'bn') as String;
    _locale = Locale(saved);
  }

  Future<void> setLocale(Locale locale) async {
    _locale = locale;
    await _box.put('locale', locale.languageCode);
    notifyListeners();
  }

  void toggle() {
    setLocale(_locale.languageCode == 'bn' ? const Locale('en') : const Locale('bn'));
  }
}

final localeProvider = ChangeNotifierProvider<LocaleNotifier>((ref) => LocaleNotifier());
