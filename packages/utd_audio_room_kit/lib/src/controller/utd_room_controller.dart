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
import '../models/room_mode.dart';
import '../core/room_manager.dart';
import '../minimizing/minimize_controller.dart';
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
  static bool get hasActiveRoom =>
      _activeController?.isConnected == true;

  final UTDRoomManager roomManager = UTDRoomManager();
  late final UTDSeatController seatController;
  late final UTDMediaController mediaController;
  late final UTDChatController chatController;
  late final UTDMinimizeController minimize;

  /// UTD Stream Engine API client for seat & speaker operations.
  UTDApiClient? _apiClient;
  UTDTokenApi? _tokenApi;
  UTDSeatApi? _seatApi;
  UTDSpeakerApi? _speakerApi;
  UTDBanApi? _banApi;
  UTDRoleApi? _roleApi;

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

  /// Set of user IDs currently speaking — updated via LiveKit events.
  final ValueNotifier<Set<String>> activeSpeakers = ValueNotifier({});

  /// Set of user IDs whose mic is disabled — polled from LiveKit participants.
  final ValueNotifier<Set<String>> mutedParticipants = ValueNotifier({});

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

  // ========== Mode Registry ==========
  final Map<String, UTDRoomMode> _modeRegistry = {
    UTDRoomMode.defaultMode.id: UTDRoomMode.defaultMode,
  };

  final ValueNotifier<UTDRoomMode> currentMode =
      ValueNotifier(UTDRoomMode.defaultMode);

  VoidCallback? _modeIdSyncListener;

  void registerModes(List<UTDRoomMode> modes) {
    for (final mode in modes) {
      _modeRegistry[mode.id] = mode;
    }
  }

  List<UTDRoomMode> get registeredModes => _modeRegistry.values.toList();

  UTDRoomMode resolveMode(String? modeId) {
    if (modeId == null) return UTDRoomMode.defaultMode;
    return _modeRegistry[modeId] ?? UTDRoomMode.defaultMode;
  }

  UTDRoomController() {
    seatController = UTDSeatController(roomManager);
    mediaController = UTDMediaController(roomManager);
    chatController = UTDChatController(roomManager);
    minimize = UTDMinimizeController(this);
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
    debugPrint('[UTDRoomController] API initialized: $baseUrl'
        '${appId != null ? ', appId: $appId' : ''}'
        '${serverSecret != null ? ', serverSecret: ***' : ''}');
  }

  Future<void> connect({
    required String url,
    required String token,
    int seatCount = 9,
    bool enableMicOnJoin = false,
    bool useSpeaker = true,
    Map<String, String> userAttributes = const {},
    String? roomName,
  }) async {
    await _cleanupPreviousRoom();

    // Same-instance reconnect (admin token re-issue fallback, room switch on
    // one controller): _cleanupPreviousRoom only handles a DIFFERENT previous
    // controller, so explicitly tear down our own still-live LiveKit session
    // before dialing the new one — otherwise the old Room is orphaned while
    // connected and keeps playing room audio after exit.
    if (roomManager.room != null) {
      await roomManager.disconnect();
    }

    // Re-entrancy guard: if connect() is called again on this same instance
    // without an intervening leave(), cancel the previous participant
    // subscriptions and remove the modeId listener first, otherwise they leak
    // and every participant/currentMode event fires twice.
    _participantJoinedSub?.cancel();
    _participantLeftSub?.cancel();
    _participantAttributesChangedSub?.cancel();
    _participantMetadataChangedSub?.cancel();
    _participantJoinedSub = null;
    _participantLeftSub = null;
    _participantAttributesChangedSub = null;
    _participantMetadataChangedSub = null;
    if (_modeIdSyncListener != null) {
      seatController.modeId.removeListener(_modeIdSyncListener!);
      _modeIdSyncListener = null;
    }

    _banHandled = false;
    _roleCache.clear();
    try {
      await roomManager.connect(url, token);
    } catch (e) {
      // Stream connect failed. Seat occupancy goes through the backend REST API
      // (take/leave-seat) and never needed the LiveKit stream, so bootstrap the
      // seat system anyway: configure the seat API + room name and build the
      // seat list so the user can still SIT on a seat (audio just won't
      // publish). The live _seat_update/metadata listeners ride the dead
      // roomManager streams and won't fire, but takeSeat/leaveSeat fall back to
      // GET /seats over HTTP. Then rethrow so the caller still renders the
      // stream-failed chrome.
      _bootstrapSeats(roomName, seatCount);
      rethrow;
    }
    _activeController = this;

    // Route ban signals (data message + server removal) to the single funnel.
    seatController.onBanned = (data) => _emitBanned(UTDBanNotice.fromData(data));
    roomManager.onParticipantRemoved =
        () => _emitBanned(const UTDBanNotice.fromDisconnect());

    // Route `_role_change` data messages into the role cache + stream.
    seatController.onRoleChanged =
        (data) => _handleRoleChange(UTDRoleChangeEvent.fromData(data));

    // Surface the reconnection handler's force-exit (>60s disconnect) to the app.
    seatController.onForceExit = () => onForceExit?.call();

    _bootstrapSeats(roomName, seatCount);

    // Wire the invitation handler so _speaker_invitation events
    // trigger the full accept/decline + takeSeat + mic flow
    seatController.setInvitationHandler((data) => handleIncomingInvitation(data));

    chatController.startListening();
    // Listen for LiveKit mute/unmute events so the controls-bar mic icon
    // reflects host/admin server-side mutes, not just local toggles.
    mediaController.startListening();

    // Post-connect audio/identity setup. None of it is required for receiving
    // audio, so for non-publishing joins (audience/admin, enableMicOnJoin
    // false — 99% of entries) it runs UNAWAITED off the join critical path.
    // The host path stays awaited to preserve the mic-publish → BT-routing
    // ordering (publishing is when MODE_IN_COMMUNICATION matters most).
    Future<void> audioSetup() async {
      try {
        // When the room wants loud output, prefer a connected Bluetooth headset
        // over the phone speaker (privacy). Plain speaker-on ignores Bluetooth.
        // applyBluetoothAudioRouting re-applies the Android audio config with
        // forceHandleAudioRouting:true AFTER LiveKit's connect set
        // MODE_IN_COMMUNICATION (which otherwise disables routing) — this is
        // what makes BT actually work.
        if (useSpeaker) {
          await mediaController.applyBluetoothAudioRouting();
        } else {
          await mediaController.setSpeakerOn(false);
        }
        if (enableMicOnJoin) {
          await mediaController.setMicrophoneEnabled(true);
          // Publishing the local mic track is when MODE_IN_COMMUNICATION
          // matters most — re-apply BT routing so the captured audio also
          // routes to the headset, not the phone mic/speaker.
          if (useSpeaker) {
            await mediaController.applyBluetoothAudioRouting();
          }
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

    if (enableMicOnJoin) {
      await audioSetup();
    } else {
      unawaited(audioSetup());
    }

    _participantJoinedSub = roomManager.participantJoinedStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      debugPrint(
          '👤 [VISITORS] participantJoined: ${p.identity} — total: ${participants.length}');
      _participantsController.add(participants);
    });
    _participantLeftSub = roomManager.participantLeftStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      // Backend handles seat cleanup via participant_left webhook.
      // We just update the participant list locally.
      // Drop any role-cache overlay for the departed user so a stale role
      // can't outlive them (the cache otherwise wins over live metadata).
      _roleCache.remove(p.identity.toString());
      debugPrint(
          '👤 [VISITORS] participantLeft: ${p.identity} — total: ${participants.length}');
      _participantsController.add(participants);
    });
    _participantAttributesChangedSub =
        roomManager.participantAttributesChangedStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      debugPrint('👤 [VISITORS] participantAttributesChanged: ${p.identity}');
      _participantsController.add(participants);
    });
    _participantMetadataChangedSub =
        roomManager.participantMetadataChangedStream.listen((p) {
      if (_disposed || _participantsController.isClosed) return;
      // The engine rewrites `role` in metadata on a role change — refresh so
      // seat badges reflect it even if a `_role_change` message was missed.
      debugPrint('👤 [VISITORS] participantMetadataChanged: ${p.identity}');
      _participantsController.add(participants);
    });
    debugPrint('👤 [VISITORS] initial participants: ${participants.length}');
    _participantsController.add(participants);

    // Start polling isSpeaking state from LiveKit participants
    _startSpeakingPoll();
  }

  /// Bootstraps the seat system (seat API config + seat list).
  ///
  /// This is intentionally independent of the LiveKit stream: seat occupancy
  /// (take/leave/move) is a backend REST operation, so it must work even when
  /// the stream connect fails. Called on both the success path and the
  /// connect-failure path of [connect].
  void _bootstrapSeats(String? roomName, int seatCount) {
    // Configure seat API with room name if available
    if (_seatApi != null && roomName != null) {
      seatController.setSeatApi(_seatApi!, roomName);
    } else if (_seatApi != null && roomManager.room?.name != null) {
      seatController.setSeatApi(_seatApi!, roomManager.room!.name!);
    }

    // Sync currentMode whenever seatController.modeId changes.
    // Must be registered BEFORE initSeats, because initSeats reads room
    // metadata synchronously and may set modeId.value immediately.
    _modeIdSyncListener = () {
      final id = seatController.modeId.value;
      if (id != null) currentMode.value = resolveMode(id);
    };
    seatController.modeId.addListener(_modeIdSyncListener!);

    seatController.initSeats(seatCount);
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
          for (final p in all) {
            if (!p.isMicrophoneEnabled()) {
              muted.add(p.identity.toString());
            }
          }
          if (!setEquals(mutedParticipants.value, muted)) {
            mutedParticipants.value = muted;
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

    debugPrint('[UTDRoomController] Cleaning up previous room before connecting');
    try {
      if (previous.minimize.isMinimizing) {
        previous.minimize.dismiss();
      }
      if (previous.isConnected) {
        await previous.leave();
      }
    } catch (e) {
      debugPrint('[UTDRoomController] Previous room cleanup error: $e');
    }
    _activeController = null;
  }

  Future<void> leave() async {
    if (_activeController == this) _activeController = null;
    // Authoritative teardown: make sure the minimize overlay state machine is
    // reset to idle. Some app exit paths (e.g. exitRoom while minimized) call
    // leave() without first going through minimize.close()/dismiss(), which
    // would otherwise leave the singleton machine stuck in `minimizing` and
    // every app-wide isMinimizing guard believing a room is still open.
    if (minimize.isMinimizing) minimize.dismiss();
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
    seatController.stopListening();
    chatController.stopListening();
    mediaController.stopListening();
    // Drain any in-flight mic publish BEFORE disconnecting: room.disconnect()
    // disposes the local tracks, and a publish caught mid-flight then hits the
    // SDK's addTransceiver with a disposed (null) track — the top live fatal.
    await mediaController.cancelPendingPublish();
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

  void refreshParticipants() {
    if (_disposed || _participantsController.isClosed) return;
    _participantsController.add(participants);
  }


  List<UTDParticipant> get participants {
    final local = roomManager.localParticipant;
    final remote = roomManager.remoteParticipants;
    final all = <Participant>[if (local != null) local, ...remote];
    final result = <UTDParticipant>[];
    for (final p in all) {
      try {
        result.add(_toUTDParticipant(p));
      } catch (e) {
        // A participant can be disposed by the SDK between snapshotting the
        // list and reading its tracks (leave/teardown race). Skip it — it is
        // gone from the room anyway.
        debugPrint('[UTDRoomController] skipped disposed participant: $e');
      }
    }
    return result;
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

  /// Whether the local participant is a host or admin.
  bool get isHostOrAdmin {
    final role = localRole;
    return role == 'host' || role == 'admin';
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
  /// `_role_change`, which feeds [_roleCache]/[roleChangeStream] — so real
  /// moderation powers are granted, not just a local badge.
  ///
  /// Retries ×3 with 1s backoff: the engine `participants` row is
  /// webhook-created and may lag connect by a moment. A `409` (already in
  /// role) counts as success. Aborts if the controller is disposed, the room
  /// disconnects, or the room changed (room switch). Returns `true` when the
  /// admin role is in effect.
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
  /// The [UTDAudioRoom] widget wires this internally to show the banned dialog
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
        debugPrint('[UTDRoomController] handleIncomingInvitation — accept result: $result');

        // Check if backend seated the user
        final seatedAt = (result?['seat_index'] as num?)?.toInt();
        if (seatedAt == null && seatIndex != null) {
          // Backend didn't auto-seat — fallback to takeSeat
          debugPrint('[UTDRoomController] handleIncomingInvitation — fallback takeSeat($seatIndex)');
          final identity = roomManager.localParticipant?.identity;
          if (identity != null) {
            await seatController.takeSeat(seatIndex, identity);
          }
        }

        // Enable microphone after a short delay. Guard against the room being
        // left/disposed during the delay — a late publish on a torn-down room
        // is the addTransceiver track-is-null fatal.
        Future.delayed(const Duration(milliseconds: 500), () async {
          if (_disposed || !isConnected) return;
          await mediaController.setMicrophoneEnabled(true);
          // Re-apply BT routing now that a local mic track is being published.
          await mediaController.applyBluetoothAudioRouting();
        });
      } catch (e) {
        debugPrint('[UTDRoomController] handleIncomingInvitation — accept error: $e');
        // Fallback: try to take seat directly
        if (seatIndex != null) {
          final identity = roomManager.localParticipant?.identity;
          if (identity != null) {
            try {
              await seatController.takeSeat(seatIndex, identity);
              Future.delayed(const Duration(milliseconds: 500), () async {
                if (_disposed || !isConnected) return;
                await mediaController.setMicrophoneEnabled(true);
                await mediaController.applyBluetoothAudioRouting();
              });
            } catch (_) {}
          }
        }
      }
    } else {
      // User declined
      await declineInvitation(invitationId);
    }
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
    _participantJoinedSub?.cancel();
    _participantLeftSub?.cancel();
    _participantAttributesChangedSub?.cancel();
    _participantMetadataChangedSub?.cancel();
    _participantsController.close();
    _roleChangeController.close();
    onRoleChanged = null;
    _roleCache.clear();
    if (_modeIdSyncListener != null) {
      seatController.modeId.removeListener(_modeIdSyncListener!);
      _modeIdSyncListener = null;
    }
    activeSpeakers.dispose();
    mutedParticipants.dispose();
    currentMode.dispose();
    seatController.dispose();
    mediaController.dispose();
    chatController.dispose();
    roomManager.dispose();
    _apiClient?.dispose();
    _apiClient = null;
    _seatApi = null;
    _speakerApi = null;
    _banApi = null;
    onBanned = null;
    debugPrint(
        '🚪 [EXIT] UTDRoomController.dispose() — done, all resources released');
  }
}
