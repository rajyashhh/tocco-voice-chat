import 'package:flutter/widgets.dart';
import 'package:livekit_client/livekit_client.dart'
    show VideoViewFit, TrackProcessor, VideoProcessorOptions;

import '../theme/utd_room_strings.dart';
import '../theme/utd_room_theme.dart';

/// Configuration for [UTDLiveRoom].
///
/// A live room is a video room: the host's camera fills the screen (the host
/// video IS the background — there is no separate background layer or seat
/// grid), and up to [maxGuestTiles] invited guests appear as floating video
/// tiles on top.
class UTDLiveRoomConfig {
  // --- Shared room behaviour ---
  final bool showControlsBar;
  final bool enableMinimize;
  final bool useSpeakerWhenJoining;

  /// When false, the kit does NOT render its built-in centered "Go Live"
  /// button during the host self-preview — the app renders its own composer
  /// (title/cover/start) and triggers [UTDMediaController.goLive] itself.
  final bool showGoLiveButton;

  // --- Live (video) behaviour ---

  /// When true, the host publishes the camera immediately on connect instead of
  /// going through the self-preview → "Go Live" step. Default false (preview),
  /// per the locked product decision (self-preview → Go Live).
  final bool autoHostCamera;

  /// When true (default), the local camera starts on the front camera.
  final bool frontCameraOnJoin;

  /// When true (default), the LOCAL camera tile is mirrored (front-camera selfie
  /// view). Remote tiles are never force-mirrored.
  final bool mirrorLocalVideo;

  /// Max simultaneous guest tiles (host excluded). The engine enforces the same
  /// cap server-side — this is the client gate.
  final int maxGuestTiles;

  /// How the host's full-bleed video is fitted. Default [VideoViewFit.cover].
  final VideoViewFit hostFit;

  /// How each floating guest tile's video is fitted. Default [VideoViewFit.cover].
  final VideoViewFit guestFit;

  /// Seat index that maps to the host tile (full-bleed). Always 0 for live.
  final int hostSeatIndex;

  // --- Theming ---
  final UTDRoomTheme theme;

  /// Strings for the built-in default UI. `null` => [UTDRoomStrings.en].
  final UTDRoomStrings? strings;

  // --- Section overrides (null = built-in default) ---
  final Widget? headerWidget;
  final Widget? messagesWidget;
  final Widget? controlsBarWidget;
  final Widget? foregroundWidget;

  // --- Tile builders (null = built-in default) ---
  /// Replaces the full-bleed host tile.
  final Widget Function(BuildContext context)? hostTileBuilder;

  /// Replaces a floating guest tile (by seat index 1..maxGuestTiles).
  final Widget Function(BuildContext context, int seatIndex)? guestTileBuilder;

  /// Optional overlay rendered at the BOTTOM of each guest tile (over the
  /// video) — the app uses it for the guest's name + per-stage gift counter.
  /// Receives the guest's identity (user id).
  final Widget Function(BuildContext context, String identity)?
      guestTileFooterBuilder;

  /// Fired when the user taps the empty stage area (the full-bleed host
  /// video, outside any button/tile). The app uses it for TikTok-style
  /// tap-hearts. Buttons/chat/tiles keep winning their own taps — this only
  /// receives taps that reach the stage layer underneath the chrome.
  final void Function()? onStageTap;

  /// User attributes shared with other participants.
  final Map<String, String> userInRoomAttributes;

  // --- Video effects (beauty / filters) ---

  /// Builds a fresh LiveKit video-effects processor for each local camera
  /// capture. `null` (default) = no effects, fully backward compatible.
  ///
  /// This is a FACTORY (not a shared instance) because LiveKit *destroys* the
  /// processor on every `restartTrack` (camera flip / reconnect), so each
  /// capture needs its own instance. The kit attaches the result to the host
  /// self-preview ([UTDMediaController.startPreview]) and to the SDK's default
  /// camera options (reconnect / autoHostCamera / guest go-live).
  ///
  /// Typed against livekit_client so the kit needs no dependency on an effects
  /// package. Wire e.g. `utd_video_effects_kit`:
  /// ```dart
  /// UTDLiveRoomConfig(buildVideoProcessor: () => VideoEffectsProcessor.create())
  /// ```
  final TrackProcessor<VideoProcessorOptions> Function()? buildVideoProcessor;

  const UTDLiveRoomConfig({
    this.showControlsBar = true,
    this.enableMinimize = true,
    this.useSpeakerWhenJoining = true,
    this.showGoLiveButton = true,
    this.autoHostCamera = false,
    this.frontCameraOnJoin = true,
    this.mirrorLocalVideo = true,
    this.maxGuestTiles = 3,
    this.hostFit = VideoViewFit.cover,
    this.guestFit = VideoViewFit.cover,
    this.hostSeatIndex = 0,
    this.theme = const UTDRoomTheme(),
    this.strings,
    this.headerWidget,
    this.messagesWidget,
    this.controlsBarWidget,
    this.foregroundWidget,
    this.hostTileBuilder,
    this.guestTileBuilder,
    this.guestTileFooterBuilder,
    this.onStageTap,
    this.userInRoomAttributes = const {},
    this.buildVideoProcessor,
  });

  /// Resolves the effective strings — the supplied [strings] or English defaults.
  UTDRoomStrings resolveStrings() => strings ?? UTDRoomStrings.en();
}
