import 'utd_api_client.dart';

/// REST API client for all seat management endpoints.
///
/// Base path: `/api/v1/rooms/:name/seats/...`
///
/// See: UTD Stream Engine — UIKit Developer Guide § Seat Management APIs
class UTDSeatApi {
  final UTDApiClient _client;

  UTDSeatApi(this._client);

  // ---------------------------------------------------------------------------
  // Get Seats
  // ---------------------------------------------------------------------------

  /// Returns current seat state including occupants, locks, and pending requests.
  ///
  /// GET /api/v1/rooms/:name/seats
  Future<Map<String, dynamic>> getSeats({
    required String roomId,
  }) {
    return _client.get('/api/v1/rooms/$roomId/seats');
  }

  // ---------------------------------------------------------------------------
  // Take Seat
  // ---------------------------------------------------------------------------

  /// Takes a seat at the given index.
  ///
  /// In `free` mode: takes immediately if available and unlocked.
  /// In `request` mode: requires a prior approved speaker request (403 otherwise).
  /// Returns `409` if user is already seated or seat is occupied.
  /// On success, backend upgrades user permissions to `canPublish: true`.
  ///
  /// POST /api/v1/rooms/:name/seats/:index/take
  Future<Map<String, dynamic>> takeSeat({
    required String roomId,
    required int seatIndex,
    required String identity,
  }) {
    return _client.post(
      '/api/v1/rooms/$roomId/seats/$seatIndex/take',
      data: {'identity': identity},
    );
  }

  /// The seated user steps down from their own seat (guest leaving the
  /// stage). Backend clears the seat and downgrades them to audience.
  ///
  /// POST /api/v1/rooms/:name/seats/leave
  Future<Map<String, dynamic>> leaveSeat({
    required String roomId,
    required String identity,
  }) {
    return _client.post(
      '/api/v1/rooms/$roomId/seats/leave',
      data: {'identity': identity},
    );
  }

  // ---------------------------------------------------------------------------
  // Kick from Seat (host/admin only) — "remove guest from stage" in live
  // ---------------------------------------------------------------------------

  /// Removes the occupant from the seat. Admin cannot kick the host.
  /// Backend downgrades the kicked user's permissions to audience.
  ///
  /// [identity] — the actor performing the kick (must be host/admin)
  ///
  /// POST /api/v1/rooms/:name/seats/:index/kick
  Future<Map<String, dynamic>> kickFromSeat({
    required String roomId,
    required int seatIndex,
    required String identity,
  }) {
    return _client.post(
      '/api/v1/rooms/$roomId/seats/$seatIndex/kick',
      data: {'identity': identity},
    );
  }

  // ---------------------------------------------------------------------------
  // Mute Seat Occupant (host/admin only)
  // ---------------------------------------------------------------------------

  /// Mutes the audio track of the participant sitting in this seat
  /// via LiveKit server-side API. Admin cannot mute the host.
  ///
  /// POST /api/v1/rooms/:name/seats/:index/mute
  Future<Map<String, dynamic>> muteSeat({
    required String roomId,
    required int seatIndex,
    required String identity,
  }) {
    return _client.post(
      '/api/v1/rooms/$roomId/seats/$seatIndex/mute',
      data: {'identity': identity},
    );
  }

  // ---------------------------------------------------------------------------
  // Unmute Seat Occupant (host/admin only)
  // ---------------------------------------------------------------------------

  /// Unmutes the audio track of the participant sitting in this seat.
  ///
  /// POST /api/v1/rooms/:name/seats/:index/unmute
  Future<Map<String, dynamic>> unmuteSeat({
    required String roomId,
    required int seatIndex,
    required String identity,
  }) {
    return _client.post(
      '/api/v1/rooms/$roomId/seats/$seatIndex/unmute',
      data: {'identity': identity},
    );
  }

}
