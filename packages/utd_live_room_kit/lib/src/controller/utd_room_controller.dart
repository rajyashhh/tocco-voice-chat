import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:livekit_client/livekit_client.dart';

import '../api/utd_api_client.dart';
import '../api/token_api.dart';
import '../api/seat_api.dart';
import '../api/speaker_api.dart';
import '../api/ban_api.dart';
import '../api/role_api.dart';
import '../api/participant_api.dart';
import '../core/room_manager.dart';
import '../minimizing/minimize_controller.dart';
import '../minimizing/pip_controller.dart';
import '../models/participant_model.dart';
import '../models/seat_model.dart';
import 'chat_controller.dart';
import 'media_controller.dart';
import 'seat_controller.dart';

class UTDRoomController {
  static UTDRoomController? _activeController;

  /// The currently active room controller, if any.
  static UTDRoomController? get activeController => _activeController;

  /// Whether any room is currently active.
  static bool get hasActiveRoom => _activeController?.isConnected == true;

  final UTDRoomManager roomManager = UTDRoomManager();
  late final UTDSeatController seatController;
  late final UTDMediaController mediaController;
  late final UTDChatController chatController;
  late final UTDMinimizeController minimize;

  /// Android OS-level Picture-in-Picture controller. Active only while in-room
  /// and only when [UTDMinimizeConfig.enableOSPip] is set; no-op on iOS /
  /// Android < 31, where the in-app minimize overlay remains the fallback.
  late final UTDPipController pip;

  /// Identity of the room owner (host), set on [connect]. Used to detect when
  /// the host leaves so every other client can end the live (show the
  /// "live ended" dialog + leave). Null until [connect] is called with it.
  String? hostIdentity;

  /// Whether THIS device is the host (room owner).
  bool get isLocalHost =>
      hostIdentity != null && hostIdentity == localIdentity;

  /// UTD Stream Engine API client for seat & speaker operations.
  UTDApiClient? _apiClient;
  UTDTokenApi? _tokenApi;
  UTDSeatApi? _seatApi;
  UTDSpeakerApi? _speakerApi;
  UTDBanApi? _banApi;
  UTDRoleApi? _roleApi;
  UTDParticipantApi? _participantApi;

  /// Access the seat API for direct seat operations.
  UTDSeatApi? get seatApi => _seatApi;

  /// Access the speaker API for request/invitation operations.
  UTDSpeakerApi? get speakerApi => _speakerApi;

  /// Access the ban API for ban/unban/list operations.
  UTDBanApi? get banApi => _banApi;

  /// Access the role API for change-role / list-by-role operations.
  UTDRoleApi? get roleApi => _roleApi;
  StreamSubscription<Participant>? _participantJoinedSub;
  StreamSubscription<Participant>? _participantLeftSub;
  StreamSubscription<Participant>? _participantAttributesChangedSub;
  StreamSubscription<Participant>? _participantMetadataChangedSub;
  StreamSubscription<List<Participant>>? _activeSpeakersSub;

  /// Set of user IDs currently speaking. Updated instantly from LiveKit's
  /// [ActiveSpeakersChangedEvent] (see [_activeSpeakersSub]) and backstopped by
  /// the 300ms [_startSpeakingPoll] in case an event is missed. Drives the
  /// speaking border on camera tiles and the wave rings on camera-off avatars.
  final ValueNotifier<Set<String>> activeSpeakers = ValueNotifier({});

  /// Set of user IDs whose mic is disabled — polled from LiveKit participants.
  final ValueNotifier<Set<String>> mutedParticipants = ValueNotifier({});

  /// Set of user IDs whose CAMERA is currently published+unmuted — polled from
  /// LiveKit participants alongside the speaking/mute poll. Drives the live tile
  /// grid's camera-on/off rendering for participants we already have a track for.
  final ValueNotifier<Set<String>> cameraOnParticipants = ValueNotifier({});

  Timer? _speakingPollTimer;

  final StreamController<List<UTDParticipant>> _participantsController =
      StreamController<List<UTDParticipant>>.broadcast();

  /// Stream of all room participants — emits on join/leave.
  Stream<List<UTDParticipant>> get participantsStream =>
      _participantsController.stream;

  /// Stream of occupied seats — emits whenever seat state changes.
  Stream<List<SeatState>> get occupiedSeatsStream =>
      seatController.occupiedSeatsStream;

  /// Stream of speaker events (requests, invitations, approvals, rejections).
  Stream<Map<String, dynamic>> get speakerEventStream =>
      seatController.speakerEventStream;

  final StreamController<UTDRoleChangeEvent> _roleChangeController =
      StreamController<UTDRoleChangeEvent>.broadcast();

  /// Stream of role changes (`_role_change`) for ALL participants. Emits for
  /// promotions/demotions by the owner and for engine auto-corrections.
  Stream<UTDRoleChangeEvent> get roleChangeStream =>
      _roleChangeController.stream;

  /// Fired for every `_role_change` (self or others). The affected user is
  /// [UTDRoleChangeEvent.identity] — the LiveKit event sender is null.
  void Function(UTDRoleChangeEvent event)? onRoleChanged;

  /// Fired when the room stays disconnected past the reconnection handler's
  /// force-exit threshold (>60s). The app should tear down the room and
  /// navigate away. Wired from [seatController] in [connect].
  void Function()? onForceExit;

  /// Applies an incoming role change to the cache and notifies listeners.
  void _handleRoleChange(UTDRoleChangeEvent event) {
    if (event.identity.isEmpty) return;
    _roleCache[event.identity] = event.role;
    if (!_roleChangeController.isClosed) {
      _roleChangeController.add(event);
    }
    onRoleChanged?.call(event);
    // Refresh participant-derived UI (seat badges) so the new role shows.
    refreshParticipants();
  }

  UTDRoomController() {
    seatController = UTDSeatController(roomManager);
    mediaController = UTDMediaController(roomManager);
    chatController = UTDChatController(roomManager);
    minimize = UTDMinimizeController(this);
    pip = UTDPipController(this);
    seatController.seats.addListener(_onSeatsChangedLocalDemotion);
  }

  /// Whether the LOCAL user held a seat on the last seats update — drives the
  /// seated→unseated transition detection below.
  bool _localWasSeated = false;

  /// When the local (non-host) user LOSES their seat — kicked by the host or
  /// the seat freed any other way — stop publishing camera/mic. Nothing else
  /// does this: the engine keeps the publish permission, so without it a
  /// kicked guest kept broadcasting and their controls bar stayed in guest
  /// mode instead of reverting to the audience request-to-speak flow.
  void _onSeatsChangedLocalDemotion() {
    final seated = localSeatIndex >= 0;
    final wasSeated = _localWasSeated;
    _localWasSeated = seated;
    if (!wasSeated || seated || isLocalHost) return;
    debugPrint(
        '🏠 [UTDRoomController] local user lost their seat → stopping mic/camera');
    mediaController.setCameraEnabled(false);
    mediaController.setMicrophoneEnabled(false);
  }

  ValueNotifier<UTDConnectionState> get connectionState =>
      roomManager.connectionState;

  bool get isConnected =>
      roomManager.connectionState.value == UTDConnectionState.connected;

  /// App ID for the UTD Stream Engine (e.g. "9325906999").
  String? _appId;

  /// Server secret for the UTD Stream Engine.
  String? _serverSecret;

  /// Get the configured App ID.
  String? get appId => _appId;

  /// Get the configured server secret.
  String? get serverSecret => _serverSecret;

  /// Initializes the UTD Stream Engine API client for seat & speaker operations.
  ///
  /// Must be called before [connect] if you want to use the new backend APIs.
  /// [baseUrl] — e.g. "https://udt-stream.com"
  /// [apiKey] — the API key for authentication (X-API-Key header)
  /// [serverSecret] — the server secret for authentication
  void initApi({
    String baseUrl = UTDApiClient.defaultBaseUrl,
    String? appId,
    String? serverSecret,
  }) {
    _appId = appId;
    _serverSecret = serverSecret;
    _apiClient = UTDApiClient(
      baseUrl: baseUrl,
      appId: appId,
      appSecret: serverSecret,
    );
    _tokenApi = UTDTokenApi(_apiClient!);
    _seatApi = UTDSeatApi(_apiClient!);
    _speakerApi = UTDSpeakerApi(_apiClient!);
    _banApi = UTDBanApi(_apiClient!);
    _roleApi = UTDRoleApi(_apiClient!);
    _participantApi = UTDParticipantApi(_apiClient!);
    debugPrint('[UTDRoomController] API initialized: $baseUrl'
        '${appId != null ? ', appId: $appId' : ''}'
        '${serverSecret != null ? ', serverSecret: ***' : ''}');
  }

  /// Sets the factory used to build a video-effects (beauty/filter) processor for
  /// each local camera capture. Propagates to BOTH capture paths:
  /// - the host self-preview ([UTDMediaController.startPreview]), and
  /// - the SDK's default camera options ([UTDRoomManager] → reconnect /
  ///   autoHostCamera / guest go-live / `setCameraEnabled`).
  ///
  /// Call BEFORE [connect] so the very first capture carries the processor.
  /// `null` (default) disables effects. The processor type is from livekit_client
  /// so no dependency on an effects package is required — wire e.g.
  /// `() => VideoEffectsProcessor.create()` from `utd_video_effects_kit`.
  void setVideoProcessorFactory(
    TrackProcessor<VideoProcessorOptions> Function()? factory,
  ) {
    roomManager.videoProcessorFactory = factory;
    mediaController.videoProcessorFactory = factory;
  }

  Future<void> connect({
    required String url,
    required String token,
    int seatCount = 9,
    bool enableMicOnJoin = false,
    bool useSpeaker = true,
    Map<String, String> userAttributes = const {},
    String? roomName,
    String? hostIdentity,
  }) async {
    this.hostIdentity = hostIdentity;
    await _cleanupPreviousRoom();

    // Same-instance reconnect (admin token re-issue fallback, room switch on
    // one controller): _cleanupPreviousRoom only handles a DIFFERENT previous
    // controller, so explicitly tear down our own still-live LiveKit session
    // before dialing the new one — otherwise the old Room is orphaned while
    // connected and keeps playing room audio/video after exit.
    if (roomManager.room != null) {
      await roomManager.disconnect();
    }

    // Re-entrancy guard: if connect() is called again on this same instance
    // without an intervening leave(), cancel the previous participant
    // subscriptions first, otherwise they leak and every participant event
    // fires twice.
    _participantJoinedSub?.cancel();
    _participantLeftSub?.cancel();
    _participantAttributesChangedSub?.cancel();
    _participantMetadataChangedSub?.cancel();
    _participantJoinedSub = null;
    _participantLeftSub = null;
    _participantAttributesChangedSub = null;
    _participantMetadataChangedSub = null;

    _banHandled = false;
    _liveEndedHandled = false;
    _roleCache.clear();
    await roomManager.connect(url, token);
    _activeController = this;

    // Route ban signals (data message + server removal) to the single funnel.
    seatController.onBanned =
        (data) => _emitBanned(UTDBanNotice.fromData(data));
    roomManager.onParticipantRemoved =
        () => _emitBanned(const UTDBanNotice.fromDisconnect());

    // Route the host's `_live_ended` broadcast to the live-ended funnel so
    // non-host clients end instantly (the host-disconnect detection in
    // [_participantLeftSub] is the guaranteed fallback).
    seatController.onLiveEnded = _emitLiveEnded;

    // Route `_role_change` data messages into the role cache + stream.
    seatController.onRoleChanged =
        (data) => _handleRoleChange(UTDRoleChangeEvent.fromData(data));

    // Surface the reconnection handler's force-exit (>60s disconnect) to the app.
    seatController.onForceExit = () => onForceExit?.call();

    // Configure seat API with room name if available
    if (_seatApi != null && roomName != null) {
      seatController.setSeatApi(_seatApi!, roomName);
    } else if (_seatApi != null && roomManager.room?.name != null) {
      seatController.setSeatApi(_seatApi!, roomManager.room!.name!);
    }

    seatController.initSeats(seatCount);

    // Wire the invitation handler so _speaker_invitation events
    // trigger the full accept/decline + takeSeat + mic flow
    seatController
        .setInvitationHandler((data) => handleIncomingInvitation(data));

    chatController.startListening();
    // Listen for LiveKit mute/unmute events so the controls-bar mic icon
    // reflects host/admin server-side mutes, not just local toggles.
    mediaController.startListening();

    // Post-connect audio/identity setup. Not required for receiving the
    // stream, so for the non-publishing audience join (the latency-critical
    // 99% case) it runs UNAWAITED off the join critical path. The host path
    // stays awaited to keep the publish-related ordering deterministic.
    Future<void> audioSetup() async {
      try {
        // When the room wants loud output, prefer a connected Bluetooth
        // headset over the phone speaker (privacy). Plain speaker-on ignores
        // Bluetooth.
        if (useSpeaker) {
          await mediaController.setSpeakerPreferBluetooth();
        } else {
          await mediaController.setSpeakerOn(false);
        }
        if (enableMicOnJoin) {
          await mediaController.setMicrophoneEnabled(true);
        }

        if (userAttributes.isNotEmpty) {
          try {
            await roomManager.localParticipant?.setAttributes(userAttributes);
          } catch (e) {
            debugPrint(
                '[UTDRoomController] setAttributes failed (non-fatal): $e');
          }
        }

        // Set participant metadata (avatar, VIP frame, etc.) via merge
        _mergeParticipantMetadata(userAttributes);
      } catch (e) {
        debugPrint(
            '[UTDRoomController] post-connect audio setup failed (non-fatal): $e');
      }
    }

    if (enableMicOnJoin || isLocalHost) {
      await audioSetup();
    } else {
      unawaited(audioSetup());
    }

    _participantJoinedSub = roomManager.participantJoinedStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      // Host came back within the disconnect grace window — the live goes on.
      if (hostIdentity != null && p.identity.toString() == hostIdentity) {
        _hostDisconnectGraceTimer?.cancel();
        _hostDisconnectGraceTimer = null;
      }
      debugPrint(
          '👤 [VISITORS] participantJoined: ${p.identity} — total: ${participants.length}');
      _emitParticipants();
    });
    _participantLeftSub = roomManager.participantLeftStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      // Host left → grace window, NOT an instant end. A host whose app was
      // killed (OOM during a gift animation — incident 2026-06-11) or who hit
      // a network blip reconnects within seconds; ending instantly threw the
      // whole audience out. The explicit `_live_ended` broadcast (the host
      // pressing end) still ends immediately via [seatController.onLiveEnded].
      final left = p.identity.toString();
      if (hostIdentity != null && left == hostIdentity && !isLocalHost) {
        _hostDisconnectGraceTimer ??= Timer(hostDisconnectGrace, () {
          _hostDisconnectGraceTimer = null;
          _emitLiveEnded();
        });
      }
      // Backend handles seat cleanup via participant_left webhook.
      // We just update the participant list locally.
      // Drop any role-cache overlay for the departed user so a stale role
      // can't outlive them (the cache otherwise wins over live metadata).
      _roleCache.remove(left);
      debugPrint(
          '👤 [VISITORS] participantLeft: ${p.identity} — total: ${participants.length}');
      _emitParticipants();
    });
    _participantAttributesChangedSub =
        roomManager.participantAttributesChangedStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      debugPrint('👤 [VISITORS] participantAttributesChanged: ${p.identity}');
      _emitParticipants();
    });
    _participantMetadataChangedSub =
        roomManager.participantMetadataChangedStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      // The engine rewrites `role` in metadata on a role change — refresh so
      // seat badges reflect it even if a `_role_change` message was missed.
      debugPrint('👤 [VISITORS] participantMetadataChanged: ${p.identity}');
      _emitParticipants();
    });
    // Event-driven speaking state: LiveKit fires this the instant the SFU's
    // audio-level detection flips someone in/out of "speaking" (camera-off,
    // mic-on speakers included — it is audio-driven, not camera-driven). This is
    // the primary signal for the avatar wave rings; the poll below is a backstop.
    _activeSpeakersSub = roomManager.activeSpeakersStream.listen((speakers) {
      if (_disposed) return;
      final speaking = speakers.map((p) => p.identity.toString()).toSet();
      if (!setEquals(activeSpeakers.value, speaking)) {
        activeSpeakers.value = speaking;
      }
    });

    debugPrint('👤 [VISITORS] initial participants: ${participants.length}');
    _emitParticipants();

    // Start polling isSpeaking state from LiveKit participants
    _startSpeakingPoll();
  }

  /// Merges user attributes into participant metadata without overwriting
  /// backend-set fields (role, _device, appId, projectId, service).
  void _mergeParticipantMetadata(Map<String, String> userAttributes) {
    if (userAttributes.isEmpty) return;
    final lp = roomManager.localParticipant;
    if (lp == null) return;

    try {
      final existing = jsonDecode(lp.metadata ?? '{}') as Map<String, dynamic>;
      // Only merge UIKit-settable fields
      for (final entry in userAttributes.entries) {
        existing[entry.key] = entry.value;
      }
      lp.setMetadata(jsonEncode(existing));
    } catch (e) {
      debugPrint('[UTDRoomController] _mergeParticipantMetadata failed: $e');
    }
  }

  /// Polls LiveKit participant isSpeaking every 300ms and updates [activeSpeakers].
  void _startSpeakingPoll() {
    _speakingPollTimer?.cancel();
    _speakingPollTimer = Timer.periodic(
      const Duration(milliseconds: 300),
      (_) {
        // Guard: bail if the controller was disposed or the room disconnected
        if (_disposed) return;

        final local = roomManager.localParticipant;
        final remote = roomManager.remoteParticipants;
        final all = <Participant>[if (local != null) local, ...remote];

        try {
          final speaking = <String>{};
          for (final p in all) {
            if (p.isSpeaking) {
              speaking.add(p.identity.toString());
            }
          }

          // Only update if changed to avoid unnecessary rebuilds
          if (!setEquals(activeSpeakers.value, speaking)) {
            activeSpeakers.value = speaking;
          }

          final muted = <String>{};
          final cameraOn = <String>{};
          for (final p in all) {
            if (!p.isMicrophoneEnabled()) {
              muted.add(p.identity.toString());
            }
            if (p.isCameraEnabled()) {
              cameraOn.add(p.identity.toString());
            }
          }
          if (!setEquals(mutedParticipants.value, muted)) {
            mutedParticipants.value = muted;
          }
          if (!setEquals(cameraOnParticipants.value, cameraOn)) {
            cameraOnParticipants.value = cameraOn;
          }

          // Backstop: keep the controls-bar mic icon (mediaController.isMicEnabled)
          // in sync with the local mic's real state in case a TrackMuted/Unmuted
          // event was missed (e.g. a host mute that landed during reconnect).
          // The event-driven sync in UTDMediaController handles the common case
          // instantly; this just guarantees eventual consistency.
          if (local != null) {
            final localMicOn = local.isMicrophoneEnabled();
            if (mediaController.isMicEnabled.value != localMicOn) {
              mediaController.isMicEnabled.value = localMicOn;
            }
            // Camera backstop (mirrors the mic one). Skipped while previewing —
            // the unpublished preview track reads as camera-off, and the
            // controls bar reflects the live preview via isPreviewing instead.
            if (!mediaController.isPreviewing.value) {
              final localCamOn = local.isCameraEnabled();
              if (mediaController.isCameraEnabled.value != localCamOn) {
                mediaController.isCameraEnabled.value = localCamOn;
              }
            }
          }
        } catch (e) {
          // Suppress "Participant disposed" errors that occur when participants
          // are cleaned up by LiveKit while a poll tick fires. This is a harmless
          // race condition — the timer is cancelled on the next dispose() call.
          if (e.toString().contains('Participant disposed')) {
            debugPrint(
                '[UTDRoomController] Suppressed disposed participant access in poll: $e');
          } else {
            rethrow;
          }
        }
      },
    );
  }

  Future<void> _cleanupPreviousRoom() async {
    final previous = _activeController;
    if (previous == null || previous == this) return;

    debugPrint(
        '[UTDRoomController] Cleaning up previous room before connecting');
    try {
      if (previous.minimize.isMinimizing) {
        previous.minimize.dismiss();
      }
      await previous.pip.disarm();
      if (previous.isConnected) {
        await previous.leave();
      }
    } catch (e) {
      debugPrint('[UTDRoomController] Previous room cleanup error: $e');
    }
    _activeController = null;
  }

  Future<void> leave() async {
    // Host leaving ends the live for everyone: broadcast `_live_ended` before
    // disconnecting so remaining clients react instantly. Best-effort — the
    // host-disconnect detection on each client is the guaranteed fallback.
    if (isLocalHost && isConnected) {
      try {
        await sendRoomMessage({'type': '_live_ended'});
      } catch (_) {}
    }
    if (_activeController == this) _activeController = null;
    // Authoritative teardown: make sure the minimize overlay state machine is
    // reset to idle. Some app exit paths (e.g. exitRoom while minimized) call
    // leave() without first going through minimize.close()/dismiss(), which
    // would otherwise leave the singleton machine stuck in `minimizing` and
    // every app-wide isMinimizing guard believing a room is still open.
    if (minimize.isMinimizing) minimize.dismiss();
    // Stop OS PiP auto-enter — once out of the room, backgrounding must not
    // trigger a system PiP window (enforces the "in-room only" requirement).
    await pip.disarm();
    _roleCache.clear();
    _speakingPollTimer?.cancel();
    _speakingPollTimer = null;
    _participantJoinedSub?.cancel();
    _participantJoinedSub = null;
    _participantLeftSub?.cancel();
    _participantLeftSub = null;
    _participantAttributesChangedSub?.cancel();
    _participantAttributesChangedSub = null;
    _participantMetadataChangedSub?.cancel();
    _participantMetadataChangedSub = null;
    _activeSpeakersSub?.cancel();
    _activeSpeakersSub = null;
    seatController.stopListening();
    chatController.stopListening();
    mediaController.stopListening();
    await roomManager.disconnect();
  }

  Future<void> sendChatMessage(String text) async {
    await chatController.sendMessage(text);
  }

  Future<void> sendRoomMessage(Map<String, dynamic> data) async {
    await roomManager.sendData(data);
  }

  Future<void> sendTargetedMessage(
    Map<String, dynamic> data,
    List<String> identities,
  ) async {
    await roomManager.sendData(data, destinationIdentities: identities);
  }

  Stream<Map<String, dynamic>> get dataStream => roomManager.dataStream;

  /// Cached identity→role map for the default seat grid's badges. Recomputed
  /// only when participants/roles actually change (the single [_emitParticipants]
  /// funnel), NOT on every 300ms speaking-poll grid rebuild — so the per-seat
  /// badge lookup costs nothing per tick instead of re-`jsonDecode`ing every
  /// participant's metadata. Mirrors the [participantRoles] getter's result.
  final ValueNotifier<Map<String, String>> participantRolesNotifier =
      ValueNotifier<Map<String, String>>(const <String, String>{});

  void refreshParticipants() {
    if (_disposed || _participantsController.isClosed) return;
    _emitParticipants();
  }

  /// Single funnel for participant-list + role-badge updates. Called from every
  /// participant event (join/leave/attributes/metadata), role changes, and the
  /// initial seed — so [participantRolesNotifier] stays in sync without the seat
  /// grid recomputing roles on each speaking-poll tick.
  void _emitParticipants() {
    if (_disposed || _participantsController.isClosed) return;
    participantRolesNotifier.value = participantRoles;
    _participantsController.add(participants);
  }

  List<UTDParticipant> get participants {
    final local = roomManager.localParticipant;
    final remote = roomManager.remoteParticipants;
    final all = <Participant>[if (local != null) local, ...remote];
    return all.map(_toUTDParticipant).toList();
  }

  static UTDParticipant _toUTDParticipant(Participant p) {
    return UTDParticipant(
      id: p.identity.toString(),
      name: p.name,
      isMicEnabled: p.isMicrophoneEnabled(),
      isSpeaking: p.isSpeaking,
      attributes: Map<String, String>.from(p.attributes),
    );
  }

  /// Overlay of identity→role applied from `_role_change` messages and from
  /// the initial list-by-role seed. Takes precedence over `participant.metadata`
  /// because (a) `_role_change` is server-sent (the event `participant` is null)
  /// and may arrive before metadata propagates, and (b) it lets callers reflect
  /// roles for users who are not currently connected. Cleared on (re)connect.
  final Map<String, String> _roleCache = {};

  /// Gets the participant's role — from the [_roleCache] overlay first, then
  /// from their LiveKit metadata.
  String? getParticipantRole(String identity) {
    final cached = _roleCache[identity];
    if (cached != null) return cached;
    try {
      Participant? p;
      if (roomManager.localParticipant?.identity == identity) {
        p = roomManager.localParticipant;
      } else {
        p = roomManager.remoteParticipants
            .where((rp) => rp.identity == identity)
            .firstOrNull;
      }
      if (p == null) return null;
      final meta = jsonDecode(p.metadata ?? '{}') as Map<String, dynamic>;
      return meta['role'] as String?;
    } catch (e) {
      return null;
    }
  }

  /// Gets the local participant's role.
  String? get localRole {
    final identity = roomManager.localParticipant?.identity;
    if (identity == null) return null;
    return getParticipantRole(identity);
  }

  /// The local participant's identity, or `null` before connect. Convenience for
  /// the built-in default UI (seat sheet, controls bar, member list), which
  /// references the local identity repeatedly.
  String? get localIdentity => roomManager.localParticipant?.identity;

  /// The seat index the local user currently occupies, or `-1` if not seated.
  int get localSeatIndex =>
      seatController.getSeatIndexByUserId(localIdentity ?? '');

  /// Whether the local participant is a host or admin.
  bool get isHostOrAdmin {
    final role = localRole;
    return role == 'host' || role == 'admin';
  }

  /// Number of occupied guest tiles (occupied seats other than the host, seat 0).
  int get occupiedGuestTileCount => seatController.seats.value
      .where((s) => s.isOccupied && s.index != 0)
      .length;

  /// Whether a guest tile is free to invite/approve someone onto. The live room
  /// caps guests at (seat_count − 1); this is the CLIENT gate. The engine
  /// enforces the same cap server-side, which wins under concurrent approvals.
  bool get hasFreeGuestTile =>
      seatController.seats.value.any((s) => !s.isOccupied && s.index != 0);

  /// Resolves the current camera [VideoTrack] for [identity]: the local
  /// participant's own publication (or the unpublished preview before Go Live),
  /// or the subscribed remote camera track. Shared by the stage, mini-overlay
  /// and PiP. Returns null when there is no camera track.
  VideoTrack? cameraTrackFor(String identity) {
    if (identity == localIdentity) {
      final lp = roomManager.localParticipant;
      final t = lp?.getTrackPublicationBySource(TrackSource.camera)?.track;
      if (t is LocalVideoTrack) return t;
      return mediaController.previewTrack;
    }
    return roomManager.getRemoteVideoTrack(identity);
  }

  /// Whether [identity]'s camera is currently on (local: enabled or previewing;
  /// remote: present in the camera-on poll).
  bool cameraOnFor(String identity) {
    if (identity == localIdentity) {
      return mediaController.isCameraEnabled.value ||
          mediaController.isPreviewing.value;
    }
    return cameraOnParticipants.value.contains(identity);
  }

  /// The [Participant] for [identity] (local or remote), or null if not present.
  Participant? participantFor(String identity) {
    final lp = roomManager.localParticipant;
    if (lp?.identity == identity) return lp;
    return roomManager.remoteParticipants
        .where((p) => p.identity == identity)
        .firstOrNull;
  }

  /// The avatar URL published by [identity] (via `userInRoomAttributes['avatar']`),
  /// or null. Used by the camera-off composition in the stage, mini-overlay and
  /// PiP to show the participant's image.
  String? avatarUrlFor(String identity) {
    final url = participantFor(identity)?.attributes['avatar'];
    return (url != null && url.isNotEmpty) ? url : null;
  }

  /// The display name for [identity]: published `name` attribute, the
  /// participant's name, or the identity as a last resort.
  String displayNameFor(String identity) {
    final p = participantFor(identity);
    final name = (p?.name.isNotEmpty == true ? p!.name : null) ??
        p?.attributes['name'];
    return (name != null && name.isNotEmpty) ? name : identity;
  }

  /// Returns a map of all participant identities to their roles.
  /// Used by the seat grid to display role badges.
  Map<String, String> get participantRoles {
    final roles = <String, String>{};
    final local = roomManager.localParticipant;
    final remote = roomManager.remoteParticipants;
    final all = <Participant>[if (local != null) local, ...remote];

    for (final p in all) {
      try {
        final meta = jsonDecode(p.metadata ?? '{}') as Map<String, dynamic>;
        final role = meta['role'] as String?;
        if (role != null) {
          roles[p.identity.toString()] = role;
        }
      } catch (_) {}
    }
    // The `_role_change` overlay wins over metadata (see [_roleCache]).
    roles.addAll(_roleCache);
    return roles;
  }

  Future<UTDTokenResponse> generateToken({
    required String identity,
    required String roomName,
    required String service,
    required String roomOwnerId,
    String? name,
    String role = 'audience',
    String? kind,
    int? seatCount,
    String? seatMode,
    int? hostSeat,
    String? modeId,
    String? deviceModel,
    String? os,
    String? osVersion,
    String? appVersion,
    Map<String, dynamic>? metadata,
  }) async {
    if (_tokenApi == null) {
      throw StateError('initApi() must be called before generateToken()');
    }
    return _tokenApi!.generateToken(
      identity: identity,
      roomName: roomName,
      service: service,
      roomOwnerId: roomOwnerId,
      name: name,
      role: role,
      kind: kind,
      seatCount: seatCount,
      seatMode: seatMode,
      hostSeat: hostSeat,
      modeId: modeId,
      deviceModel: deviceModel,
      os: os,
      osVersion: osVersion,
      appVersion: appVersion,
      metadata: metadata,
    );
  }

  // ---------------------------------------------------------------------------
  // Speaker Request helpers (convenience wrappers around speakerApi)
  // ---------------------------------------------------------------------------

  /// The room ID used for speaker API calls.
  /// Prefers [seatController.roomId] (set via connect roomName param),
  /// falls back to LiveKit room name.
  String? get _speakerRoomId => seatController.roomId ?? roomManager.room?.name;

  /// Request to speak (audience in request mode).
  Future<Map<String, dynamic>?> requestToSpeak() async {
    if (_speakerApi == null) return null;
    final roomId = _speakerRoomId;
    final identity = roomManager.localParticipant?.identity;
    if (roomId == null || identity == null) return null;

    try {
      return await _speakerApi!.requestToSpeak(
        roomName: roomId,
        identity: identity,
      );
    } catch (e) {
      debugPrint('[UTDRoomController] requestToSpeak error: $e');
      return null;
    }
  }

  /// Cancel speaker request.
  Future<bool> cancelSpeakerRequest() async {
    if (_speakerApi == null) return false;
    final roomId = _speakerRoomId;
    final identity = roomManager.localParticipant?.identity;
    if (roomId == null || identity == null) return false;

    try {
      await _speakerApi!.cancelRequest(
        roomName: roomId,
        identity: identity,
      );
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] cancelSpeakerRequest error: $e');
      return false;
    }
  }

  /// Approve a speaker request (host/admin).
  Future<Map<String, dynamic>?> approveSpeakerRequest(int requestId) async {
    if (_speakerApi == null) return null;
    final roomId = _speakerRoomId;
    final identity = roomManager.localParticipant?.identity;
    if (roomId == null || identity == null) return null;
    if (!hasFreeGuestTile) {
      debugPrint('[UTDRoomController] approveSpeakerRequest blocked — guest tiles full');
      return null;
    }

    try {
      return await _speakerApi!.approveRequest(
        roomName: roomId,
        requestId: requestId,
        identity: identity,
      );
    } catch (e) {
      debugPrint('[UTDRoomController] approveSpeakerRequest error: $e');
      return null;
    }
  }

  /// Reject a speaker request (host/admin).
  Future<bool> rejectSpeakerRequest(int requestId) async {
    if (_speakerApi == null) return false;
    final roomId = _speakerRoomId;
    final identity = roomManager.localParticipant?.identity;
    if (roomId == null || identity == null) return false;

    try {
      await _speakerApi!.rejectRequest(
        roomName: roomId,
        requestId: requestId,
        identity: identity,
      );
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] rejectSpeakerRequest error: $e');
      return false;
    }
  }

  /// Invite a user to speak (host/admin).
  ///
  /// [seatIndex] — optional target seat index. If provided, the invited user
  /// will be seated on this specific seat when they accept.
  Future<Map<String, dynamic>?> inviteToSpeak(
    String targetIdentity, {
    int? seatIndex,
  }) async {
    if (_speakerApi == null) return null;
    final roomId = _speakerRoomId;
    final identity = roomManager.localParticipant?.identity;
    if (roomId == null || identity == null) return null;
    if (!hasFreeGuestTile) {
      debugPrint('[UTDRoomController] inviteToSpeak blocked — guest tiles full');
      return null;
    }

    try {
      return await _speakerApi!.inviteToSpeak(
        roomName: roomId,
        identity: identity,
        targetIdentity: targetIdentity,
        seatIndex: seatIndex,
      );
    } catch (e) {
      debugPrint('[UTDRoomController] inviteToSpeak error: $e');
      return null;
    }
  }

  /// Accept a speaker invitation.
  Future<Map<String, dynamic>?> acceptInvitation(int invitationId) async {
    if (_speakerApi == null) return null;
    final roomId = _speakerRoomId;
    final identity = roomManager.localParticipant?.identity;
    if (roomId == null || identity == null) return null;

    try {
      return await _speakerApi!.acceptInvitation(
        roomName: roomId,
        invitationId: invitationId,
        identity: identity,
      );
    } catch (e) {
      debugPrint('[UTDRoomController] acceptInvitation error: $e');
      return null;
    }
  }

  // ---------------------------------------------------------------------------
  // Host media control (server-authoritative force-mute of a guest)
  // ---------------------------------------------------------------------------

  /// Host/admin: force [targetIdentity]'s camera on/off. Goes through the engine
  /// (server-side track mute) so it can't be ignored and survives reconnect.
  /// Host/admin: remove [targetIdentity] from the broadcast entirely
  /// (engine-side disconnect via the LiveKit server API).
  Future<bool> kickParticipant(String targetIdentity) async {
    final roomId = _speakerRoomId;
    if (_participantApi == null || roomId == null) return false;
    try {
      await _participantApi!.kick(
        roomId: roomId,
        targetIdentity: targetIdentity,
      );
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] kickParticipant error: $e');
      return false;
    }
  }

  Future<bool> setRemoteCameraEnabled(String targetIdentity, bool enabled) async {
    final roomId = _speakerRoomId;
    final actor = roomManager.localParticipant?.identity;
    if (_participantApi == null || roomId == null || actor == null) return false;
    try {
      await _participantApi!.setCamera(
        roomId: roomId,
        targetIdentity: targetIdentity,
        identity: actor,
        enabled: enabled,
      );
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] setRemoteCameraEnabled error: $e');
      return false;
    }
  }

  /// Host/admin: force [targetIdentity]'s microphone on/off (server-side).
  Future<bool> setRemoteMicEnabled(String targetIdentity, bool enabled) async {
    final roomId = _speakerRoomId;
    final actor = roomManager.localParticipant?.identity;
    if (_participantApi == null || roomId == null || actor == null) return false;
    try {
      await _participantApi!.setMic(
        roomId: roomId,
        targetIdentity: targetIdentity,
        identity: actor,
        enabled: enabled,
      );
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] setRemoteMicEnabled error: $e');
      return false;
    }
  }

  // ---------------------------------------------------------------------------
  // Ban helpers (convenience wrappers around banApi)
  // ---------------------------------------------------------------------------

  /// Ban a user. Room-scoped by default; pass [global] `true` for a project-wide
  /// ban. [durationSeconds] null => permanent. Returns `true` on success.
  Future<bool> banUser(
    String identity, {
    String? reason,
    int? durationSeconds,
    bool global = false,
  }) async {
    if (_banApi == null) return false;
    final roomName = global ? null : _speakerRoomId;
    if (!global && roomName == null) return false;
    try {
      await _banApi!.banUser(
        identity: identity,
        roomName: roomName,
        reason: reason,
        durationSeconds: durationSeconds,
      );
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] banUser error: $e');
      return false;
    }
  }

  /// Remove a ban. Pass [global] `true` to lift a project-wide ban (sends no
  /// `room_name`). For a room-scoped ban, pass the ban's own [roomName] (the
  /// bans list is account-wide, so it may belong to a different room than the
  /// current one); when omitted it defaults to the current room. Returns `true`
  /// on success.
  Future<bool> unbanUser(
    String identity, {
    bool global = false,
    String? roomName,
  }) async {
    if (_banApi == null) return false;
    final effectiveRoom = global ? null : (roomName ?? _speakerRoomId);
    try {
      await _banApi!.unbanUser(identity: identity, roomName: effectiveRoom);
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] unbanUser error: $e');
      return false;
    }
  }

  /// List the project's active bans (paginated). Returns `null` on error.
  Future<UTDBanListPage?> listBans({int page = 1, int perPage = 20}) async {
    if (_banApi == null) return null;
    try {
      return await _banApi!.listBans(page: page, perPage: perPage);
    } catch (e) {
      debugPrint('[UTDRoomController] listBans error: $e');
      return null;
    }
  }

  // ---------------------------------------------------------------------------
  // Role helpers (convenience wrappers around roleApi)
  // ---------------------------------------------------------------------------

  /// Changes [targetIdentity]'s [role]. Owner-only (the server enforces this and
  /// returns `403` for anyone else — gate the UI to the owner too).
  ///
  /// The actor is the local participant and the room is the current room.
  /// Returns the result on success; **throws** on REST error so the caller can
  /// map `403/404/409/422` to a user-facing message.
  Future<UTDRoleChangeResult> changeRole({
    required String targetIdentity,
    required String role,
  }) async {
    if (_roleApi == null) {
      throw StateError('initApi() must be called before changeRole()');
    }
    final roomName = _speakerRoomId;
    final actor = roomManager.localParticipant?.identity;
    if (roomName == null || actor == null) {
      throw StateError('Room/actor not ready for changeRole()');
    }
    final result = await _roleApi!.changeRole(
      roomName: roomName,
      targetIdentity: targetIdentity,
      actorIdentity: actor,
      role: role,
    );
    // Optimistic — the broadcast `_role_change` confirms shortly after.
    if (result.identity.isNotEmpty) {
      _roleCache[result.identity] = result.role;
    }
    return result;
  }

  /// Self-upgrade to `admin` after an optimistic audience join: the app's
  /// enter-room response resolved AFTER connect and listed the local user as
  /// an admin. Calls the engine role endpoint with the room OWNER as the
  /// actor (the same client-asserted trust model as the token endpoint). The
  /// engine updates LiveKit permissions + metadata and broadcasts
  /// `_role_change`, so real moderation powers are granted.
  ///
  /// Retries ×3 with 1s backoff (the engine `participants` row is
  /// webhook-created and may lag connect). `409` (already in role) counts as
  /// success. Aborts on dispose/disconnect/room switch.
  Future<bool> upgradeSelfRole({
    required String ownerId,
    required String selfId,
  }) async {
    if (_roleApi == null) return false;
    final roomName = _speakerRoomId;
    if (roomName == null) return false;

    for (var attempt = 0; attempt < 3; attempt++) {
      if (attempt > 0) {
        await Future.delayed(const Duration(seconds: 1));
      }
      if (_disposed || !isConnected || _speakerRoomId != roomName) {
        return false;
      }
      try {
        final result = await _roleApi!.changeRole(
          roomName: roomName,
          targetIdentity: selfId,
          actorIdentity: ownerId,
          role: 'admin',
        );
        if (result.identity.isNotEmpty) {
          _roleCache[result.identity] = result.role;
        }
        refreshParticipants();
        return true;
      } on DioException catch (e) {
        if (e.response?.statusCode == 409) {
          // Already in role — the goal state is reached.
          _roleCache[selfId] = 'admin';
          refreshParticipants();
          return true;
        }
        debugPrint(
            '[UTDRoomController] upgradeSelfRole attempt ${attempt + 1} failed: '
            '${e.response?.statusCode ?? e.type}');
      } catch (e) {
        debugPrint(
            '[UTDRoomController] upgradeSelfRole attempt ${attempt + 1} failed: $e');
      }
    }
    return false;
  }

  /// Lists participants holding [role] (paginated). Returns `null` on error.
  Future<UTDRoleListPage?> listByRole(
    String role, {
    int page = 1,
    int perPage = 20,
  }) async {
    if (_roleApi == null) return null;
    final roomName = _speakerRoomId;
    if (roomName == null) return null;
    try {
      return await _roleApi!.listByRole(
        roomName: roomName,
        role: role,
        page: page,
        perPage: perPage,
      );
    } catch (e) {
      debugPrint('[UTDRoomController] listByRole error: $e');
      return null;
    }
  }

  // ---------------------------------------------------------------------------
  // Invitation handling — full flow managed by the package
  // ---------------------------------------------------------------------------

  /// Fired exactly once when the LOCAL user is banned, from any source:
  /// - a `_banned` data message (richest: reason + expiry),
  /// - `DisconnectReason.participantRemoved` (fallback, reason unknown),
  /// - a token `403 User is banned` on (re)join.
  ///
  /// The [UTDLiveRoom] widget wires this internally to show the banned dialog
  /// and leave the room. De-duplicated via [_emitBanned].
  void Function(UTDBanNotice notice)? onBanned;

  /// Guards against firing [onBanned] more than once (e.g. the `_banned`
  /// message and the participant-removed disconnect that follows it).
  bool _banHandled = false;

  void _emitBanned(UTDBanNotice notice) {
    if (_banHandled) return;
    _banHandled = true;
    onBanned?.call(notice);
  }

  /// Fired exactly once on a NON-host client when the host leaves the room (the
  /// live is over), from either source:
  /// - the host's `_live_ended` data broadcast (instant), or
  /// - the host's [ParticipantDisconnectedEvent] (guaranteed fallback).
  ///
  /// The [UTDLiveRoom] widget wires this internally to show the "live ended"
  /// dialog and leave the room. De-duplicated via [_emitLiveEnded].
  void Function()? onLiveEnded;

  /// Guards against firing [onLiveEnded] more than once (the broadcast and the
  /// host-disconnect event that follows it).
  bool _liveEndedHandled = false;

  /// How long viewers wait for a disconnected host to come back before the
  /// live is declared over. Matches the reconnection handler's force-exit
  /// window so a host-side network recovery fits inside it.
  static const Duration hostDisconnectGrace = Duration(seconds: 60);

  /// Pending "host disconnected" grace countdown; cancelled when the host
  /// rejoins, when the live ends explicitly, or on dispose.
  Timer? _hostDisconnectGraceTimer;

  void _emitLiveEnded() {
    // The host never ends its own live via this funnel — leaving is the normal
    // exit flow for the host.
    if (isLocalHost || _liveEndedHandled) return;
    _liveEndedHandled = true;
    _hostDisconnectGraceTimer?.cancel();
    _hostDisconnectGraceTimer = null;
    onLiveEnded?.call();
  }

  /// Surfaces a token `403 User is banned` as a ban notice. Called by the
  /// widget when [generateToken] throws [UTDBannedException].
  void notifyBannedFromToken() {
    _emitBanned(const UTDBanNotice.fromTokenForbidden());
  }

  /// Callback that the app sets to show an invitation UI to the user.
  /// Should return `true` if the user accepts, `false` if they decline.
  /// The package calls this when a `_speaker_invitation` event is received.
  ///
  /// The data map contains:
  /// - `invitation_id` (int)
  /// - `inviter_identity` (String)
  /// - `target_identity` (String)
  /// - `seat_index` (int?) — the specific seat the user is invited to
  ///
  /// Example usage from the app:
  /// ```dart
  /// controller.onInvitationUI = (data) async {
  ///   return await showMyInvitationDialog(data); // returns true/false
  /// };
  /// ```
  Future<bool> Function(Map<String, dynamic> data)? onInvitationUI;

  /// Navigator key for showing default dialogs.
  /// Must be set by the app if using the default invitation dialog.
  /// Example: `controller.navigatorKey = navKey;`
  GlobalKey<NavigatorState>? navigatorKey;

  /// Shows the default invitation dialog built into the package.
  /// Used when [onInvitationUI] is not set by the app.
  /// Returns `true` if accepted, `false` if declined.
  Future<bool> _showDefaultInvitationDialog(Map<String, dynamic> data) async {
    final ctx = navigatorKey?.currentState?.context;
    if (ctx == null) return false;

    final inviterIdentity = data['inviter_identity'] as String?;
    final seatIndex = (data['seat_index'] as num?)?.toInt();
    final seatDisplay = seatIndex != null ? '${seatIndex + 1}' : '?';

    final completer = Completer<bool>();

    showDialog(
      context: ctx,
      barrierDismissible: false,
      builder: (dialogCtx) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Row(
          children: [
            Icon(Icons.mic, color: Colors.blue, size: 24),
            SizedBox(width: 8),
            Text('Speaker Invitation',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Admin ${inviterIdentity ?? ''} invited you to sit on seat #$seatDisplay',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 15),
            ),
            const SizedBox(height: 8),
            Text(
              'المشرف ${inviterIdentity ?? ''} دعاك للجلوس على الكرسي رقم $seatDisplay',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 14, color: Colors.grey),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(dialogCtx).pop();
              if (!completer.isCompleted) completer.complete(false);
            },
            child: const Text('Decline', style: TextStyle(color: Colors.red)),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(dialogCtx).pop();
              if (!completer.isCompleted) completer.complete(true);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              foregroundColor: Colors.white,
            ),
            child: const Text('Accept'),
          ),
        ],
      ),
    ).then((_) {
      if (!completer.isCompleted) completer.complete(false);
    });

    return completer.future;
  }

  /// Called internally when a `_speaker_invitation` event arrives.
  /// Handles the full invitation flow:
  /// 1. Calls [onInvitationUI] (or default dialog) to let the app show UI
  /// 2. If accepted → calls acceptInvitation API
  /// 3. If backend didn't auto-seat → falls back to takeSeat
  /// 4. Enables microphone
  /// 5. If declined → calls declineInvitation API
  Future<void> handleIncomingInvitation(Map<String, dynamic> data) async {
    final invitationId = (data['invitation_id'] as num?)?.toInt();
    final seatIndex = (data['seat_index'] as num?)?.toInt();
    if (invitationId == null) return;

    // Ask the app to show UI and get user's decision.
    // If onInvitationUI is set, use it. Otherwise, use the default dialog.
    final bool accepted;
    if (onInvitationUI != null) {
      accepted = await onInvitationUI!.call(data);
    } else {
      accepted = await _showDefaultInvitationDialog(data);
    }

    if (accepted) {
      try {
        // Accept via API — backend should auto-seat user on seatIndex
        final result = await acceptInvitation(invitationId);
        debugPrint(
            '[UTDRoomController] handleIncomingInvitation — accept result: $result');

        // Check if backend seated the user
        final seatedAt = (result?['seat_index'] as num?)?.toInt();
        if (seatedAt == null && seatIndex != null) {
          // Backend didn't auto-seat — fallback to takeSeat
          debugPrint(
              '[UTDRoomController] handleIncomingInvitation — fallback takeSeat($seatIndex)');
          final identity = roomManager.localParticipant?.identity;
          if (identity != null) {
            await seatController.takeSeat(seatIndex, identity);
          }
        }

        // Enable camera + mic REACTIVELY once the engine grants publish — not
        // on a fixed delay, which races the ParticipantPermissionsUpdatedEvent.
        _enableGuestMediaWhenCanPublish();
      } catch (e) {
        debugPrint(
            '[UTDRoomController] handleIncomingInvitation — accept error: $e');
        // Fallback: try to take seat directly
        if (seatIndex != null) {
          final identity = roomManager.localParticipant?.identity;
          if (identity != null) {
            try {
              await seatController.takeSeat(seatIndex, identity);
              _enableGuestMediaWhenCanPublish();
            } catch (_) {}
          }
        }
      }
    } else {
      // User declined
      await declineInvitation(invitationId);
    }
  }

  /// Reactively enables the guest's camera + mic once the engine grants publish
  /// permission, then stops listening. Replaces the brittle
  /// `Future.delayed(500ms)` that raced the [ParticipantPermissionsUpdatedEvent]
  /// — the engine can grant later or sooner than any fixed delay. The locked
  /// guest default when going live is camera + mic BOTH on.
  /// Pending wait for publish permission (see [_enableGuestMediaWhenCanPublish]).
  /// Tracked in fields so [dispose] can cancel both — otherwise the 10s timer
  /// fires (and the listener stays registered) after the controller is gone.
  Timer? _guestMediaTimer;
  VoidCallback? _guestMediaListener;

  void _enableGuestMediaWhenCanPublish() {
    if (_disposed) return;
    // Already granted (the permission update can land during the accept
    // round-trip) → enable immediately.
    if (mediaController.canPublish.value) {
      _enableGuestMedia();
      return;
    }
    // Never stack listeners/timers if invited again before the grant lands.
    _cancelGuestMediaWait();
    void listener() {
      if (_disposed) return;
      if (mediaController.canPublish.value) {
        _cancelGuestMediaWait();
        _enableGuestMedia();
      }
    }

    _guestMediaListener = listener;
    mediaController.canPublish.addListener(listener);
    // Safety net: stop waiting after 10s so the listener can't leak if the
    // grant never arrives (e.g. the accept ultimately failed server-side).
    _guestMediaTimer =
        Timer(const Duration(seconds: 10), _cancelGuestMediaWait);
  }

  /// Cancels the pending publish-permission wait (timer + listener). Idempotent;
  /// safe to call from the listener, the timeout, and [dispose].
  void _cancelGuestMediaWait() {
    _guestMediaTimer?.cancel();
    _guestMediaTimer = null;
    final listener = _guestMediaListener;
    if (listener != null) {
      mediaController.canPublish.removeListener(listener);
      _guestMediaListener = null;
    }
  }

  /// Enables the guest's mic + camera — the locked "camera + mic both on"
  /// default for a guest going live. The media controller swallows errors.
  Future<void> _enableGuestMedia() async {
    await mediaController.setMicrophoneEnabled(true);
    await mediaController.setCameraEnabled(true);
  }

  /// Decline a speaker invitation.
  Future<bool> declineInvitation(int invitationId) async {
    if (_speakerApi == null) return false;
    final roomId = _speakerRoomId;
    final identity = roomManager.localParticipant?.identity;
    if (roomId == null || identity == null) return false;

    try {
      await _speakerApi!.declineInvitation(
        roomName: roomId,
        invitationId: invitationId,
        identity: identity,
      );
      return true;
    } catch (e) {
      debugPrint('[UTDRoomController] declineInvitation error: $e');
      return false;
    }
  }

  bool _disposed = false;

  void dispose() {
    if (_disposed) return;
    _disposed = true;
    if (_activeController == this) _activeController = null;
    debugPrint(
        '🚪 [EXIT] UTDRoomController.dispose() — releasing all resources...');

    _speakingPollTimer?.cancel();
    _speakingPollTimer = null;
    _hostDisconnectGraceTimer?.cancel();
    _hostDisconnectGraceTimer = null;
    _cancelGuestMediaWait();
    _participantJoinedSub?.cancel();
    _participantLeftSub?.cancel();
    _participantAttributesChangedSub?.cancel();
    _participantMetadataChangedSub?.cancel();
    _activeSpeakersSub?.cancel();
    _participantsController.close();
    _roleChangeController.close();
    onRoleChanged = null;
    _roleCache.clear();
    pip.dispose();
    activeSpeakers.dispose();
    mutedParticipants.dispose();
    cameraOnParticipants.dispose();
    participantRolesNotifier.dispose();
    seatController.seats.removeListener(_onSeatsChangedLocalDemotion);
    seatController.dispose();
    mediaController.dispose();
    chatController.dispose();
    roomManager.dispose();
    _apiClient?.dispose();
    _apiClient = null;
    _seatApi = null;
    _speakerApi = null;
    _banApi = null;
    _participantApi = null;
    onBanned = null;
    onLiveEnded = null;
    debugPrint(
        '🚪 [EXIT] UTDRoomController.dispose() — done, all resources released');
  }
}
