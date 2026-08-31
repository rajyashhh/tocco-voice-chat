import 'package:flutter/material.dart';

import '../app/theme.dart';
import '../models/room.dart';

/// A square room cover card with a frosted info footer, a "HOT" badge and a
/// live-listener count.
class RoomCard extends StatelessWidget {
  final Room room;
  final VoidCallback? onTap;

  const RoomCard({super.key, required this.room, this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadii.lg),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppRadii.lg),
            boxShadow: AppShadows.card,
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppRadii.lg),
            child: Stack(
              fit: StackFit.expand,
              children: [
                Image.asset(room.cover, fit: BoxFit.cover),
                // bottom scrim for legibility
                const DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.center,
                      end: Alignment.bottomCenter,
                      colors: [Colors.transparent, Color(0xCC1E1B2E)],
                    ),
                  ),
                ),
                Positioned(
                  top: 10,
                  left: 10,
                  child: _Pill(
                    gradient: AppColors.ocean,
                    child: Text(room.tag,
                        style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
                  ),
                ),
                if (room.isHot)
                  Positioned(
                    top: 10,
                    right: 10,
                    child: _Pill(
                      gradient: AppColors.sunset,
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.local_fire_department_rounded, color: Colors.white, size: 13),
                          SizedBox(width: 3),
                          Text('HOT', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700)),
                        ],
                      ),
                    ),
                  ),
                Positioned(
                  left: 12,
                  right: 12,
                  bottom: 12,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        room.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 14),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Text(room.hostCountry, style: const TextStyle(fontSize: 13)),
                          const Spacer(),
                          const Icon(Icons.headphones_rounded, color: Colors.white70, size: 14),
                          const SizedBox(width: 3),
                          Text(
                            _format(room.listeners),
                            style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _format(int n) => n >= 1000 ? '${(n / 1000).toStringAsFixed(1)}k' : '$n';
}

class _Pill extends StatelessWidget {
  final Widget child;
  final Gradient gradient;
  const _Pill({required this.child, required this.gradient});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        gradient: gradient,
        borderRadius: BorderRadius.circular(AppRadii.pill),
      ),
      child: child,
    );
  }
}
