import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../offline/hive_boxes.dart';
import '../../features/auth/auth_provider.dart';

class SyncResult {
  final int synced;
  final int failed;
  final String message;
  final bool isOffline;

  const SyncResult({
    required this.synced,
    required this.failed,
    required this.message,
    this.isOffline = false,
  });
}

class SyncStatusState {
  final bool isOnline;
  final bool isSyncing;
  final int pendingCount;
  final DateTime? lastSyncTime;
  final String? lastMessage;

  const SyncStatusState({
    this.isOnline = true,
    this.isSyncing = false,
    this.pendingCount = 0,
    this.lastSyncTime,
    this.lastMessage,
  });

  SyncStatusState copyWith({
    bool? isOnline,
    bool? isSyncing,
    int? pendingCount,
    DateTime? lastSyncTime,
    String? lastMessage,
  }) {
    return SyncStatusState(
      isOnline: isOnline ?? this.isOnline,
      isSyncing: isSyncing ?? this.isSyncing,
      pendingCount: pendingCount ?? this.pendingCount,
      lastSyncTime: lastSyncTime ?? this.lastSyncTime,
      lastMessage: lastMessage ?? this.lastMessage,
    );
  }
}

class SyncService extends StateNotifier<SyncStatusState> {
  final ApiClient _apiClient;
  final Box _pendingBox = Hive.box(HiveBoxes.pendingSync);
  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;
  bool _isDisposed = false;

  SyncService(this._apiClient)
      : super(SyncStatusState(
          isOnline: true, // Optimistic default
          pendingCount: Hive.box(HiveBoxes.pendingSync).length,
        )) {
    _init();
  }

  void _init() async {
    // 1. Initial connectivity check with Android cold-start recovery
    await checkConnection();

    // 2. Cold-start retry: Android network callbacks may take 600-1200ms to register
    Future.delayed(const Duration(milliseconds: 700), () {
      if (!_isDisposed) checkConnection();
    });
    Future.delayed(const Duration(milliseconds: 2000), () {
      if (!_isDisposed) checkConnection();
    });

    // 3. Listen for network changes
    try {
      _connectivitySub = Connectivity().onConnectivityChanged.listen((List<ConnectivityResult> results) async {
        if (_isDisposed) return;
        final hasAdapter = results.isNotEmpty && !results.contains(ConnectivityResult.none);

        if (hasAdapter) {
          // Verify actual server reachability when network adapter connects
          final reachable = await _apiClient.checkReachability();
          final wasOffline = !state.isOnline;
          state = state.copyWith(isOnline: reachable);

          if (reachable && wasOffline && pendingCount > 0) {
            syncPendingTransactions();
          }
        } else {
          state = state.copyWith(isOnline: false);
        }
      });
    } catch (e) {
      debugPrint('SyncService connectivity subscription error: $e');
    }
  }

  /// Actively checks whether the device has internet and can reach the backend.
  Future<bool> checkConnection() async {
    try {
      final results = await Connectivity().checkConnectivity();
      final hasAdapter = results.isNotEmpty && !results.contains(ConnectivityResult.none);

      if (!hasAdapter) {
        state = state.copyWith(isOnline: false, pendingCount: pendingCount);
        return false;
      }

      // Check real server reachability
      final reachable = await _apiClient.checkReachability();
      state = state.copyWith(isOnline: reachable, pendingCount: pendingCount);
      return reachable;
    } catch (e) {
      debugPrint('checkConnection error: $e');
      return state.isOnline;
    }
  }

  /// Manually update online status when an API call succeeds or fails with network error.
  void setOnline(bool online) {
    if (state.isOnline != online) {
      state = state.copyWith(isOnline: online);
    }
  }

  int get pendingCount => _pendingBox.length;

  /// Returns list of all pending items with their storage keys
  List<Map<String, dynamic>> getPendingItems() {
    final list = <Map<String, dynamic>>[];
    for (final key in _pendingBox.keys) {
      final raw = _pendingBox.get(key);
      if (raw != null) {
        final map = Map<String, dynamic>.from(raw as Map);
        map['_key'] = key;
        list.add(map);
      }
    }
    return list;
  }

  /// Deletes a specific pending item from Hive box
  Future<void> removePendingItem(dynamic key) async {
    await _pendingBox.delete(key);
    state = state.copyWith(pendingCount: pendingCount);
  }

  Future<void> addToPending({
    required String endpoint,
    required Map<String, dynamic> data,
    String? title,
  }) async {
    await _pendingBox.add({
      'endpoint': endpoint,
      'data': data,
      'title': title ?? 'Offline Transaction',
      'timestamp': DateTime.now().toIso8601String(),
      'status': 'pending',
    });
    state = state.copyWith(pendingCount: pendingCount);
  }

  /// Sync all pending transactions. First attempts backend /collection/sync batch,
  /// then falls back to individual endpoint calls.
  Future<SyncResult> syncPendingTransactions() async {
    if (state.isSyncing) {
      return SyncResult(
        synced: 0,
        failed: 0,
        message: 'সিঙ্ক প্রক্রিয়া চলমান রয়েছে...',
      );
    }

    final keys = _pendingBox.keys.toList();
    if (keys.isEmpty) {
      state = state.copyWith(pendingCount: 0);
      return const SyncResult(
        synced: 0,
        failed: 0,
        message: 'সিঙ্ক করার মতো কোনো অফলাইন লেনদেন নেই।',
      );
    }

    // Verify connection before starting
    final online = await checkConnection();
    if (!online) {
      return const SyncResult(
        synced: 0,
        failed: 0,
        message: 'ইন্টারনেট সংযোগ পাওয়া যায়নি। সংযোগ নিশ্চিত করে পুনরায় চেষ্টা করুন।',
        isOffline: true,
      );
    }

    state = state.copyWith(isSyncing: true);
    int syncedCount = 0;
    int failedCount = 0;
    String? firstErrorMsg;

    try {
      // Step 1: Try Batch Sync via /collection/sync
      bool batchSuccess = false;
      try {
        final batchPayload = <Map<String, dynamic>>[];
        final keyMap = <String, dynamic>{};

        for (final key in keys) {
          final rawItem = _pendingBox.get(key);
          if (rawItem == null) continue;
          final item = Map<String, dynamic>.from(rawItem as Map);
          final data = Map<String, dynamic>.from(item['data'] ?? {});
          final endpoint = (item['endpoint'] as String? ?? '').toLowerCase();

          final localId = key.toString();
          keyMap[localId] = key;

          final type = endpoint.contains('loan') || data.containsKey('loan_id') ? 'loan' : 'savings';
          final date = data['txn_date'] ??
              data['collection_date'] ??
              data['payment_date'] ??
              DateTime.now().toIso8601String().split('T')[0];

          batchPayload.add({
            'local_id': localId,
            'type': type,
            'amount': data['amount'],
            if (type == 'savings') 'account_id': data['account_id'],
            if (type == 'loan') 'loan_id': data['loan_id'],
            'txn_date': date,
            'collection_date': date,
            'payment_method': data['payment_method'] ?? 'cash',
            'notes': data['notes'] ?? item['title'],
          });
        }

        if (batchPayload.isNotEmpty) {
          final response = await _apiClient.dio.post(
            ApiEndpoints.collectionSync,
            data: {'collections': batchPayload},
          );

          if (response.statusCode == 200 && response.data is Map) {
            batchSuccess = true;
            final results = response.data['results'] as List? ?? [];
            for (final res in results) {
              if (res is Map) {
                final localId = res['local_id']?.toString();
                final key = keyMap[localId];
                final status = res['status']?.toString();
                if (status == 'success') {
                  if (key != null) await _pendingBox.delete(key);
                  syncedCount++;
                } else {
                  final err = (res['error'] ?? '').toString().toLowerCase();
                  if (err.contains('already collected') ||
                      err.contains('already deposited') ||
                      err.contains('already paid')) {
                    if (key != null) await _pendingBox.delete(key);
                    syncedCount++;
                  } else {
                    failedCount++;
                    firstErrorMsg ??= res['error']?.toString();
                  }
                }
              }
            }
          }
        }
      } catch (batchErr) {
        debugPrint('Batch sync endpoint failed, falling back to individual POSTs: $batchErr');
        batchSuccess = false;
      }

      // Step 2: Fallback to individual POSTs if batch failed
      if (!batchSuccess) {
        for (final key in keys) {
          final rawItem = _pendingBox.get(key);
          if (rawItem == null) continue;
          final item = Map<String, dynamic>.from(rawItem as Map);

          try {
            final endpoint = item['endpoint'] as String?;
            final data = Map<String, dynamic>.from(item['data'] ?? {});

            final date = data['txn_date'] ??
                data['collection_date'] ??
                data['payment_date'] ??
                DateTime.now().toIso8601String().split('T')[0];
            data['txn_date'] = date;
            data['collection_date'] = date;
            data['payment_date'] = date;

            if (endpoint != null && endpoint.isNotEmpty) {
              await _apiClient.dio.post(endpoint, data: data);
              await _pendingBox.delete(key);
              syncedCount++;
            }
          } catch (e) {
            if (e is DioException) {
              debugPrint('Sync failed for item $key: [${e.response?.statusCode}] ${e.response?.data}');
              final errData = e.response?.data;
              final errMsg = errData is Map ? (errData['message'] ?? e.message) : e.message;
              final errStr = (errMsg ?? e.toString()).toLowerCase();

              if (errStr.contains('already collected') ||
                  errStr.contains('already deposited') ||
                  errStr.contains('already paid')) {
                // If it was already deposited/collected on server, safe to remove
                await _pendingBox.delete(key);
                syncedCount++;
              } else if (e.type == DioExceptionType.connectionError ||
                  e.type == DioExceptionType.connectionTimeout) {
                // Network dropped mid-batch: mark offline and stop
                state = state.copyWith(isOnline: false);
                firstErrorMsg ??= 'সার্ভারের সাথে সংযোগ বিচ্ছিন্ন হয়েছে।';
                break;
              } else {
                // Validation error or area restriction: count failure and continue with other items!
                failedCount++;
                firstErrorMsg ??= errMsg?.toString();
              }
            } else {
              debugPrint('Sync failed for item $key: $e');
              failedCount++;
              firstErrorMsg ??= e.toString();
            }
          }
        }
      }
    } finally {
      final remaining = pendingCount;
      String summaryMsg;
      if (syncedCount > 0 && failedCount == 0) {
        summaryMsg = '$syncedCount টি কালেকশন সফলভাবে সিঙ্ক হয়েছে!';
      } else if (syncedCount > 0 && failedCount > 0) {
        summaryMsg = '$syncedCount টি সিঙ্ক হয়েছে, $failedCount টি ব্যর্থ হয়েছে (${firstErrorMsg ?? "ত্রুটি"})';
      } else if (failedCount > 0) {
        summaryMsg = 'সিঙ্ক ব্যর্থ হয়েছে: ${firstErrorMsg ?? "সার্ভার ত্রুটি"}';
      } else {
        summaryMsg = 'সার্ভারে সিঙ্ক সম্পন্ন হয়েছে।';
      }

      state = state.copyWith(
        isSyncing: false,
        pendingCount: remaining,
        lastSyncTime: DateTime.now(),
        lastMessage: summaryMsg,
      );
    }

    String finalMessage;
    if (syncedCount > 0 && failedCount == 0) {
      finalMessage = '$syncedCount টি কালেকশন সফলভাবে সিঙ্ক হয়েছে!';
    } else if (syncedCount > 0 && failedCount > 0) {
      finalMessage = '$syncedCount টি সিঙ্ক হয়েছে, $failedCount টি ব্যর্থ হয়েছে ($firstErrorMsg)';
    } else if (failedCount > 0) {
      finalMessage = 'সিঙ্ক ব্যর্থ হয়েছে: ${firstErrorMsg ?? "সার্ভার ত্রুটি"}';
    } else {
      finalMessage = 'সিঙ্ক করার মতো কিছু পাওয়া যায়নি।';
    }

    return SyncResult(
      synced: syncedCount,
      failed: failedCount,
      message: finalMessage,
    );
  }

  @override
  void dispose() {
    _isDisposed = true;
    _connectivitySub?.cancel();
    super.dispose();
  }
}

final syncServiceProvider = StateNotifierProvider<SyncService, SyncStatusState>((ref) {
  final apiClient = ref.read(apiClientProvider);
  return SyncService(apiClient);
});
