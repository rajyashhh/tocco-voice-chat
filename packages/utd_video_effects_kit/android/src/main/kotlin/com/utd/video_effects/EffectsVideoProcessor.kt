package com.utd.video_effects

import android.graphics.Bitmap
import com.cloudwebrtc.webrtc.video.LocalVideoTrack
import org.webrtc.VideoFrame

/**
 * Pure-CPU video-effects processor.
 *
 * Every captured frame is converted to I420 via [VideoFrame.Buffer.toI420] (which works
 * for BOTH texture and software capture), run through the [CpuEffects] chain (LUT filter
 * + skin smoothing + skin tone + whitening), and returned as a fresh I420 frame. No
 * OpenGL / EGL is used, so this is immune to the capture-mode and GL-context fragility of
 * a texture-only path — it works on every device regardless of camera API.
 *
 * [onFrame] runs on the capture thread; flutter_webrtc chains the RETURN value of each
 * registered processor into the published stream AND the local preview, so the effect
 * shows in both. On any error it returns the input frame unchanged — video never breaks.
 */
class EffectsVideoProcessor : LocalVideoTrack.ExternalVideoFrameProcessing {

    @Volatile var enabled: Boolean = false

    /** Keys of the currently-loaded LUT / skin assets (so the plugin skips reloads). */
    @Volatile var lutAssetKey: String? = null
    @Volatile var skinAssetKey: String? = null

    private val cpu = CpuEffects()

    // Diagnostics — surface the first frame + each distinct passthrough reason once.
    private var frameCount = 0L
    private var loggedFirstFrame = false
    private var loggedToI420Null = false
    private var loggedRenderNull = false
    private var loggedOk = false

    var smoothing: Float
        get() = cpu.smoothing
        set(v) { cpu.smoothing = v }
    var whitening: Float
        get() = cpu.whitening
        set(v) { cpu.whitening = v }
    var skinAmount: Float
        get() = cpu.skinAmount
        set(v) { cpu.skinAmount = v }

    fun setLutBitmap(bmp: Bitmap?) = cpu.setLutBitmap(bmp)
    fun setSkinBitmaps(skin: Bitmap?, bg: Bitmap?) = cpu.setSkinBitmaps(skin, bg)

    override fun onFrame(frame: VideoFrame): VideoFrame {
        frameCount++
        if (!loggedFirstFrame) {
            loggedFirstFrame = true
            android.util.Log.i(
                TAG,
                "onFrame: FIRST frame — enabled=$enabled, buffer=${frame.buffer.javaClass.simpleName}, " +
                    "${frame.buffer.width}x${frame.buffer.height}, smoothing=${cpu.smoothing}, " +
                    "whitening=${cpu.whitening}, lut=${lutAssetKey ?: "none"} (CPU path)",
            )
        }
        if (!enabled) return frame

        val i420 = frame.buffer.toI420() ?: run {
            if (!loggedToI420Null) {
                loggedToI420Null = true
                android.util.Log.w(TAG, "onFrame: PASSTHROUGH — buffer.toI420() returned null.")
            }
            return frame
        }
        try {
            val out = cpu.render(i420) ?: run {
                if (!loggedRenderNull) {
                    loggedRenderNull = true
                    android.util.Log.i(TAG, "onFrame: passthrough — no active effect (enabled but all params neutral).")
                }
                return frame
            }
            if (!loggedOk) {
                loggedOk = true
                android.util.Log.i(TAG, "onFrame: CPU render OK — effect applied (${out.width}x${out.height}).")
            }
            return VideoFrame(out, frame.rotation, frame.timestampNs)
        } catch (e: Throwable) {
            android.util.Log.e(TAG, "onFrame: CPU render error → passthrough (frame=$frameCount)", e)
            return frame
        } finally {
            i420.release()
        }
    }

    fun dispose() = cpu.dispose()

    companion object {
        private const val TAG = "UtdVideoFx"
    }
}
