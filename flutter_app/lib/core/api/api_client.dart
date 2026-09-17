import 'package:dio/dio.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'api_endpoints.dart';

class ApiClient {
  static const String _baseUrl = ApiEndpoints.baseUrl;
  static const String _tokenBox = 'auth';
  static const String _tokenKey = 'token';

  late final Dio _dio;
  late final Box _box;

  ApiClient() {
    _dio = Dio(BaseOptions(
      baseUrl: _baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        final token = _box.get(_tokenKey);
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) {
        handler.next(error);
      },
    ));

    _box = Hive.box(_tokenBox);
  }

  Dio get dio => _dio;

  Future<void> setToken(String token) async {
    await _box.put(_tokenKey, token);
  }

  Future<void> clearToken() async {
    await _box.delete(_tokenKey);
  }

  String? get token => _box.get(_tokenKey);

  /// Tests whether the API server is currently reachable.
  Future<bool> checkReachability() async {
    try {
      final response = await _dio.get(
        ApiEndpoints.me,
        options: Options(
          receiveTimeout: const Duration(seconds: 6),
          sendTimeout: const Duration(seconds: 6),
          validateStatus: (status) => status != null && status < 500,
        ),
      );
      // Any response under 500 (even 401 Unauthorized) means the host is reachable and online.
      return response.statusCode != null && response.statusCode! < 500;
    } catch (_) {
      return false;
    }
  }
}
