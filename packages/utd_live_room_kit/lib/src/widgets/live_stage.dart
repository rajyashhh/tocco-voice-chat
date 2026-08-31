import 'package:flutter/material.dart';
import 'package:livekit_client/livekit_client.dart';

import '../controller/utd_room_controller.dart';
import '../models/room_config.dart';
import '../models/seat_model.dart';
import 'live_tile_widget.dart';

/// The live video stage.
///
/// The host's camera fills the whole screen (the host video IS the background —
/// there is no seat grid and no separate background layer). Invited guests
/// appear as a vertical strip of floating video tiles on top.
///
/// Tracks are resolved at build time (never stored): the local participant's
/// camera comes from its own publication (or the unpublished preview track
/// before "Go Live"); remote cameras come from
/// [UTDRoomManager.getRemoteVideoTrack]. Each tile is keyed by occupant identity
/// so its renderer is reused across rebuilds.
class UTDLiveStage extends StatelessWidget {
  final UTDRoomController controller;
  final UTDLiveRoomConfig config;

  /// The room owner — always the full-bleed host tile, seated or not.
  final String roomOwnerId;

  /// Per-guest-tile tap (opens the tile action sheet). The [BuildContext] is
  /// under the [UTDRoomScope] so the sheet can read theme/strings.
  final void Function(BuildContext context, SeatState seat)? onGuestTileTap;

  /// When false, the stage renders the HOST layer only and the guest tile
  /// strip is rendered by the parent as a separate layer (the live room puts
  /// it ABOVE the swipe-to-clear chrome PageView, which is hit-test opaque
  /// and would otherwise swallow tile taps).
  final bool showGuestTiles;

  /// When false, the full-bleed host layer is skipped — used by the parent to
  /// render a guest-tiles-only overlay above the chrome.
  final bool showHost;

  const UTDLiveStage({
    super.key,
    required this.controller,
    required this.config,
    required this.roomOwnerId,
    this.onGuestTileTap,
    this.showGuestTiles = true,
    this.showHost = true,
  });

  // Floating guest tile dimensions (portrait, ~9:16).
  static const double _guestTileWidth = 104;
  static const double _guestTileHeight = 150;

  @override
  Widget build(BuildContext context) {
    // Any of these changing affects what/where we render. They only notify on
    // real changes (the polls diff with setEquals), so this is not a per-tick
    // rebuild. VideoTrackRenderers are keyed by identity → no flicker.
    final listenable = Listenable.merge([
      controller.seatController.seats,
      controller.roomManager.videoTracksNotifier,
      controller.cameraOnParticipants,
      controller.activeSpeakers,
      controller.participantRolesNotifier,
      controller.mediaController.isCameraEnabled,
      controller.mediaController.isPreviewing,
    ]);

    return AnimatedBuilder(
      animation: listenable,
      builder: (context, _) {
        final guestSeats = controller.seatController.seats.value
            .where((s) => s.isOccupied && s.index != config.hostSeatIndex)
            .take(config.maxGuestTiles)
            .toList();

        return Stack(
          children: [
            if (showHost)
              Positioned.fill(
                // Stage-level tap (tap-hearts): only taps that fall through
                // the chrome (buttons/chat/tiles win their own) land here.
                child: config.onStageTap == null
                    ? _hostTile(context)
                    : GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: config.onStageTap,
                        child: _hostTile(context),
                      ),
              ),
            if (showGuestTiles && guestSeats.isNotEmpty)
              Positioned(
                top: MediaQuery.of(context).padding.top + 118,
                // Left side — opposite the chat/messages column (which is
                // right-anchored in the RTL layout), so a long guest strip
                // never overlaps the tabs/messages (owner report 2026-06-11).
                left: 12,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    for (final seat in guestSeats)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: SizedBox(
                          width: _guestTileWidth,
                          height: _guestTileHeight,
                          child: _guestTile(context, seat),
                        ),
                      ),
                  ],
                ),
              ),
          ],
        );
      },
    );
  }

  // ── Host (full-bleed) ──

  Widget _hostTile(BuildContext context) {
    if (config.hostTileBuilder != null) return config.hostTileBuilder!(context);

    final isLocal = roomOwnerId == controller.localIdentity;
    return UTDLiveTile(
      key: ValueKey('live-tile-$roomOwnerId'),
      displayName: _nameOf(roomOwnerId, null),
      avatarUrl: _avatarOf(roomOwnerId, null),
      track: controller.cameraTrackFor(roomOwnerId),
      isCameraOn: controller.cameraOnFor(roomOwnerId),
      isMicMuted: _micMuted(roomOwnerId),
      isSpeaking: controller.activeSpeakers.value.contains(roomOwnerId),
      // Camera-off: keep the host avatar in the upper third, clear of the chat.
      avatarAlignment: const Alignment(0, -0.45),
      fit: config.hostFit,
      mirrorMode: (isLocal && config.mirrorLocalVideo)
          ? VideoViewMirrorMode.auto
          : VideoViewMirrorMode.off,
      theme: config.theme,
      // Full-screen host: a larger camera-off avatar reads better than the
      // default tile-sized one.
      avatarRadius: 64,
      showRoleBadge: false,
      showSpeakingBorder: false,
    );
  }

  // ── Guests (floating squares) ──

  Widget _guestTile(BuildContext context, SeatState seat) {
    if (config.guestTileBuilder != null) {
      return config.guestTileBuilder!(context, seat.index);
    }
    final id = seat.occupantUserId!;
    final isLocal = id == controller.localIdentity;
    final tile = UTDLiveTile(
      key: ValueKey('live-tile-$id'),
      displayName: _nameOf(id, seat),
      avatarUrl: _avatarOf(id, seat),
      track: controller.cameraTrackFor(id),
      isCameraOn: controller.cameraOnFor(id),
      isMicMuted: _micMuted(id),
      isSpeaking: controller.activeSpeakers.value.contains(id),
      role: controller.participantRolesNotifier.value[id],
      fit: config.guestFit,
      mirrorMode: (isLocal && config.mirrorLocalVideo)
          ? VideoViewMirrorMode.auto
          : VideoViewMirrorMode.off,
      theme: config.theme,
      avatarRadius: 28,
      borderRadius: BorderRadius.circular(12),
      onTap:
          onGuestTileTap == null ? null : () => onGuestTileTap!(context, seat),
    );
    final footer = config.guestTileFooterBuilder?.call(context, id);
    if (footer == null) return tile;
    return Stack(
      children: [
        Positioned.fill(child: tile),
        Positioned(left: 0, right: 0, bottom: 0, child: footer),
      ],
    );
  }

  // ── Resolution helpers ──

  bool _micMuted(String identity) =>
      controller.mutedParticipants.value.contains(identity);

  Participant? _participant(String identity) {
    final lp = controller.roomManager.localParticipant;
    if (lp?.identity == identity) return lp;
    return controller.roomManager.remoteParticipants
        .where((p) => p.identity == identity)
        .firstOrNull;
  }

  String _nameOf(String identity, SeatState? seat) {
    final p = _participant(identity);
    final name = seat?.attributes['name'] ??
        seat?.attributes['cn'] ??
        (p?.name.isNotEmpty == true ? p!.name : null) ??
        p?.attributes['name'] ??
        identity;
    return name.isEmpty ? identity : name;
  }

  String? _avatarOf(String identity, SeatState? seat) {
    final p = _participant(identity);
    final url = seat?.attributes['avatar'] ?? p?.attributes['avatar'];
    return (url != null && url.isNotEmpty) ? url : null;
  }
}
