import 'package:flutter/material.dart';

import '../app/theme.dart';

/// A grid tile for the Profile screen's feature list (Tasks, Store, …).
///
/// Tapping it just calls [onTap] — every option in the demo shows a
/// "Coming Soon" sheet.
class ProfileOptionTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback? onTap;

  const ProfileOptionTile({
    super.key,
    required this.icon,
    required this.label,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadii.md),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.purple.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(AppRadii.md),
            ),
            child: Icon(icon, color: AppColors.purple, size: 24),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}
