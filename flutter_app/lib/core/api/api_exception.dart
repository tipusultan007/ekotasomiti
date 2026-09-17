import 'package:dio/dio.dart';

class ApiException implements Exception {
  final int? statusCode;
  final String message;
  final dynamic data;

  ApiException({this.statusCode, required this.message, this.data});

  @override
  String toString() => 'ApiException($statusCode): $message';

  factory ApiException.fromDioError(dynamic error) {
    if (error is DioException) {
      if (error.response != null) {
        final data = error.response!.data;
        final message = data is Map ? (data['message'] ?? 'Unknown error') : 'Server error';
        return ApiException(statusCode: error.response!.statusCode, message: message, data: data);
      }
      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.sendTimeout ||
          error.type == DioExceptionType.receiveTimeout) {
        return ApiException(message: 'Connection timeout. Please check your network.');
      }
      return ApiException(message: 'Network error. Please check your connection.');
    }
    return ApiException(message: error.toString());
  }
}
