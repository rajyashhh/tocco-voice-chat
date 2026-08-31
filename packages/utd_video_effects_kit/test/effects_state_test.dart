import 'package:flutter_test/flutter_test.dart';
import 'package:utd_video_effects_kit/utd_video_effects_kit.dart';

void main() {
  group('EffectsState', () {
    test('defaults are an inert (off) state', () {
      const s = EffectsState();
      expect(s.enabled, isFalse);
      expect(s.smoothing, 0);
      expect(s.whitening, 0);
      expect(s.filterKey, isNull);
      expect(s.filterIntensity, 1.0);
      expect(s.backgroundBlur, 0);
    });

    test('copyWith updates only the named fields', () {
      const s = EffectsState();
      final next = s.copyWith(enabled: true, smoothing: 0.5);
      expect(next.enabled, isTrue);
      expect(next.smoothing, 0.5);
      // untouched
      expect(next.whitening, 0);
      expect(next.filterIntensity, 1.0);
    });

    test('copyWith can set filterKey and can clear it back to null', () {
      const s = EffectsState();
      final withFilter = s.copyWith(filterKey: 'warm', filterIntensity: 0.8);
      expect(withFilter.filterKey, 'warm');
      expect(withFilter.filterIntensity, 0.8);

      // Passing null explicitly clears the filter (sentinel-backed copyWith).
      final cleared = withFilter.copyWith(filterKey: null);
      expect(cleared.filterKey, isNull);
      // intensity preserved because it wasn't passed
      expect(cleared.filterIntensity, 0.8);
    });

    test('copyWith without filterKey preserves the existing key', () {
      final s = const EffectsState().copyWith(filterKey: 'forest');
      final next = s.copyWith(smoothing: 0.3);
      expect(next.filterKey, 'forest');
    });

    test('toMap round-trips the fields the native pipeline reads', () {
      final s = const EffectsState().copyWith(
        enabled: true,
        smoothing: 0.6,
        whitening: 0.2,
        filterKey: 'soft',
        filterIntensity: 0.9,
        backgroundBlur: 0.4,
      );
      expect(s.toMap(), {
        'enabled': true,
        'smoothing': 0.6,
        'whitening': 0.2,
        'filterKey': 'soft',
        'filterIntensity': 0.9,
        'backgroundBlur': 0.4,
        'skinColor': null,
      });
    });

    test('skinColor can be set and cleared independently of filterKey', () {
      final s = const EffectsState().copyWith(filterKey: 'fresh', skinColor: 'nuanbai');
      expect(s.filterKey, 'fresh');
      expect(s.skinColor, 'nuanbai');
      final cleared = s.copyWith(skinColor: null);
      expect(cleared.skinColor, isNull);
      expect(cleared.filterKey, 'fresh'); // untouched
    });

    test('value equality + hashCode', () {
      final a = const EffectsState().copyWith(smoothing: 0.5, filterKey: 'warm');
      final b = const EffectsState().copyWith(smoothing: 0.5, filterKey: 'warm');
      final c = const EffectsState().copyWith(smoothing: 0.5, filterKey: 'cold');
      expect(a, equals(b));
      expect(a.hashCode, equals(b.hashCode));
      expect(a, isNot(equals(c)));
    });
  });
}
