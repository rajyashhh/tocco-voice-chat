import 'package:flutter/material.dart';

import '../app/theme.dart';

/// A soft full-screen gradient wash used behind most screens.
///
/// Optionally renders two blurred decorative "blobs" for a modern, frosted
/// look. Purely cosmetic — no state, no logic.
class GradientBackground extends StatelessWidget {
  final Widget child;
  final Gradient gradient;
  final bool showBlobs;

  const GradientBackground({
    super.key,
    required this.child,
    this.gradient = AppColors.backdrop,
    this.showBlobs = true,
  });

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(gradient: gradient),
      child: Stack(
        children: [
          if (showBlobs) ...[
            Positioned(top: -80, right: -60, child: _blob(AppColors.pink, 220)),
            Positioned(top: 120, left: -90, child: _blob(AppColors.blue, 200)),
            Positioned(bottom: -70, right: -40, child: _blob(AppColors.purple, 240)),
          ],
          Positioned.fill(child: child),
        ],
      ),
    );
  }

  Widget _blob(Color color, double size) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [color.withValues(alpha: 0.22), color.withValues(alpha: 0.0)],
        ),
      ),
    );
  }
}
