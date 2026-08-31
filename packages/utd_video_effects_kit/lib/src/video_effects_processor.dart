import 'package:flutter/foundation.dart';
import 'package:livekit_client/livekit_client.dart';

import 'effects_state.dart';
import 'video_effects_processor_impl.dart';

/// A LiveKit-compatible video processor that applies GPU beauty/filter effects
/// natively and exposes the result via [processedTrack].
///
/// It **is** a `TrackProcessor<VideoProcessorOptions>`, so it plugs straight
/// into `CameraCaptureOptions(processor: ...)` /
/// `LocalVideoTrack.setProcessor(...)`.
///
/// IMPORTANT: there is intentionally NO `process(VideoFrame)` method. The
/// shipped livekit_client 2.7/2.8 `TrackProcessor` interface has no per-frame
/// hook — it exposes lifecycle (`init`/`restart`/`destroy`/`onPublish`/
/// `onUnpublish`) plus a [processedTrack] getter. All pixel work happens on the
/// native side; this Dart object is a lifecycle + effect-control wrapper.
///
/// While native pixel processing is unavailable ([isSupported] == false — web,
/// unsupported devices, or before the native pipeline lands) the processor is a
/// **safe passthrough**: [processedTrack] stays null and LiveKit keeps the
/// original camera track, so video always publishes.
abstract class VideoEffectsProcessor
    extends TrackProcessor<VideoProcessorOptions> {
  /// Generative constructor so concrete subclasses can `super()`.
  VideoEffectsProcessor();

  /// Builds the default native-backed implementation.
  factory VideoEffectsProcessor.create({
    EffectsState initial = const EffectsState(),
  }) =>
      VideoEffectsProcessorImpl(initial: initial);

  /// Live, read-only snapshot of current effect settings (bind UI to this).
  ValueListenable<EffectsState> get state;

  /// Whether this device/platform can actually process pixels. false ⇒
  /// [processedTrack] is a passthrough.
  bool get isSupported;

  /// Master on/off switch.
  Future<void> setEnabled(bool enabled);

  /// Skin-smoothing strength, `0.0..1.0`.
  Future<void> setSmoothing(double amount);

  /// Whitening / brightness strength, `0.0..1.0`.
  Future<void> setWhitening(double amount);

  /// Apply a LUT color filter by [lutAssetKey] (null clears it), with [intensity]
  /// `0.0..1.0`.
  Future<void> setFilter(String? lutAssetKey, {double intensity = 1.0});

  /// Background-blur strength, `0.0..1.0` (`0` = off).
  Future<void> setBackgroundBlur(double amount);

  /// Apply a skin-tone preset by key (null clears it). See [VideoEffectsSkinTones].
  Future<void> setSkinColor(String? presetKey);

  /// Final teardown: releases the effect-state listenable. Call when the live
  /// session ends. This is distinct from LiveKit's per-capture [destroy] (which
  /// only detaches the native session and is reused across camera restarts).
  void dispose();
}
