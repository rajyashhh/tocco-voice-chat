# Changelog

## 0.1.0 (GPU pipeline + M2)

- **Android: zero-copy OpenGL ES pipeline** (`GlEffects` + `EffectsVideoProcessor`)
  replacing the CPU/I420 path — OES→FBO passes, per-frame output texture, holds
  30fps. Effects: square-LUT color filter, bilateral skin smoothing, whitening
  lift, and **skin-tone** (dual-LUT with a YCbCr skin mask).
- **iOS M2:** CoreImage chain extended with Gaussian/dissolve smoothing +
  brightness whitening (skin-tone deferred — needs a CoreImage skin mask).
- **Dart:** `EffectsState.skinColor` + `VideoEffectsProcessor.setSkinColor` +
  `VideoEffectsSkinTones` catalog (5 presets). SkinColor textures now declared as
  assets (`assets/skin/**`).
- Android Kotlin verified to compile in the app Gradle context.

## 0.1.0 (native LUT)

- Native **in-place** frame seam: registers an `ExternalVideoFrameProcessing`
  (Android) / `ExternalVideoProcessingDelegate` (iOS) on the flutter_webrtc
  `LocalVideoTrack` (found by id via `FlutterWebRTCPlugin.sharedSingleton`), so
  effects appear in both the local preview and the encoded stream. `processedTrack`
  stays null by design.
- **LUT color filters implemented:** Android CPU/I420 LUT; iOS CoreImage
  `CIColorCubeWithColorSpace`. Loads `assets/luts/<key>.png` (512² square LUTs).
- 13 bundled filters (ColorfulStyle + whitening/rosy/clarity) from the ZEGO
  resource set (LUTs only — no ZEGO engine).
- Android plugin verified to compile in the app Gradle context (webrtc-sdk
  144.7559.01 + auto-wired `:flutter_webrtc`). iOS pending on-device build.
- SkinColor textures staged under `assets/skin/**` (blend documented in NATIVE.md;
  shader not yet implemented).

## 0.1.0 (scaffold)

- Initial scaffold.
- Public Dart API: `VideoEffectsProcessor` (a livekit_client
  `TrackProcessor<VideoProcessorOptions>`), `EffectsState`, and the
  `utd_video_effects_kit/control` method-channel contract.
- Safe-passthrough behavior: until the native GPU pipeline lands, `isSupported`
  is `false` and `processedTrack` is `null`, so the original camera frames flow
  unmodified and video always publishes.
- Android (Kotlin) + iOS (Swift) plugin scaffolds implementing the channel
  contract as passthrough, with documented Milestone-0/1 TODOs.
- Integrates with `utd_live_room_kit` via its `UTDLiveRoomConfig.buildVideoProcessor`
  factory — no dependency from the kit onto this package.
