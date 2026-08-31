/// A bundled LUT color filter.
///
/// Each filter is a 512×512 square LUT PNG (the same format ZEGO's
/// ColorfulStyle / FaceWhitening resources ship in) consumed by the native LUT
/// pass. [asset] is a Flutter asset key resolvable with `rootBundle` /
/// `AssetImage` from any package in the app.
class VideoEffectFilter {
  /// Stable key passed to [VideoEffectsProcessor.setFilter]. e.g. `fresh`.
  final String key;

  /// Human-readable label for pickers. e.g. `Fresh`.
  final String label;

  /// Packaged asset path of the 512×512 LUT PNG.
  final String asset;

  const VideoEffectFilter(this.key, this.label, this.asset);
}

/// Catalog of the LUT color filters bundled with this package (sourced from the
/// ZEGO ColorfulStyle + FaceWhitening resource set, which are plain square LUTs).
///
/// Only LUT-based effects live here — they run in the DIY native pipeline with
/// no third-party engine. The other ZEGO resources (makeup `.lua`, 3D pendants,
/// `.model` AI files, skin-color/rosy/clarity) are engine-proprietary and are
/// NOT part of this catalog; see README "Using the ZEGO asset set".
class VideoEffectsFilters {
  VideoEffectsFilters._();

  static const String _dir = 'packages/utd_video_effects_kit/assets/luts';

  /// All bundled LUT filters, in display order.
  static const List<VideoEffectFilter> all = [
    VideoEffectFilter('fresh', 'Fresh', '$_dir/fresh.png'),
    VideoEffectFilter('night', 'Night', '$_dir/night.png'),
    VideoEffectFilter('autumn', 'Autumn', '$_dir/autumn.png'),
    VideoEffectFilter('brighten', 'Brighten', '$_dir/brighten.png'),
    VideoEffectFilter('sunset', 'Sunset', '$_dir/sunset.png'),
    VideoEffectFilter('cool', 'Cool', '$_dir/cool.png'),
    VideoEffectFilter('sweet', 'Sweet', '$_dir/sweet.png'),
    VideoEffectFilter('cozily', 'Cozily', '$_dir/cozily.png'),
    VideoEffectFilter('creamy', 'Creamy', '$_dir/creamy.png'),
    VideoEffectFilter('film_like', 'Film', '$_dir/film_like.png'),
    // Global-style LUTs lifted from the ZEGO beauty resources (each a single
    // `type:"style"` LUT PNG — no face mask / engine needed).
    VideoEffectFilter('whitening', 'Whitening', '$_dir/whitening.png'),
    VideoEffectFilter('rosy', 'Rosy', '$_dir/rosy.png'),
    VideoEffectFilter('clarity', 'Clarity', '$_dir/clarity.png'),
    // Owner-supplied pack (2026-06-10), converted from .cube to square LUT PNG.
    VideoEffectFilter('vivid_skin', 'Vivid Skin', '$_dir/vivid_skin.png'),
    VideoEffectFilter('romance', 'Romance', '$_dir/romance.png'),
    VideoEffectFilter('peachy', 'Peachy', '$_dir/peachy.png'),
    VideoEffectFilter('ocean_blue', 'Ocean Blue', '$_dir/ocean_blue.png'),
    VideoEffectFilter('cinema', 'Cinema', '$_dir/cinema.png'),
    VideoEffectFilter('classic_film', 'Classic Film', '$_dir/classic_film.png'),
    VideoEffectFilter('summer', 'Summer', '$_dir/summer.png'),
    VideoEffectFilter('tokyo', 'Tokyo', '$_dir/tokyo.png'),
    VideoEffectFilter('portra', 'Portra', '$_dir/portra.png'),
    VideoEffectFilter('infrared', 'Infrared', '$_dir/infrared.png'),
    VideoEffectFilter('crimson', 'Crimson', '$_dir/crimson.png'),
    VideoEffectFilter('spring', 'Spring', '$_dir/spring.png'),
    VideoEffectFilter('hollywood', 'Hollywood', '$_dir/hollywood.png'),
    VideoEffectFilter('madmax', 'Mad Max', '$_dir/madmax.png'),
  ];

  /// The asset path for a filter [key], or null for an unknown / cleared key.
  static String? assetFor(String? key) {
    if (key == null) return null;
    for (final f in all) {
      if (f.key == key) return f.asset;
    }
    return null;
  }

  /// Preview thumbnail (a portrait with the filter applied) for pickers.
  /// Generated offline into `assets/previews/<key>.jpg`. Pass null for the
  /// unfiltered "none" preview.
  static String previewFor(String? key) =>
      'packages/utd_video_effects_kit/assets/previews/${key ?? 'none'}.jpg';

  /// Preview thumbnail for a skin-tone preset (same portrait, skin LUT grade).
  static String skinPreviewFor(String? key) =>
      'packages/utd_video_effects_kit/assets/previews/${key == null ? 'none' : 'skin_$key'}.jpg';
}

/// A skin-tone preset (dual-LUT grade: a skin LUT applied over skin regions, a
/// background LUT elsewhere; from ZEGO's SkinColorResources). Both LUTs are
/// 512² square LUTs.
class VideoEffectSkinTone {
  final String key;       // 'nuanbai'
  final String label;     // 'Warm'
  final String skinAsset; // filter_skin.png
  final String bgAsset;   // filter_bg.png
  const VideoEffectSkinTone(this.key, this.label, this.skinAsset, this.bgAsset);
}

/// Catalog of bundled skin-tone presets (`assets/skin/<key>/`).
class VideoEffectsSkinTones {
  VideoEffectsSkinTones._();

  static const String _dir = 'packages/utd_video_effects_kit/assets/skin';

  static const List<VideoEffectSkinTone> all = [
    VideoEffectSkinTone('nuanbai', 'Warm', '$_dir/nuanbai/filter_skin.png', '$_dir/nuanbai/filter_bg.png'),
    VideoEffectSkinTone('lengbai', 'Cool', '$_dir/lengbai/filter_skin.png', '$_dir/lengbai/filter_bg.png'),
    VideoEffectSkinTone('fenbai', 'Pink', '$_dir/fenbai/filter_skin.png', '$_dir/fenbai/filter_bg.png'),
    VideoEffectSkinTone('xiaomai', 'Wheat', '$_dir/xiaomai/filter_skin.png', '$_dir/xiaomai/filter_bg.png'),
    VideoEffectSkinTone('meihei', 'Tan', '$_dir/meihei/filter_skin.png', '$_dir/meihei/filter_bg.png'),
  ];

  static VideoEffectSkinTone? byKey(String? key) {
    if (key == null) return null;
    for (final t in all) {
      if (t.key == key) return t;
    }
    return null;
  }
}
