# utd_video_effects_kit

Real-time video filters & beauty effects for LiveKit-based Flutter apps —
modeled on ZEGOCLOUD's `zego_effects_plugin`, but built on `flutter_webrtc` +
native GPU pipelines so it integrates with `utd_live_room_kit`.

The public surface is **one** class — `VideoEffectsProcessor` — which *is* a
livekit_client `TrackProcessor<VideoProcessorOptions>`. That means it drops into
any `LocalVideoTrack` capture with zero glue.

> **Status: GPU effects implemented (v0.1.0); needs on-device validation.**
> The Dart API, method-channel contract, and `utd_live_room_kit` integration are
> complete. The native in-place pipeline implements **LUT color filters + skin
> smoothing + whitening + skin-tone**:
> - **Android** — a zero-copy **OpenGL ES** pipeline (`GlEffects` + `EffectsVideoProcessor`):
>   OES→FBO passes, square-LUT + bilateral smoothing + skin-tone (dual-LUT, YCbCr
>   mask) + whitening, per-frame output texture. Compiles in the app Gradle context ✅.
> - **iOS** — CoreImage chain (`CIColorCubeWithColorSpace` + Gaussian/dissolve
>   smoothing + brightness whitening) in an `ExternalVideoProcessingDelegate`
>   (device `RTCCVPixelBuffer` path; written, **not yet device-built**). iOS
>   skin-tone is deferred (needs a CoreImage skin mask).
>
> Background blur is the remaining effect (M3). Validate on a device — see below.

## Why a `TrackProcessor` (and not a `process(VideoFrame)` callback)

The shipped `livekit_client` 2.7/2.8 `TrackProcessor` interface has **no
per-frame hook**. It exposes lifecycle — `init` / `restart` / `destroy` /
`onPublish` / `onUnpublish` — plus a `processedTrack` getter. LiveKit swaps in
`processedTrack` only when it is non-null; otherwise the source track flows
unchanged. So:

- **All pixel work is native.** This Dart object is a lifecycle + control wrapper.
- **A null `processedTrack` is a safe passthrough** — never a broken stream.

## Usage

### Standalone

```dart
import 'package:livekit_client/livekit_client.dart';
import 'package:utd_video_effects_kit/utd_video_effects_kit.dart';

final fx = VideoEffectsProcessor.create();

final track = await LocalVideoTrack.createCameraTrack(
  CameraCaptureOptions(processor: fx),
);

// Control effects at any time (cheap native calls once the pipeline lands):
await fx.setEnabled(true);
await fx.setSmoothing(0.6);
await fx.setWhitening(0.2);
await fx.setFilter('warm', intensity: 0.8);
await fx.setBackgroundBlur(0.4);

// Bind UI to the live state:
ValueListenableBuilder<EffectsState>(
  valueListenable: fx.state,
  builder: (_, s, __) => Slider(value: s.smoothing, onChanged: fx.setSmoothing),
);
```

### With `utd_live_room_kit`

The kit takes a **factory** (not an instance) because LiveKit destroys the
processor on every `restartTrack` (camera flip / reconnect):

```dart
UTDLiveRoom(
  // …,
  config: UTDLiveRoomConfig(
    buildVideoProcessor: () => VideoEffectsProcessor.create(),
  ),
);
```

The kit attaches the processor to the host self-preview **and** to the SDK's
default camera options, so it survives camera switch, preview→go-live, reconnect,
and guest go-live. Rendering needs no changes — `VideoTrackRenderer` shows
`processedTrack` automatically.

To toggle effects at runtime you usually call the processor's own setters (e.g.
`setEnabled(false)`); to attach/detach the processor on the live track entirely,
the kit also exposes `controller.mediaController.setVideoProcessor(...)`.

## Method-channel contract (`utd_video_effects_kit/control`)

| Method | Args | Returns |
|---|---|---|
| `isSupported` | — | `bool` |
| `attach` | `{trackId, state}` | `{supported, sessionId, processedTrackId}` |
| `detach` | `{sessionId}` | — |
| `setEffects` | `{sessionId, state}` | — |

`state` is `EffectsState.toMap()`.

## Bundled filters & the ZEGO asset set

This package ships the **LUT color filters** extracted from the ZEGO Effects
resource bundle (`Resources/ColorfulStyleResources/*` + `FaceWhiteningResources`)
as plain **512×512 square LUT PNGs** under `assets/luts/`. These are a standard,
engine-agnostic format the DIY native LUT pass consumes directly — no third-party
engine or license required.

Use them via the catalog:

```dart
for (final f in VideoEffectsFilters.all) {
  // f.key ('fresh'), f.label ('Fresh'), f.asset (Image.asset path)
}
await fx.setFilter('fresh'); // resolves to assets/luts/fresh.png natively
```

Bundled keys: `fresh, night, autumn, brighten, sunset, cool, sweet, cozily,
creamy, film_like, whitening`.

**Not bundled (ZEGO-engine-proprietary).** The rest of the ZEGO asset set —
makeup (`.lua`), 3D pendants/stickers (`.obj` + `.lua`), the AI `.model` files,
and the skin-color/rosy/clarity/teeth bundles — only work with ZEGO's native
Effects engine (`ZegoEffects.create(appID, appSign)` + `setResources`). The host
app deliberately removed ZEGO, so those are **out of scope for the DIY path**.
To use them you'd add a `ZegoEffectsEngine` adapter behind `VideoEffectsProcessor`
and re-introduce the ZEGO SDK + license (a v2 "buy" decision — see PLAN.md §2/§6).
Skin smoothing / whitening / background blur in this package are DIY shader
effects and need no asset.

## Build & validate (on device)

The native code can only be verified on real hardware. After adding the package:

```bash
flutter pub get
# Android — the plugin's Kotlin already compiles in the app Gradle context:
(cd android && ./gradlew :utd_video_effects_kit:compileDebugKotlin)
flutter run                       # real Android device, host goes live, pick a filter
# iOS — first device build resolves WebRTC-SDK 144.7559.01 + flutter_webrtc pods:
(cd ios && pod install)
flutter run                       # real iOS device (simulator passes frames through)
```

Validate: (1) host go-live still works with no filter (passthrough); (2) picking
a filter visibly grades the LOCAL preview AND what remote viewers see; (3) camera
flip keeps the filter (LiveKit re-runs `init` on the new track id); (4) no fps
collapse at 720p (if Android stutters, that's the CPU/I420 path — move to the GL
texture path described in `NATIVE.md`).

## Roadmap

- **M0 — frame seam:** ✅ in-place processor on the flutter_webrtc track
  (`addProcessor` / `addProcessing`), affects preview + encoder.
- **M1 — LUT color filter:** ✅ Android GL (zero-copy texture) + iOS `CIColorCube`,
  loading `assets/luts/<key>.png`.
- **M2 — smoothing / whitening / skin-color:** ✅ Android GL (bilateral smoothing,
  whitening lift, dual-LUT skin-tone with a YCbCr mask using `assets/skin/**`);
  iOS smoothing + whitening (skin-tone deferred). See `NATIVE.md`.
- **M3 — Background blur:** MediaPipe Selfie Segmentation + blend, perf/thermal
  hardening. *(remaining)*
- **Perf:** Android GL holds 30fps zero-copy; profile on low-end devices.
- **v2 — Buy adapter:** face reshaping / makeup / 3D stickers / bg replacement
  behind the same `VideoEffectsProcessor` API (Banuba / Tencent XMagic / DeepAR).

See `PLAN.md` for the full architecture, native frame-format handling, the shader
pipeline, build-vs-buy analysis, and milestone estimates.

## Platforms

Android + iOS (mobile). Web returns `isSupported == false` (passthrough).
