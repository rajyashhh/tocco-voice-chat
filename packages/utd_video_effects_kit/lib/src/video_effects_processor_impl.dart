import 'package:flutter/foundation.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart' as rtc;
import 'package:livekit_client/livekit_client.dart';

import 'effects_state.dart';
import 'video_effects_filters.dart';
import 'video_effects_platform.dart';
import 'video_effects_processor.dart';

/// Default [VideoEffectsProcessor] implementation.
///
/// Lifecycle is driven by LiveKit's `LocalTrack.setProcessor` / `restartTrack` /
/// `stopProcessor` (see livekit_client `local.dart`):
/// `init` → (camera switch) `restart` → `destroy`. Because LiveKit *destroys*
/// the processor on every `restartTrack`, callers should build a FRESH instance
/// per capture (the kit holds a `buildVideoProcessor` factory, not a singleton).
class VideoEffectsProcessorImpl extends VideoEffectsProcessor {
  VideoEffectsProcessorImpl({EffectsState initial = const EffectsState()})
      : _state = ValueNotifier<EffectsState>(initial);

  final VideoEffectsPlatform _platform = VideoEffectsPlatform();
  final ValueNotifier<EffectsState> _state;

  int? _sessionId;
  bool _supported = false;
  rtc.MediaStreamTrack? _processedTrack;

  @override
  String get name => 'utd-video-effects';

  @override
  ValueListenable<EffectsState> get state => _state;

  @override
  bool get isSupported => _supported;

  @override
  rtc.MediaStreamTrack? get processedTrack => _processedTrack;

  @override
  Future<void> init(VideoProcessorOptions options) async {
    // IN-PLACE model (see NATIVE.md): we never produce a separate processed
    // track. Native registers an ExternalVideoFrameProcessing on the SAME
    // underlying flutter_webrtc track (found by id) and edits frames at the
    // shared VideoSource, so both the local preview and the encoder see the
    // effect. [processedTrack] therefore stays null by design — LiveKit keeps
    // the original LocalVideoTrack published.
    //
    // LiveKit re-runs init() on the SAME processor instance after a
    // restartTrack (camera flip / reconnect) with the NEW track id, so this
    // re-attaches to the new track automatically; destroy() detaches the old.
    _supported = !kIsWeb && await _platform.isSupported();
    if (!_supported) {
      // Web / unsupported → safe passthrough (original frames flow untouched).
      debugPrint('[UtdVideoFx] init: isSupported=false (web or MissingPluginException) '
          '→ passthrough, native effects NOT running');
      _processedTrack = null;
      return;
    }
    // A stale session can survive when LiveKit re-inits this instance against a
    // new track without having called destroy() (this instance is reused across
    // captures) — detach it first or the attach loop below would be skipped.
    final stale = _sessionId;
    if (stale != null) {
      _sessionId = null;
      await _platform.detach(sessionId: stale);
    }
    // flutter_webrtc registers the native LocalTrack during getUserMedia, but
    // LiveKit calls processor.init in the same microtask burst — retry briefly
    // so a registration race doesn't silently leave the session in passthrough.
    final trackId = options.track.id ?? '';
    for (var attempt = 0; attempt < 5 && _sessionId == null; attempt++) {
      if (attempt > 0) {
        await Future<void>.delayed(const Duration(milliseconds: 300));
      }
      _sessionId = await _platform.attach(
        trackId: trackId,
        state: _nativeState(_state.value),
      );
    }
    debugPrint('[UtdVideoFx] init: trackId=${options.track.id} supported=$_supported '
        'sessionId=$_sessionId  (null sessionId ⇒ native attach failed ⇒ passthrough)');
    _processedTrack = null; // intentional — in-place editing, not a new track
  }

  @override
  Future<void> restart(VideoProcessorOptions options) async {
    await destroy();
    await init(options);
  }

  @override
  Future<void> destroy() async {
    final id = _sessionId;
    if (id != null) {
      await _platform.detach(sessionId: id);
      _sessionId = null;
    }
    _processedTrack = null;
  }

  @override
  Future<void> onPublish(Room room) async {}

  @override
  Future<void> onUnpublish() async {}

  // ---------------------------------------------------------------------------
  // Effect controls — update local state + push to the native session (if any).
  // ---------------------------------------------------------------------------

  /// State sent to native, with the active filter + skin-tone bundled LUT asset
  /// paths resolved from the catalogs so the native pipeline can load them.
  Map<String, dynamic> _nativeState(EffectsState s) {
    final tone = VideoEffectsSkinTones.byKey(s.skinColor);
    return {
      ...s.toMap(),
      'filterAsset': VideoEffectsFilters.assetFor(s.filterKey),
      'skinSkinAsset': tone?.skinAsset,
      'skinBgAsset': tone?.bgAsset,
      'skinAmount': tone != null ? 1.0 : 0.0,
    };
  }

  Future<void> _apply(EffectsState next) async {
    _state.value = next;
    final id = _sessionId;
    if (id != null) {
      await _platform.setEffects(sessionId: id, state: _nativeState(next));
    }
  }

  @override
  Future<void> setEnabled(bool enabled) =>
      _apply(_state.value.copyWith(enabled: enabled));

  @override
  Future<void> setSmoothing(double amount) =>
      _apply(_state.value.copyWith(smoothing: amount.clamp(0.0, 1.0)));

  @override
  Future<void> setWhitening(double amount) =>
      _apply(_state.value.copyWith(whitening: amount.clamp(0.0, 1.0)));

  @override
  Future<void> setFilter(String? lutAssetKey, {double intensity = 1.0}) =>
      _apply(_state.value.copyWith(
        filterKey: lutAssetKey,
        filterIntensity: intensity.clamp(0.0, 1.0),
      ));

  @override
  Future<void> setBackgroundBlur(double amount) =>
      _apply(_state.value.copyWith(backgroundBlur: amount.clamp(0.0, 1.0)));

  @override
  Future<void> setSkinColor(String? presetKey) =>
      _apply(_state.value.copyWith(skinColor: presetKey));

  @override
  void dispose() {
    // Native session (if any) is detached by LiveKit's destroy() when the track
    // stops; here we only release the Dart-side state listenable.
    _state.dispose();
  }
}
