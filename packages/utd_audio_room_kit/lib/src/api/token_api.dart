import 'package:dio/dio.dart';

import 'utd_api_client.dart';
import '../models/token_response_model.dart';
import '../models/ban_model.dart';

export '../models/token_response_model.dart';

/// Thrown when token generation fails for a non-ban reason (network/timeout,
/// engine 5xx, malformed body). Carries the HTTP [statusCode] when available so
/// callers can tell a transport failure apart from an engine error.
class UTDTokenException implements Exception {
  final int? statusCode;
  final String message;
  UTDTokenException(this.message, {this.statusCode});
  @override
  String toString() => 'UTDTokenException($statusCode): $message';
}

class UTDTokenApi {
  final UTDApiClient _client;

  UTDTokenApi(this._client);

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
    final data = <String, dynamic>{
      'identity': identity,
      'room_name': roomName,
      'service': service,
      'room_owner_id': roomOwnerId,
    };

    if (name != null) data['name'] = name;
    data['role'] = role;
    if (seatCount != null) data['seat_count'] = seatCount;
    if (seatMode != null) data['seat_mode'] = seatMode;
    if (hostSeat != null) data['host_seat'] = hostSeat;
    if (modeId != null) data['mode_id'] = modeId;
    if (deviceModel != null) data['device_model'] = deviceModel;
    if (os != null) data['os'] = os;
    if (osVersion != null) data['os_version'] = osVersion;
    if (appVersion != null) data['app_version'] = appVersion;
    if (metadata != null) data['metadata'] = metadata;

    try {
      final response = await _client.post('/api/v1/token', data: data);
      final parsed = UTDTokenResponse.fromJson(response);
      if (parsed.token.isEmpty || parsed.url.isEmpty) {
        throw UTDTokenException('Malformed token response from engine');
      }
      return parsed;
    } on DioException catch (e) {
      if (e.response?.statusCode == 403) {
        final body = e.response?.data;
        final message = (body is Map && body['message'] != null)
            ? body['message'].toString()
            : 'User is banned';
        throw UTDBannedException(message);
      }
      final body = e.response?.data;
      final message = (body is Map && body['message'] != null)
          ? body['message'].toString()
          : (e.message ?? 'Token request failed');
      throw UTDTokenException(message, statusCode: e.response?.statusCode);
    }
  }
}
