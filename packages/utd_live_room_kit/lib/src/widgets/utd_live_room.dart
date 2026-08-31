import 'dart:async';

import 'package:flutter/foundation.dart' show kDebugMode;
import 'package:flutter/material.dart';
import 'package:livekit_client/livekit_client.dart' show CameraPosition;

import '../controller/utd_room_controller.dart';
import '../minimizing/mini_overlay_machine.dart';
import '../models/ban_model.dart';
import '../models/role_model.dart';
import '../models/room_config.dart';
import '../theme/utd_room_scope.dart';
import '../theme/utd_room_strings.dart';
import '../theme/utd_room_theme.dart';
import 'banned_dialog.dart';
import 'live_ended_dialog.dart';
import 'default_connect_error.dart';
import 'default_controls_bar.dart';
import 'default_message_list.dart';
import 'default_room_header.dart';
import 'live_stage.dart';
import 'pip_view.dart';
import 'sheets/live_tile_action_sheet.dart';
import 'sheets/utd_sheet.dart';

/// A LiveKit-based video live room.
///
/// The host's camera fills the screen; up to [UTDLiveRoomConfig.maxGuestTiles]
/// invited guests appear as floating video tiles. There is no seat grid and no
/// separate background layer — the host video IS the background. The host enters
/// a self-preview and taps "Go Live" to publish (unless
/// [UTDLiveRoomConfig.autoHostCamera] is set). Audience can watch + chat and may
/// request to go live.
class UTDLiveRoom extends StatefulWidget {
  final String appId;
  final String serverSecret;
  final String userId;
  final String userName;
  final String roomId;
  final String roomOwnerId;

  /// Identities the app considers admins (owner -> host, these -> admin, rest ->
  /// audience). No engine round-trip; the engine only force-upgrades the owner.
  final Set<String> adminIds;

  /// Async source for [adminIds] when they are not known yet at build time
  /// (e.g. the app backend's enter-room response is still in flight). The
  /// token step NEVER waits for it: everyone joins immediately with the
  /// best-known role, and when this future resolves and lists the local user
  /// as an admin, the kit self-upgrades through the engine role endpoint
  /// ([UTDRoomController.upgradeSelfRole]) — server-side permissions +
  /// `_role_change` broadcast, so full moderation powers without serializing
  /// every join behind the enter-room response.
  final Future<Set<String>> Function()? adminIdsResolver;

  /// Optional SYNC probe for the admin list at token time (no waiting): the
  /// enter-room response may have landed during the route transition, in
  /// which case the join can use an `admin` token directly.
  final Set<String> Function()? adminIdsNow;

  final UTDLiveRoomConfig config;
  final void Function(UTDRoomController controller)? onControllerReady;
  final void Function(bool isConnected)? onConnectionChanged;
  final void Function(Object error, StackTrace stackTrace)? onConnectError;

  /// Reuse an existing (already-connected) controller — e.g. restoring from a
  /// minimized overlay.
  final UTDRoomController? controller;

  const UTDLiveRoom({
    super.key,
    required this.appId,
    required this.serverSecret,
    required this.userId,
    required this.userName,
    required this.roomId,
    required this.roomOwnerId,
    this.adminIds = const {},
    this.adminIdsResolver,
    this.adminIdsNow,
    this.config = const UTDLiveRoomConfig(),
    this.onControllerReady,
    this.onConnectionChanged,
    this.onConnectError,
    this.controller,
  });

  @override
  State<UTDLiveRoom> createState() => _UTDLiveRoomState();
}

class _UTDLiveRoomState extends State<UTDLiveRoom> {
  late final UTDRoomController _controller;
  bool _connected = false;

  /// True when the initial connect FAILED and the consumer supplied an
  /// [onConnectError] (so the room is NOT torn down — entry is decoupled from
  /// the stream). The user stays in the room: the chrome — header (exit) and
  /// controls bar (gift) — must render even though the video stage + guest
  /// tiles are inert. Distinct from [_connected]: only that flag enables
  /// stream-backed affordances (host go-live, guest video).
  bool _streamFailed = false;

  /// True while the host is in self-preview and has not yet tapped "Go Live".
  bool _showGoLive = false;

  /// Set when connect() fails and the consumer did not supply [onConnectError].
  Object? _connectError;
  StreamSubscription<UTDRoleChangeEvent>? _roleSnackSub;

  /// Two-page chrome carousel: page 0 = clear screen (video only), page 1 =
  /// full overlay UI. Starts on the overlay page; in the app's RTL layout
  /// going 1→0 (hide) is a leftward swipe and 0→1 (restore) a rightward one,
  /// matching the requested gesture.
  final PageController _overlayPageController = PageController(initialPage: 1);

  bool get _isHost => widget.userId == widget.roomOwnerId;

  @override
  void initState() {
    super.initState();

    if (UTDMiniOverlayMachine.instance.isMinimizing) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          UTDMiniOverlayMachine.instance
              .changeState(UTDMiniOverlayState.inAudioRoom);
        }
      });
    }

    if (widget.controller != null && widget.controller!.isConnected) {
      _controller = widget.controller!;
      _controller.initApi(
          appId: widget.appId, serverSecret: widget.serverSecret);
      // Wire the video-effects (beauty/filter) factory BEFORE connect so the
      // first camera capture carries the processor. Harmless on the reuse branch
      // (already-connected) — it applies to any subsequent re-capture.
      _controller.setVideoProcessorFactory(widget.config.buildVideoProcessor);
      _connected = true;
      widget.onControllerReady?.call(_controller);
      // Re-entering an already-connected room (restored from minimize): re-arm
      // OS PiP for this freshly-mounted screen.
      _controller.pip.armIfEnabled();
    } else {
      _controller = widget.controller ?? UTDRoomController();
      _controller.initApi(
          appId: widget.appId, serverSecret: widget.serverSecret);
      // Wire the video-effects (beauty/filter) factory BEFORE connect so the
      // first camera capture carries the processor. Harmless on the reuse branch
      // (already-connected) — it applies to any subsequent re-capture.
      _controller.setVideoProcessorFactory(widget.config.buildVideoProcessor);
      widget.onControllerReady?.call(_controller);
      _connect();
    }

    // Bans handled entirely inside the package (dialog → leave → onClose hook).
    _controller.onBanned = _handleBanned;
    // Host leaving ends the live for every other client (same dialog→leave→
    // onClose funnel as bans). ??= so a host app can override it.
    _controller.onLiveEnded ??= _handleLiveEnded;
    // Default force-exit only if the host did not wire its own (??= so host wins).
    _controller.onForceExit ??= _handleForceExit;

    if (widget.config.headerWidget == null &&
        widget.config.foregroundWidget == null) {
      _roleSnackSub = _controller.roleChangeStream.listen((e) {
        if (!mounted) return;
        if (e.identity != _controller.localIdentity) return;
        utdShowSnack(
            context, widget.config.resolveStrings().roleChangedTo(e.role));
      });
    }
  }

  Future<void> _connect() async {
    // Host + 3 guest tiles by default (matches the engine's live defaults).
    final seatCount = widget.config.maxGuestTiles + 1;
    final joinWatch = Stopwatch()..start();
    try {
      // NO waiting on the enter-room response: join immediately with the
      // best-known role ([adminIds], else the sync [adminIdsNow] probe) and
      // self-upgrade post-connect if the resolver later lists the local user
      // as an admin (see _armAdminUpgrade). Non-admins pay zero wait.
      var adminIds = widget.adminIds;
      if (!_isHost && adminIds.isEmpty && widget.adminIdsNow != null) {
        try {
          adminIds = widget.adminIdsNow!();
        } catch (_) {}
      }
      final role = _isHost
          ? 'host'
          : adminIds.contains(widget.userId)
              ? 'admin'
              : 'audience';
      if (kDebugMode) {
        debugPrint(
            '[JOIN_TT] live tokenReq role=$role +${joinWatch.elapsedMilliseconds}ms');
      }
      final tokenResponse = await _controller.generateToken(
        identity: widget.userId,
        roomName: widget.roomId,
        roomOwnerId: widget.roomOwnerId,
        service: 'rooms',
        kind: 'live',
        role: role,
        name: widget.userName,
        // Live defaults are also applied server-side for kind:'live'; passing
        // them explicitly for the room creator keeps creation deterministic.
        seatCount: _isHost ? seatCount : null,
        seatMode: _isHost ? 'request' : null,
        hostSeat: _isHost ? widget.config.hostSeatIndex : null,
      );

      await _controller.connect(
        url: tokenResponse.url,
        token: tokenResponse.token,
        seatCount: seatCount,
        // The host enables the mic at "Go Live" (or autoHostCamera); audience
        // never publishes on join. So never enable the mic at connect.
        enableMicOnJoin: false,
        useSpeaker: widget.config.useSpeakerWhenJoining,
        userAttributes: widget.config.userInRoomAttributes,
        roomName: widget.roomId,
        // Lets the controller detect the host leaving and end the live for
        // every other client.
        hostIdentity: widget.roomOwnerId,
      );

      if (kDebugMode) {
        debugPrint(
            '[JOIN_TT] live connected +${joinWatch.elapsedMilliseconds}ms');
      }
      if (!mounted) return;
      setState(() => _connected = true);
      widget.onConnectionChanged?.call(true);
      _controller.pip.armIfEnabled();
      if (!_isHost && role != 'admin') {
        _armAdminUpgrade();
      }
      await _startHostFlow();
    } on UTDBannedException catch (_) {
      if (mounted) {
        widget.onConnectionChanged?.call(false);
        _controller.notifyBannedFromToken();
      }
    } catch (e, st) {
      debugPrint('[UTDLiveRoom] connect failed: $e\n$st');
      if (mounted) {
        widget.onConnectionChanged?.call(false);
        if (widget.onConnectError != null) {
          // Entry is decoupled from the stream: the consumer keeps the user in
          // the room. Render the chrome (header exit + controls-bar gift)
          // instead of the connecting skeleton; the video stage + guest tiles
          // stay inert (no connection), host go-live is disabled.
          setState(() => _streamFailed = true);
          widget.onConnectError!(e, st);
        } else {
          setState(() => _connectError = e);
        }
      }
    }
  }

  /// Attaches the (non-blocking) admin self-upgrade: when the enter-room
  /// response resolves and lists the local user as an admin, upgrade the
  /// already-connected audience session through the engine role endpoint.
  /// Fallback on persistent failure: one silent token re-issue + reconnect
  /// with the admin role — admins only, rare.
  void _armAdminUpgrade() {
    final resolver = widget.adminIdsResolver;
    if (resolver == null) return;
    resolver().then((ids) async {
      if (!mounted || !ids.contains(widget.userId)) return;
      if (!_controller.isConnected) return;
      final upgraded = await _controller.upgradeSelfRole(
        ownerId: widget.roomOwnerId,
        selfId: widget.userId,
      );
      if (!upgraded && mounted && _controller.isConnected) {
        await _reconnectAsAdmin();
      }
    }).catchError((Object e) {
      debugPrint('[UTDLiveRoom] adminIdsResolver failed (non-fatal): $e');
    });
  }

  Future<void> _reconnectAsAdmin() async {
    try {
      final tokenResponse = await _controller.generateToken(
        identity: widget.userId,
        roomName: widget.roomId,
        roomOwnerId: widget.roomOwnerId,
        service: 'rooms',
        kind: 'live',
        role: 'admin',
        name: widget.userName,
      );
      // The user may have exited (or been removed) while the token request
      // was in flight — reconnecting then would resurrect a room session
      // nobody is looking at.
      if (!mounted || !_controller.isConnected) return;
      await _controller.connect(
        url: tokenResponse.url,
        token: tokenResponse.token,
        seatCount: widget.config.maxGuestTiles + 1,
        enableMicOnJoin: false,
        useSpeaker: widget.config.useSpeakerWhenJoining,
        userAttributes: widget.config.userInRoomAttributes,
        roomName: widget.roomId,
        hostIdentity: widget.roomOwnerId,
      );
      // Exited while the reconnect itself was in flight: tear the fresh
      // session down immediately instead of leaving it audible.
      if (!mounted) await _controller.leave();
    } on UTDBannedException catch (_) {
      if (mounted) _controller.notifyBannedFromToken();
    } catch (e) {
      debugPrint('[UTDLiveRoom] admin token re-issue failed (non-fatal): $e');
    }
  }

  /// Host go-live experience: self-preview → "Go Live" (default), or publish
  /// immediately when [UTDLiveRoomConfig.autoHostCamera] is set. Audience: no-op.
  Future<void> _startHostFlow() async {
    if (!_isHost) return;
    final position = widget.config.frontCameraOnJoin
        ? CameraPosition.front
        : CameraPosition.back;
    if (widget.config.autoHostCamera) {
      await _controller.mediaController.goLive();
      return;
    }
    await _controller.mediaController.startPreview(position: position);
    if (mounted) setState(() => _showGoLive = true);
  }

  Future<void> _goLive() async {
    await _controller.mediaController.goLive();
    if (mounted) setState(() => _showGoLive = false);
  }

  void _handleForceExit() => unawaited(_forceExit());

  Future<void> _forceExit() async {
    final strings = widget.config.resolveStrings();
    if (mounted) utdShowSnack(context, strings.connectionLost);
    final onClose = _controller.minimize.config?.onClose;
    await _controller.leave();
    if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
    onClose?.call();
  }

  Future<void> _handleBanned(UTDBanNotice notice) async {
    if (mounted) {
      await showUTDBannedDialog(context, notice);
      await _controller.leave();
      if (!mounted) return;
      final onClose = _controller.minimize.config?.onClose;
      Navigator.of(context).popUntil((route) => route.isFirst);
      onClose?.call();
      return;
    }
    // Minimized: this widget is disposed; route the ban through the app navigator.
    final ctx = _controller.navigatorKey?.currentContext;
    if (ctx != null) await showUTDBannedDialog(ctx, notice);
    if (_controller.minimize.isMinimizing) {
      await _controller.minimize.close();
    } else {
      await _controller.leave();
      _controller.minimize.config?.onClose?.call();
    }
  }

  /// The host left → end the live for this (non-host) client: show the
  /// "live ended" dialog, leave, pop to the first route, then run the host
  /// app's teardown via the `onClose` hook. Mirrors [_handleBanned].
  Future<void> _handleLiveEnded() async {
    if (mounted) {
      await showUTDLiveEndedDialog(context);
      await _controller.leave();
      if (!mounted) return;
      final onClose = _controller.minimize.config?.onClose;
      Navigator.of(context).popUntil((route) => route.isFirst);
      onClose?.call();
      return;
    }
    // Widget disposed (e.g. backgrounded): route through the app navigator.
    final ctx = _controller.navigatorKey?.currentContext;
    if (ctx != null) await showUTDLiveEndedDialog(ctx);
    await _controller.leave();
    _controller.minimize.config?.onClose?.call();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _controller.minimize.captureNavigation(context);
  }

  void _retry() {
    setState(() => _connectError = null);
    _connect();
  }

  Future<void> _exitOnError() async {
    final onClose = _controller.minimize.config?.onClose;
    await _controller.leave();
    if (!mounted) return;
    Navigator.of(context).popUntil((route) => route.isFirst);
    onClose?.call();
  }

  @override
  void dispose() {
    _roleSnackSub?.cancel();
    _overlayPageController.dispose();
    _controller.pip.disarm();
    if (!_controller.minimize.isMinimizing) {
      _controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = widget.config.theme;
    final strings = widget.config.resolveStrings();

    return ValueListenableBuilder<bool>(
      valueListenable: _controller.pip.isInPip,
      builder: (context, inPip, child) =>
          inPip ? UTDPipView(controller: _controller) : child!,
      child: UTDRoomScope(
        theme: theme,
        strings: strings,
        controller: _controller,
        hostSeatIndex: widget.config.hostSeatIndex,
        roomOwnerId: widget.roomOwnerId,
        child: Scaffold(
          backgroundColor: theme.background,
          body: Stack(
            children: [
              // Base layer: the full-bleed video stage (host fills the screen).
              // Guest tiles are rendered SEPARATELY above the chrome PageView —
              // the scrollable is hit-test opaque and would swallow tile taps.
              if (_connected)
                Positioned.fill(
                  child: UTDLiveStage(
                    controller: _controller,
                    config: widget.config,
                    roomOwnerId: widget.roomOwnerId,
                    showGuestTiles: false,
                  ),
                )
              else if (_connectError != null)
                Positioned.fill(
                  child: UTDConnectErrorView(
                    theme: theme,
                    strings: strings,
                    onRetry: _retry,
                    onExit: _exitOnError,
                  ),
                )
              else
                // Connecting skeleton: a stage-shaped dark gradient (the host
                // video IS the background) with a subtle indicator — instant
                // visual structure instead of a bare spinner on black. Once the
                // connect has FAILED but the user stays (_streamFailed), the
                // spinner is dropped: the gradient remains as the static room
                // frame behind the (now-rendered) chrome.
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Color(0xFF2A2A33),
                          Color(0xFF17171D),
                          Color(0xFF0E0E12),
                        ],
                      ),
                    ),
                    child: _streamFailed
                        ? const SizedBox.expand()
                        : Center(
                            child: SizedBox(
                              width: 28,
                              height: 28,
                              child: CircularProgressIndicator(
                                strokeWidth: 2.5,
                                color: Colors.white.withValues(alpha: 0.6),
                              ),
                            ),
                          ),
                  ),
                ),

              // Overlays (header top, chat bottom-start, controls bottom).
              // bottomStart (not bottomLeft): the chat column must hug the
              // reading edge — right in RTL locales, left in LTR.
              //
              // The whole chrome lives in a two-page horizontal PageView —
              // "a screen over the screen": swiping toward the reading
              // direction slides every overlay away for an unobstructed view
              // of the video, swiping back restores it (owner request
              // 2026-06-11). Inner horizontal scrollables (visitor strip)
              // win over the PageView inside their own bounds, as usual.
              //
              // Rendered when connected OR when the stream connect failed but
              // the user is staying (_streamFailed) — so the header exit and the
              // controls-bar gift button are reachable. The video stage + guest
              // tiles below stay gated to _connected (no connection = no video).
              if (_connected || _streamFailed)
                Positioned.fill(
                  child: PageView(
                    controller: _overlayPageController,
                    physics: const ClampingScrollPhysics(),
                    children: [
                      // Page 0 — the "clear screen": nothing but the video.
                      // The scrollable is hit-test-opaque, so stage taps must
                      // be re-emitted from INSIDE the page (tap-hearts).
                      GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: widget.config.onStageTap,
                        child: const SizedBox.expand(),
                      ),
                      // Page 1 — chrome. A bottom-layer detector catches taps
                      // that fall through the chrome's transparent gaps (the
                      // chrome widgets keep winning their own taps), since the
                      // PageView blocks them from reaching the stage below.
                      Stack(
                        children: [
                          if (widget.config.onStageTap != null)
                            Positioned.fill(
                              child: GestureDetector(
                                behavior: HitTestBehavior.opaque,
                                onTap: widget.config.onStageTap,
                                child: const SizedBox.expand(),
                              ),
                            ),
                          SafeArea(
                        child: Column(
                          children: [
                            widget.config.headerWidget ??
                                UTDDefaultRoomHeader(
                                  controller: _controller,
                                  enableMinimize: widget.config.enableMinimize,
                                ),
                            Expanded(
                              // A custom messagesWidget gets the WHOLE area
                              // between the header and the controls bar and
                              // manages its own size/alignment (the app's
                              // tabbed chat anchors itself bottom-start and
                              // can expand up to just below the header). Only
                              // the built-in default keeps the legacy 0.72×0.6
                              // bottom-start framing.
                              child: widget.config.messagesWidget ??
                                  Align(
                                    alignment: AlignmentDirectional.bottomStart,
                                    child: FractionallySizedBox(
                                      widthFactor: 0.72,
                                      heightFactor: 0.6,
                                      child: UTDDefaultMessageList(
                                        chatController:
                                            _controller.chatController,
                                      ),
                                    ),
                                  ),
                            ),
                            widget.config.controlsBarWidget ??
                                (widget.config.showControlsBar
                                    ? UTDDefaultControlsBar(
                                        controller: _controller)
                                    : const SizedBox()),
                          ],
                        ),
                      ),
                        ],
                      ),
                    ],
                  ),
                ),

              // Guest tiles layer — ABOVE the chrome PageView so tile taps
              // (action sheet) keep working; visible in clear-screen mode too.
              if (_connected)
                Positioned.fill(
                  child: UTDLiveStage(
                    controller: _controller,
                    config: widget.config,
                    roomOwnerId: widget.roomOwnerId,
                    showHost: false,
                    onGuestTileTap: (ctx, seat) => UTDLiveTileActionSheet.show(
                      ctx,
                      controller: _controller,
                      seat: seat,
                    ),
                  ),
                ),

              // Host "Go Live" button (only while in self-preview, and only
              // when the app did not take over the composer UI).
              if (_connected && _showGoLive && widget.config.showGoLiveButton)
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 96,
                  child: Center(child: _goLiveButton(theme, strings)),
                ),

              if (widget.config.foregroundWidget != null)
                Positioned.fill(child: widget.config.foregroundWidget!),
            ],
          ),
        ),
      ),
    );
  }

  Widget _goLiveButton(UTDRoomTheme theme, UTDRoomStrings strings) {
    return ElevatedButton.icon(
      onPressed: _goLive,
      icon: const Icon(Icons.podcasts),
      label: Text(strings.goLive),
      style: ElevatedButton.styleFrom(
        backgroundColor: theme.primary,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
      ),
    );
  }
}
