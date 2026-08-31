import 'dart:async';
import 'dart:convert';
import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:permission_handler/permission_handler.dart';

import '../api/seat_api.dart';
import '../core/room_manager.dart';
import '../core/reconnection_handler.dart';
import '../core/sync_manager.dart';
import '../models/seat_model.dart';

/// Manages seat state for the audio room.
///
/// **Architecture (per UIKit Developer Guide):**
/// - All seat mutations go through the REST API (UTD Stream Engine).
/// - The backend pushes `_seat_update` data messages after every change.
/// - This controller updates local state ONLY from `_seat_update` or
///   room metadata `_seats` — never from local optimistic updates.
///
/// Two sources of truth:
/// 1. **Room metadata (`_seats`)** — read on join & reconnect.
/// 2. **Data messages (`_seat_update`)** — real-time updates while connected.
class UTDSeatController {
  static const _tag = '[UTDSeatController]';
  final UTDRoomManager _roomManager;
  StreamSubscription<Map<String, dynamic>>? _dataSub;

  /// The UTD Seat API client — set via [setSeatApi].
  UTDSeatApi? _seatApi;

  /// The LiveKit room name — needed for API calls.
  String? _roomId;

  /// Exposes the room ID for use by UTDRoomController speaker helpers.
  String? get roomId => _roomId;

  /// Subscription to room metadata changes (live _seats sync).
  StreamSubscription<String>? _metadataSub;

  /// Reconnection handler — tiered reconnect strategy.
  UTDReconnectionHandler? _reconnectionHandler;

  /// Sync manager — periodic light sync.
  UTDSyncManager? _syncManager;

  UTDSeatController(this._roomManager);

  /// Configure the seat API and room name for backend calls.
  /// Must be called after [initApi] on [UTDRoomController].
  void setSeatApi(UTDSeatApi seatApi, String roomId) {
    _seatApi = seatApi;
    _roomId = roomId;
    debugPrint('$_tag setSeatApi configured for room: $roomId');
  }

  /// Reactive seat state list.
  final ValueNotifier<List<SeatState>> seats = ValueNotifier([]);

  /// Current seat mode — "free" or "request".
  final ValueNotifier<String> seatMode = ValueNotifier('free');

  /// Current room mode ID (e.g. "3", "5", "cinema").
  /// Distinct from seatMode ("free"/"request").
  final ValueNotifier<String?> modeId = ValueNotifier(null);

  /// Pending speaker requests (for host/admin UI).
  final ValueNotifier<List<SpeakerRequest>> pendingRequests = ValueNotifier([]);

  final StreamController<List<SeatState>> _occupiedSeatsController =
      StreamController<List<SeatState>>.broadcast();
  VoidCallback? _seatsListener;

  /// Stream of occupied seats — emits whenever the seat state changes.
  Stream<List<SeatState>> get occupiedSeatsStream =>
      _occupiedSeatsController.stream;

  /// Speaker request/invitation event streams for the UIKit to listen to.
  final StreamController<Map<String, dynamic>> _speakerEventController =
      StreamController<Map<String, dynamic>>.broadcast();

  /// Stream of speaker events (_speaker_request, _speaker_request_approved,
  /// _speaker_request_rejected, _speaker_invitation, etc.)
  Stream<Map<String, dynamic>> get speakerEventStream =>
      _speakerEventController.stream;

  /// Callback invoked when a `_speaker_invitation` event is received.
  /// Set this from the app layer to show the invitation dialog.
  /// The callback receives the full event data map.
  void Function(Map<String, dynamic> data)? onSpeakerInvitationReceived;

  /// Callback invoked when a `_speaker_invitation_accepted` event is received.
  void Function(Map<String, dynamic> data)? onSpeakerInvitationAccepted;

  /// Callback invoked when a `_speaker_invitation_declined` event is received.
  void Function(Map<String, dynamic> data)? onSpeakerInvitationDeclined;

  /// Callback invoked when a `_speaker_request` event is received (for host/admin).
  void Function(Map<String, dynamic> data)? onSpeakerRequestReceived;

  /// Callback invoked when a `_speaker_request_approved` event is received.
  void Function(Map<String, dynamic> data)? onSpeakerRequestApproved;

  /// Callback invoked when a `_speaker_request_rejected` event is received.
  void Function(Map<String, dynamic> data)? onSpeakerRequestRejected;

  /// Callback invoked when a `_mode_change` event is received.
  void Function(Map<String, dynamic> data)? onModeChange;

  /// Callback invoked when a `_banned` event targets the local user.
  void Function(Map<String, dynamic> data)? onBanned;

  /// Callback invoked when the host broadcasts `_live_ended` (the live is over).
  /// Set by [UTDRoomController] so non-host clients end instantly.
  void Function()? onLiveEnded;

  /// Callback invoked when a `_role_change` event is received (for ANY user).
  /// Set by [UTDRoomController]; never filtered by identity here.
  void Function(Map<String, dynamic> data)? onRoleChanged;

  /// Callback invoked when the room has been disconnected long enough (>60s)
  /// that the reconnection handler gives up. Set by [UTDRoomController] so the
  /// app can tear down the dead room and navigate away. Without this the user
  /// is stranded on a frozen room.
  void Function()? onForceExit;

  /// Internal handler for the full invitation flow — set by UTDRoomController.
  /// When set, `_speaker_invitation` events trigger `handleIncomingInvitation`
  /// on the controller, which manages accept/decline + takeSeat + mic.
  Future<void> Function(Map<String, dynamic> data)? _onInvitationHandler;

  /// Sets the invitation handler. Called by UTDRoomController after connect.
  void setInvitationHandler(
      Future<void> Function(Map<String, dynamic> data)? handler) {
    _onInvitationHandler = handler;
  }

  /// Current layout mode seat count.
  int _seatCount = 9;

  /// Monotonic counter bumped on every [_applyBackendSeatState]. Used by the
  /// awaited GET /seats paths to detect that a fresher real-time apply
  /// (`_seat_update` / metadata) landed during the await, so a stale GET result
  /// is not allowed to clobber newer state. (The backend payload carries no
  /// version field, so this is the best available ordering guard.)
  int _applyGeneration = 0;

  // ---------------------------------------------------------------------------
  // Initialization
  // ---------------------------------------------------------------------------

  /// Initialize seats for a given layout mode.
  /// Seats are populated from room metadata (`_seats`) automatically.
  void initSeats(int seatCount) {
    debugPrint('$_tag initSeats(seatCount: $seatCount)');
    _seatCount = seatCount;
    seats.value = List.generate(seatCount, (i) => SeatState(index: i));
    _seatsListener = () {
      if (_disposed) return;
      final occupied = seats.value.where((s) => s.isOccupied).toList();
      _occupiedSeatsController.add(occupied);
    };
    seats.addListener(_seatsListener!);
    _listenToBackendMessages();
    _listenToRoomMetadataChanges();
    _initReconnectionHandler();
    _initSyncManager();

    // Read initial seat state from room metadata
    _readRoomMetadataSeats();

    debugPrint(
        '$_tag initSeats complete — ${seats.value.length} seats created');
  }

  // ---------------------------------------------------------------------------
  // Read seat state from Room Metadata (_seats)
  // ---------------------------------------------------------------------------

  /// Reads `_seats` from room metadata and applies it to local state.
  /// Called on join and reconnect.
  void _readRoomMetadataSeats() {
    final room = _roomManager.room;
    if (room == null) {
      debugPrint('$_tag _readRoomMetadataSeats — room is null, skipping');
      return;
    }

    final metadataStr = room.metadata;
    if (metadataStr == null || metadataStr.isEmpty) {
      debugPrint(
          '$_tag _readRoomMetadataSeats — no room metadata, falling back to GET /seats');
      _fetchSeatsFromApiOnce();
      return;
    }

    try {
      final meta = jsonDecode(metadataStr) as Map<String, dynamic>;
      final seatsData = meta['_seats'] as Map<String, dynamic>?;
      if (seatsData != null) {
        debugPrint(
            '$_tag _readRoomMetadataSeats — found _seats in metadata: $seatsData');
        _applyBackendSeatState(seatsData);
      } else {
        debugPrint(
            '$_tag _readRoomMetadataSeats — no _seats in metadata, falling back to GET /seats');
        _fetchSeatsFromApiOnce();
      }
    } catch (e) {
      debugPrint('$_tag _readRoomMetadataSeats — parse error: $e');
      _fetchSeatsFromApiOnce();
    }
  }

  /// One-time fallback: fetches seat state from GET /seats API.
  /// Called only when room metadata is null/empty on first join.
  Future<void> _fetchSeatsFromApiOnce() async {
    if (_seatApi == null || _roomId == null) {
      debugPrint('$_tag _fetchSeatsFromApiOnce — API not configured, skipping');
      return;
    }

    try {
      debugPrint(
          '$_tag _fetchSeatsFromApiOnce — fetching seats via GET /seats');
      final gen = _applyGeneration;
      final data = await _seatApi!.getSeats(roomId: _roomId!);
      // Skip if a real-time _seat_update / metadata apply already landed during
      // the await (it is fresher than this one-time fallback fetch).
      if (_applyGeneration == gen) {
        _applyBackendSeatState(data);
        debugPrint('$_tag _fetchSeatsFromApiOnce — applied successfully');
      } else {
        debugPrint(
            '$_tag _fetchSeatsFromApiOnce — skipped (newer apply landed)');
      }
    } catch (e) {
      debugPrint('$_tag _fetchSeatsFromApiOnce — error: $e');
    }
  }

  /// Re-sync seats from room metadata. Call this on reconnect.
  void resyncFromMetadata() {
    debugPrint('$_tag resyncFromMetadata');
    _readRoomMetadataSeats();
  }

  // ---------------------------------------------------------------------------
  // Apply backend seat state (_seat_update or _seats metadata)
  // ---------------------------------------------------------------------------

  /// Applies the full seat state from backend data.
  /// Both `_seat_update` data messages and `_seats` metadata use the same structure:
  /// ```json
  /// { "count": 9, "mode": "free", "seats": [...], "requests": [...] }
  /// ```
  /// Resize the local seat list to [count], preserving existing seat state for
  /// overlapping indices. Used after a `_mode_change` changes the seat count so
  /// `seats.value.length` stays in sync with [_seatCount] until the next
  /// `_seat_update` arrives.
  void _resizeSeats(int count) {
    if (_disposed || count <= 0) return;
    final old = seats.value;
    if (old.length == count) return;
    seats.value = List<SeatState>.generate(
      count,
      (i) => i < old.length ? old[i] : SeatState(index: i),
    );
  }

  void _applyBackendSeatState(Map<String, dynamic> data) {
    // Reached through awaited async paths (reconnect full-sync, GET /seats
    // fallback). If dispose() ran during the await gap, the ValueNotifiers
    // below are already disposed — writing to them throws. Bail out.
    if (_disposed) return;
    _applyGeneration++;
    final roomState = RoomSeatState.fromJson(data);

    debugPrint(
        '$_tag _applyBackendSeatState — count: ${roomState.count}, mode: ${roomState.mode}, '
        'seats: ${roomState.seats.length}, requests: ${roomState.requests.length}');

    // Update seat mode
    seatMode.value = roomState.mode;

    // Update mode ID if present
    if (roomState.modeId != null) {
      modeId.value = roomState.modeId;
    }

    // Update pending requests
    pendingRequests.value = roomState.requests;

    // Update seat count if changed
    if (roomState.count != _seatCount && roomState.count > 0) {
      _seatCount = roomState.count;
    }

    // Build new seat list from backend data
    final newSeats = List<SeatState>.generate(_seatCount, (i) {
      // Find matching seat from backend
      final backendSeat =
          roomState.seats.where((s) => s.index == i).firstOrNull;

      if (backendSeat != null) {
        // Use backend attributes directly — the server now provides
        // name, avatar, frame, etc. in the _seat_update payload.
        // Only fall back to existing local attributes if backend didn't send
        // any AND the SAME user still occupies this index. Otherwise (the seat
        // was vacated and re-taken by someone else) we'd graft the previous
        // occupant's name/avatar onto the new occupant.
        if (backendSeat.attributes.isEmpty &&
            backendSeat.occupantUserId != null) {
          final existing = i < seats.value.length ? seats.value[i] : null;
          if (existing != null &&
              existing.occupantUserId == backendSeat.occupantUserId &&
              existing.attributes.isNotEmpty) {
            return backendSeat.copyWith(attributes: existing.attributes);
          }
        }
        return backendSeat;
      }
      return SeatState(index: i);
    });

    // Only reassign (and thus notify -> rebuild the whole seat grid) when the
    // seat content actually changed. The backend pushes _seat_update frequently
    // and a fresh List instance always != the old one by identity, so without
    // this guard every redundant push rebuilt all SeatAvatarWidgets. SeatState
    // is Equatable, so listEquals compares by content.
    if (!listEquals(newSeats, seats.value)) {
      seats.value = newSeats;
    }
  }

  // ---------------------------------------------------------------------------
  // API Operations — Call REST API, let _seat_update handle state
  // ---------------------------------------------------------------------------

  /// User takes a seat at [index].
  /// Calls UTD Stream API — state update comes via `_seat_update`.
  Future<bool> takeSeat(
    int index,
    String userId,
  ) async {
    debugPrint('$_tag takeSeat(index: $index, userId: $userId)');

    final micStatus = await Permission.microphone.status;
    if (micStatus.isDenied) {
      await Permission.microphone.request();
    }

    // Android 12+ needs BLUETOOTH_CONNECT at runtime to route mic audio to a
    // paired Bluetooth headset. Without it, taking the mic falls back to the
    // phone speaker/mic even when a BT headset is connected. (Declared in the
    // manifest but never requested before.) iOS handles BT routing without this.
    if (Platform.isAndroid) {
      final btStatus = await Permission.bluetoothConnect.status;
      if (btStatus.isDenied) {
        await Permission.bluetoothConnect.request();
      }
    }

    if (index < 0 || index >= _seatCount) {
      debugPrint(
          '$_tag takeSeat REJECTED — index out of range (seatCount: $_seatCount)');
      return false;
    }

    if (_seatApi == null || _roomId == null) {
      debugPrint('$_tag takeSeat REJECTED — API not configured');
      return false;
    }

    try {
      debugPrint('$_tag takeSeat — calling UTD API: POST /seats/$index/take');
      await _seatApi!.takeSeat(
        roomId: _roomId!,
        seatIndex: index,
        identity: userId,
      );
      debugPrint('$_tag takeSeat — UTD API SUCCESS (waiting for _seat_update)');
      return true;
    } catch (e) {
      debugPrint('$_tag takeSeat — UTD API ERROR: $e');
      return false;
    }
  }

  /// Remove occupant from seat at [index] (host/admin only).
  /// Calls UTD Stream API — state update comes via `_seat_update`.
  Future<bool> kickFromSeat(int index, {required String identity}) async {
    debugPrint('$_tag kickFromSeat(index: $index, identity: $identity)');

    if (_seatApi == null || _roomId == null) {
      debugPrint('$_tag kickFromSeat REJECTED — API not configured');
      return false;
    }

    try {
      debugPrint(
          '$_tag kickFromSeat — calling UTD API: POST /seats/$index/kick');
      await _seatApi!.kickFromSeat(
        roomId: _roomId!,
        seatIndex: index,
        identity: identity,
      );
      debugPrint(
          '$_tag kickFromSeat — UTD API SUCCESS (waiting for _seat_update)');
      return true;
    } catch (e) {
      debugPrint('$_tag kickFromSeat — UTD API ERROR: $e');
      return false;
    }
  }

  /// The local guest steps down from their own seat (leave the stage).
  /// Calls UTD Stream API — state update comes via `_seat_update`.
  Future<bool> leaveSeat(String identity) async {
    debugPrint('$_tag leaveSeat(identity: $identity)');

    if (_seatApi == null || _roomId == null) {
      debugPrint('$_tag leaveSeat REJECTED — API not configured');
      return false;
    }

    try {
      await _seatApi!.leaveSeat(roomId: _roomId!, identity: identity);
      debugPrint('$_tag leaveSeat — UTD API SUCCESS (waiting for _seat_update)');
      return true;
    } catch (e) {
      debugPrint('$_tag leaveSeat — UTD API ERROR: $e');
      return false;
    }
  }

  /// Mute a specific seat (host/admin only).
  /// Calls UTD Stream API — server-side mute via LiveKit.
  Future<bool> muteSeat(int index, {required String identity}) async {
    debugPrint('$_tag muteSeat(index: $index, identity: $identity)');

    if (_seatApi == null || _roomId == null) return false;

    try {
      debugPrint('$_tag muteSeat — calling UTD API: POST /seats/$index/mute');
      await _seatApi!.muteSeat(
        roomId: _roomId!,
        seatIndex: index,
        identity: identity,
      );
      debugPrint('$_tag muteSeat — UTD API SUCCESS');
      return true;
    } catch (e) {
      debugPrint('$_tag muteSeat — UTD API ERROR: $e');
      return false;
    }
  }

  /// Unmute a specific seat (host/admin only).
  /// Calls UTD Stream API — server-side unmute via LiveKit.
  Future<bool> unmuteSeat(int index, {required String identity}) async {
    debugPrint('$_tag unmuteSeat(index: $index, identity: $identity)');

    if (_seatApi == null || _roomId == null) return false;

    try {
      debugPrint(
          '$_tag unmuteSeat — calling UTD API: POST /seats/$index/unmute');
      await _seatApi!.unmuteSeat(
        roomId: _roomId!,
        seatIndex: index,
        identity: identity,
      );
      debugPrint('$_tag unmuteSeat — UTD API SUCCESS');
      return true;
    } catch (e) {
      debugPrint('$_tag unmuteSeat — UTD API ERROR: $e');
      return false;
    }
  }

  // ---------------------------------------------------------------------------
  // Query helpers
  // ---------------------------------------------------------------------------

  /// Get seat index (tile) by user ID. Returns -1 if not found.
  int getSeatIndexByUserId(String userId) {
    return seats.value.indexWhere((s) => s.occupantUserId == userId);
  }

  // ---------------------------------------------------------------------------
  // Room Metadata live listener
  // ---------------------------------------------------------------------------

  /// Listens to room metadata changes from LiveKit.
  /// This is a fallback in case a `_seat_update` data message is missed.
  /// On every metadata change, we extract `_seats` and apply it.
  void _listenToRoomMetadataChanges() {
    debugPrint('$_tag _listenToRoomMetadataChanges — subscribing');
    _metadataSub = _roomManager.roomMetadataStream.listen((metadataStr) {
      try {
        final meta = jsonDecode(metadataStr) as Map<String, dynamic>;
        final seatsData = meta['_seats'] as Map<String, dynamic>?;
        if (seatsData != null) {
          debugPrint('$_tag room metadata changed — applying _seats');
          _applyBackendSeatState(seatsData);
        }
      } catch (e) {
        debugPrint('$_tag room metadata change parse error: $e');
      }
    });
  }

  // ---------------------------------------------------------------------------
  // Reconnection Handler (Fix #4)
  // ---------------------------------------------------------------------------

  /// Initializes the reconnection handler with tiered sync strategy.
  void _initReconnectionHandler() {
    _reconnectionHandler = UTDReconnectionHandler(
      onLightSync: () async {
        debugPrint('$_tag reconnection — light sync (< 15s)');
        resyncFromMetadata();
      },
      onFullSync: () async {
        debugPrint('$_tag reconnection — full sync (15-60s)');
        // Full sync: re-read metadata + fetch seats via API if available
        resyncFromMetadata();
        if (_seatApi != null && _roomId != null) {
          final gen = _applyGeneration;
          try {
            final data = await _seatApi!.getSeats(roomId: _roomId!);
            // Skip if a fresher real-time apply (_seat_update / metadata) landed
            // during the await — otherwise the stale GET clobbers newer state.
            if (_applyGeneration == gen) {
              _applyBackendSeatState(data);
            } else {
              debugPrint(
                  '$_tag full sync — skipping stale GET /seats (newer apply landed)');
            }
          } catch (e) {
            debugPrint('$_tag reconnection full sync API error: $e');
          }
        }
      },
      onForceExit: () {
        debugPrint('$_tag reconnection — force exit (> 60s)');
        // Surface to UTDRoomController -> app so the dead room is torn down and
        // the user is navigated away (mirrors the ban/participant-removed path).
        onForceExit?.call();
      },
    );

    // Wire reconnection handler to room manager connection state changes
    _roomManager.connectionState.addListener(_onConnectionStateChanged);
  }

  /// Tracks connection state changes to trigger reconnection handler.
  void _onConnectionStateChanged() {
    final state = _roomManager.connectionState.value;
    if (state == UTDConnectionState.reconnecting ||
        state == UTDConnectionState.disconnected) {
      _reconnectionHandler?.onDisconnected();
      _syncManager?.stopPeriodicSync();
    } else if (state == UTDConnectionState.connected) {
      _reconnectionHandler?.onReconnected();
      // No periodic sync — only reconnection full sync is used.
    }
  }

  // ---------------------------------------------------------------------------
  // Sync Manager (Fix #4)
  // ---------------------------------------------------------------------------

  /// Initializes the sync manager (used only for reconnection full sync,
  /// NOT periodic polling).
  void _initSyncManager() {
    _syncManager = UTDSyncManager(
      fetchSeatMap: () async {
        if (_seatApi == null || _roomId == null) return seats.value;
        final data = await _seatApi!.getSeats(roomId: _roomId!);
        final roomState = RoomSeatState.fromJson(data);
        return roomState.seats;
      },
      fetchFullRoomState: () async {
        if (_seatApi == null || _roomId == null) return {};
        return await _seatApi!.getSeats(roomId: _roomId!);
      },
      applyFullState: (state) {
        if (state.isNotEmpty) _applyBackendSeatState(state);
      },
      applySeats: (newSeats) {
        seats.value = newSeats;
      },
    );
    // No periodic sync — we rely on _seat_update data messages +
    // room metadata _seats. GET /seats is only called as a fallback
    // when metadata is empty on first join (see _readRoomMetadataSeats).
  }

  // ---------------------------------------------------------------------------
  // Private — listen to backend data messages
  // ---------------------------------------------------------------------------

  /// Listen to ALL data messages from the backend.
  /// Routes `_seat_update` to seat state, and speaker events to their stream.
  void _listenToBackendMessages() {
    debugPrint('$_tag _listenToBackendMessages — subscribing to data stream');
    _dataSub = _roomManager.dataStream.listen((data) {
      if (_disposed) return;
      final type = data['type'] as String?;

      // Handle backend system messages (prefixed with _)
      if (type != null) {
        switch (type) {
          case '_mode_change':
            debugPrint('$_tag received _mode_change from backend: $data');
            final newModeId = data['mode_id'] as String?;
            if (newModeId != null) modeId.value = newModeId;
            final newSeatCount = (data['seat_count'] as num?)?.toInt();
            if (newSeatCount != null &&
                newSeatCount > 0 &&
                newSeatCount != _seatCount) {
              _seatCount = newSeatCount;
              // Keep seats.value.length in sync with _seatCount immediately.
              // Otherwise (until the next _seat_update) take/availability guards
              // read the new count while the UI and isSeatAvailable read the old
              // seats list, giving inconsistent results.
              _resizeSeats(newSeatCount);
            }
            onModeChange?.call(data);
            break;
          case '_seat_update':
            debugPrint('$_tag received _seat_update from backend');
            _applyBackendSeatState(data);
            break;
          case '_speaker_request':
            debugPrint('$_tag received speaker event: $type');
            _speakerEventController.add(data);
            onSpeakerRequestReceived?.call(data);
            break;
          case '_speaker_request_approved':
            debugPrint('$_tag received speaker event: $type');
            _speakerEventController.add(data);
            onSpeakerRequestApproved?.call(data);
            break;
          case '_speaker_request_rejected':
            debugPrint('$_tag received speaker event: $type');
            _speakerEventController.add(data);
            onSpeakerRequestRejected?.call(data);
            break;
          case '_speaker_invitation':
            debugPrint('$_tag received speaker event: $type');
            _speakerEventController.add(data);
            onSpeakerInvitationReceived?.call(data);
            // Also trigger the full invitation flow on UTDRoomController
            // if onInvitationUI is set (package-managed flow)
            _onInvitationHandler?.call(data);
            break;
          case '_speaker_invitation_accepted':
            debugPrint('$_tag received speaker event: $type');
            _speakerEventController.add(data);
            onSpeakerInvitationAccepted?.call(data);
            break;
          case '_speaker_invitation_declined':
            debugPrint('$_tag received speaker event: $type');
            _speakerEventController.add(data);
            onSpeakerInvitationDeclined?.call(data);
            break;
          case '_banned':
            debugPrint('$_tag received _banned from backend: $data');
            // The server only sends `_banned` to the banned participant, but
            // guard with an identity check when one is present, just in case.
            final target = data['identity'] as String?;
            final me = _roomManager.localParticipant?.identity;
            if (target == null || me == null || target == me) {
              onBanned?.call(data);
            }
            break;
          case '_role_change':
            // Broadcast to everyone (server-sent, so the event participant is
            // null). Do NOT filter by identity — the controller needs every
            // change to maintain its role cache.
            debugPrint('$_tag received _role_change from backend: $data');
            onRoleChanged?.call(data);
            break;
          case '_live_ended':
            // Host broadcast: the live is over. Non-host filtering happens in
            // the controller's _emitLiveEnded funnel.
            debugPrint('$_tag received _live_ended from host');
            onLiveEnded?.call();
            break;
        }
        return;
      }

      // Legacy client-side messages (messageContent pattern) — keep for backward compat
      final content = data['messageContent'] as Map<String, dynamic>?;
      if (content == null) return;

      final message = content['message'] as String?;
      if (message == null) return;

      debugPrint(
          '$_tag received legacy data channel message: $message | content: $content');
    });
  }

  void stopListening() {
    _dataSub?.cancel();
    _dataSub = null;
    _metadataSub?.cancel();
    _metadataSub = null;
    _roomManager.connectionState.removeListener(_onConnectionStateChanged);
    _reconnectionHandler?.dispose();
    _reconnectionHandler = null;
    _syncManager?.dispose();
    _syncManager = null;
    if (_seatsListener != null) {
      seats.removeListener(_seatsListener!);
      _seatsListener = null;
    }
  }

  bool _disposed = false;

  /// Dispose resources.
  void dispose() {
    if (_disposed) return;
    _disposed = true;
    debugPrint('$_tag dispose()');
    stopListening();
    _occupiedSeatsController.close();
    _speakerEventController.close();
    seatMode.dispose();
    modeId.dispose();
    pendingRequests.dispose();
    seats.dispose();
  }
}
