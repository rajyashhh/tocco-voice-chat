# Implementation Plan — `utd_video_effects_kit`

Real-time video filters & beauty effects for the LiveKit-based `utd_live_room_kit` (Tocco Voice)

> Scope note: This plan is built strictly from the four research findings supplied. Where a finding is uncertain or two findings conflict, that is called out inline with a **Safe path** marker. No APIs are invented; every LiveKit/kit symbol cited is from the research or verified in-repo (`media_controller.dart:307`, `room_manager.dart:130-133`).

---

## 1. Goal & Scope

### 1.1 What "looks like `zego_effects_plugin`" realistically means here

`zego_effects_plugin` is **not a streaming SDK** — it is a per-frame processor that ZEGO's Express engine drives via a custom-video-processing hook (`onCapturedUnprocessedTextureData → processTexture → sendCustomVideoProcessedTextureData`). Our equivalent must do the same job, but the host engine is **LiveKit + flutter_webrtc**, not Express. The architectural analogue is therefore:

| ZEGO concept | Our equivalent |
|---|---|
| `ZegoExpressEngine` custom-video-process hook | LiveKit `TrackProcessor<VideoProcessorOptions>` seam (native `org.webrtc.VideoProcessor` / iOS `RTCVideoFrame` interceptor) |
| `processTexture(textureID, param) → textureID` | Android: GL `TEXTURE_EXTERNAL_OES → FBO chain → TextureBuffer` |
| `processImageBuffer(CVPixelBuffer) → CVPixelBuffer` | iOS: `CVPixelBuffer → MTLTexture → Metal passes → CVPixelBuffer` |
| `enableImageProcessing` + `enable*/set*Param` | Dart `MethodChannel` toggles on a `VideoEffectsProcessor` |
| `initEnv(w,h)` / `uninitEnv()` lifecycle | Processor `init()` / `destroy()` lifecycle (the LiveKit interface gives us exactly these hooks) |

So "looks like zego" realistically means: **a Dart effects API with `enable*/setIntensity/setLut`-style toggles, backed by two native GPU pipelines, plugged into LiveKit's processor seam so processed frames flow straight to the WebRTC encoder.** It does **not** mean replicating ZEGO's full proprietary feature surface (148-keypoint makeup, 3D pendants) in v1 — that is a multi-year vendor effort per the build-vs-buy research.

### 1.2 v1 Goals
- A federated Flutter plugin `utd_video_effects_kit` exposing an abstract `VideoEffectsProcessor` that **implements LiveKit's `TrackProcessor<VideoProcessorOptions>`**.
- Native GPU pipelines: **Metal/Core Image (iOS)**, **OpenGL ES 3.0/GLSL (Android)**.
- Effects: **LUT color filters, skin smoothing (frequency-separation/bilateral), whitening, background blur** (MediaPipe Selfie Segmentation).
- Clean integration into `utd_live_room_kit` via an **injectable processor factory** on `UTDLiveRoomConfig`, covering preview, publish, camera-switch, and reconnect (all four capture paths the kit-code research identified).
- Graceful **no-op passthrough** on web/unsupported devices.

### 1.3 Explicit Non-Goals for v1
- **No face reshaping** (eye-enlarge, face-slim, nose/jaw) — the build-vs-buy research is explicit that DIY here "will look amateur." Deferred to v2 (vendor adapter).
- **No makeup try-on** (lipstick/eyeshadow with per-feature segmentation) — requires dense face parsing only commercial engines have. v2/vendor.
- **No 3D stickers / AR masks / Animoji** with occlusion & head pose — true AR-engine territory. v2/vendor.
- **No green-screen chroma key.** (zego has it; low demand for a live-social app, deferred.)
- **No web pixel processing** — flutter_webrtc on web has a different processor path (`processor_web.dart`); v1 ships a Dart no-op there.
- **No desktop** (matches zego_effects_plugin's own Android+iOS-only scope).
- **No bundled commercial SDK** in v1 — only the *adapter interface* so a vendor can be dropped in later.

---

## 2. Build-vs-Buy Decision

### 2.1 Recommendation: **Hybrid — DIY now, pluggable vendor adapter later.**

The build-vs-buy research is unambiguous on two points that drive this:
1. **There is no turnkey LiveKit beauty plugin from any vendor.** Every option — DIY or commercial — requires us to write a native LiveKit video-processor bridge. We are paying that integration cost regardless.
2. DIY covers **~60% of perceived value cheaply** (LUTs + smoothing + background blur), while high-end beauty/reshaping/makeup/3D is where commercial engines justify their license cost.

Therefore: **build the native processor once as a vendor-swappable layer.** Ship DIY effects in it now. When the product justifies the license, drop a commercial engine behind the *same* `VideoEffectsProcessor` abstraction without rewiring LiveKit.

### 2.2 What we build vs buy

| Capability | Decision | Rationale (from research) |
|---|---|---|
| LUT color filters (.cube / 512×512 HALD) | **BUILD** | "trivial GPU shader pass… highest ROI, free, fully WebRTC-controllable" |
| Skin smoothing (bilateral / high-pass) | **BUILD** | "good-enough 'soften' look at zero license cost" (ref: YUCI/YUGPUImage HighPassSkinSmoothing, MIT) |
| Whitening / brightness / warmth | **BUILD** | Same shader chain; cheap color math |
| Background blur | **BUILD** | MediaPipe Selfie Segmentation (Apache-2.0, ~0.7ms mask) + blend shader |
| Simple 2D landmark stickers (glasses/hats) | **BUILD (v1.5, optional)** | "Doable but more work; quality is 'decent', not Snap-grade" |
| High-quality beauty + **face reshaping** | **BUY (v2 adapter)** | "requires dense face parsing… DIY will look amateur" |
| Realistic **makeup try-on** | **BUY (v2 adapter)** | "DeepAR/MediaPipe lack per-feature face parsing on-device" |
| **3D stickers / AR masks / Animoji** | **BUY (v2 adapter)** | "a true AR engine job" |
| Production background **replacement** (clean hair/edges) | **BUY (v2 adapter)** | MediaPipe fine for blur, commercial better for clean replacement |

### 2.3 Vendor pick for the v2 adapter (when triggered)

Per the survey, ranked for a **LiveKit + Flutter** app:
1. **Banuba Face AR SDK** — best fit. Official `banuba_sdk` Flutter plugin + Offscreen Effect Player (OEP) is a true camera-independent frame-in/frame-out (YUV I420 buffer **or** external ARGB texture) that "maps cleanly onto a LiveKit video processor." Best beauty/makeup quality. *Downside:* premium, opaque MAU pricing.
2. **Tencent XMagic** — strong runner-up. Official `tencent_effect_flutter`, clean `process(textureId) → textureId` (engine-neutral, thread-flexible), and the **only vendor with public tiered pricing** ($999–$5,999/mo). *Downside:* TRTC-centric docs, domain-bound license, large `LiteAVSDK_Professional` Android dep.
3. **DeepAR** — cheapest to start, genuinely frame-friendly (CUSTOM frame push), good stickers; but shallow beauty/makeup and Zalando-ownership roadmap risk.

**Adapter design implication:** the abstraction must support **both** integration shapes the top vendors expose — *texture-id-in/texture-id-out* (XMagic) and *buffer-in/buffer-or-texture-out* (Banuba OEP). Our native `EffectsEngine` interface (§3.2) is defined to accommodate both so the adapter is a thin shim.

> **Avoid for this project:** Agora's beauty extensions — they only work inside Agora RTC, not LiveKit. Useful **only** as a reference blueprint for how to wrap FaceUnity/ByteDance in our own processor.

---

## 3. Architecture

### 3.1 Frame flow: camera → effects → WebRTC encoder

```
                       ┌─────────────────────── utd_live_room_kit (Dart) ───────────────────────┐
                       │  UTDLiveRoomConfig.buildVideoProcessor: () => VideoEffectsProcessor()  │
                       └───────────────────────────────────┬───────────────────────────────────┘
                                                            │ injected into
                       CameraCaptureOptions(processor: ...) │ (startPreview + RoomOptions defaults)
                                                            ▼
   ┌──────────────────────────────── livekit_client (2.8.0) ────────────────────────────────┐
   │  LocalVideoTrack.createCameraTrack → setProcessor → processor.init(VideoProcessorOptions)│
   │  setProcessedTrack(processor.processedTrack)  ──►  RTCRtpSender / encoder / publish      │
   └──────────────────────────────────────────┬─────────────────────────────────────────────┘
                                               │ NATIVE seam (NOT Dart — see §3.3)
        ┌──────────────────────────────────────┴───────────────────────────────────────┐
        │  ANDROID: org.webrtc.VideoProcessor.onFrameReceived(VideoFrame)               │
        │  iOS:     RTCVideoCapturer delegate / RTCVideoFrame interceptor               │
        └──────────────────────────────────────┬───────────────────────────────────────┘
                                               ▼
   Camera (OES texture / CVPixelBuffer)  →  GPU FBO/Metal chain  →  processed texture/buffer
        Pass0 ingest → Pass1 smoothing → Pass2 LUT → (Pass3 bg-blur) → Pass N output
                                               │
                                               ▼
                       wrapped back into VideoFrame/RTCVideoFrame  →  HW encoder (zero readback)
```

**Critical architectural fact (from the LiveKit research):** the shipped `TrackProcessor` interface is **track-level, NOT frame-level**. It has `init / restart / destroy / onPublish / onUnpublish` + a `processedTrack` getter **and no `process(VideoFrame)` hook and no frame stream**. The `VideoProcessor.process(VideoFrame)` shape seen in GitHub issue #880 was the *feature request text, not the shipped API* — **we do not code against it.** A processor only takes effect if `processedTrack` is **non-null**; a pure-Dart processor can only pass through. Therefore **all pixel work happens natively**, and the Dart processor's `processedTrack` returns a native track produced by our native pipeline.

### 3.2 Dart plugin API surface

The public processor **is** a `TrackProcessor<VideoProcessorOptions>`. The effect toggles are plain methods that forward to native over a `MethodChannel`.

```dart
// utd_video_effects_kit/lib/src/video_effects_processor.dart
import 'package:livekit_client/livekit_client.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart' as rtc;

/// A LiveKit-compatible video processor that applies GPU beauty/filter effects
/// natively and exposes the result via [processedTrack].
///
/// NOTE: There is intentionally NO process(VideoFrame) method — the shipped
/// livekit_client 2.7/2.8 TrackProcessor interface has no per-frame hook. All
/// pixel work is done on the native side; this Dart object is a lifecycle +
/// effect-control wrapper. Effect on a non-null processedTrack only.
abstract class VideoEffectsProcessor
    extends TrackProcessor<VideoProcessorOptions> {
  /// Live, read-only snapshot of current effect settings (for UI binding).
  ValueListenable<EffectsState> get state;

  /// Whether this device/platform supports native pixel processing.
  /// false on web & unsupported devices -> [processedTrack] is a passthrough.
  bool get isSupported;

  // ---- effect controls (forwarded to native; cheap MethodChannel calls) ----
  Future<void> setEnabled(bool enabled);                 // master switch
  Future<void> setSmoothing(double amount);              // 0..1
  Future<void> setWhitening(double amount);              // 0..1
  Future<void> setFilter(String? lutAssetKey, {double intensity = 1.0});
  Future<void> setBackgroundBlur(double amount);         // 0..1 (0 = off)

  /// Factory entry point used by the kit.
  factory VideoEffectsProcessor.create({
    EffectsState initial = const EffectsState(),
  }) = VideoEffectsProcessorImpl;
}

class EffectsState {
  final bool enabled;
  final double smoothing;
  final double whitening;
  final String? filterKey;
  final double filterIntensity;
  final double backgroundBlur;
  const EffectsState({
    this.enabled = false,
    this.smoothing = 0,
    this.whitening = 0,
    this.filterKey,
    this.filterIntensity = 1.0,
    this.backgroundBlur = 0,
  });
}
```

Concrete impl skeleton (the part LiveKit actually calls):

```dart
// utd_video_effects_kit/lib/src/video_effects_processor_impl.dart
class VideoEffectsProcessorImpl extends VideoEffectsProcessor {
  static const _ch = MethodChannel('utd_video_effects_kit/control');
  int? _sessionId;                  // native pipeline handle
  rtc.MediaStreamTrack? _processed; // the ONLY output channel for LiveKit

  @override
  String get name => 'utd-video-effects';

  @override
  Future<void> init(VideoProcessorOptions options) async {
    // options.track is the ORIGINAL camera MediaStreamTrack (opaque native
    // handle). NO RTCVideoFrame / pixel buffer is handed to us here.
    if (!isSupported) {            // web / unsupported -> passthrough no-op
      _processed = options.track;
      return;
    }
    // Hand the source track to native; native attaches a WebRTC VideoProcessor
    // (Android) / RTCVideoFrame interceptor (iOS), runs the GPU chain, and
    // returns a handle to a NEW processed MediaStreamTrack.
    final res = await _ch.invokeMethod('attach', {
      'trackId': options.track.id,
      'state': _stateToMap(state.value),
    });
    _sessionId = res['sessionId'] as int;
    _processed = await _resolveNativeTrack(res['processedTrackId'] as String);
  }

  @override
  Future<void> restart(VideoProcessorOptions options) async {
    await destroy();
    await init(options);
  }

  @override
  Future<void> destroy() async {
    if (_sessionId != null) {
      await _ch.invokeMethod('detach', {'sessionId': _sessionId});
      _sessionId = null;
    }
    _processed = null;
  }

  @override Future<void> onPublish(Room room) async {}   // hook if needed
  @override Future<void> onUnpublish() async {}

  @override
  rtc.MediaStreamTrack? get processedTrack => _processed; // LiveKit reads this
}
```

> **Why a factory, not a singleton instance** (kit-code research, caveat B): `stopProcessor()` **destroys** the processor on every `restartTrack`, then re-inits. A single shared instance can be re-init'd after destroy and is fragile. The kit must hold a **`VideoEffectsProcessor Function()` factory** so each fresh capture/restart gets its own instance. (This differs from zego, where one Effects engine persists; LiveKit's lifecycle forces per-track instances.)

### 3.3 Native pipeline per platform — where the real work lives

The Dart `init()` above only *hands the source track to native*. The actual interception is native:

- **Android:** attach an `org.webrtc.VideoProcessor` to the camera capturer's `CapturerObserver`; `onFrameReceived(VideoFrame)` gives a `VideoFrame.Buffer` — usually a **TextureBuffer (GL_TEXTURE_EXTERNAL_OES)** or NV12, rarely I420. We keep it on the GPU, run the FBO chain, wrap the output `GL_TEXTURE_2D` into a new `VideoFrame` via `SurfaceTextureHelper` + `YuvConverter`, and emit it as a new `MediaStreamTrack`.
- **iOS:** intercept `RTCVideoFrame` (buffer is `RTCCVPixelBuffer` on device, `RTCI420Buffer` on simulator — **handle both**). Bridge `CVPixelBuffer → MTLTexture` via `CVMetalTextureCache`, run Metal passes, render back into a Metal-compatible `CVPixelBuffer` from a `CVPixelBufferPool`, re-wrap as `RTCCVPixelBuffer → RTCVideoFrame`, emit as a new track.

> **Uncertain area — flagged.** The research confirms `flutter_webrtc 1.4.0` exposes external textures **only for rendering**, not a "mutate-and-republish from Dart" path, and that the supported native pattern is "a processor whose `processedTrack` getter returns a native `MediaStreamTrack` produced by a native pipeline." It cites `livekit-examples/flutter-filter-plugin-example` and "a buffer/custom-capturer" as the reliable routes.
> **Safe path:** Spike the exact mechanism by which our native code obtains the source camera frames and produces a new `MediaStreamTrack` that flutter_webrtc/LiveKit will accept as `processedTrack`. The two candidate seams are **(a)** hooking the existing LiveKit/WebRTC camera capturer's `VideoProcessor`/`CapturerObserver`, or **(b)** a **custom capturer** where we own capture and feed processed frames in (analogous to ZEGO's "Custom Video Capture" alternative). Decide between (a) and (b) in **Milestone 0** (§9) — this is the #1 riskiest unknown.

### 3.4 Native engine abstraction (vendor-swappable)

Inside native, the GPU chain sits behind one interface so DIY and a future vendor are interchangeable (the `EffectsEngine` is *not* exposed to Dart):

```
EffectsEngine (native interface, iOS + Android)
 ├─ DiyEffectsEngine        // v1: our Metal/GLSL FBO chain (LUT, smooth, blur)
 ├─ BanubaEffectsEngine     // v2: wraps OEP (buffer/texture in -> out)
 └─ XMagicEffectsEngine     // v2: wraps process(textureId) -> textureId

method: long/textureId/CVPixelBuffer process(input, width, height, EffectsState)
        + initEnv(w,h) + uninitEnv()   // mirrors zego's lifecycle discipline
```

This directly mirrors zego's discipline (`initEnv(w,h)` on stream start, `uninitEnv()` on stop — *"skipping uninitEnv leaks memory"*). Our native `attach`/`detach` map onto these.

---

## 4. Integration with `utd_live_room_kit`

### 4.1 The exact injection points (cited from kit-code research)

The kit has **two asymmetric capture paths**, and **both must be wired** — neither alone is sufficient:

**PRIMARY — Path A (host preview + camera-switch + preview→publish reuse):**
`media_controller.dart:307` `startPreview()` is the only place the kit calls `LocalVideoTrack.createCameraTrack` directly. Add the processor to that inline `CameraCaptureOptions`:

```dart
// media_controller.dart, inside startPreview (currently lines 307-312)
_previewTrack = await LocalVideoTrack.createCameraTrack(
  CameraCaptureOptions(
    cameraPosition: position,
    params: VideoParametersPresets.h720_169,
    processor: _buildVideoProcessor?.call(), // <-- ADD (factory, fresh instance)
  ),
);
```

This single spot covers **preview, `switchCamera`, AND `goLive`'s `publishVideoTrack(preview)` reuse** because:
- `createCameraTrack` applies `options.processor` via `setProcessor` (`video.dart:202-204`).
- `switchCamera → setCameraPosition → restartTrack` **preserves `_processor`** across the front/back flip (`local.dart:306-326` captures `final processor = _processor;`, `stopProcessor()`, then `if (processor != null) await setProcessor(processor)`). **No extra wiring for the switch.**
- `goLive` reuses the same preview `LocalVideoTrack` object (`media_controller.dart:348`), and `publishVideoTrack` honors the attached processor (`track.processor?.onPublish(room)`, `local.dart:526`).

**SECONDARY — Path B (autoHostCamera, audience go-live, reconnect, any `setCameraEnabled(true)`):**
`room_manager.dart:130-133` builds `RoomOptions.defaultCameraCaptureOptions`. The SDK's `setSourceEnabled → createCameraTrack(defaultCameraCaptureOptions)` (`local.dart:811-813`) is what these paths use. Add the processor there:

```dart
// room_manager.dart:130-133
defaultCameraCaptureOptions: CameraCaptureOptions(
  cameraPosition: CameraPosition.front,
  params: VideoParametersPresets.h720_169,
  processor: _buildVideoProcessor?.call(), // <-- ADD
),
```

> **In-repo gotcha (verified):** `room_manager.dart:122` declares `roomOptions: const RoomOptions(...)`. Adding `processor: _buildVideoProcessor?.call()` is a runtime value, so **the `const` must be dropped** from this `RoomOptions`/`CameraCaptureOptions`. This is a 1-line change but easy to miss. (The kit-code research lists this site but does not flag the `const`; confirmed in the file.)

**Coverage of paths (kit-code research, caveat A):** "you must attach the processor in BOTH the preview options AND the RoomOptions defaults… neither alone is sufficient." Path A is created **without** reading RoomOptions defaults, so Path B's site does not cover preview, and vice-versa.

### 4.2 How the processor survives camera-switch / preview→publish / reconnect

| Event | Survival mechanism | Source |
|---|---|---|
| Front/back **camera switch** | `restartTrack` re-attaches `_processor` automatically | `local.dart:306-326` |
| **Preview → publish** | `goLive` reuses the same preview track object; processor already attached | `media_controller.dart:348`, `local.dart:526` |
| **Reconnect re-publish** | Path B recreates track from `defaultCameraCaptureOptions` (which now carries the factory) | `media_controller.dart:134-148`, `local.dart:811-813` |
| **Render (tiles/PiP/mini)** | `VideoTrackRenderer` shows `processedTrack` automatically via `setProcessedTrack` | `live_tile_widget.dart:81` — **zero render changes** |

Because `setProcessor` calls `setProcessedTrack(processor.processedTrack)`, **rendering is processor-agnostic.** No changes to `live_tile_widget.dart`, `live_stage.dart`, mini-overlay, or OS PiP.

### 4.3 Minimal API changes to `utd_live_room_kit`

1. **`room_config.dart` — `UTDLiveRoomConfig`** (the public, app-facing config). Add a **factory field** (per §3.2 rationale — a factory, not a shared instance):
   ```dart
   /// Builds a fresh video-effects processor for each camera capture/restart.
   /// Null = no effects (current behavior, fully backward compatible).
   final TrackProcessor<VideoProcessorOptions> Function()? buildVideoProcessor;
   ```
   Non-breaking, additive.

2. **Plumb the factory** from `utd_live_room.dart` `connect()/_startHostFlow` → `UTDRoomController.connect()` params (`utd_room_controller.dart:194-203`) → into **both** `mediaController` (for Path A `startPreview`) **and** `roomManager.connect`'s `RoomOptions` (for Path B). `roomManager` is constructed inside `UTDRoomController()` (`utd_room_controller.dart:33`), so the factory is passed through the controller. Store it as `_buildVideoProcessor` on `UTDMediaController` and `UTDRoomManager`.

3. **Optional runtime toggle** (turn beauty on/off mid-live without re-capture) — `UTDMediaController.setVideoProcessor()`:
   ```dart
   // resolve active local camera track (published, else preview) and (de)attach
   Future<void> setVideoProcessor(TrackProcessor<VideoProcessorOptions>? p) async {
     final t = _roomManager.localParticipant
             ?.videoTrackPublications
             .where((pub) => pub.source == TrackSource.camera)
             .map((pub) => pub.track).whereType<LocalVideoTrack>().firstOrNull
         ?? _previewTrack;
     if (t == null) return;
     p == null ? await t.stopProcessor() : await t.setProcessor(p);
   }
   ```
   (Mirrors the `switchCamera` track-resolution at `media_controller.dart:273-277`.) For *intensity changes within an active effect*, prefer the cheap `processor.setSmoothing(...)` MethodChannel call over re-attaching a track.

> **No seat/state-machine changes.** The kit-code research confirms video capture/render is fully isolated from seats, roles, reconnection, and chat. None of those controllers' signatures change.

---

## 5. Native Implementation Details

### 5.1 iOS

- **GPU stack:** **Metal + Metal Performance Shaders** for the multi-pass beauty chain; **Core Image (`CIColorCube`/`CIColorCubeWithColorSpace`)** as the fast path for LUTs. **Do not use OpenGL ES** (deprecated since iOS 12). GPUImage3 is stalled — if accelerating, use **Harbeth** (Metal, MIT, CVPixelBuffer support) or MetalPetal.
- **Frame in:** `RTCVideoFrame` → buffer is `RTCCVPixelBuffer` (device, usually NV12 `kCVPixelFormatType_420YpCbCr8BiPlanarFullRange`) or `RTCI420Buffer` (simulator). **Handle both.**
- **Bridging:** `CVPixelBuffer → MTLTexture` via `CVMetalTextureCache` (zero-copy). For NV12 bind Y (R8) + CbCr (RG8) planes, convert YUV→RGB in-shader (BT.601/709). `RTCCVPixelBuffer` is *sometimes not* Metal-compatible → **allocate our own output `CVPixelBufferPool` with `kCVPixelBufferMetalCompatibilityKey: true`.**
- **Frame out:** render RGB→NV12 into a pooled, Metal-compatible `CVPixelBuffer`, wrap as `RTCCVPixelBuffer → RTCVideoFrame`.
- **Threading:** LiveKit processors run on a **dedicated serial queue and are NOT thread-safe.** Build `CIContext` / `MTLComputePipelineState` / `CVMetalTextureCache` / `CVPixelBufferPool` **once in `init`**, reuse per frame, never share across instances.
- **Rendering caveat (uncertain):** ZEGO recommends Platform-View over Texture rendering on iOS to avoid preview tearing with their engine. **Safe path:** this is a ZEGO/Express-specific note; the kit already uses LiveKit's `VideoTrackRenderer`. Watch for preview tearing during the iOS spike; if it appears, evaluate render mode then — do not pre-optimize.

### 5.2 Android

- **GPU stack:** **OpenGL ES 3.0 + GLSL** (matches WebRTC's OES texture model). GPUImage-for-Android is an acceptable filter-set accelerator. Vulkan is overkill.
- **Zero-copy path (preferred):** `VideoFrame.Buffer` arrives as **TextureBuffer (GL_TEXTURE_EXTERNAL_OES)** or NV12. Sample OES with `#extension GL_OES_EGL_image_external : require` + `samplerExternalOES`, and **transform UVs with `getTransformMatrix()` re-queried every frame.** OES textures can't be render targets → render into offscreen **FBO (GL_TEXTURE_2D)**; chain LUT + smoothing passes through ping-ponged FBOs.
- **Frame out:** wrap final `GL_TEXTURE_2D` into a `VideoFrame` via `SurfaceTextureHelper` (shared EGL context + `YuvConverter`). HW encoder takes the texture directly; SW encoder (VP8) calls `buffer.toI420()` (readback) — **avoid SW encoders.**
- **CPU fallback:** if I420/NV12 in system memory (simulator/SW), upload to `GL_TEXTURE_2D` and run the same shaders. **CPU per-pixel beauty cannot hold 30fps** — texture path always preferred.
- **Permissions/build:** CAMERA already present (the kit uses camera). If a vendor engine is added later, ProGuard keep rules per vendor (zego needed `-keep class **.zego.**{*;}`).

### 5.3 Frame formats & conversions (both platforms)

- WebRTC frames: I420 (universal fallback via `toI420()`), NV12 (iOS `RTCCVPixelBuffer`, Android HW), NV21, RGB, or texture handle.
- **Key cost rule:** YUV↔RGB conversions and **texture→CPU readback (`toI420`)** are the expensive steps. Keep everything **GPU-resident end-to-end** and let the **HW encoder consume the texture** — no readbacks.

### 5.4 Shader pipeline (the v1 beauty chain)

Multi-pass FBO chain; **build all pipeline state, samplers, weight uniforms, LUT textures once and reuse.** Order: **smoothing → (geometry, v2) → LUT/color** so the LUT grades finished skin.

- **Pass 0 — ingest/decode:** OES/CVPixelBuffer → RGB; YUV→RGB if needed (BT.601/709 in-shader).
- **Pass 1 — skin smoothing (frequency separation):**
  (a) downscale blur to ½×½ (big perf win, no visible loss);
  (b) edge-preserving blur (bilateral, or two separable Gaussians) → low-freq layer;
  (c) high-pass = `original.rgb − blurred.rgb + 0.5`;
  (d) composite low-freq + attenuated high-pass, blend back toward original by an `amount` uniform (0..1);
  (e) optional skin mask (YCbCr/CIELAB heuristic and/or MediaPipe landmarks) to protect eyes/lips/hair. Refs: YUCI/YUGPUImage HighPassSkinSmoothing (MIT). **Whitening** is a cheap color-curve adjustment folded into this composite.
- **Pass 2 — LUT color grade:** prefer native `sampler3D` where supported; otherwise **512×512 HALD level-8** 2D LUT with two adjacent-blue-slice `texture2D` samples + `mix()`, **filtering set to NEAREST** to avoid slice bleed. Generate neutral with `ffmpeg -f lavfi -i haldclutsrc=level=8 -frames:v 1 lut.png`, grade, ship PNG (same asset works in GLSL & Metal/Core Image).
- **Pass 3 — background blur:** MediaPipe Selfie Segmentation mask → blend sharp foreground over blurred background.
- **Pass N — output:** render to FBO/Metal-compatible CVPixelBuffer handed back to WebRTC.

### 5.5 Face detection for beauty

- **Engine:** **MediaPipe Face Landmarker** (Apache-2.0; 478 landmarks + 52 blendshapes; cross-platform; GPU; detect-then-track). Single codebase for skin masks (and later v2 reshaping/AR).
- **Async rule:** run detection **off the render loop** on a downscaled copy; **reuse the last landmark set if detection lags; never block frame delivery.**
- **iOS-only premium add-ons (later, optional):** Apple Vision (`VNDetectFaceLandmarksRequest`) or ARKit `ARFaceAnchor` (TrueDepth front only). Not the primary cross-platform path.
- **v1 note:** smoothing+LUT+blur ship usefully **without** landmarks (skin-color heuristic suffices for masking). Landmark-driven masking is a v1.x refinement; landmark-driven *geometry* is v2.

---

## 6. Effect Feature Set: v1 vs v2

| Effect | v1 | v2 | Build/Buy |
|---|---|---|---|
| LUT color filters | ✅ | — | Build |
| Skin smoothing (freq-sep/bilateral) | ✅ | — | Build |
| Whitening / brightness / warmth | ✅ | — | Build |
| Background **blur** (MediaPipe seg) | ✅ | — | Build |
| Skin-mask protection (landmark-driven) | ⚠️ v1.x | — | Build |
| 2D landmark stickers (glasses/hats) | ⬜ optional v1.5 | ✅ | Build |
| Rosy/sharpen/clarity/teeth/eye-bright | ⬜ | ✅ (some Build, some Buy) | Mixed |
| **Face reshaping** (eye/face/nose/jaw) | ❌ | ✅ | **Buy** (Banuba/XMagic) |
| **Makeup** (lipstick/eyeshadow/blush) | ❌ | ✅ | **Buy** |
| **3D stickers / AR masks / Animoji** | ❌ | ✅ | **Buy** |
| Background **replacement** (clean edges) | ❌ | ✅ | **Buy** |
| Chroma key / green screen | ❌ | ⬜ | (deferred) |

v1 covers "~60% of perceived value cheaply." v2 = drop a vendor adapter behind the unchanged `VideoEffectsProcessor`/`EffectsEngine` abstraction.

---

## 7. Licensing, Config, Performance, Fallbacks

### 7.1 Licensing / config
- **v1 (DIY):** zero license cost. GPUImage (BSD), MediaPipe (Apache-2.0), LUT assets self-authored. No per-MAU "success tax."
- **v2 (vendor):** if Banuba/XMagic/DeepAR is added, credentials/license keys live in **native config** behind the adapter (mirrors zego's `create(appID, appSign)` auth-at-init). Expose a Dart `configure({apiKey, ...})` on the vendor-specific subclass only; the core `VideoEffectsProcessor` API stays vendor-neutral. **Get written quotes scoped to iOS+Android, exact feature set, and MAU before committing.** XMagic is the only public-priced vendor ($999–$5,999/mo); Banuba/FaceUnity/BytePlus are quote-only; DeepAR is MAU-based with beauty as a **paid add-on**.

### 7.2 Performance budget
- **Targets:** **720p @ 30fps** (the kit already uses `h720_169`). 30fps = **~33ms/frame for ALL passes combined.** Keep the beauty chain to a few passes.
- **Cap resolution to what is published** and let LiveKit simulcast downscale (the kit already uses `adaptiveStream`/`dynacast`).
- **½×½ blur passes**, baked Gaussian weights, compute sample UVs in the vertex shader (minimize dependent texture reads on tiled mobile GPUs).
- **Reuse all GPU state** (CIContext / pipeline state / texture cache / buffer pool / GL programs / FBOs) created once.
- **Thermal-aware throttling:** watch `ProcessInfo.thermalState` (iOS) / `PowerManager` thermal status (Android); under throttle, drop to ½-res blur or a "lite" mode, cap fps. Sustained GPU+camera+encoder throttles "within minutes."
- **Pool output buffers** (`CVPixelBufferPool` / texture pool) to avoid per-frame alloc churn.

### 7.3 Fallbacks (graceful no-op)
- **Web:** `processor_web.dart` path — ship a Dart **passthrough** (`processedTrack = options.track`); no pixel work. `isSupported = false`.
- **Unsupported / low-end devices:** if native `attach` fails or device GPU is too weak (or thermal critical at start), return **passthrough** so video still publishes — never break the stream.
- **Simulator / SW-encoder path:** functional but slower (CPU/I420 readback); acceptable for dev, not the production path.
- **Effects master-off:** `setEnabled(false)` makes native a 1:1 copy (or returns the source track) — visually identical to no processor, validating the path cheaply.

---

## 8. Project Structure, Example App, Test Strategy

### 8.1 Structure — **federated plugin**

```
packages/utd_video_effects_kit/
├── lib/
│   ├── utd_video_effects_kit.dart            # public exports
│   └── src/
│       ├── video_effects_processor.dart      # abstract API (extends TrackProcessor)
│       ├── video_effects_processor_impl.dart # MethodChannel + processedTrack
│       ├── effects_state.dart
│       └── platform_interface.dart           # method/event channel contract
├── android/                                  # Kotlin: VideoProcessor, GLSL FBO chain, MediaPipe
│   └── src/main/assets/luts/                 # 512x512 HALD PNGs
├── ios/                                      # Swift: RTCVideoFrame interceptor, Metal/Core Image
│   └── Assets/luts/                          # same LUT PNGs (Create folders)
├── example/                                  # standalone demo: camera + sliders + LiveKit publish
└── test/ + integration_test/
```

Rationale: native pixel work on two platforms + shared Dart API is exactly the federated/plugin shape. (zego_effects_plugin is similarly plugin+native, Android+iOS only.)

### 8.2 Example app
A standalone LiveKit publisher: live camera tile + sliders for smoothing/whitening/background-blur + a LUT picker + an A/B "effects on/off" toggle. Doubles as the **manual perf/quality harness** and the iOS Platform-View-vs-Texture spike bed. Must run on **real devices** (simulator/emulator hit the I420/CPU fallback, which is not representative).

### 8.3 Test strategy
- **Unit (Dart):** `EffectsState` mapping; processor lifecycle (`init/restart/destroy`); **passthrough correctness** when `isSupported == false` (`processedTrack == options.track`); factory produces fresh instances.
- **Integration (`integration_test`, real device):** golden-frame comparison — capture a known input through the native chain (camera-off → `captureFrame()` snapshot, the only frame accessor) and assert LUT/smoothing deltas within tolerance.
- **Kit integration tests (in `utd_live_room_kit`):** assert the processor attaches across **all four paths** — preview, camera-switch (`restartTrack` survival), preview→publish, reconnect (Path B re-create) — by checking `localVideoTrack.processor != null` / `processedTrack` swap after each.
- **Perf harness (manual + scripted):** frame-time + thermal logging at 720p/30fps on a low, mid, and high-end device each for iOS & Android.
- **Regression guard:** a CI smoke test confirming `buildVideoProcessor == null` (default) leaves the kit's capture/publish behavior byte-identical to today (backward compat).

---

## 9. Phased Roadmap, Effort Estimates, Riskiest Unknowns

> Estimates assume one senior Flutter+native engineer; ranges reflect the spike outcomes. "wk" = engineer-weeks.

**Milestone 0 — Spikes (the riskiest unknowns first) — ~1.5–2.5 wk**
- 🔴 **#1 risk:** How does native code obtain camera frames and emit a `MediaStreamTrack` that LiveKit accepts as `processedTrack`? Prototype seam (a) WebRTC `VideoProcessor`/`CapturerObserver` hook vs (b) custom capturer, on **both** platforms, using `livekit-examples/flutter-filter-plugin-example` as the reference. **Deliverable:** a tint-shader passthrough proving end-to-end frame-in/frame-out on a real device. *If neither (a) nor (b) works cleanly, the whole approach is re-scoped here — fail fast.*
- 🟠 **#2 risk:** iOS preview tearing (the ZEGO Platform-View caveat) — confirm `VideoTrackRenderer` shows processed output without tearing.
- 🟠 **#3 risk:** Frame-format variance (NV12 vs OES vs I420/simulator) — confirm we handle device + simulator buffers.

**Milestone 1 — DIY core pipeline — ~3–4 wk**
- iOS Metal/Core Image chain + Android GLSL FBO chain (ingest, output, zero-copy).
- Effects: **LUT filter + skin smoothing + whitening.** LUT asset toolchain (ffmpeg HALD).
- Dart `VideoEffectsProcessor` impl + MethodChannel control. Passthrough fallback.

**Milestone 2 — Kit integration — ~1–1.5 wk**
- Add `buildVideoProcessor` to `UTDLiveRoomConfig`; plumb to `startPreview` (Path A) **and** `RoomOptions` defaults (Path B, drop `const`). Optional `setVideoProcessor` runtime toggle.
- Verify survival across switch/publish/reconnect; confirm zero render changes.

**Milestone 3 — Background blur + perf/thermal — ~2–3 wk**
- MediaPipe Selfie Segmentation (async, off-loop) + blend shader.
- ½-res blur, state reuse, buffer pools, thermal throttling, "lite" mode. Device perf pass.

**Milestone 4 — Hardening, example, tests, docs — ~1.5–2 wk**
- Example app polish, test suite, web/unsupported no-op, backward-compat smoke test.

**v1 total ≈ 9–13 wk.**

**v2 (later, vendor adapter) — ~3–5 wk per vendor** — define native `EffectsEngine`, implement `BanubaEffectsEngine` (OEP buffer/texture) **or** `XMagicEffectsEngine` (`process(textureId)`), wire license config, A/B against DIY. (Banuba's official Flutter plugin + OEP is the lowest-friction; XMagic if public pricing is decisive.)

---

## 10. Open Questions / Risks

1. **(Highest) Native frame seam — does flutter_webrtc actually let us emit a processed `MediaStreamTrack`?** The research is confident the *pattern* exists (native pipeline → `processedTrack`) and names reference repos, but explicitly notes flutter_webrtc 1.4.0 exposes external textures *only for rendering* and that the pure-Dart processor path is buggy/incomplete. **Resolve in Milestone 0; this gates everything.**
2. **LiveKit version drift.** Repo declares `^2.7.0`; resolved is **2.8.0** (per `pubspec.lock` and kit-code research). Processor code is byte-identical between 2.7.0/2.8.0, but pin/track LiveKit upgrades — the processor seam is the integration's load-bearing surface.
3. **`const RoomOptions` removal** (`room_manager.dart:122`) — small but mandatory; confirm no other code depends on that const.
4. **Processor lifecycle vs zego model.** LiveKit *destroys* the processor on every `restartTrack` (unlike zego's persistent Effects engine). The **factory** mitigates this, but native must cleanly `attach`/`detach` (zego's `initEnv`/`uninitEnv` discipline) on every camera switch/reconnect without leaks. Verify under rapid switch/reconnect churn.
5. **Per-feature face segmentation gap.** MediaPipe gives landmarks, not parsed lip/eye/skin regions — so v1 makeup/reshaping quality would be "amateur." This is *why* those are Buy/v2. Don't let scope creep pull them into v1.
6. **Thermal/battery on low-end devices** — sustained 720p/30fps + camera + encoder + GPU may throttle in minutes; the "lite"/throttle path is essential, not optional. Needs real low-end-device validation.
7. **iOS rendering mode** — the ZEGO Platform-View-over-Texture recommendation is engine-specific; whether it bites our LiveKit `VideoTrackRenderer` is unknown until the Milestone-0 spike.
8. **Web** — `processor_web.dart` differs from native; v1 is no-op there. If web effects are ever needed, that's a separate canvas→MediaStream effort (the DeepAR web model), out of scope.
9. **Vendor pricing is mostly opaque** — only XMagic is publicly tiered; Banuba/FaceUnity/BytePlus require sales engagement. The v2 timeline depends on procurement, not just engineering.

---

### Key files this plan touches in `utd_live_room_kit` (all absolute)
- `<repo>/packages/utd_live_room_kit/lib/src/controller/media_controller.dart` — `startPreview` (line 307, **primary inject**), `setVideoProcessor` (new), `switchCamera` track-resolution pattern (273-277).
- `<repo>/packages/utd_live_room_kit/lib/src/core/room_manager.dart` — `defaultCameraCaptureOptions` (130-133, **secondary inject**); **drop `const`** at line 122.
- `<repo>/packages/utd_live_room_kit/lib/src/controller/utd_room_controller.dart` — thread factory through `connect()` (194-203), controller ctor (33).
- `<repo>/packages/utd_live_room_kit/lib/src/models/room_config.dart` — add `buildVideoProcessor` field to `UTDLiveRoomConfig`.
- `<repo>/packages/utd_live_room_kit/lib/src/widgets/utd_live_room.dart` — forward `config.buildVideoProcessor` into controller (`connect`/`_startHostFlow`, 160-211).
- `<repo>/packages/utd_live_room_kit/lib/src/widgets/live_tile_widget.dart` — **no change** (`VideoTrackRenderer` line 81 renders `processedTrack` automatically).

### New package (to create)
- `<repo>/packages/utd_video_effects_kit/` — federated plugin (Dart API + Swift/Metal + Kotlin/GLSL + MediaPipe + LUT assets + example).