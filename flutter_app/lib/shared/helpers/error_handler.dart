import 'dart:io';
import 'package:dio/dio.dart';

class AppErrorHandler {
  AppErrorHandler._();

  /// Converts any error or exception into a clean, human-readable message.
  static String getMessage(dynamic error, {bool isBn = true}) {
    if (error == null) {
      return isBn ? 'একটি অপ্রত্যাশিত ত্রুটি ঘটেছে।' : 'An unexpected error occurred.';
    }

    if (error is DioException) {
      return _formatDioError(error, isBn: isBn);
    }

    if (error is SocketException) {
      return isBn
          ? 'ইন্টারনেট সংযোগ বিচ্ছিন্ন। অনুগ্রহ করে আপনার নেটওয়ার্ক সংযোগ পরীক্ষা করুন।'
          : 'No internet connection. Please check your network connection.';
    }

    final errStr = error.toString();

    // Catch nested DioException string representations
    if (errStr.contains('DioException') || errStr.contains('SocketException')) {
      if (errStr.contains('connection error') ||
          errStr.contains('Failed host lookup') ||
          errStr.contains('Connection refused')) {
        return isBn
            ? 'সার্ভারে সংযোগ করা যাচ্ছে না। আপনার ইন্টারনেট সংযোগ পরীক্ষা করুন।'
            : 'Cannot connect to server. Please check your internet connection.';
      }
      if (errStr.contains('timeout') || errStr.contains('timed out')) {
        return isBn
            ? 'সার্ভার থেকে সাড়া পেতে বেশি সময় নিচ্ছে। কিছুক্ষণ পর চেষ্টা করুন।'
            : 'Connection timed out. Please try again later.';
      }
      if (errStr.contains('401')) {
        return isBn
            ? 'লগইন সেশনের মেয়াদ শেষ। অনুগ্রহ করে আবার লগইন করুন।'
            : 'Session expired. Please log in again.';
      }
      if (errStr.contains('403')) {
        return isBn
            ? 'এই কাজটি করার অনুমতি নেই।'
            : 'You do not have permission for this action.';
      }
      if (errStr.contains('404')) {
        return isBn ? 'অনুরোধকৃত তথ্য পাওয়া যায়নি।' : 'Requested resource was not found.';
      }
      if (errStr.contains('500') || errStr.contains('502') || errStr.contains('503')) {
        return isBn
            ? 'সার্ভারে সমস্যা দেখা দিয়েছে। কিছুক্ষণ পর চেষ্টা করুন।'
            : 'Server error occurred. Please try again later.';
      }
    }

    // Clean up Exception: prefix if present
    String cleaned = errStr.replaceAll(RegExp(r'^Exception:\s*'), '').trim();
    if (cleaned.isEmpty) {
      return isBn ? 'একটি ত্রুটি ঘটেছে। পুনরায় চেষ্টা করুন।' : 'An error occurred. Please try again.';
    }

    return cleaned;
  }

  static String _formatDioError(DioException error, {required bool isBn}) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return isBn
            ? 'সার্ভারের সাথে সংযোগের সময় শেষ হয়েছে (Timeout)। অনুগ্রহ করে পুনরায় চেষ্টা করুন।'
            : 'Connection timed out. Please try again.';

      case DioExceptionType.connectionError:
        return isBn
            ? 'ইন্টারনেট সংযোগ পাওয়া যায়নি বা সার্ভার অফলাইন। অনুগ্রহ করে নেটওয়ার্ক পরীক্ষা করুন।'
            : 'No internet connection or server unreachable. Please check your network.';

      case DioExceptionType.badResponse:
        final statusCode = error.response?.statusCode;
        final data = error.response?.data;

        // Try to extract server-provided error message
        String? serverMsg;
        if (data is Map) {
          if (data['message'] != null && data['message'].toString().trim().isNotEmpty) {
            serverMsg = data['message'].toString().trim();
          } else if (data['error'] != null && data['error'].toString().trim().isNotEmpty) {
            serverMsg = data['error'].toString().trim();
          } else if (data['errors'] != null && data['errors'] is Map) {
            final errors = data['errors'] as Map;
            final firstVal = errors.values.firstOrNull;
            if (firstVal is List && firstVal.isNotEmpty) {
              serverMsg = firstVal.first.toString();
            } else if (firstVal != null) {
              serverMsg = firstVal.toString();
            }
          }
        }

        if (serverMsg != null && serverMsg.isNotEmpty) {
          return serverMsg;
        }

        if (statusCode == 401) {
          return isBn
              ? 'লগইন সেশনের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে আবার লগইন করুন।'
              : 'Your session has expired. Please log in again.';
        } else if (statusCode == 403) {
          return isBn
              ? 'এই তথ্য দেখার বা পরিবর্তন করার অনুমতি আপনার নেই।'
              : 'You do not have permission to perform this action.';
        } else if (statusCode == 404) {
          return isBn
              ? 'অনুরোধকৃত তথ্য সার্ভারে খুঁজে পাওয়া যায়নি।'
              : 'Requested data was not found on the server.';
        } else if (statusCode == 422) {
          return isBn
              ? 'প্রদত্ত তথ্য সঠিক নয়। অনুগ্রহ করে তথ্য যাচাই করুন।'
              : 'Invalid data submitted. Please check the inputs.';
        } else if (statusCode != null && statusCode >= 500) {
          return isBn
              ? 'সার্ভারে সাময়িক ত্রুটি দেখা দিয়েছে (HTTP $statusCode)। কিছুক্ষণ পর আবার চেষ্টা করুন।'
              : 'Server encountered an error ($statusCode). Please try again later.';
        }

        return isBn
            ? 'সার্ভার থেকে ত্রুটি এসেছে (HTTP ${statusCode ?? "unknown"})।'
            : 'Server returned an error (${statusCode ?? "unknown"}).';

      case DioExceptionType.cancel:
        return isBn ? 'অনুরোধটি বাতিল করা হয়েছে।' : 'Request was cancelled.';

      case DioExceptionType.badCertificate:
        return isBn
            ? 'সার্ভার নিরাপত্তা সনদপত্র (SSL) যাচাই করা যায়নি।'
            : 'SSL certificate could not be validated.';

      case DioExceptionType.unknown:
      default:
        if (error.error is SocketException) {
          return isBn
              ? 'ইন্টারনেট সংযোগ নেই। দয়া করে ইন্টারনেট কানেকশন চেক করুন।'
              : 'No internet connection. Please verify your network.';
        }
        return isBn
            ? 'সংযোগ ব্যর্থ হয়েছে। অনুগ্রহ করে ইন্টারনেট সংযোগ পরীক্ষা করুন।'
            : 'Connection failed. Please check your internet connection.';
    }
  }
}
