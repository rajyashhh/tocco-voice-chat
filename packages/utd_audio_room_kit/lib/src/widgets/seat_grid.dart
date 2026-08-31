import 'package:flutter/material.dart';

import '../controller/seat_controller.dart';
import '../models/seat_model.dart';
import 'seat_widget.dart';

// Renders seats in rows based on layout mode — supports all 8 modes
class UTDSeatGrid extends StatelessWidget {
  final List<List<int>> rows;
  final double seatSize;
  final List<SeatState> seats;
  final Set<String> activeSpeakers;
  final void Function(int index, SeatState seat)? onSeatTap;

  /// Maps occupant identity → role string (e.g., "host", "admin", "guest").
  /// Used to display role badges on seats.
  final Map<String, String> participantRoles;

  // Custom builders passed through to UTDSeatWidget
  final Widget Function(SeatState seat, double size)? seatBuilder;
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

  const UTDSeatGrid({
    super.key,
    required this.rows,
    required this.seatSize,
    required this.seats,
    this.activeSpeakers = const {},
    this.participantRoles = const {},
    this.onSeatTap,
    this.seatBuilder,
    this.avatarBuilder,
    this.emptySeatBuilder,
    this.lockedSeatBuilder,
  });

  @Deprecated('Use UTDRoomMode with UTDAudioRoom.modes instead')
  factory UTDSeatGrid.fromMode({
    Key? key,
    required String mode,
    required double screenWidth,
    required List<SeatState> seats,
    Set<String> activeSpeakers = const {},
    Map<String, String> participantRoles = const {},
    void Function(int index, SeatState seat)? onSeatTap,
    Widget Function(SeatState seat, double size)? seatBuilder,
    Widget Function(
      String userId,
      double size,
      Map<String, String> attributes,
      bool isMuted,
      int seatIndex,
      String userName,
    )? avatarBuilder,
    Widget Function(int index, double size)? emptySeatBuilder,
    Widget Function(int index, double size)? lockedSeatBuilder,
  }) {
    final count = UTDSeatController.seatCountForMode(mode);
    final rows = <List<int>>[];
    for (var i = 0; i < count; i += 4) {
      final end = (i + 4 > count) ? count : i + 4;
      rows.add(List.generate(end - i, (j) => i + j));
    }
    final seatSize = screenWidth / 5;

    return UTDSeatGrid(
      key: key,
      rows: rows,
      seatSize: seatSize,
      seats: seats,
      activeSpeakers: activeSpeakers,
      participantRoles: participantRoles,
      onSeatTap: onSeatTap,
      seatBuilder: seatBuilder,
      avatarBuilder: avatarBuilder,
      emptySeatBuilder: emptySeatBuilder,
      lockedSeatBuilder: lockedSeatBuilder,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        for (int i = 0; i < rows.length; i++) ...[
          if (i > 0) const SizedBox(height: 5),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              for (final seatIndex in rows[i])
                _buildSeat(seatIndex),
            ],
          ),
        ],
      ],
    );
  }

  Widget _buildSeat(int index) {
    final seat = index < seats.length
        ? seats[index]
        : SeatState(index: index);

    final isSpeaking = seat.occupantUserId != null &&
        activeSpeakers.contains(seat.occupantUserId);

    // Look up role for the occupant (if any)
    final role = seat.occupantUserId != null
        ? participantRoles[seat.occupantUserId]
        : null;

    return UTDSeatWidget(
      seat: seat,
      size: seatSize,
      isSpeaking: isSpeaking,
      role: role,
      onTap: () => onSeatTap?.call(index, seat),
      seatBuilder: seatBuilder,
      avatarBuilder: avatarBuilder,
      emptySeatBuilder: emptySeatBuilder,
      lockedSeatBuilder: lockedSeatBuilder,
    );
  }
}
