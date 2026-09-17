import 'package:hive_flutter/hive_flutter.dart';

class HiveBoxes {
  static const String auth = 'auth';
  static const String user = 'user';
  static const String members = 'members';
  static const String savingsAccounts = 'savings_accounts';
  static const String loans = 'loans';
  static const String pendingSync = 'pending_sync';
  static const String dashboard = 'dashboard';
  static const String offlineCache = 'offline_cache';

  static Future<void> init() async {
    await Hive.initFlutter();
    await Hive.openBox(auth);
    await Hive.openBox(user);
    await Hive.openBox(members);
    await Hive.openBox(savingsAccounts);
    await Hive.openBox(loans);
    await Hive.openBox(pendingSync);
    await Hive.openBox(dashboard);
    await Hive.openBox(offlineCache);
  }
}
