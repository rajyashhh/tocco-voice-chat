import 'package:flutter/material.dart';
import 'seat_model.dart';

// Room configuration — replaces ZegoUIKitPrebuiltLiveAudioRoomConfig
class UTDAudioRoomConfig {
  final bool showControlsBar;
  final bool showSeatNames;
  final bool enableMinimize;
  final bool turnOnMicrophoneWhenJoining;
  final bool useSpeakerWhenJoining;
  final int hostSeatIndex;

  // Custom section widgets — null = default, custom = replaces default
  final Widget? headerWidget;
  final Widget? messagesWidget;
  final Widget? controlsBarWidget;
  final Widget? pkWidget;
  final Widget? backgroundWidget;
  final Widget? foregroundWidget;

  // Custom builders for seats
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

  // User attributes shared with other participants
  final Map<String, String> userInRoomAttributes;

  const UTDAudioRoomConfig({
    this.showControlsBar = true,
    this.showSeatNames = true,
    this.enableMinimize = true,
    this.turnOnMicrophoneWhenJoining = false,
    this.useSpeakerWhenJoining = true,
    this.hostSeatIndex = 0,
    this.headerWidget,
    this.messagesWidget,
    this.controlsBarWidget,
    this.pkWidget,
    this.backgroundWidget,
    this.foregroundWidget,
    this.seatBuilder,
    this.avatarBuilder,
    this.emptySeatBuilder,
    this.lockedSeatBuilder,
    this.userInRoomAttributes = const {},
  });

  factory UTDAudioRoomConfig.host() => const UTDAudioRoomConfig(
        turnOnMicrophoneWhenJoining: true,
      );

  factory UTDAudioRoomConfig.audience() => const UTDAudioRoomConfig(
        turnOnMicrophoneWhenJoining: false,
      );
}
