import 'package:flutter/material.dart';

import '../models/seat_model.dart';

// Individual seat — accepts custom builders for avatar, empty state, foreground
class UTDSeatWidget extends StatelessWidget {
  final SeatState seat;
  final double? size;
  final bool isSpeaking;

  /// The participant's role (host, admin, guest, audience, visitor).
  /// Read from participant metadata `role` field.
  final String? role;

  final VoidCallback? onTap;
  final Widget Function(SeatState seat, double size)? seatBuilder;

  /// `size` is the FULL seat slot size — identical to what [emptySeatBuilder]
  /// and [lockedSeatBuilder] receive — so all three seat states can size their
  /// content from one shared value and stay visually consistent. (The builder
  /// is responsible for any inset, e.g. drawing the avatar at `size * ratio`.)
  final Widget Function(
    String userId,
    double size,
    Map<String, String> attributes,
    bool isMuted,
    int seatIndex,
    String userName,
  )? avatarBuilder;
  final Widget Function(int index, double size)? emptySeatBuilder;
  final Widget Function(int index, double size)? lockedSeatBuilder;

  const UTDSeatWidget({
    super.key,
    required this.seat,
    this.size,
    this.isSpeaking = false,
    this.role,
    this.onTap,
    this.seatBuilder,
    this.avatarBuilder,
    this.emptySeatBuilder,
    this.lockedSeatBuilder,
  });

  double _resolvedSize(BuildContext context) =>
      size ?? MediaQuery.of(context).size.width / 6;

  @override
  Widget build(BuildContext context) {
    final s = _resolvedSize(context);
    if (seatBuilder != null) {
      return GestureDetector(onTap: onTap, child: seatBuilder!(seat, s));
    }

    return GestureDetector(
      onTap: onTap,
      child: SizedBox(
        width: s,
        height: s,
        child: seat.isOccupied
            ? _occupiedSeat(s)
            : seat.isLocked
                ? _lockedSeat(s)
                : _emptySeat(s),
      ),
    );
  }

  Widget _lockedSeat(double s) {
    if (lockedSeatBuilder != null) return lockedSeatBuilder!(seat.index, s);

    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Container(
          width: s * 0.7,
          height: s * 0.7,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: Colors.white.withValues(alpha: 0.4),
          ),
          child: Icon(Icons.lock, color: Colors.white54, size: s * 0.3),
        ),
        const SizedBox(height: 2),
        Text('${seat.index + 1}',
            style: const TextStyle(color: Colors.white, fontSize: 10)),
      ],
    );
  }

  Widget _emptySeat(double s) {
    if (emptySeatBuilder != null) return emptySeatBuilder!(seat.index, s);

    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Stack(
          children: [
            Container(
              width: s * 0.7,
              height: s * 0.7,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.4),
              ),
              child: Icon(Icons.mic_none, color: Colors.white54, size: s * 0.3),
            ),
            // Reserved seat badge
            if (seat.isReserved)
              Positioned(
                top: 0,
                right: 0,
                child: Container(
                  width: s * 0.22,
                  height: s * 0.22,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.amber,
                  ),
                  child: Icon(Icons.star, color: Colors.white, size: s * 0.14),
                ),
              ),
          ],
        ),
        const SizedBox(height: 2),
        Text('${seat.index + 1}',
            style: const TextStyle(color: Colors.white, fontSize: 10)),
      ],
    );
  }

  Widget _occupiedSeat(double s) {
    if (avatarBuilder != null) {
      // Pass the FULL slot size (same as empty/locked builders) so the app sizes
      // all three seat states from one shared ratio. The builder applies its own
      // avatar inset.
      return avatarBuilder!(
        seat.occupantUserId!,
        s,
        seat.attributes,
        seat.isMuted,
        seat.index,
        seat.attributes['name'] ?? '',
      );
    }
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Stack(
          alignment: Alignment.center,
          children: [
            Container(
              width: s * 0.7,
              height: s * 0.7,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: isSpeaking
                    ? Border.all(color: Colors.green, width: 2)
                    : null,
              ),
              child: CircleAvatar(
                radius: s * 0.35,
                backgroundColor: Colors.grey[700],
                child: Text(
                  (seat.occupantUserId ?? '?')[0].toUpperCase(),
                  style: TextStyle(color: Colors.white, fontSize: s * 0.2),
                ),
              ),
            ),
            // Mute indicator
            if (seat.isMuted)
              Positioned(
                bottom: 0,
                right: 0,
                child: Icon(Icons.mic_off, color: Colors.red, size: s * 0.2),
              ),
            // Reserved seat badge on occupied seat
            if (seat.isReserved)
              Positioned(
                top: 0,
                right: 0,
                child: Container(
                  width: s * 0.22,
                  height: s * 0.22,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.amber,
                  ),
                  child: Icon(Icons.star, color: Colors.white, size: s * 0.14),
                ),
              ),
            // Role badge (host/admin/guest) — bottom left
            if (role != null && _isPrivilegedRole(role!))
              Positioned(
                bottom: 0,
                left: 0,
                child: _roleBadge(s),
              ),
          ],
        ),
      ],
    );
  }

  /// Whether the role should show a badge.
  bool _isPrivilegedRole(String r) {
    return r == 'host' || r == 'admin' || r == 'guest';
  }

  /// Builds a small role badge widget.
  Widget _roleBadge(double s) {
    Color badgeColor;
    String label;

    switch (role) {
      case 'host':
        badgeColor = Colors.orangeAccent;
        label = 'H';
        break;
      case 'admin':
        badgeColor = Colors.blueAccent;
        label = 'A';
        break;
      case 'guest':
        badgeColor = Colors.green;
        label = 'G';
        break;
      default:
        return const SizedBox.shrink();
    }

    return Container(
      width: s * 0.22,
      height: s * 0.22,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: badgeColor,
        border: Border.all(color: Colors.white, width: 1),
      ),
      child: Center(
        child: Text(
          label,
          style: TextStyle(
            color: Colors.white,
            fontSize: s * 0.12,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}
