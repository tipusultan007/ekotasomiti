import 'package:flutter/material.dart';

class AppColors {
  AppColors._();

  static const Color primary = Color(0xFF1565C0);
  static const Color primaryDark = Color(0xFF0D47A1);
  static const Color primaryLight = Color(0xFF42A5F5);
  static const Color accent = Color(0xFFFFA000);
  static const Color success = Color(0xFF2E7D32);
  static const Color danger = Color(0xFFC62828);
  static const Color warning = Color(0xFFF57F17);
  static const Color info = Color(0xFF0277BD);

  static const Color paid = Color(0xFF2E7D32);
  static const Color due = Color(0xFFF57F17);
  static const Color overdue = Color(0xFFC62828);
  static const Color partial = Color(0xFF1565C0);
}

class AppTheme {
  AppTheme._();

  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      colorSchemeSeed: AppColors.primary,
      brightness: Brightness.light,
      appBarTheme: const AppBarTheme(
        centerTitle: true,
        elevation: 0,
      ),
      cardTheme: CardThemeData(
        elevation: 1,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      ),
    );
  }
}
