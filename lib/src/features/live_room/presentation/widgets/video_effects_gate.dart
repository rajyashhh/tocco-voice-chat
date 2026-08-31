import 'package:general/src/core/index.dart';
import 'package:utd_video_effects_kit/utd_video_effects_kit.dart' as fx;

import '../live_room_data.dart';

/// Entitlement gate in front of the kit's ready-made [fx.VideoEffectsSheet].
///
/// Video effects are a separate PAID add-on. When this install isn't subscribed
/// the attached processor is a forced passthrough (`entitled == false`), so the
/// sheet's controls would silently do nothing. Broadcasting must never depend on
/// effects: block only THIS sheet with a clear message and never open an empty /
/// dead sheet. The processor driven is the session's single instance owned by
/// [LiveRoomData] — the same one attached to the live camera pipeline.
class VideoEffectsGate {
  static Future<void> show(BuildContext context) async {
    final fxProcessor = LiveRoomData.instance.videoEffects;
    if (fxProcessor == null || !fxProcessor.entitled) {
      Methods.showToast(
        context,
        message:
            Methods.getLang() == 'ar'
                ? 'الفلاتر والتجميل باقة إضافية غير مفعّلة على هذا التطبيق — البث يعمل بشكل طبيعي بدونها.'
                : 'Beauty & filters are an add-on that isn\'t activated on this app — your live works normally without them.',
        isError: false,
      );
      return;
    }
    return fx.VideoEffectsSheet.show(context, processor: fxProcessor);
  }
}
