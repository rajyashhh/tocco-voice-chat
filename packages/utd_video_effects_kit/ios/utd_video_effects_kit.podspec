Pod::Spec.new do |s|
  s.name             = 'utd_video_effects_kit'
  s.version          = '0.1.0'
  s.summary          = 'Real-time video filters & beauty effects for LiveKit / flutter_webrtc.'
  s.description      = <<-DESC
GPU video effects (LUT color grading, skin smoothing, whitening, background blur)
exposed as a livekit_client TrackProcessor. The native pipeline registers an
in-place frame processor on the flutter_webrtc LocalVideoTrack and color-grades
frames with CoreImage (CIColorCube).
                       DESC
  s.homepage         = 'https://your-domain.com'
  s.license          = { :type => 'Proprietary' }
  s.author           = { 'UTD' => 'dev@utd.example' }
  s.source           = { :path => '.' }
  s.source_files     = 'Classes/**/*'
  s.public_header_files = 'Classes/**/*.h'

  s.dependency 'Flutter'
  # MUST be the exact WebRTC build flutter_webrtc 1.4.0 + livekit_client 2.8.0 pin,
  # or pod install fails with a version conflict / two WebRTC binaries.
  s.dependency 'WebRTC-SDK', '144.7559.01'
  # Exposes FlutterWebRTCPlugin / LocalVideoTrack / ExternalVideoProcessingDelegate
  # (all public pod headers) for `import flutter_webrtc` from Swift.
  s.dependency 'flutter_webrtc'

  s.platform = :ios, '13.0'
  s.swift_version = '5.0'
  s.static_framework = true
  s.pod_target_xcconfig = {
    'DEFINES_MODULE' => 'YES',
    'SWIFT_VERSION' => '5.0',
    'EXCLUDED_ARCHS[sdk=iphonesimulator*]' => 'i386'
  }
end
