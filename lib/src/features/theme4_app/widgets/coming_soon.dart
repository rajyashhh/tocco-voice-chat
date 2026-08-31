import 'package:flutter/material.dart';

import '../app/theme.dart';

/// Shows a small frosted bottom sheet announcing a feature is "Coming Soon".
///
/// Used by every non-navigational tap in the demo so the UI feels responsive
/// without wiring up any real behaviour.
Future<void> showComingSoon(BuildContext context, String feature) {
  return showModalBottomSheet(
    context: context,
    backgroundColor: Colors.transparent,
    builder: (_) => Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.fromLTRB(24, 28, 24, 28),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadii.lg),
        boxShadow: AppShadows.soft,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              gradient: AppColors.brand,
              borderRadius: BorderRadius.circular(AppRadii.lg),
              boxShadow: AppShadows.soft,
            ),
            child: const Icon(Icons.rocket_launch_rounded, color: Colors.white, size: 30),
          ),
          const SizedBox(height: 16),
          Text(feature, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
          const SizedBox(height: 6),
          const Text(
            'Coming Soon ✨',
            style: TextStyle(fontSize: 14, color: AppColors.subtle),
          ),
        ],
      ),
    ),
  );
}
