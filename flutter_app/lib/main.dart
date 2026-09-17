import 'dart:ui';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'app.dart';
import 'core/offline/hive_boxes.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await HiveBoxes.init();

  ErrorWidget.builder = (FlutterErrorDetails details) {
    return Material(
      child: Container(
        color: Colors.white,
        padding: const EdgeInsets.all(16),
        child: SingleChildScrollView(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, color: Colors.red, size: 48),
              const SizedBox(height: 8),
              const Text('Widget Error', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              const SizedBox(height: 8),
              Text(details.exceptionAsString(), style: const TextStyle(fontSize: 12)),
            ],
          ),
        ),
      ),
    );
  };

  FlutterError.onError = (details) {
    FlutterError.presentError(details);
  };

  PlatformDispatcher.instance.onError = (error, stack) {
    debugPrint('UNCAUGHT ASYNC ERROR: $error');
    return true;
  };

  runApp(const ProviderScope(child: EkotaApp()));
}
