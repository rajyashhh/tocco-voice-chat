import 'dart:ui';

import 'package:flutter/material.dart';

import '../app/theme.dart';

/// A frosted, rounded surface with a soft shadow — the building block for most
/// cards in the app.
class GlassCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;
  final double radius;
  final double opacity;
  final VoidCallback? onTap;
  final bool blur;

  const GlassCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.radius = AppRadii.lg,
    this.opacity = 0.72,
    this.onTap,
    this.blur = true,
  });

  @override
  Widget build(BuildContext context) {
    final borderRadius = BorderRadius.circular(radius);

    Widget surface = Container(
      padding: padding,
      child: child,
    );

    if (blur) {
      surface = ClipRRect(
        borderRadius: borderRadius,
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
          child: surface,
        ),
      );
    }

    if (onTap == null) return surface;

    return Material(
      color: Colors.transparent,
      borderRadius: borderRadius,
      child: InkWell(
        onTap: onTap,
        borderRadius: borderRadius,
        child: surface,
      ),
    );
  }
}
