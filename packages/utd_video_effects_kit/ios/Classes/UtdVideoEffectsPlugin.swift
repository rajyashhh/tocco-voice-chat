import Flutter
import UIKit
import WebRTC
import flutter_webrtc

/// Native entry point for the video-effects pipeline (iOS).
///
/// Implements the in-place frame seam: we look up the flutter_webrtc
/// `LocalVideoTrack` by id (the same id LiveKit's TrackProcessor.init gives us)
/// via `FlutterWebRTCPlugin.sharedSingleton().localTracks`, and register an
/// `ExternalVideoProcessingDelegate` that color-grades each frame with a
/// 512×512 square LUT via CoreImage (`CIColorCubeWithColorSpace`). The processor
/// runs at the shared `RTCVideoSource`, so the effect shows in BOTH the local
/// preview and the encoded/published stream.
///
/// Channel contract (see VideoEffectsPlatform on the Dart side):
///  - isSupported -> Bool
///  - attach {trackId, state} -> {supported, sessionId}
///  - detach {sessionId}
///  - setEffects {sessionId, state}   (state.filterAsset = LUT asset key, or null)
public class UtdVideoEffectsPlugin: NSObject, FlutterPlugin {
  private static let channelName = "utd_video_effects_kit/control"

  private let registrar: FlutterPluginRegistrar
  private var sessions: [Int: Session] = [:]
  private var nextSessionId = 1

  private struct Session {
    let trackId: String
    let processor: LutVideoProcessor
  }

  init(registrar: FlutterPluginRegistrar) {
    self.registrar = registrar
    super.init()
  }

  public static func register(with registrar: FlutterPluginRegistrar) {
    let channel = FlutterMethodChannel(name: channelName, binaryMessenger: registrar.messenger())
    let instance = UtdVideoEffectsPlugin(registrar: registrar)
    registrar.addMethodCallDelegate(instance, channel: channel)
  }

  public func handle(_ call: FlutterMethodCall, result: @escaping FlutterResult) {
    let args = call.arguments as? [String: Any]
    switch call.method {
    case "isSupported":
      result(true)

    case "attach":
      guard let trackId = args?["trackId"] as? String, !trackId.isEmpty,
            let track = localVideoTrack(trackId) else {
        result(["supported": false]); return
      }
      let state = args?["state"] as? [String: Any] ?? [:]
      let processor = LutVideoProcessor()
      applyState(processor, state)
      track.addProcessing(processor)
      let id = nextSessionId; nextSessionId += 1
      sessions[id] = Session(trackId: trackId, processor: processor)
      result(["supported": true, "sessionId": id])

    case "detach":
      if let id = args?["sessionId"] as? Int, let session = sessions.removeValue(forKey: id) {
        localVideoTrack(session.trackId)?.removeProcessing(session.processor)
      }
      result(nil)

    case "setEffects":
      if let id = args?["sessionId"] as? Int, let session = sessions[id] {
        applyState(session.processor, args?["state"] as? [String: Any] ?? [:])
      }
      result(nil)

    default:
      result(FlutterMethodNotImplemented)
    }
  }

  private func localVideoTrack(_ trackId: String) -> LocalVideoTrack? {
    return FlutterWebRTCPlugin.sharedSingleton()?.localTracks?[trackId] as? LocalVideoTrack
  }

  /// Pushes Dart `EffectsState` into the native processor (loads the LUT once per change).
  private func applyState(_ processor: LutVideoProcessor, _ state: [String: Any]) {
    processor.enabled = state["enabled"] as? Bool ?? false
    processor.smoothing = Float(state["smoothing"] as? Double ?? 0)
    processor.whitening = Float(state["whitening"] as? Double ?? 0)
    // NOTE: skin-tone (skinColor) is Android-only for now — it needs a per-pixel
    // skin mask; the iOS CoreImage path will add it in a follow-up.
    let filterAsset = state["filterAsset"] as? String
    if filterAsset != processor.lutAssetKey {
      processor.lutAssetKey = filterAsset
      processor.setLut(filterAsset != nil ? loadLut(filterAsset!) : nil)
    }
  }

  /// Loads a 512×512 square-LUT PNG (a Flutter asset key) as a CGImage.
  private func loadLut(_ assetKey: String) -> CGImage? {
    // assetKey is e.g. "packages/utd_video_effects_kit/assets/luts/fresh.png".
    let key = registrar.lookupKey(forAsset: assetKey)
    guard let path = Bundle.main.path(forResource: key, ofType: nil),
          let image = UIImage(contentsOfFile: path)?.cgImage else {
      return nil
    }
    return image
  }
}
