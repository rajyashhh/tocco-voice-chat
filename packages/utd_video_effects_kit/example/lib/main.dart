import 'package:flutter/material.dart';
import 'package:livekit_client/livekit_client.dart';
import 'package:utd_video_effects_kit/utd_video_effects_kit.dart';

/// Minimal demo: open the camera with a [VideoEffectsProcessor] attached and
/// drive its effect sliders live.
///
/// Run on a REAL DEVICE — simulators/emulators hit the I420/CPU fallback and
/// (once the native pipeline exists) won't be representative. Platform runners
/// (android/ ios/) are generated with `flutter create .` in this directory.
void main() => runApp(const _EffectsDemoApp());

class _EffectsDemoApp extends StatelessWidget {
  const _EffectsDemoApp();

  @override
  Widget build(BuildContext context) {
    return const MaterialApp(
      debugShowCheckedModeBanner: false,
      home: _EffectsDemoPage(),
    );
  }
}

class _EffectsDemoPage extends StatefulWidget {
  const _EffectsDemoPage();

  @override
  State<_EffectsDemoPage> createState() => _EffectsDemoPageState();
}

class _EffectsDemoPageState extends State<_EffectsDemoPage> {
  final VideoEffectsProcessor _fx = VideoEffectsProcessor.create();
  LocalVideoTrack? _track;

  @override
  void initState() {
    super.initState();
    _start();
  }

  Future<void> _start() async {
    final track = await LocalVideoTrack.createCameraTrack(
      CameraCaptureOptions(processor: _fx),
    );
    await _fx.setEnabled(true);
    if (mounted) setState(() => _track = track);
  }

  @override
  void dispose() {
    _track?.stop();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final track = _track;
    return Scaffold(
      appBar: AppBar(title: const Text('Video Effects Demo')),
      body: Column(
        children: [
          Expanded(
            child: track == null
                ? const Center(child: CircularProgressIndicator())
                : VideoTrackRenderer(track,
                    mirrorMode: VideoViewMirrorMode.mirror),
          ),
          if (!_fx.isSupported)
            const Padding(
              padding: EdgeInsets.all(8),
              child: Text(
                'Native effects pipeline not available — running passthrough.',
                style: TextStyle(color: Colors.orange),
                textAlign: TextAlign.center,
              ),
            ),
          ValueListenableBuilder<EffectsState>(
            valueListenable: _fx.state,
            builder: (_, s, __) => Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                children: [
                  _slider('Smoothing', s.smoothing, _fx.setSmoothing),
                  _slider('Whitening', s.whitening, _fx.setWhitening),
                  _slider('Background blur', s.backgroundBlur,
                      _fx.setBackgroundBlur),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _slider(String label, double value, ValueChanged<double> onChanged) {
    return Row(
      children: [
        SizedBox(width: 130, child: Text(label)),
        Expanded(child: Slider(value: value, onChanged: onChanged)),
      ],
    );
  }
}
