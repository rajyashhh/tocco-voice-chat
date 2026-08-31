import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:general/src/core/cache/image_cache_manager.dart';
import 'package:general/src/core/constants/asset_manager.dart';
import 'package:general/src/core/constants/end_points.dart';

/// THE single coin (currency) image for the whole app.
///
/// Source of truth: the admin-panel "Coin Image" (Settings -> Brand), which the
/// backend mirrors to `<storage_url>/coin.png`. Every screen that shows the
/// currency coin renders THIS widget, so the owner's uploaded image appears
/// app-wide from one place — no per-screen baked icon drift.
///
/// Resilience: while the network image hasn't resolved yet (first boot before
/// `/config/settings`, offline, or the panel image was never uploaded) the
/// widget shows [fallbackAsset] (a baked asset) so a coin slot is NEVER blank.
/// Once the panel image lands, every live [CoinIcon] flips to it at once.
class CoinIcon extends StatefulWidget {
  /// Square shortcut — sets both width and height.
  final double? size;
  final double? width;
  final double? height;
  final BoxFit fit;

  /// Baked-asset fallback shown until the panel image resolves. Defaults to
  /// the canonical baked coin (`assets/icons/coin.png`).
  final String? fallbackAsset;

  const CoinIcon({
    super.key,
    this.size,
    this.width,
    this.height,
    this.fit = BoxFit.contain,
    this.fallbackAsset,
  });

  // ── Shared, app-wide resolution of the panel coin image ──────────────────
  // One download/disk-lookup serves every CoinIcon instance. `_rev` bumps when
  // the resolved file appears or its BYTES change (session refresh), so all
  // mounted instances rebuild together.
  static File? _resolved;
  static bool _loading = false;
  static bool _refreshedThisSession = false;
  static final ValueNotifier<int> _rev = ValueNotifier<int>(0);

  static String get _url => '${EndPoints.storageURL}coin.png';

  /// Bumped whenever the resolved panel coin image appears/changes. Decoration
  /// call sites (DecorationImage/ImageProvider slots) listen to this and
  /// rebuild with [imageProvider].
  static ValueListenable<int> get revision => _rev;

  /// ImageProvider form of the unified coin for Decoration/ImageProvider slots
  /// (tab indicators, DecorationImage, watermarks). Same panel-first,
  /// baked-fallback rule as the widget. Kicks off resolution if needed.
  static ImageProvider imageProvider({String? fallbackAsset}) {
    _ensureLoaded();
    final file = _resolved;
    if (file != null) return FileImage(file);
    return AssetImage(fallbackAsset ?? AssetsManager.coin);
  }

  static Future<void> _ensureLoaded() async {
    // storageURL empty means settings haven't landed yet — retry on the next
    // instance build instead of caching a permanent failure.
    if (_loading || EndPoints.storageURL.isEmpty) return;
    if (_resolved != null && _refreshedThisSession) return;
    _loading = true;
    try {
      final cache = AssetCacheManager();
      if (_resolved == null) {
        final file = await cache.getCachedAsset(_url);
        if (file != null) {
          _resolved = file;
          _rev.value++;
        }
      }
      // The panel mirror overwrites coin.png IN PLACE (same key), so a
      // disk-cache hit can be stale after the owner uploads a new coin. Force
      // ONE re-download per session; if the bytes changed, evict the stale
      // decoded image and bump so every instance repaints the new coin.
      if (!_refreshedThisSession) {
        _refreshedThisSession = true;
        final fresh = await cache.downloadWithProgress(_url);
        if (fresh != null) {
          await FileImage(fresh).evict();
          _resolved = fresh;
          _rev.value++;
        }
      }
    } catch (_) {
      // Keep the fallback; a later instance retries.
      _refreshedThisSession = false;
    } finally {
      _loading = false;
    }
  }

  @override
  State<CoinIcon> createState() => _CoinIconState();
}

class _CoinIconState extends State<CoinIcon> {
  @override
  void initState() {
    super.initState();
    CoinIcon._ensureLoaded();
  }

  @override
  Widget build(BuildContext context) {
    final w = widget.size ?? widget.width;
    final h = widget.size ?? widget.height;
    return ValueListenableBuilder<int>(
      valueListenable: CoinIcon._rev,
      builder: (context, rev, _) {
        final file = CoinIcon._resolved;
        if (file != null) {
          return Image.file(
            file,
            key: ValueKey('coin-icon-$rev'),
            width: w,
            height: h,
            fit: widget.fit,
            gaplessPlayback: true,
            errorBuilder: (context, error, stackTrace) => _fallback(w, h),
          );
        }
        return _fallback(w, h);
      },
    );
  }

  Widget _fallback(double? w, double? h) => Image.asset(
        widget.fallbackAsset ?? AssetsManager.coin,
        width: w,
        height: h,
        fit: widget.fit,
        errorBuilder: (context, error, stackTrace) =>
            SizedBox(width: w, height: h),
      );
}
