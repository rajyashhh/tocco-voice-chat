package com.utd.video_effects

import android.graphics.Bitmap
import org.webrtc.JavaI420Buffer
import org.webrtc.VideoFrame

/**
 * Pure-CPU implementation of the effect chain — no OpenGL / EGL.
 *
 * Mirrors the fragment shader in [GlEffects] (LUT color filter + bilateral skin
 * smoothing + dual-LUT skin tone + whitening) but runs entirely over I420 pixels on
 * the CPU. Because every WebRTC frame can produce an I420 buffer via
 * `VideoFrame.Buffer.toI420()` (texture OR software capture), this path works on every
 * device regardless of camera API, and is immune to the EGL-context / OES fragility of
 * a texture-only GL path.
 *
 * Per frame: I420 → RGB → [smoothing] → [skin tone] → [LUT] → [whitening] → I420.
 * Scratch buffers are reused across frames; LUT PNGs are decoded once into 64³ cubes
 * for O(1) lookup. Heavier than the GL path — profile on low-end devices; downscale if
 * needed.
 */
class CpuEffects {

    @Volatile var smoothing = 0f
    @Volatile var whitening = 0f
    @Volatile var skinAmount = 0f

    // 64×64×64 RGB cubes (each entry 0xRRGGBB), built from the square-LUT PNGs.
    @Volatile private var lut: IntArray? = null
    @Volatile private var skinLut: IntArray? = null
    @Volatile private var bgLut: IntArray? = null

    // Reused scratch (capture thread only — frames are serialized).
    private var rgb = IntArray(0)     // YUV → RGB
    private var pre = IntArray(0)     // after smoothing
    private var outRgb = IntArray(0)  // after color effects
    private var bufW = 0
    private var bufH = 0

    // exp() weight table for the bilateral smoothing, indexed by luma diff (0..255).
    private val expW = FloatArray(256) { Math.exp(-it / 255.0 * 8.0).toFloat() }

    fun setLutBitmap(bmp: Bitmap?) {
        lut = bmp?.let { buildCube(it) }
        bmp?.recycle()
    }

    fun setSkinBitmaps(skin: Bitmap?, bg: Bitmap?) {
        skinLut = skin?.let { buildCube(it) }
        bgLut = bg?.let { buildCube(it) }
        skin?.recycle(); bg?.recycle()
    }

    fun dispose() { lut = null; skinLut = null; bgLut = null }

    /** Decodes a square LUT PNG (8×8 tiles) into a 64³ cube. Tolerant of non-512 sizes. */
    private fun buildCube(bmp: Bitmap): IntArray {
        val w = bmp.width
        val h = bmp.height
        val px = IntArray(w * h)
        bmp.getPixels(px, 0, w, 0, 0, w, h)
        val tileW = w / 8   // 64 for a 512px LUT
        val tileH = h / 8
        val cube = IntArray(64 * 64 * 64)
        for (b in 0 until 64) {
            val baseX = (b % 8) * tileW
            val baseY = (b / 8) * tileH
            for (g in 0 until 64) {
                val sy = baseY + g * tileH / 64
                val row = sy * w
                val cb = b * 4096 + g * 64
                for (r in 0 until 64) {
                    val sx = baseX + r * tileW / 64
                    cube[cb + r] = px[row + sx] and 0xFFFFFF
                }
            }
        }
        return cube
    }

    private fun ensure(w: Int, h: Int) {
        if (bufW == w && bufH == h) return
        rgb = IntArray(w * h)
        pre = IntArray(w * h)
        outRgb = IntArray(w * h)
        bufW = w
        bufH = h
    }

    /**
     * Applies the active effects to [src] and returns a fresh I420 buffer, or null when
     * no effect is active (the caller then passes the original frame through untouched).
     */
    fun render(src: VideoFrame.I420Buffer): VideoFrame.Buffer? {
        val lutC = lut
        val skinC = skinLut
        val bgC = bgLut
        val doSkin = skinC != null && bgC != null && skinAmount > 0f
        val doSmooth = smoothing > 0f
        val wh = whitening
        if (lutC == null && !doSkin && !doSmooth && wh <= 0f) return null

        val w = src.width
        val h = src.height
        if (w <= 0 || h <= 0) return null
        ensure(w, h)

        // 1) YUV → RGB
        val dy = src.dataY; val du = src.dataU; val dv = src.dataV
        val sy = src.strideY; val su = src.strideU; val sv = src.strideV
        val rgb = this.rgb
        var i = 0
        for (y in 0 until h) {
            val yr = y * sy
            val chromaRow = y shr 1
            val ur = chromaRow * su
            val vr = chromaRow * sv
            for (x in 0 until w) {
                val yy = dy.get(yr + x).toInt() and 0xFF
                val cx = x shr 1
                val uu = du.get(ur + cx).toInt() and 0xFF
                val vv = dv.get(vr + cx).toInt() and 0xFF
                rgb[i++] = yuvToRgb(yy, uu, vv)
            }
        }

        // 2) smoothing → pre (or reuse rgb when off)
        val pre = if (doSmooth) { smooth(rgb, this.pre, w, h); this.pre } else rgb

        // 3) skin-tone → LUT → whitening
        val outRgb = this.outRgb
        val sa = skinAmount
        val n = w * h
        i = 0
        while (i < n) {
            val c = pre[i]
            var r = (c ushr 16) and 0xFF
            var g = (c ushr 8) and 0xFF
            var b = c and 0xFF

            if (doSkin) {
                val idx = (b shr 2) * 4096 + (g shr 2) * 64 + (r shr 2)
                val grad = if (skinMask(r, g, b)) skinC!![idx] else bgC!![idx]
                val gr = (grad ushr 16) and 0xFF
                val gg = (grad ushr 8) and 0xFF
                val gb = grad and 0xFF
                r = (r + (gr - r) * sa).toInt()
                g = (g + (gg - g) * sa).toInt()
                b = (b + (gb - b) * sa).toInt()
            }

            if (lutC != null) {
                val p = lutC[(b shr 2) * 4096 + (g shr 2) * 64 + (r shr 2)]
                r = (p ushr 16) and 0xFF
                g = (p ushr 8) and 0xFF
                b = p and 0xFF
            }

            if (wh > 0f) {
                // 0.6 ceiling (was 0.35 — read as no-op on real faces).
                r += ((255 - r) * 0.6f * wh).toInt()
                g += ((255 - g) * 0.6f * wh).toInt()
                b += ((255 - b) * 0.6f * wh).toInt()
            }

            outRgb[i] = (clamp(r) shl 16) or (clamp(g) shl 8) or clamp(b)
            i++
        }

        // 4) RGB → I420
        val out = JavaI420Buffer.allocate(w, h)
        val oy = out.dataY; val ou = out.dataU; val ov = out.dataV
        val osy = out.strideY; val osu = out.strideU; val osv = out.strideV

        // Y, full resolution
        i = 0
        for (y in 0 until h) {
            val yr = y * osy
            for (x in 0 until w) {
                val c = outRgb[i++]
                oy.put(yr + x, rgb2y((c ushr 16) and 0xFF, (c ushr 8) and 0xFF, c and 0xFF))
            }
        }
        // U/V, 2×2 averaged
        val cw = (w + 1) / 2
        val ch = (h + 1) / 2
        for (cy in 0 until ch) {
            val y0 = cy * 2
            val ur = cy * osu
            val vr = cy * osv
            for (cx in 0 until cw) {
                val x0 = cx * 2
                var sr = 0; var sg = 0; var sb = 0; var cnt = 0
                var dyy = 0
                while (dyy < 2) {
                    val py = y0 + dyy
                    if (py < h) {
                        var dxx = 0
                        while (dxx < 2) {
                            val pxx = x0 + dxx
                            if (pxx < w) {
                                val c = outRgb[py * w + pxx]
                                sr += (c ushr 16) and 0xFF
                                sg += (c ushr 8) and 0xFF
                                sb += c and 0xFF
                                cnt++
                            }
                            dxx++
                        }
                    }
                    dyy++
                }
                val ar = sr / cnt; val ag = sg / cnt; val ab = sb / cnt
                ou.put(ur + cx, rgb2u(ar, ag, ab))
                ov.put(vr + cx, rgb2v(ar, ag, ab))
            }
        }
        return out
    }

    /** Luminance-weighted bilateral over two rings of 8 neighbours (2px + 5px),
     *  blended by sqrt([smoothing]) — matches the GL shader's strengthened
     *  smoothWide (single 3x3 ring was imperceptible at full slider). */
    private fun smooth(src: IntArray, dst: IntArray, w: Int, h: Int) {
        val amt = Math.sqrt(smoothing.toDouble()).toFloat()
        for (y in 0 until h) {
            val row = y * w
            for (x in 0 until w) {
                val idx = row + x
                val c = src[idx]
                val cr = (c ushr 16) and 0xFF
                val cg = (c ushr 8) and 0xFF
                val cb = c and 0xFF
                val lum = 0.299f * cr + 0.587f * cg + 0.114f * cb
                var sumR = cr.toFloat(); var sumG = cg.toFloat(); var sumB = cb.toFloat()
                var wsum = 1f
                var k = 0
                while (k < 16) {
                    var nx = x + OFFX[k]; if (nx < 0) nx = 0 else if (nx >= w) nx = w - 1
                    var ny = y + OFFY[k]; if (ny < 0) ny = 0 else if (ny >= h) ny = h - 1
                    val s = src[ny * w + nx]
                    val sr = (s ushr 16) and 0xFF
                    val sg = (s ushr 8) and 0xFF
                    val sb = s and 0xFF
                    val slum = 0.299f * sr + 0.587f * sg + 0.114f * sb
                    var d = (slum - lum).toInt(); if (d < 0) d = -d; if (d > 255) d = 255
                    // outer ring (k >= 8) weighted at 60% like the GL path
                    val wgt = if (k < 8) expW[d] else expW[d] * 0.6f
                    sumR += sr * wgt; sumG += sg * wgt; sumB += sb * wgt; wsum += wgt
                    k++
                }
                val mr = sumR / wsum; val mg = sumG / wsum; val mb = sumB / wsum
                val rr = clamp((cr + (mr - cr) * amt).toInt())
                val gg = clamp((cg + (mg - cg) * amt).toInt())
                val bb = clamp((cb + (mb - cb) * amt).toInt())
                dst[idx] = (rr shl 16) or (gg shl 8) or bb
            }
        }
    }

    /** YCbCr skin-likelihood test (matches the GL shader's skinMask). */
    private fun skinMask(r: Int, g: Int, b: Int): Boolean {
        val rf = r / 255f; val gf = g / 255f; val bf = b / 255f
        val yl = 0.299f * rf + 0.587f * gf + 0.114f * bf
        val cb = (bf - yl) * 0.564f + 0.5f
        val cr = (rf - yl) * 0.713f + 0.5f
        return cr in 0.33f..0.50f && cb in 0.33f..0.52f
    }

    private fun yuvToRgb(y: Int, u: Int, v: Int): Int {
        val c = y - 16; val d = u - 128; val e = v - 128
        val r = clamp((298 * c + 409 * e + 128) shr 8)
        val g = clamp((298 * c - 100 * d - 208 * e + 128) shr 8)
        val b = clamp((298 * c + 516 * d + 128) shr 8)
        return (r shl 16) or (g shl 8) or b
    }

    private fun rgb2y(r: Int, g: Int, b: Int): Byte =
        (clamp(((66 * r + 129 * g + 25 * b + 128) shr 8) + 16)).toByte()

    private fun rgb2u(r: Int, g: Int, b: Int): Byte =
        (clamp(((-38 * r - 74 * g + 112 * b + 128) shr 8) + 128)).toByte()

    private fun rgb2v(r: Int, g: Int, b: Int): Byte =
        (clamp(((112 * r - 94 * g - 18 * b + 128) shr 8) + 128)).toByte()

    private fun clamp(v: Int): Int = if (v < 0) 0 else if (v > 255) 255 else v

    companion object {
        // 8-neighbour offsets at a 2px radius (matches the shader's o * texel * 2.0).
        private val OFFX =
            intArrayOf(2, -2, 0, 0, 2, -2, 2, -2, 5, -5, 0, 0, 5, -5, 5, -5)
        private val OFFY =
            intArrayOf(0, 0, 2, -2, 2, 2, -2, -2, 0, 0, 5, -5, 5, 5, -5, -5)
    }
}
