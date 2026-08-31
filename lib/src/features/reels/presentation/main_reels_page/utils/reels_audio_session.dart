import 'package:audio_session/audio_session.dart';
import 'package:flutter/foundation.dart';

/// Owns the platform audio session for the reels feed.
///
/// Reels must NOT mix with other audio. When reels actually starts playing we
/// activate the session ([activate]); when reels stops or leaves the screen
/// (pause-all / RouteAware push / app pause / dispose) we deactivate it
/// ([deactivate]). Deactivating releases the audio route so the room regains
/// bluetooth output and reels audio stops bleeding into the room.
///
/// Requests are COALESCED to the last intent: a call that arrives while an
/// activate/deactivate is in flight is not dropped (dropping desynced the
/// singleton from the real OS route — "wants audio but silent" / "route still
/// held by reels"); instead the latest desired state is applied once the
/// in-flight call completes. Never throws to the caller.
class ReelsAudioSession {
  ReelsAudioSession._();

  static final ReelsAudioSession instance = ReelsAudioSession._();

  AudioSession? _session;
  bool _active = false;
  bool _busy = false;
  bool _desiredActive = false;
  bool _configured = false;

  Future<void> activate() => _setDesired(true);

  Future<void> deactivate() => _setDesired(false);

  Future<void> _setDesired(bool active) async {
    _desiredActive = active;
    if (_busy) return; // The running loop applies the latest desire.
    _busy = true;
    try {
      // Loop until reality matches the LAST requested intent (it may flip
      // while we're awaiting the platform call).
      while (_active != _desiredActive) {
        final target = _desiredActive;
        try {
          final session = _session ??= await AudioSession.instance;
          if (target && !_configured) {
            await session.configure(const AudioSessionConfiguration.music());
            _configured = true;
          }
          await session.setActive(target);
          _active = target;
        } catch (e) {
          if (kDebugMode) {
            debugPrint(
                '[ReelsAudioSession] setActive($target) failed: $e');
          }
          // Don't lie about the state on failure; bail so a later explicit
          // call retries instead of spinning here.
          break;
        }
      }
    } finally {
      _busy = false;
    }
  }
}
