import 'package:flutter/services.dart';

/// Thin method-channel wrapper over the native effects pipeline.
///
/// Every call is defensive: on web, on a device without the native plugin
/// registered, or on any native error, the methods degrade to "unsupported /
/// no-op" so the caller falls back to a passthrough rather than breaking the
/// camera stream.
class VideoEffectsPlatform {
  static const MethodChannel _channel =
      MethodChannel('utd_video_effects_kit/control');

  /// Whether the native side can actually process pixels on this device.
  /// false on web, when the plugin isn't registered, or while the native GPU
  /// pipeline is still a scaffold (Milestone 0/1).
  Future<bool> isSupported() async {
    try {
      return (await _channel.invokeMethod<bool>('isSupported')) ?? false;
    } on MissingPluginException {
      return false;
    } catch (_) {
      return false;
    }
  }

  /// Hands the source camera track to native, which attaches a WebRTC video
  /// processor / RTCVideoFrame interceptor and runs the GPU chain. Returns a
  /// native session id, or null if native declined (→ passthrough).
  ///
  /// NOTE: returning the *processed* track back to Dart as a `MediaStreamTrack`
  /// is the Milestone-0 spike (see PLAN.md §3.3). Until then native returns
  /// `supported:false` and this yields null.
  Future<int?> attach({
    required String trackId,
    required Map<String, dynamic> state,
  }) async {
    try {
      final res = await _channel.invokeMapMethod<String, dynamic>(
        'attach',
        {'trackId': trackId, 'state': state},
      );
      if (res == null || res['supported'] != true) return null;
      return (res['sessionId'] as num?)?.toInt();
    } catch (_) {
      return null;
    }
  }

  /// Tears down a native session (mirrors ZEGO's `uninitEnv` discipline —
  /// skipping it leaks GPU/native memory).
  Future<void> detach({required int sessionId}) async {
    try {
      await _channel.invokeMethod('detach', {'sessionId': sessionId});
    } catch (_) {}
  }

  /// Pushes new effect parameters to a live native session (cheap; avoids
  /// re-attaching the track for an intensity change).
  Future<void> setEffects({
    required int sessionId,
    required Map<String, dynamic> state,
  }) async {
    try {
      await _channel
          .invokeMethod('setEffects', {'sessionId': sessionId, 'state': state});
    } catch (_) {}
  }
}
