package com.utd.video_effects

import android.graphics.Bitmap
import android.opengl.GLES11Ext
import android.opengl.GLES20
import org.webrtc.GlShader
import org.webrtc.GlUtil
import org.webrtc.RendererCommon
import org.webrtc.VideoFrame
import java.nio.FloatBuffer

/**
 * GPU effects renderer for WebRTC texture frames (zero-copy, no CPU readback).
 *
 * Two passes, run on the capture thread where the shared EGL context is already
 * current (lazy GL init on first [render]):
 *   1. OES → RGB: copy the camera's external texture into an intermediate 2D
 *      texture, applying the frame's transform matrix.
 *   2. Effects: sample the intermediate texture and apply smoothing → skin-color
 *      → LUT filter → whitening into a FRESH per-frame output texture.
 *
 * The output texture is generated per frame and deleted via the returned
 * [Output.release] (so a frame still held by the encoder is never overwritten).
 *
 * NOTE: written against io.github.webrtc-sdk:android:144.7559.01 public GL
 * helpers (GlShader, GlUtil, RendererCommon). Compiles in-app; the GL rendering
 * itself needs on-device validation (context/thread/OES specifics).
 */
class GlEffects {

    class Output(val textureId: Int, val width: Int, val height: Int)

    @Volatile var smoothing = 0f
    @Volatile var whitening = 0f
    @Volatile var skinAmount = 0f

    private var ready = false
    private var loggedGlError = false  // surface the first GL error once (see UtdVideoFx)

    private var copyProgram: GlShader? = null   // OES -> RGB
    private var effectProgram: GlShader? = null  // RGB -> RGB (effects)

    private var fbo = 0
    private var interTex = 0
    private var interW = 0
    private var interH = 0

    // LUT / skin textures (2D). 0 = not set.
    private var lutTex = 0
    private var skinTex = 0
    private var bgTex = 0
    private var neutralTex = 0

    private val fullQuad: FloatBuffer = GlUtil.createFloatBuffer(
        floatArrayOf(-1f, -1f, 1f, -1f, -1f, 1f, 1f, 1f)
    )
    private val texQuad: FloatBuffer = GlUtil.createFloatBuffer(
        floatArrayOf(0f, 0f, 1f, 0f, 0f, 1f, 1f, 1f)
    )

    // Pending LUT bitmaps to upload on the GL thread (set from any thread).
    @Volatile private var pendingLut: Bitmap? = null
    @Volatile private var pendingSkin: Bitmap? = null
    @Volatile private var pendingBg: Bitmap? = null
    @Volatile private var clearLut = false
    @Volatile private var clearSkin = false

    fun setLutBitmap(bmp: Bitmap?) { if (bmp == null) clearLut = true else pendingLut = bmp }
    fun setSkinBitmaps(skin: Bitmap?, bg: Bitmap?) {
        if (skin == null || bg == null) clearSkin = true else { pendingSkin = skin; pendingBg = bg }
    }

    /** Renders [input] through the effect chain. Returns null to signal passthrough. */
    fun render(input: VideoFrame.TextureBuffer): Output? {
        ensureGl()
        uploadPending()

        val w = input.width
        val h = input.height
        ensureInter(w, h)

        val texMatrix = RendererCommon.convertMatrixFromAndroidGraphicsMatrix(input.transformMatrix)
        val isOes = input.type == VideoFrame.TextureBuffer.Type.OES

        // ---- Pass 1: input texture -> intermediate RGB (apply transform) ----
        GLES20.glBindFramebuffer(GLES20.GL_FRAMEBUFFER, fbo)
        GLES20.glFramebufferTexture2D(
            GLES20.GL_FRAMEBUFFER, GLES20.GL_COLOR_ATTACHMENT0, GLES20.GL_TEXTURE_2D, interTex, 0
        )
        GLES20.glViewport(0, 0, w, h)
        val copy = copyProgram!!
        copy.useProgram()
        GLES20.glActiveTexture(GLES20.GL_TEXTURE0)
        GLES20.glBindTexture(
            if (isOes) GLES11Ext.GL_TEXTURE_EXTERNAL_OES else GLES20.GL_TEXTURE_2D, input.textureId
        )
        GLES20.glUniform1i(copy.getUniformLocation("sTex"), 0)
        GLES20.glUniformMatrix4fv(copy.getUniformLocation("uTexMatrix"), 1, false, texMatrix, 0)
        drawQuad(copy)

        // ---- Pass 2: intermediate -> fresh output texture (effects) ----
        val outTex = GlUtil.generateTexture(GLES20.GL_TEXTURE_2D)
        GLES20.glBindTexture(GLES20.GL_TEXTURE_2D, outTex)
        GLES20.glTexImage2D(
            GLES20.GL_TEXTURE_2D, 0, GLES20.GL_RGBA, w, h, 0,
            GLES20.GL_RGBA, GLES20.GL_UNSIGNED_BYTE, null
        )
        GLES20.glBindFramebuffer(GLES20.GL_FRAMEBUFFER, fbo)
        GLES20.glFramebufferTexture2D(
            GLES20.GL_FRAMEBUFFER, GLES20.GL_COLOR_ATTACHMENT0, GLES20.GL_TEXTURE_2D, outTex, 0
        )
        GLES20.glViewport(0, 0, w, h)
        val fx = effectProgram!!
        fx.useProgram()
        bind2D(0, interTex, fx, "sTex")
        bind2D(1, if (lutTex != 0) lutTex else neutralTex, fx, "sLut")
        bind2D(2, if (skinTex != 0) skinTex else neutralTex, fx, "sSkinLut")
        bind2D(3, if (bgTex != 0) bgTex else neutralTex, fx, "sBgLut")
        GLES20.glUniform2f(fx.getUniformLocation("uTexel"), 1f / w, 1f / h)
        GLES20.glUniform1f(fx.getUniformLocation("uSmoothing"), smoothing)
        GLES20.glUniform1f(fx.getUniformLocation("uWhitening"), whitening)
        GLES20.glUniform1f(fx.getUniformLocation("uLutMix"), if (lutTex != 0) 1f else 0f)
        GLES20.glUniform1f(fx.getUniformLocation("uSkin"), if (skinTex != 0) skinAmount else 0f)
        // identity matrix for the 2D pass
        GLES20.glUniformMatrix4fv(fx.getUniformLocation("uTexMatrix"), 1, false, IDENTITY, 0)
        drawQuad(fx)

        GLES20.glBindFramebuffer(GLES20.GL_FRAMEBUFFER, 0)
        GLES20.glBindTexture(GLES20.GL_TEXTURE_2D, 0)
        GLES20.glFinish()
        val err = GLES20.glGetError()
        if (err != GLES20.GL_NO_ERROR && !loggedGlError) {
            loggedGlError = true
            android.util.Log.e(
                TAG,
                "render: glGetError=0x${Integer.toHexString(err)} (w=$w h=$h oes=$isOes) — " +
                    "effect output is likely wrong/black; check EGL context currency on the capture thread.",
            )
        }
        return Output(outTex, w, h)
    }

    /** Deletes an output texture. MUST run on the GL thread. */
    fun releaseTexture(textureId: Int) {
        GLES20.glDeleteTextures(1, intArrayOf(textureId), 0)
    }

    private fun drawQuad(shader: GlShader) {
        shader.setVertexAttribArray("aPos", 2, fullQuad)
        shader.setVertexAttribArray("aTex", 2, texQuad)
        GLES20.glDrawArrays(GLES20.GL_TRIANGLE_STRIP, 0, 4)
    }

    private fun bind2D(unit: Int, tex: Int, shader: GlShader, name: String) {
        GLES20.glActiveTexture(GLES20.GL_TEXTURE0 + unit)
        GLES20.glBindTexture(GLES20.GL_TEXTURE_2D, tex)
        GLES20.glUniform1i(shader.getUniformLocation(name), unit)
    }

    private fun ensureGl() {
        if (ready) return
        // GlShader compiles/links here and THROWS on failure (e.g. no current EGL
        // context on this thread) — the throw propagates to EffectsVideoProcessor.onFrame's
        // catch, which now logs it under UtdVideoFx.
        copyProgram = GlShader(VERTEX, COPY_OES_FRAGMENT)
        effectProgram = GlShader(VERTEX, EFFECT_FRAGMENT)
        val fb = IntArray(1); GLES20.glGenFramebuffers(1, fb, 0); fbo = fb[0]
        neutralTex = makeNeutralTexture()
        ready = true
        android.util.Log.i(TAG, "ensureGl: shaders compiled, FBO ready — GL pipeline initialized.")
    }

    private fun ensureInter(w: Int, h: Int) {
        if (interTex != 0 && interW == w && interH == h) return
        if (interTex != 0) GLES20.glDeleteTextures(1, intArrayOf(interTex), 0)
        interTex = GlUtil.generateTexture(GLES20.GL_TEXTURE_2D)
        GLES20.glBindTexture(GLES20.GL_TEXTURE_2D, interTex)
        GLES20.glTexImage2D(
            GLES20.GL_TEXTURE_2D, 0, GLES20.GL_RGBA, w, h, 0,
            GLES20.GL_RGBA, GLES20.GL_UNSIGNED_BYTE, null
        )
        interW = w; interH = h
    }

    private fun uploadPending() {
        if (clearLut) { deleteTex(lutTex); lutTex = 0; clearLut = false }
        if (clearSkin) { deleteTex(skinTex); deleteTex(bgTex); skinTex = 0; bgTex = 0; clearSkin = false }
        pendingLut?.let { deleteTex(lutTex); lutTex = uploadBitmap(it); it.recycle(); pendingLut = null }
        pendingSkin?.let { deleteTex(skinTex); skinTex = uploadBitmap(it); it.recycle(); pendingSkin = null }
        pendingBg?.let { deleteTex(bgTex); bgTex = uploadBitmap(it); it.recycle(); pendingBg = null }
    }

    private fun deleteTex(tex: Int) { if (tex != 0) GLES20.glDeleteTextures(1, intArrayOf(tex), 0) }

    private fun uploadBitmap(bmp: Bitmap): Int {
        val tex = GlUtil.generateTexture(GLES20.GL_TEXTURE_2D)
        GLES20.glBindTexture(GLES20.GL_TEXTURE_2D, tex)
        // NEAREST filtering avoids blue-slice bleed in the square LUT.
        GLES20.glTexParameteri(GLES20.GL_TEXTURE_2D, GLES20.GL_TEXTURE_MIN_FILTER, GLES20.GL_LINEAR)
        GLES20.glTexParameteri(GLES20.GL_TEXTURE_2D, GLES20.GL_TEXTURE_MAG_FILTER, GLES20.GL_LINEAR)
        GLES20.glTexParameteri(GLES20.GL_TEXTURE_2D, GLES20.GL_TEXTURE_WRAP_S, GLES20.GL_CLAMP_TO_EDGE)
        GLES20.glTexParameteri(GLES20.GL_TEXTURE_2D, GLES20.GL_TEXTURE_WRAP_T, GLES20.GL_CLAMP_TO_EDGE)
        android.opengl.GLUtils.texImage2D(GLES20.GL_TEXTURE_2D, 0, bmp, 0)
        return tex
    }

    private fun makeNeutralTexture(): Int {
        val tex = GlUtil.generateTexture(GLES20.GL_TEXTURE_2D)
        GLES20.glBindTexture(GLES20.GL_TEXTURE_2D, tex)
        GLES20.glTexImage2D(
            GLES20.GL_TEXTURE_2D, 0, GLES20.GL_RGBA, 1, 1, 0,
            GLES20.GL_RGBA, GLES20.GL_UNSIGNED_BYTE,
            java.nio.ByteBuffer.wrap(byteArrayOf(0, 0, 0, -1))
        )
        return tex
    }

    fun dispose() {
        copyProgram?.release(); effectProgram?.release()
        copyProgram = null; effectProgram = null
        deleteTex(interTex); deleteTex(lutTex); deleteTex(skinTex); deleteTex(bgTex); deleteTex(neutralTex)
        interTex = 0; lutTex = 0; skinTex = 0; bgTex = 0; neutralTex = 0; interW = 0; interH = 0
        if (fbo != 0) { GLES20.glDeleteFramebuffers(1, intArrayOf(fbo), 0); fbo = 0 }
        ready = false
    }

    companion object {
        private const val TAG = "UtdVideoFx"

        private val IDENTITY = floatArrayOf(
            1f, 0f, 0f, 0f, 0f, 1f, 0f, 0f, 0f, 0f, 1f, 0f, 0f, 0f, 0f, 1f
        )

        private const val VERTEX = """
            attribute vec4 aPos;
            attribute vec4 aTex;
            uniform mat4 uTexMatrix;
            varying vec2 vTex;
            void main() {
              gl_Position = aPos;
              vTex = (uTexMatrix * aTex).xy;
            }
        """

        private const val COPY_OES_FRAGMENT = """
            #extension GL_OES_EGL_image_external : require
            precision mediump float;
            varying vec2 vTex;
            uniform samplerExternalOES sTex;
            void main() { gl_FragColor = texture2D(sTex, vTex); }
        """

        // Square (8x8 tiles of 64) LUT lookup — the canonical GPUImage shader.
        private const val EFFECT_FRAGMENT = """
            precision highp float;
            varying vec2 vTex;
            uniform sampler2D sTex;
            uniform sampler2D sLut;
            uniform sampler2D sSkinLut;
            uniform sampler2D sBgLut;
            uniform vec2 uTexel;
            uniform float uSmoothing;
            uniform float uWhitening;
            uniform float uLutMix;
            uniform float uSkin;

            vec3 lut(vec3 c, sampler2D tex) {
              float blue = c.b * 63.0;
              vec2 q1; q1.y = floor(floor(blue) / 8.0); q1.x = floor(blue) - q1.y * 8.0;
              vec2 q2; q2.y = floor(ceil(blue) / 8.0);  q2.x = ceil(blue)  - q2.y * 8.0;
              vec2 p1; p1.x = q1.x * 0.125 + 0.5/512.0 + (0.125 - 1.0/512.0) * c.r;
                       p1.y = q1.y * 0.125 + 0.5/512.0 + (0.125 - 1.0/512.0) * c.g;
              vec2 p2; p2.x = q2.x * 0.125 + 0.5/512.0 + (0.125 - 1.0/512.0) * c.r;
                       p2.y = q2.y * 0.125 + 0.5/512.0 + (0.125 - 1.0/512.0) * c.g;
              vec3 a = texture2D(tex, p1).rgb;
              vec3 b = texture2D(tex, p2).rgb;
              return mix(a, b, fract(blue));
            }

            // luminance-weighted bilateral over two rings of 8 neighbours
            // (2px + 5px) — the single 3x3 ring read as "barely on" at full
            // slider, so the reach was widened for a real smooth ceiling.
            vec3 smoothWide(vec3 c) {
              float lum = dot(c, vec3(0.299, 0.587, 0.114));
              vec3 sum = c; float wsum = 1.0;
              for (int i = 0; i < 8; i++) {
                vec2 o;
                if (i == 0) o = vec2(1.0, 0.0); else if (i == 1) o = vec2(-1.0, 0.0);
                else if (i == 2) o = vec2(0.0, 1.0); else if (i == 3) o = vec2(0.0, -1.0);
                else if (i == 4) o = vec2(1.0, 1.0); else if (i == 5) o = vec2(-1.0, 1.0);
                else if (i == 6) o = vec2(1.0, -1.0); else o = vec2(-1.0, -1.0);
                vec3 s1 = texture2D(sTex, vTex + o * uTexel * 2.0).rgb;
                float w1 = exp(-abs(dot(s1, vec3(0.299, 0.587, 0.114)) - lum) * 6.0);
                sum += s1 * w1; wsum += w1;
                vec3 s2 = texture2D(sTex, vTex + o * uTexel * 5.0).rgb;
                float w2 = exp(-abs(dot(s2, vec3(0.299, 0.587, 0.114)) - lum) * 6.0) * 0.6;
                sum += s2 * w2; wsum += w2;
              }
              return sum / wsum;
            }

            // simple YCbCr skin-likelihood mask
            float skinMask(vec3 c) {
              float y  = dot(c, vec3(0.299, 0.587, 0.114));
              float cb = (c.b - y) * 0.564 + 0.5;
              float cr = (c.r - y) * 0.713 + 0.5;
              float m = step(0.33, cr) * step(cr, 0.50) * step(0.33, cb) * step(cb, 0.52);
              return m;
            }

            void main() {
              vec3 c = texture2D(sTex, vTex).rgb;
              // sqrt response: mid-slider already feels strong, full = max.
              if (uSmoothing > 0.0) c = mix(c, smoothWide(c), sqrt(uSmoothing));
              if (uSkin > 0.0) {
                vec3 g = mix(lut(c, sBgLut), lut(c, sSkinLut), skinMask(c));
                c = mix(c, g, uSkin);
              }
              if (uLutMix > 0.0) c = mix(c, lut(c, sLut), uLutMix);
              if (uWhitening > 0.0) c = mix(c, c + (1.0 - c) * 0.6, uWhitening);
              gl_FragColor = vec4(clamp(c, 0.0, 1.0), 1.0);
            }
        """
    }
}
