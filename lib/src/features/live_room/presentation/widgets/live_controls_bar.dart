import 'dart:async';

import 'package:general/src/core/index.dart';
import 'package:general/src/features/live_room/presentation/component/messages/messages_button/live_input_board.dart';
import 'package:general/src/features/live_room/presentation/widgets/live_share_sheet.dart';
import 'package:utd_live_room_kit/utd_live_room_kit.dart' as live;

import 'live_control_button.dart';
import 'live_gift_button.dart';
import 'live_more_sheet.dart';
import 'live_pk_sheet.dart';
import 'package:general/src/features/room/presentation/admin_permissions/room_admin_permissions.dart';

/// The live room's bottom bar, styled like the audio room's [ButtomBarWidget]
/// (input pill + translucent circle buttons + the same SVGA gift icon).
///
/// Layout: input pill, mic, camera, requests (host/admin: pending queue with
/// badge — audience: apply-to-speak), gift, more. The "more" sheet keeps only
/// speaker / flip camera / beauty. The controller comes from
/// [live.UTDRoomScope] (the bar is rendered inside the kit's scope).
class LiveControlsBar extends StatefulWidget {
  const LiveControlsBar({super.key});

  @override
  State<LiveControlsBar> createState() => _LiveControlsBarState();
}

class _LiveControlsBarState extends State<LiveControlsBar> {
  live.UTDRoomController? _c;
  StreamSubscription<live.UTDRoleChangeEvent>? _roleSub;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_c != null) return;
    final c = live.UTDRoomScope.of(context).controller;
    _c = c;
    // Role has no ValueNotifier; rebuild when the local user's role changes so
    // the host/admin requests button appears/disappears.
    _roleSub = c.roleChangeStream.listen((e) {
      if (!mounted) return;
      if (e.identity == c.localIdentity) setState(() {});
    });
  }

  @override
  void dispose() {
    _roleSub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scope = live.UTDRoomScope.of(context);
    final c = _c ?? scope.controller;
    final theme = scope.theme;
    final media = c.mediaController;

    return Padding(
      padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 8.h),
      child: AnimatedBuilder(
        animation: c.seatController.seats,
        builder: (context, _) {
          // VIEWER bar (not the host, not on stage): no "more" sheet — a
          // chat pill with a visible "اكتب هنا" hint takes the start side,
          // and a tight corner group: speaker / request-to-speak / gift box
          // (gift outermost in the corner). Owner spec 2026-06-11.
          final isViewer = !c.isLocalHost && c.localSeatIndex < 0;
          if (isViewer) {
            return Row(
              children: [
                Expanded(child: _typeHerePill(context)),
                10.wBox,
                // Share moved down from the header next to the other action
                // buttons (owner 2026-06-12).
                LiveControlButton(
                  icon: Icons.share,
                  onTap: () => LiveShareSheet.open(context),
                ),
                6.wBox,
                ValueListenableBuilder<bool>(
                  valueListenable: media.isSpeakerOn,
                  builder: (_, on, __) => LiveControlButton(
                    icon: on ? Icons.volume_up : Icons.volume_off,
                    iconColor: on ? null : theme.danger,
                    onTap: media.toggleSpeaker,
                  ),
                ),
                6.wBox,
                _requestsButton(c, scope.strings),
                6.wBox,
                const LiveGiftButton(),
              ],
            );
          }
          return _stageBar(context, c, theme, media);
        },
      ),
    );
  }

  /// Host / on-stage layout — the original full bar (mic, camera, more…).
  Widget _stageBar(
    BuildContext context,
    live.UTDRoomController c,
    live.UTDRoomTheme theme,
    live.UTDMediaController media,
  ) {
    final scope = live.UTDRoomScope.of(context);
    return Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _inputPill(context),
          // Mic + camera: only while actually ON STAGE (the host, or a guest
          // holding a seat). canPublish alone is wrong — the engine keeps the
          // publish permission after the host kicks a guest off the stage, so
          // the kicked guest kept guest controls instead of reverting to the
          // audience request-to-speak flow.
          AnimatedBuilder(
            animation:
                Listenable.merge([media.canPublish, c.seatController.seats]),
            builder: (_, __) {
              final onStage =
                  c.isLocalHost || c.localSeatIndex >= 0;
              if (!media.canPublish.value || !onStage) {
                return const SizedBox.shrink();
              }
              return ValueListenableBuilder<bool>(
                valueListenable: media.isMicEnabled,
                builder: (_, on, __) => LiveControlButton(
                  icon: on ? Icons.mic : Icons.mic_off,
                  iconColor: on ? null : theme.danger,
                  onTap: media.toggleMicrophone,
                ),
              );
            },
          ),
          AnimatedBuilder(
            animation:
                Listenable.merge([media.canPublish, c.seatController.seats]),
            builder: (_, __) {
              final onStage =
                  c.isLocalHost || c.localSeatIndex >= 0;
              if (!media.canPublish.value || !onStage) {
                return const SizedBox.shrink();
              }
              return AnimatedBuilder(
                animation: Listenable.merge(
                    [media.isCameraEnabled, media.isPreviewing]),
                builder: (_, __) {
                  // Camera is "on" when published OR while in the host's
                  // pre-live preview (the camera is actually running then), so
                  // the button is honest the moment the host enters.
                  final on =
                      media.isCameraEnabled.value || media.isPreviewing.value;
                  return LiveControlButton(
                    icon: on ? Icons.videocam : Icons.videocam_off,
                    iconColor: on ? null : theme.danger,
                    onTap: () => _toggleCamera(c),
                  );
                },
              );
            },
          ),
          ValueListenableBuilder<bool>(
            valueListenable: media.isSpeakerOn,
            builder: (_, on, __) => LiveControlButton(
              icon: on ? Icons.volume_up : Icons.volume_off,
              iconColor: on ? null : theme.danger,
              onTap: media.toggleSpeaker,
            ),
          ),
          _requestsButton(c, scope.strings),
          const LiveGiftButton(),
          // PK battle — host only, opens the Mico-style PK sheet (random
          // match + friend challenge). Same VS artwork as the audio room's
          // PK button (owner 2026-08-08: the drawn wordmark looked bad; the
          // audio-room asset is the good one).
          if (c.isLocalHost)
            GestureDetector(
              onTap: () => LivePkSheet.show(context, c),
              child: Image.asset(
                AssetsManager.pkRoomNew,
                width: 39.w,
                height: 39.h,
                fit: BoxFit.cover,
              ),
            ),
          // The (...) sheet now always has content for everyone on stage —
          // share lives inside it (owner 2026-06-12), on top of the host/admin
          // entries and the camera entries (flip/beauty) when the camera is on.
          LiveControlButton(
            icon: Icons.more_horiz,
            onTap: () => LiveMoreSheet.show(context),
          ),
        ],
      );
  }

  /// Viewer chat pill: the chat icon with a visible "اكتب هنا" hint area, so
  /// it's obvious tapping it opens the keyboard.
  Widget _typeHerePill(BuildContext context) {
    return InkWell(
      onTap: () => Navigator.of(context).push(LiveInRoomMessageInputBoard()),
      child: Container(
        height: 38.h,
        padding: context.paddingSymmetric(horizontal: 8),
        decoration: BoxDecoration(
          color: ColorManager.onDark.withValues(alpha: 0.2),
          borderRadius: 20.radius,
        ),
        child: Row(
          children: [
            if (ConstantsManager.isTheme1)
              Image.asset(
                AssetsManager.chatIcon,
                width: 28.w,
                height: 28.h,
                fit: BoxFit.cover,
              )
            else
              Icon(Icons.chat_bubble_outline,
                  color: ColorManager.onDark, size: 20.sp),
            8.wBox,
            Expanded(
              child: TextWidget(
                Methods.getLang() == 'ar' ? 'اكتب هنا...' : 'Type here...',
                maxLines: 1,
                style: context.bodySmall.colorExt(
                  ColorManager.onDark.withValues(alpha: 0.8),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// The audio room's "send message" pill (white 20% rounded container), wired
  /// to the live input board.
  Widget _inputPill(BuildContext context) {
    return InkWell(
      onTap: () => Navigator.of(context).push(LiveInRoomMessageInputBoard()),
      child: ConstantsManager.isTheme1
          ? Image.asset(
              AssetsManager.chatIcon,
              width: 36.w,
              height: 36.h,
              fit: BoxFit.cover,
            )
          : Container(
              padding: context.paddingOnly(start: 5),
              decoration: BoxDecoration(
                color: ColorManager.onDark.withValues(alpha: 0.2),
                borderRadius: 20.radius,
              ),
              width: ScreenUtil().screenWidth * 0.26,
              height: 30,
              child: Row(
                children: [
                  10.wBox,
                  Expanded(
                    child: TextWidget(
                      StringManager.sendMessage.tr(),
                      maxLines: 1,
                      style: context.bodySmall.colorExt(ColorManager.onDark),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  /// Standalone requests button (was inside the "more" sheet): host/admin get
  /// the pending speaker-request queue with a badge; the audience gets
  /// apply-to-speak / cancel (only in request mode while off-stage).
  Widget _requestsButton(live.UTDRoomController c, live.UTDRoomStrings strings) {
    if (c.isHostOrAdmin) {
      // Granular gate: an admin without "manage requests" gets no queue.
      if (!c.isLocalHost &&
          !RoomAdminPermissions.iCan(RoomAdminPermissions.manageRequests)) {
        return const SizedBox.shrink();
      }
      return ValueListenableBuilder<List<live.SpeakerRequest>>(
        valueListenable: c.seatController.pendingRequests,
        builder: (_, requests, __) => LiveControlButton(
          // A co-host/guest icon, not a raised hand — the queue is "people
          // who want to JOIN the stage" (owner report 2026-06-11).
          icon: Icons.person_add_alt_1,
          badgeCount: requests.length,
          onTap: () => live.UTDRequestQueueSheet.show(context, controller: c),
        ),
      );
    }
    return ValueListenableBuilder<String>(
      valueListenable: c.seatController.seatMode,
      builder: (_, mode, __) {
        if (mode != 'request') return const SizedBox.shrink();
        return ValueListenableBuilder<List<live.SeatState>>(
          valueListenable: c.seatController.seats,
          builder: (_, __, ___) {
            if (c.localSeatIndex >= 0) return const SizedBox.shrink();
            return ValueListenableBuilder<List<live.SpeakerRequest>>(
              valueListenable: c.seatController.pendingRequests,
              builder: (_, requests, __) {
                final me = c.localIdentity;
                final requested =
                    me != null && requests.any((r) => r.identity == me);
                return LiveControlButton(
                  // Same guest icon as the host's queue button (both
                  // perspectives, owner spec); hourglass while pending.
                  icon: requested ? Icons.hourglass_top : Icons.person_add_alt_1,
                  onTap: () async {
                    if (requested) {
                      await c.cancelSpeakerRequest();
                    } else {
                      await c.requestToSpeak();
                    }
                  },
                );
              },
            );
          },
        );
      },
    );
  }

  /// Camera toggle aware of the host's pre-live preview: tapping while
  /// previewing stops the preview; tapping pre-live (host, not yet "Go Live")
  /// with no preview restarts one; otherwise it toggles the published camera
  /// (live host / guests).
  void _toggleCamera(live.UTDRoomController c) {
    final media = c.mediaController;
    if (media.isPreviewing.value) {
      media.stopPreview();
    } else if (c.isLocalHost && media.liveStartedAt.value == null) {
      media.startPreview();
    } else {
      media.toggleCamera();
    }
  }
}
