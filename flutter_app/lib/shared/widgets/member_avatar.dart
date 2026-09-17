import 'package:flutter/material.dart';
import '../../core/api/api_endpoints.dart';

class MemberAvatar extends StatelessWidget {
  final String name;
  final String? photoUrl;
  final double radius;
  final double fontSize;
  final Color? backgroundColor;
  final Color? textColor;
  final Border? border;
  final bool showCameraBadge;
  final VoidCallback? onCameraTap;

  const MemberAvatar({
    super.key,
    required this.name,
    this.photoUrl,
    this.radius = 24,
    this.fontSize = 16,
    this.backgroundColor,
    this.textColor,
    this.border,
    this.showCameraBadge = false,
    this.onCameraTap,
  });

  static String? resolveUrl(String? url) {
    if (url == null || url.trim().isEmpty) return null;
    final trimmed = url.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return trimmed;
    }
    // Convert relative storage paths
    final cleanPath = trimmed.startsWith('/') ? trimmed.substring(1) : trimmed;
    final host = ApiEndpoints.host;
    if (cleanPath.startsWith('storage/')) {
      return '$host/$cleanPath';
    }
    return '$host/storage/$cleanPath';
  }

  @override
  Widget build(BuildContext context) {
    final resolved = resolveUrl(photoUrl);
    final initial = name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : 'M';
    final bgColor = backgroundColor ?? const Color(0xFF2563EB);
    final txtColor = textColor ?? Colors.white;

    Widget avatarWidget = Container(
      width: radius * 2,
      height: radius * 2,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: bgColor,
        border: border,
        image: resolved != null
            ? DecorationImage(
                image: NetworkImage(resolved),
                fit: BoxFit.cover,
                onError: (exception, stackTrace) {},
              )
            : null,
      ),
      alignment: Alignment.center,
      child: resolved == null
          ? Text(
              initial,
              style: TextStyle(
                color: txtColor,
                fontSize: fontSize,
                fontWeight: FontWeight.bold,
              ),
            )
          : null,
    );

    if (showCameraBadge || onCameraTap != null) {
      return Stack(
        clipBehavior: Clip.none,
        children: [
          avatarWidget,
          Positioned(
            bottom: 0,
            right: 0,
            child: InkWell(
              onTap: onCameraTap,
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 2),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.2),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.camera_alt_rounded,
                  size: 14,
                  color: Colors.white,
                ),
              ),
            ),
          ),
        ],
      );
    }

    return avatarWidget;
  }
}
