import 'package:flutter/foundation.dart';

/// Sentinel so [EffectsState.copyWith] can distinguish "leave [filterKey]
/// unchanged" from "set [filterKey] to null" (clear the filter).
const Object _unset = Object();

/// Immutable snapshot of the active video-effect settings.
///
/// Mirrors the per-effect toggles in ZEGO's Effects SDK, reduced to the v1
/// feature set this package builds in-house (LUT color filter, skin smoothing,
/// whitening, background blur). All intensities are normalized to `0.0..1.0`.
@immutable
class EffectsState {
  /// Master switch. When false the native pipeline is a 1:1 copy (visually
  /// identical to no processor).
  final bool enabled;

  /// Skin-smoothing strength (frequency-separation / bilateral). `0` = off.
  final double smoothing;

  /// Whitening / brightness lift folded into the smoothing composite. `0` = off.
  final double whitening;

  /// Key of the active LUT (HALD CLUT) color filter, or null for none.
  final String? filterKey;

  /// Blend amount for [filterKey] (`0` = neutral, `1` = full grade).
  final double filterIntensity;

  /// Background-blur strength (MediaPipe selfie segmentation + blur). `0` = off.
  final double backgroundBlur;

  /// Key of the active skin-tone preset (dual-LUT skin grade), or null for none.
  /// See [VideoEffectsSkinTones].
  final String? skinColor;

  const EffectsState({
    this.enabled = false,
    this.smoothing = 0,
    this.whitening = 0,
    this.filterKey,
    this.filterIntensity = 1.0,
    this.backgroundBlur = 0,
    this.skinColor,
  });

  EffectsState copyWith({
    bool? enabled,
    double? smoothing,
    double? whitening,
    Object? filterKey = _unset,
    double? filterIntensity,
    double? backgroundBlur,
    Object? skinColor = _unset,
  }) {
    return EffectsState(
      enabled: enabled ?? this.enabled,
      smoothing: smoothing ?? this.smoothing,
      whitening: whitening ?? this.whitening,
      filterKey:
          identical(filterKey, _unset) ? this.filterKey : filterKey as String?,
      filterIntensity: filterIntensity ?? this.filterIntensity,
      backgroundBlur: backgroundBlur ?? this.backgroundBlur,
      skinColor:
          identical(skinColor, _unset) ? this.skinColor : skinColor as String?,
    );
  }

  /// Serialized form sent to the native pipeline over the method channel.
  Map<String, dynamic> toMap() => {
        'enabled': enabled,
        'smoothing': smoothing,
        'whitening': whitening,
        'filterKey': filterKey,
        'filterIntensity': filterIntensity,
        'backgroundBlur': backgroundBlur,
        'skinColor': skinColor,
      };

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EffectsState &&
          runtimeType == other.runtimeType &&
          enabled == other.enabled &&
          smoothing == other.smoothing &&
          whitening == other.whitening &&
          filterKey == other.filterKey &&
          filterIntensity == other.filterIntensity &&
          backgroundBlur == other.backgroundBlur &&
          skinColor == other.skinColor;

  @override
  int get hashCode => Object.hash(enabled, smoothing, whitening, filterKey,
      filterIntensity, backgroundBlur, skinColor);

  @override
  String toString() => 'EffectsState(enabled: $enabled, smoothing: $smoothing, '
      'whitening: $whitening, filterKey: $filterKey, '
      'filterIntensity: $filterIntensity, backgroundBlur: $backgroundBlur, '
      'skinColor: $skinColor)';
}
