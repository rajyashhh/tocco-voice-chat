import CoreImage
import WebRTC
import flutter_webrtc

/// Applies a 512×512 square LUT to each frame via CoreImage `CIColorCubeWithColorSpace`.
///
/// `onFrame` runs on the WebRTC capture thread (under the VideoProcessingAdapter
/// lock) for every frame. It must return an `RTCVideoFrame` preserving rotation +
/// timestamp. When disabled or no LUT is set it returns the input unchanged.
///
/// All heavy state (CIContext, the cube CIFilter, the output buffer pool) is built
/// once and reused. The LUT-PNG → cube conversion follows CIColorCube ordering
/// (R fastest, then G, then B), with the 512×512 PNG laid out as 8×8 tiles of
/// 64×64 (blue = tile index).
public class LutVideoProcessor: NSObject, ExternalVideoProcessingDelegate {

  /// Master switch. When false, frames pass through untouched.
  @objc public var enabled: Bool = false

  /// Asset key of the currently-loaded LUT (so the plugin can skip reloads).
  @objc public var lutAssetKey: String?

  /// Skin-smoothing strength, 0..1 (Gaussian blur blended over the original).
  @objc public var smoothing: Float = 0

  /// Whitening / brightness lift, 0..1.
  @objc public var whitening: Float = 0

  private let ciContext: CIContext
  private let lock = NSLock()
  private var colorCube: CIFilter? // nil => passthrough

  private var pixelBufferPool: CVPixelBufferPool?
  private var poolW = 0
  private var poolH = 0
  private let sRGB = CGColorSpace(name: CGColorSpace.sRGB)!

  public override init() {
    self.ciContext = CIContext(options: [.cacheIntermediates: false])
    super.init()
  }

  /// Builds (or clears) the cube filter from a 512×512 square-LUT image.
  public func setLut(_ cgImage: CGImage?) {
    let filter: CIFilter?
    if let cg = cgImage {
      let cube = Self.makeCubeData(from: cg, dimension: 64)
      let f = CIFilter(name: "CIColorCubeWithColorSpace")
      f?.setValue(64, forKey: "inputCubeDimension")
      f?.setValue(cube, forKey: "inputCubeData")
      f?.setValue(sRGB, forKey: "inputColorSpace")
      filter = f
    } else {
      filter = nil
    }
    lock.lock(); colorCube = filter; lock.unlock()
  }

  // MARK: - ExternalVideoProcessingDelegate

  public func onFrame(_ frame: RTCVideoFrame) -> RTCVideoFrame {
    lock.lock()
    let cube = colorCube
    let on = enabled
    let sm = smoothing
    let wh = whitening
    lock.unlock()
    guard on, cube != nil || sm > 0 || wh > 0 else { return frame }

    // Source pixel buffer. Device camera frames are RTCCVPixelBuffer (zero-copy
    // into CIImage). The simulator / software path delivers RTCI420Buffer — we
    // pass those through unmodified (the simulator is not the production path; a
    // YUV→BGRA conversion can be added later if simulator effects are needed).
    guard let cv = frame.buffer as? RTCCVPixelBuffer else { return frame }
    let srcPixelBuffer = cv.pixelBuffer
    let w = CVPixelBufferGetWidth(srcPixelBuffer)
    let h = CVPixelBufferGetHeight(srcPixelBuffer)

    // Effect chain: smoothing → LUT → whitening.
    var img = CIImage(cvPixelBuffer: srcPixelBuffer)
    if sm > 0, let blur = CIFilter(name: "CIGaussianBlur"),
       let dissolve = CIFilter(name: "CIDissolveTransition") {
      blur.setValue(img, forKey: kCIInputImageKey)
      blur.setValue(Double(sm) * 6.0, forKey: kCIInputRadiusKey)
      if let blurred = blur.outputImage?.cropped(to: img.extent) {
        dissolve.setValue(img, forKey: kCIInputImageKey)
        dissolve.setValue(blurred, forKey: "inputTargetImage")
        dissolve.setValue(Double(sm), forKey: kCIInputTimeKey)
        if let o = dissolve.outputImage { img = o }
      }
    }
    if let cube = cube {
      cube.setValue(img, forKey: kCIInputImageKey)
      if let o = cube.outputImage { img = o }
    }
    if wh > 0, let cc = CIFilter(name: "CIColorControls") {
      cc.setValue(img, forKey: kCIInputImageKey)
      cc.setValue(Double(wh) * 0.12, forKey: kCIInputBrightnessKey)
      if let o = cc.outputImage { img = o }
    }
    let outImage = img

    // Render to a pooled BGRA buffer
    ensurePool(width: w, height: h)
    var dst: CVPixelBuffer?
    guard let pool = pixelBufferPool,
          CVPixelBufferPoolCreatePixelBuffer(kCFAllocatorDefault, pool, &dst) == kCVReturnSuccess,
          let outBuffer = dst else {
      return frame
    }
    ciContext.render(outImage, to: outBuffer,
                     bounds: CGRect(x: 0, y: 0, width: w, height: h),
                     colorSpace: sRGB)

    // 4) Wrap back, preserving rotation + timestamp
    let rtcBuffer = RTCCVPixelBuffer(pixelBuffer: outBuffer)
    return RTCVideoFrame(buffer: rtcBuffer, rotation: frame.rotation, timeStampNs: frame.timeStampNs)
  }

  // MARK: - Helpers

  private func ensurePool(width: Int, height: Int) {
    if pixelBufferPool != nil && poolW == width && poolH == height { return }
    poolW = width; poolH = height
    let attrs: [String: Any] = [
      kCVPixelBufferPixelFormatTypeKey as String: kCVPixelFormatType_32BGRA,
      kCVPixelBufferWidthKey as String: width,
      kCVPixelBufferHeightKey as String: height,
      kCVPixelBufferIOSurfacePropertiesKey as String: [:],
      kCVPixelBufferMetalCompatibilityKey as String: true,
    ]
    var pool: CVPixelBufferPool?
    CVPixelBufferPoolCreate(kCFAllocatorDefault, nil, attrs as CFDictionary, &pool)
    pixelBufferPool = pool
  }

  /// 512×512 square LUT (8×8 tiles of 64×64) → Float32 RGBA premultiplied cube,
  /// ordered with R fastest, then G, then B (what CIColorCube expects).
  private static func makeCubeData(from cg: CGImage, dimension d: Int) -> Data {
    let tilesPerRow = 8
    let tile = d              // 64
    let imgW = tilesPerRow * tile
    let imgH = tilesPerRow * tile
    var src = [UInt8](repeating: 0, count: imgW * imgH * 4)
    let cs = CGColorSpace(name: CGColorSpace.sRGB)!
    guard let ctx = CGContext(data: &src, width: imgW, height: imgH,
                              bitsPerComponent: 8, bytesPerRow: imgW * 4, space: cs,
                              bitmapInfo: CGImageAlphaInfo.noneSkipLast.rawValue) else {
      return Data()
    }
    // A CGBitmapContext has a LOWER-LEFT origin: drawing a (top-down) CGImage into it
    // writes the image's TOP row to the HIGHEST buffer row, leaving `src` vertically
    // MIRRORED vs. the source PNG. The cube reader below assumes a top-down buffer
    // (green 0 and blue-tile-row 0 at buffer row 0, matching the Android GL path). Flip
    // the context before drawing to undo the mirror — without this the cube is built from
    // a flipped LUT, scrambling green-within-tile and the blue tile rows, so the grade
    // comes out wrong (and near-identity for symmetric LUTs → filters look like a no-op).
    ctx.translateBy(x: 0, y: CGFloat(imgH))
    ctx.scaleBy(x: 1, y: -1)
    ctx.draw(cg, in: CGRect(x: 0, y: 0, width: imgW, height: imgH))

    var cube = [Float](repeating: 0, count: d * d * d * 4)
    var o = 0
    for b in 0..<d {
      let baseX = (b % tilesPerRow) * tile
      let baseY = (b / tilesPerRow) * tile
      for g in 0..<d {
        let py = baseY + g
        for r in 0..<d {
          let px = baseX + r
          let p = (py * imgW + px) * 4
          cube[o + 0] = Float(src[p + 0]) / 255.0
          cube[o + 1] = Float(src[p + 1]) / 255.0
          cube[o + 2] = Float(src[p + 2]) / 255.0
          cube[o + 3] = 1.0
          o += 4
        }
      }
    }
    return cube.withUnsafeBufferPointer { Data(buffer: $0) }
  }
}
