import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/models/user.dart';
import '../../core/offline/hive_boxes.dart';

enum AuthStatus { initial, authenticated, unauthenticated, loading }

class AuthState {
  final AuthStatus status;
  final User? user;
  final String? error;

  AuthState({this.status = AuthStatus.initial, this.user, this.error});

  AuthState copyWith({AuthStatus? status, User? user, String? error, bool clearError = false}) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final ApiClient _apiClient;
  final Box _authBox = Hive.box(HiveBoxes.auth);

  AuthNotifier(this._apiClient) : super(_getInitialState(_apiClient, Hive.box(HiveBoxes.auth))) {
    _checkAuth();
  }

  static AuthState _getInitialState(ApiClient apiClient, Box authBox) {
    final token = apiClient.token;
    final cachedUserData = authBox.get('user');
    if (token != null && token.toString().isNotEmpty) {
      if (cachedUserData != null) {
        try {
          final user = User.fromJson(Map<String, dynamic>.from(cachedUserData));
          return AuthState(status: AuthStatus.authenticated, user: user);
        } catch (_) {
          return AuthState(status: AuthStatus.authenticated);
        }
      }
      return AuthState(status: AuthStatus.authenticated);
    }
    return AuthState(status: AuthStatus.unauthenticated);
  }

  Future<void> _checkAuth() async {
    final token = _apiClient.token;
    if (token != null && token.toString().isNotEmpty) {
      try {
        final response = await _apiClient.dio.get(ApiEndpoints.me);
        final user = User.fromJson(response.data);
        await _authBox.put('user', response.data);
        state = state.copyWith(status: AuthStatus.authenticated, user: user, clearError: true);
      } on DioException catch (e) {
        if (e.response?.statusCode == 401) {
          // Unauthorized / expired token: clear credentials
          await _apiClient.clearToken();
          await _authBox.delete('user');
          state = AuthState(status: AuthStatus.unauthenticated);
        } else {
          // Network issue or offline: retain authenticated state with cached user
          if (state.user == null) {
            final cachedUserData = _authBox.get('user');
            if (cachedUserData != null) {
              try {
                final user = User.fromJson(Map<String, dynamic>.from(cachedUserData));
                state = state.copyWith(status: AuthStatus.authenticated, user: user);
              } catch (_) {}
            }
          }
        }
      } catch (e) {
        // Keep authenticated in offline mode
      }
    } else {
      state = state.copyWith(status: AuthStatus.unauthenticated);
    }
  }

  Future<void> login(String phone, String password) async {
    state = state.copyWith(status: AuthStatus.loading, clearError: true);
    try {
      final response = await _apiClient.dio.post(ApiEndpoints.login, data: {
        'phone': phone,
        'password': password,
      });

      final token = response.data['token'] as String;
      final user = User.fromJson(response.data['user']);

      await _apiClient.setToken(token);
      await _authBox.put('user', response.data['user']);

      state = state.copyWith(status: AuthStatus.authenticated, user: user, clearError: true);
    } on DioException catch (e) {
      String message = 'Login failed.';
      if (e.response != null) {
        final data = e.response!.data;
        if (data is Map) {
          if (data['message'] != null) {
            message = data['message'].toString();
          } else if (data['errors'] != null) {
            final errors = data['errors'] as Map;
            final firstErrors = errors.values.first;
            if (firstErrors is List && firstErrors.isNotEmpty) {
              message = firstErrors.first.toString();
            }
          }
        }
        message += ' (${e.response!.statusCode})';
      } else if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.sendTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        message = 'Connection timeout. Please check your network.';
      } else if (e.type == DioExceptionType.connectionError) {
        message = 'Cannot connect to server. Please check your connection.';
      } else {
        message = 'Network error: ${e.message}';
      }
      state = state.copyWith(status: AuthStatus.unauthenticated, error: message);
    } catch (e) {
      state = state.copyWith(status: AuthStatus.unauthenticated, error: 'Error: ${e.toString()}');
    }
  }

  Future<void> logout() async {
    try {
      await _apiClient.dio.post(ApiEndpoints.logout);
    } catch (_) {}
    await _apiClient.clearToken();
    await _authBox.clear();
    state = AuthState(status: AuthStatus.unauthenticated);
  }
}

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.read(apiClientProvider));
});
