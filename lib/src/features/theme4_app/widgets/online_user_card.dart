import 'package:flutter/material.dart';

import '../app/theme.dart';
import '../models/user.dart';
import 'glass_card.dart';

/// A tall card for the Home "Online Users" list, with avatar, name, an online
/// dot and a gradient "Hi" button.
class OnlineUserCard extends StatelessWidget {
  final AppUser user;
  final VoidCallback? onHi;

  const OnlineUserCard({super.key, required this.user, this.onHi});

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisSize: MainAxisSize.min,
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(AppRadii.md),
                child: Image.asset(user.avatar, width: 96, height: 96, fit: BoxFit.cover),
              ),
              if (user.isOnline)
                Positioned(
                  right: 8,
                  top: 8,
                  child: Container(
                    width: 14,
                    height: 14,
                    decoration: BoxDecoration(
                      color: const Color(0xFF34D399),
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Flexible(
                child: Text(
                  user.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                ),
              ),
              const SizedBox(width: 4),
              Text(user.country, style: const TextStyle(fontSize: 13)),
            ],
          ),
          const SizedBox(height: 2),
          Text(
            user.tagline,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 11, color: AppColors.subtle),
          ),
          const SizedBox(height: 10),
          _HiButton(onTap: onHi),
        ],
      ),
    );
  }
}

class _HiButton extends StatelessWidget {
  final VoidCallback? onTap;
  const _HiButton({this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadii.pill),
        child: Ink(
          decoration: BoxDecoration(
            gradient: AppColors.brand,
            borderRadius: BorderRadius.circular(AppRadii.pill),
          ),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 8),
            alignment: Alignment.center,
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.waving_hand_rounded, size: 15, color: Colors.white),
                SizedBox(width: 6),
                Text('Hi', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
