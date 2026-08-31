import 'dart:io' show Platform;

import 'package:general/src/core/constants/end_points.dart';
import 'package:general/src/core/network/dio_factory.dart';
import 'package:general/src/core/services/dependency_injection_service.dart';
import 'package:flutter/foundation.dart';

/// Server-signed UTD Stream tokens (the production pattern).
///
/// The engine forbids identity-bearing mints with the publishable app_key
/// (403 `appkey_identity_mint_disabled`), so the kit no longer mints its own
/// token. Instead the app asks OUR backend — which verifies the logged-in
/// user via Sanctum and signs the engine request with the server_secret that
/// never ships in the app — and hands the ready response to the kit via its
/// `tokenProvider` (the kit adopts the per-user bearer automatically).
class StreamTokenService {
  StreamTokenService._();

  /// Fetches a server-signed token for [roomName]. Returns the raw engine
  /// token payload (token/url/ws_url/user_token/seats...) or null on failure —
  /// null lets the kit fall back to its own mint path, which keeps rooms
  /// working on installs whose engine project allows app_key minting.
  static Future<Map<String, dynamic>?> fetch({
    required String roomName,
    required String service, // 'rooms' | 'streaming'
    String? role,
    String? roomOwnerId,
    int? seatCount,
    String? seatMode,
    int? hostSeat,
    String? modeId,
  }) async {
    try {
      // The engine gates the beauty-filter (videoEffects) entitlement on the
      // caller's OS at mint time and fails closed without it.
      final os = kIsWeb
          ? null
          : Platform.isAndroid
              ? 'android'
              : Platform.isIOS
                  ? 'ios'
                  : null;
      final response = await di<DioFactory>().post(
        '${EndPoints.baseURL}/stream/token',
        data: {
          'room_name': roomName,
          'service': service,
          if (os != null) 'os': os,
          if (role != null) 'role': role,
          if (roomOwnerId != null) 'room_owner_id': roomOwnerId,
          if (seatCount != null) 'seat_count': seatCount,
          if (seatMode != null) 'seat_mode': seatMode,
          if (hostSeat != null) 'host_seat': hostSeat,
          if (modeId != null) 'mode_id': modeId,
        },
      );
      final body = response.data;
      final data = (body is Map) ? body['data'] : null;
      if (data is Map<String, dynamic> && (data['token'] ?? '') != '') {
        return data;
      }
      if (data is Map) {
        final cast = Map<String, dynamic>.from(data);
        if ((cast['token'] ?? '') != '') return cast;
      }
      debugPrint('[StreamTokenService] backend returned no token — '
          'falling back to kit mint');
      return null;
    } catch (e) {
      debugPrint('[StreamTokenService] fetch failed: $e — '
          'falling back to kit mint');
      return null;
    }
  }
}