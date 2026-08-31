/// utd_video_effects_kit — real-time video filters & beauty effects for
/// LiveKit-based Flutter apps.
///
/// The public surface is a single [VideoEffectsProcessor] that implements
/// livekit_client's `TrackProcessor<VideoProcessorOptions>`. Attach it to a
/// `LocalVideoTrack` (directly, or via `utd_live_room_kit`'s
/// `UTDLiveRoomConfig.buildVideoProcessor` factory) and toggle effects through
/// its setters.
///
/// ```dart
/// final fx = VideoEffectsProcessor.create();
/// final track = await LocalVideoTrack.createCameraTrack(
///   CameraCaptureOptions(processor: fx),
/// );
/// await fx.setSmoothing(0.6);
/// await fx.setFilter('warm', intensity: 0.8);
/// ```
///
/// See `PLAN.md` (sibling of this package) for the full architecture and the
/// native GPU pipeline roadmap. Until the native pipeline lands, the processor
/// runs as a **safe passthrough** (the original camera frames flow unmodified).
library utd_video_effects_kit;

export 'src/effects_state.dart';
export 'src/video_effects_filters.dart';
export 'src/video_effects_processor.dart';
