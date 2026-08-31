package com.utd.video_effects

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import com.cloudwebrtc.webrtc.FlutterWebRTCPlugin
import com.cloudwebrtc.webrtc.video.LocalVideoTrack
import io.flutter.embedding.engine.plugins.FlutterPlugin
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import io.flutter.plugin.common.MethodChannel.MethodCallHandler
import io.flutter.plugin.common.MethodChannel.Result

/**
 * Native entry point for the video-effects pipeline (Android, GPU path).
 *
 * Looks up the flutter_webrtc [LocalVideoTrack] by id (the id LiveKit's
 * TrackProcessor.init hands us) via [FlutterWebRTCPlugin.sharedSingleton] and
 * registers an [EffectsVideoProcessor] that color-grades / smooths each frame on
 * the GPU. Because flutter_webrtc runs the processor at the shared VideoSource,
 * the effect shows in BOTH the local preview and the encoded/published stream.
 *
 * Channel contract (see VideoEffectsPlatform on the Dart side):
 *  - isSupported -> Bool
 *  - attach {trackId, state} -> {supported, sessionId}
 *  - detach {sessionId}
 *  - setEffects {sessionId, state}
 *
 * state keys: enabled, smoothing, whitening, skinAmount, filterAsset (LUT key),
 * skinSkinAsset, skinBgAsset.
 */
class UtdVideoEffectsPlugin : FlutterPlugin, MethodCallHandler {
    private lateinit var channel: MethodChannel
    private lateinit var context: Context
    private lateinit var flutterAssets: FlutterPlugin.FlutterAssets

    private val sessions = HashMap<Int, Session>()
    private var nextSessionId = 1

    /** The flutter_webrtc plugin instance of THIS engine, captured at attach
     *  time. `FlutterWebRTCPlugin.sharedSingleton` is a static that the LAST
     *  attached engine overwrites — the app's background-service engine
     *  (room notification) steals it right at room entry, so going through the
     *  singleton resolved the camera against an engine whose localTracks map is
     *  empty (the `registered keys=<empty>` failure). GeneratedPluginRegistrant
     *  registers flutter_webrtc before this plugin, so the snapshot here is the
     *  same-engine instance. */
    private var webrtcSameEngine: FlutterWebRTCPlugin? = null

    private class Session(val trackId: String, val processor: EffectsVideoProcessor)

    override fun onAttachedToEngine(binding: FlutterPlugin.FlutterPluginBinding) {
        context = binding.applicationContext
        flutterAssets = binding.flutterAssets
        channel = MethodChannel(binding.binaryMessenger, CHANNEL)
        channel.setMethodCallHandler(this)
        webrtcSameEngine = FlutterWebRTCPlugin.sharedSingleton
    }

    override fun onMethodCall(call: MethodCall, result: Result) {
        when (call.method) {
            "isSupported" -> result.success(true)

            "attach" -> {
                val trackId = call.argument<String>("trackId")
                @Suppress("UNCHECKED_CAST")
                val state = call.argument<Map<String, Any?>>("state") ?: emptyMap()
                if (trackId.isNullOrEmpty()) {
                    android.util.Log.w(TAG, "attach: empty trackId → passthrough")
                    result.success(mapOf("supported" to false)); return
                }
                val track = localVideoTrack(trackId)
                if (track == null) {
                    android.util.Log.w(
                        TAG,
                        "attach: getLocalTrack('$trackId') = null → passthrough. " +
                            "The track LiveKit handed us is not in flutter_webrtc's localTracks " +
                            "(sharedSingleton=${FlutterWebRTCPlugin.sharedSingleton != null}). " +
                            "registered keys=${registeredTrackKeys()}",
                    )
                    result.success(mapOf("supported" to false)); return
                }
                val processor = EffectsVideoProcessor()
                applyState(processor, state)
                track.addProcessor(processor)
                val id = nextSessionId++
                sessions[id] = Session(trackId, processor)
                android.util.Log.i(
                    TAG,
                    "attach: OK trackId='$trackId' sessionId=$id enabled=${processor.enabled} " +
                        "lut=${processor.lutAssetKey ?: "none"}",
                )
                result.success(mapOf("supported" to true, "sessionId" to id))
            }

            "detach" -> {
                val id = call.argument<Int>("sessionId")
                val session = if (id != null) sessions.remove(id) else null
                if (session != null) {
                    localVideoTrack(session.trackId)?.removeProcessor(session.processor)
                    session.processor.dispose()
                }
                result.success(null)
            }

            "setEffects" -> {
                val id = call.argument<Int>("sessionId")
                @Suppress("UNCHECKED_CAST")
                val state = call.argument<Map<String, Any?>>("state") ?: emptyMap()
                val session = if (id != null) sessions[id] else null
                if (session != null) {
                    applyState(session.processor, state)
                    android.util.Log.i(
                        TAG,
                        "setEffects: sessionId=$id enabled=${session.processor.enabled} " +
                            "smoothing=${session.processor.smoothing} whitening=${session.processor.whitening} " +
                            "lut=${session.processor.lutAssetKey ?: "none"}",
                    )
                } else {
                    android.util.Log.w(TAG, "setEffects: NO session for id=$id → effect change dropped")
                }
                result.success(null)
            }

            else -> result.notImplemented()
        }
    }

    override fun onDetachedFromEngine(binding: FlutterPlugin.FlutterPluginBinding) {
        for ((_, session) in sessions) {
            localVideoTrack(session.trackId)?.removeProcessor(session.processor)
            session.processor.dispose()
        }
        sessions.clear()
        channel.setMethodCallHandler(null)
    }

    private fun localVideoTrack(trackId: String): LocalVideoTrack? {
        // Same-engine instance first; static singleton as fallback (and adopt
        // whichever one actually resolves the track).
        val candidates = listOfNotNull(
            webrtcSameEngine,
            FlutterWebRTCPlugin.sharedSingleton,
        ).distinct()
        for (plugin in candidates) {
            val track = plugin.getLocalTrack(trackId) as? LocalVideoTrack
            if (track != null) {
                webrtcSameEngine = plugin
                return track
            }
        }
        return null
    }

    /** Debug aid for attach misses: reflectively lists the ids flutter_webrtc
     *  actually has in MethodCallHandlerImpl.localTracks, so a key mismatch is
     *  visible in one logcat line instead of being a black box. */
    private fun registeredTrackKeys(): String = try {
        val plugin = FlutterWebRTCPlugin.sharedSingleton ?: return "<no singleton>"
        val handlerField = FlutterWebRTCPlugin::class.java.getDeclaredField("methodCallHandler")
        handlerField.isAccessible = true
        val handler = handlerField.get(plugin) ?: return "<no handler>"
        val tracksField = handler.javaClass.getDeclaredField("localTracks")
        tracksField.isAccessible = true
        val map = tracksField.get(handler) as? Map<*, *> ?: return "<not a map>"
        map.entries.joinToString { "${it.key}:${it.value?.javaClass?.simpleName}" }
            .ifEmpty { "<empty>" }
    } catch (e: Throwable) {
        "<reflection failed: ${e.javaClass.simpleName}>"
    }

    /** Pushes Dart [EffectsState] into the native processor, loading assets on change. */
    private fun applyState(p: EffectsVideoProcessor, state: Map<String, Any?>) {
        p.enabled = state["enabled"] as? Boolean ?: false
        p.smoothing = toFloat(state["smoothing"])
        p.whitening = toFloat(state["whitening"])
        p.skinAmount = toFloat(state["skinAmount"])

        val filterAsset = state["filterAsset"] as? String
        if (filterAsset != p.lutAssetKey) {
            p.lutAssetKey = filterAsset
            p.setLutBitmap(if (filterAsset != null) loadBitmap(filterAsset) else null)
        }

        val skinAsset = state["skinSkinAsset"] as? String
        val bgAsset = state["skinBgAsset"] as? String
        if (skinAsset != p.skinAssetKey) {
            p.skinAssetKey = skinAsset
            if (skinAsset != null && bgAsset != null) {
                p.setSkinBitmaps(loadBitmap(skinAsset), loadBitmap(bgAsset))
            } else {
                p.setSkinBitmaps(null, null)
            }
        }
    }

    private fun toFloat(v: Any?): Float = when (v) {
        is Double -> v.toFloat()
        is Int -> v.toFloat()
        is Float -> v
        else -> 0f
    }

    /** Decodes a Flutter asset key (e.g. "packages/…/assets/luts/fresh.png") to a Bitmap. */
    private fun loadBitmap(assetKey: String): Bitmap? {
        return try {
            val path = flutterAssets.getAssetFilePathByName(assetKey)
            context.assets.open(path).use { BitmapFactory.decodeStream(it) }
        } catch (e: Exception) {
            android.util.Log.e(TAG, "loadBitmap: failed to load asset '$assetKey' → LUT/skin will be null", e)
            null
        }
    }

    companion object {
        private const val CHANNEL = "utd_video_effects_kit/control"
        private const val TAG = "UtdVideoFx"
    }
}
