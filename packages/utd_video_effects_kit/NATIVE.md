# Native pipeline — implementation notes

This documents the **confirmed** native frame seam and the per-effect porting
plan. It supplements `PLAN.md` (the high-level design) with the concrete APIs
verified against the installed `flutter_webrtc 1.4.0` source.

## The frame seam (confirmed — this is the M0 unlock)

flutter_webrtc already runs a chain of "external" frame processors on the
capturer, **in place**, before the frame reaches every sink (the local renderer
preview AND the WebRTC encoder). We hook that chain instead of producing a new
`MediaStreamTrack`. So our livekit `TrackProcessor.processedTrack` stays **null**
(LiveKit keeps the original track) and we register a native processor on that
same underlying track, found by id.

### Android
- `com.cloudwebrtc.webrtc.FlutterWebRTCPlugin.sharedSingleton` (public static)
  → `LocalTrack getLocalTrack(String trackId)`.
- `com.cloudwebrtc.webrtc.video.LocalVideoTrack` (a `LocalTrack`) implements
  `org.webrtc.VideoProcessor` and exposes:
  - `addProcessor(ExternalVideoFrameProcessing)` / `removeProcessor(...)`
  - `interface ExternalVideoFrameProcessing { VideoFrame onFrame(VideoFrame); }`
  - `onFrameCaptured()` runs all processors in order, then forwards to the sink.

### iOS
- `FlutterWebRTCPlugin.sharedSingleton().localTracks` —
  `NSMutableDictionary<NSString*, id<LocalTrack>>`.
- `LocalVideoTrack addProcessing:(id<ExternalVideoProcessingDelegate>)` /
  `removeProcessing:`.
- `@protocol ExternalVideoProcessingDelegate { - (RTCVideoFrame*)onFrame:(RTCVideoFrame*); }`
- `VideoProcessingAdapter` is the `RTCVideoCapturerDelegate` that drives it.

### Method-channel contract (Dart ↔ native), unchanged
`attach(trackId, state) -> {supported, sessionId}` registers the processor;
`detach(sessionId)` removes it; `setEffects(sessionId, state)` updates params.
`state` carries `filterAsset` (resolved LUT path) + smoothing/whitening/blur.

## Per-effect porting plan (no ZEGO engine)

| Effect | Native technique | Asset | Needs landmarks? |
|---|---|---|---|
| LUT color filter | square-LUT shader (Android GL) / `CIColorCube` (iOS) | `assets/luts/<key>.png` | no |
| Whitening / Rosy / Clarity | same as LUT (global) | bundled LUTs | no |
| Skin smoothing | frequency-separation / bilateral shader | none | no (color mask) |
| Background blur | MediaPipe Selfie Segmentation + blur | none (MediaPipe model) | no |
| Skin color | dual-LUT + temperature blend (below) | `assets/skin/<preset>/*` | no (color mask) |
| Makeup / teeth | texture warp / regional LUT | ZEGO textures | yes (MediaPipe FaceMesh) |
| 3D pendants, reshape | — | — | ZEGO-engine only (skipped) |

## SkinColor blend (ported from ZEGO `config.json`)

`SkinColorResources/*/config.json` → `type:"skin"`, `range:[0,100]`,
`externals: { bg_lut, skin_lut, mask, t_max, t_min }`, and the parent
`effect_config` sets `bind_face:false`, `binarization:false`,
`light_wrapping:true`.

Extracted to `assets/skin/<preset>/` for the 5 presets
(`fenbai, meihei, xiaomai, lengbai, nuanbai`):
`filter_bg.png` (512²), `filter_skin.png` (512²), `mask.png` (512²),
`temperature_min.png` / `temperature_max.png` (289×17 strips).

Shader algorithm:
1. Build a **skin mask** per pixel. `bind_face:false` ⇒ no face landmarks —
   use a YCbCr/HSV skin-color heuristic (optionally combined with the bundled
   `mask.png` as a prior). `light_wrapping:true` ⇒ soft-feather the mask edges.
2. `skinGraded = squareLUT(src, filter_skin.png)`;
   `bgGraded = squareLUT(src, filter_bg.png)`.
3. `graded = mix(bgGraded, skinGraded, skinMask)`.
4. **Temperature**: interpolate a small LUT between `temperature_min` and
   `temperature_max` by `intensity` (the `range:[0,100]` slider) and apply.
5. `out = mix(src, graded, intensity)`.

This needs no AI model and no third-party engine — only the bundled textures and
a color-based skin mask. (For higher quality later, swap the heuristic mask for
MediaPipe Selfie Segmentation.)

> NOTE: `assets/skin/**` is staged but NOT yet declared in `pubspec.yaml` — add
> the `assets/skin/` entry when the SkinColor shader lands (avoids shipping the
> ~2.4 MB until it's used).
