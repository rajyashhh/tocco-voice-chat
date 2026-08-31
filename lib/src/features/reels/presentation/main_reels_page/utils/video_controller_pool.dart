import 'dart:async';
import 'dart:developer' as dev;
import 'package:flutter/foundation.dart';
import 'package:general/src/core/cache/reels_cache_manager.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/utils/reels_audio_session.dart';
import 'package:video_player/video_player.dart';

typedef PositionCallback = void Function(Duration position);
typedef DurationCallback = void Function(Duration duration);
typedef ErrorCallback = void Function(int index);
typedef ControllerReadyCallback = void Function(int index);

/// Gate verbose logging. Always false in release; dev logs only in debug.
const bool _verbose = false;

void _log(String message) {
  if (kDebugMode && _verbose) {
    dev.log(message, name: 'VideoPool');
  }
}

/// Manages the window of [VideoPlayerController]s around the active reel.
///
/// ONE-master principle: this pool NEVER decides to play/pause for lifecycle.
/// The active widget is the sole code that calls `play()`. The pool only:
/// manages the init/dispose window + preload, emits position/duration for the
/// active index, surfaces per-index errors, applies volume (mute), and drives
/// the audio session activate/deactivate.
class VideoControllerPool {
  final Map<int, VideoPlayerController> _controllers = {};
  final Map<int, VoidCallback> _listeners = {};
  final Map<int, bool> _initializing = {};
  final Map<int, DateTime> _loadStartTimes = {};
  final Set<int> _errored = {};

  final PositionCallback onPositionUpdate;
  final bool preloadAdjacent;

  /// Fired when the active controller exposes a non-zero duration (after late
  /// init too, not just a synchronous read at page-change time).
  DurationCallback? onDurationReady;

  /// Fired when a controller fails to initialize or reports an error
  /// mid-playback. Carries the reel index.
  ErrorCallback? onError;

  /// Fired every time a controller finishes initializing and is stored in the
  /// window. The feed bloc republishes its controllers map on this signal so
  /// the UI binds the fresh controller immediately (no dependence on a later
  /// unrelated state change) — the root cause of "video sometimes never
  /// starts": the controller existed but the widget never learned about it.
  ControllerReadyCallback? onControllerReady;

  DateTime _lastPositionEmit = DateTime(0);
  bool _disposed = false;
  bool _isMuted = false;

  /// Last observed isPlaying of the ACTIVE controller; edge-detector for
  /// audio-session activate/deactivate.
  bool _lastActivePlaying = false;

  /// Re-entrancy token. Bumped at the top of [onPageChanged]; every async
  /// continuation (and the delayed-preload closure) bails if the token moved.
  int _pageGen = 0;

  /// The index window {prev, current, +1, +2} of the LATEST page-change.
  /// Init continuations keep their controller when the index is still inside
  /// this window (a fast swipe forward must NOT throw away the +1/+2 preloads
  /// it just paid for — the old gen-strict bail did exactly that, forcing a
  /// cold re-init on every fast swipe).
  Set<int> _window = const {};

  /// The reel currently shown. Tracked so a controller that finishes
  /// initializing AFTER the user already swiped to it still gets its volume
  /// applied and its duration surfaced for the right (active) index.
  int _currentIndex = -1;

  /// Last duration emitted for the active controller, so the listener doesn't
  /// re-fire [onDurationReady] every tick once the value is known.
  Duration _lastEmittedDuration = Duration.zero;

  VideoControllerPool({
    required this.onPositionUpdate,
    this.preloadAdjacent = true,
    this.onDurationReady,
    this.onError,
  });

  Map<int, VideoPlayerController> get controllers =>
      Map.unmodifiable(_controllers);

  VideoPlayerController? operator [](int index) => _controllers[index];

  /// The controller for the currently active reel, or null if not ready.
  VideoPlayerController? get activeController => _controllers[_currentIndex];

  int get currentIndex => _currentIndex;

  bool get isMuted => _isMuted;

  bool hasError(int index) => _errored.contains(index);

  /// Applies mute to the active controller (non-active stay silent) and
  /// remembers the choice for subsequent inits/page-changes.
  void setMuted(bool muted) {
    _isMuted = muted;
    _applyVolumes();
  }

  /// Active controller volume = mute ? 0 : 1; every non-active controller is
  /// always silent so preloaded/adjacent reels never bleed audio.
  void _applyVolumes() {
    for (final entry in _controllers.entries) {
      final isActive = entry.key == _currentIndex;
      entry.value.setVolume(isActive ? (_isMuted ? 0.0 : 1.0) : 0.0);
    }
  }

  /// Synchronous window move: disposes out-of-window controllers, applies
  /// volumes, and KICKS OFF inits without blocking the caller. The active
  /// reel's init can take seconds on a cold network — the feed must never
  /// await it before updating its own state (that stalled the whole feed and
  /// let a second swipe re-enter mid-await). Readiness is delivered through
  /// [onControllerReady] instead.
  void onPageChanged({
    required int newIndex,
    required List<String?> urls,
  }) {
    if (_disposed) return;

    final gen = ++_pageGen;

    _log('Page changed to #$newIndex | loaded=${_controllers.length}');

    _currentIndex = newIndex;
    _lastEmittedDuration = Duration.zero;
    _lastActivePlaying = false;

    // Window: previous + current + TWO ahead (TikTok-style look-ahead so the
    // next swipes land on an already-initialized controller). Cap = 4 live
    // controllers, bounded so we never exhaust the device's MediaCodecs.
    final windowIndices = <int>{newIndex};
    if (preloadAdjacent) {
      if (newIndex + 1 < urls.length) windowIndices.add(newIndex + 1);
      if (newIndex + 2 < urls.length) windowIndices.add(newIndex + 2);
      if (newIndex > 0) windowIndices.add(newIndex - 1);
    }
    _window = windowIndices;

    // Dispose controllers outside the window.
    final toRemove = _controllers.keys
        .where((i) => !windowIndices.contains(i))
        .toList();
    for (final i in toRemove) {
      _log('Disposing controller #$i');
      final listener = _listeners[i];
      if (listener != null) _controllers[i]?.removeListener(listener);
      _listeners.remove(i);
      _controllers[i]?.pause();
      _controllers[i]?.dispose();
      _controllers.remove(i);
      _loadStartTimes.remove(i);
      _errored.remove(i);
    }

    // Apply volume so the active reel is audible (subject to mute) and the
    // rest are silent. Late inits re-apply for themselves.
    // NOTE: we never call play() here — the widget does.
    _applyVolumes();

    // Surface duration synchronously if already known (late init handled in
    // the listener).
    _maybeEmitActiveDuration();

    // Active first, then +1 then +2 (sequential so the active reel wins the
    // bandwidth race). Never awaited by the caller.
    unawaited(() async {
      await _ensureInitialized(newIndex, urls);
      if (_disposed || gen != _pageGen || !preloadAdjacent) return;
      if (newIndex + 1 < urls.length) {
        await _ensureInitialized(newIndex + 1, urls);
      }
      if (_disposed || gen != _pageGen) return;
      if (newIndex + 2 < urls.length) {
        await _ensureInitialized(newIndex + 2, urls);
      }
    }());
  }

  Future<void> _ensureInitialized(int index, List<String?> urls) async {
    if (_disposed) return;
    if (_controllers.containsKey(index)) return;
    if (_initializing[index] == true) return;

    // Out of bounds = the list simply hasn't loaded that far yet (the initial
    // page-change fires before the first fetch lands) — silently skip.
    if (index < 0 || index >= urls.length) return;

    final url = urls[index];
    if (url == null || url.isEmpty) {
      // An in-bounds reel with no playable URL is a real error, not a silent
      // skip — otherwise the slot shows a frozen thumbnail forever with no
      // retry affordance.
      if (!_errored.contains(index)) {
        _errored.add(index);
        onError?.call(index);
      }
      return;
    }

    // Async resumes below are stale only when the pool died or the index fell
    // OUT of the current window. Same-gen is irrelevant: a fast swipe forward
    // bumps the gen but +1/+2 preloads are still wanted — discarding them
    // forced a cold re-init on every fast swipe (the "fast scroll → video
    // never plays" family).
    bool stale() => _disposed || !_window.contains(index);

    _initializing[index] = true;
    _loadStartTimes[index] = DateTime.now();
    _errored.remove(index);

    // Declared outside the try so the catch/timeout path can dispose a
    // half-initialized controller instead of leaking a native ExoPlayer.
    VideoPlayerController? controller;

    try {
      final cacheManager = ReelsCacheManager();
      final cachedFile = await cacheManager.getCachedFileOrNull(url);
      if (stale()) return;

      if (cachedFile != null) {
        _log('#$index from CACHE');
        controller = VideoPlayerController.file(
          cachedFile,
          videoPlayerOptions: VideoPlayerOptions(mixWithOthers: false),
        );
      } else {
        _log('#$index from NETWORK');
        controller = VideoPlayerController.networkUrl(
          Uri.parse(url),
          videoPlayerOptions: VideoPlayerOptions(mixWithOthers: false),
        );
        // Warm the cache in the background (silent).
        cacheManager.getSingleFile(url).then(
          (_) => _log('#$index cached'),
          onError: (e) => _log('#$index cache fail: $e'),
        );
      }

      // Bound init: a hung network/decoder must become a skippable error, never
      // an infinite spinner. 12s is generous for a slow connection; a genuine
      // timeout throws TimeoutException → caught below → marked errored.
      await controller.initialize().timeout(const Duration(seconds: 12));

      _log('#$index ready | duration=${controller.value.duration.inSeconds}s');

      controller.setLooping(true);

      // The pool may have died or the index may have left the window while we
      // were initializing. Storing this controller now would leak it — dispose
      // it and bail. In-window continuations are kept even across gen bumps
      // (fast swipe forward keeps its warm preloads).
      if (stale()) {
        await controller.dispose();
        // SELF-HEAL: if the user has now landed back on this index, its fresh
        // onPageChanged init bailed on our `_initializing` flag while this
        // stale attempt was still running — without this it would be stranded
        // with no controller and no retry. Re-run init once this attempt's
        // `finally` has cleared `_initializing` (microtask).
        if (!_disposed && index == _currentIndex) {
          scheduleMicrotask(() {
            if (!_disposed &&
                index == _currentIndex &&
                !_controllers.containsKey(index) &&
                _initializing[index] != true) {
              unawaited(_ensureInitialized(index, urls));
            }
          });
        }
        return;
      }

      _controllers[index] = controller;
      _attachListener(index, controller);

      // Apply the correct volume immediately: active reel honours mute, all
      // others stay silent. The widget owns play() — we never auto-play.
      controller.setVolume(
        index == _currentIndex ? (_isMuted ? 0.0 : 1.0) : 0.0,
      );

      if (index == _currentIndex) {
        _maybeEmitActiveDuration();
      }

      // Publish readiness so the feed re-emits its controllers map and the
      // widget binds this controller NOW (not on the next unrelated rebuild).
      onControllerReady?.call(index);
    } catch (e) {
      _log('#$index init failed: $e');
      // Dispose the half-initialized controller (timeout / network / decoder
      // failure) so the native player is released and not leaked.
      if (!_controllers.containsKey(index)) {
        try {
          await controller?.dispose();
        } catch (_) {}
      }
      if (!_disposed && _window.contains(index)) {
        _errored.add(index);
        onError?.call(index);
      }
    } finally {
      _initializing.remove(index);
    }
  }

  void _attachListener(int index, VideoPlayerController controller) {
    void listener() {
      if (_disposed) return;
      final value = controller.value;

      // Surface mid-playback errors for any index.
      if (value.hasError && !_errored.contains(index)) {
        _errored.add(index);
        onError?.call(index);
        return;
      }

      // Only the ACTIVE controller emits position, and only while playing.
      if (index == _currentIndex) {
        if (value.isPlaying) {
          final now = DateTime.now();
          if (now.difference(_lastPositionEmit).inMilliseconds > 250) {
            _lastPositionEmit = now;
            onPositionUpdate(value.position);
          }
        }

        // Drive the audio session off the REAL playback state: playing →
        // activate, stopped → release the route (a tap-pause used to keep the
        // route held because deactivate only ran on pauseAll).
        if (value.isPlaying != _lastActivePlaying) {
          _lastActivePlaying = value.isPlaying;
          unawaited(value.isPlaying
              ? ReelsAudioSession.instance.activate()
              : ReelsAudioSession.instance.deactivate());
        }
      }

      // Duration may only become known after late init.
      if (index == _currentIndex) {
        _maybeEmitActiveDuration();
      }
    }

    _listeners[index] = listener;
    controller.addListener(listener);
  }

  void _maybeEmitActiveDuration() {
    final controller = _controllers[_currentIndex];
    if (controller == null) return;
    final dur = controller.value.duration;
    if (dur > Duration.zero && dur != _lastEmittedDuration) {
      _lastEmittedDuration = dur;
      onDurationReady?.call(dur);
    }
  }

  /// Clears the error flag for [index] and re-initializes it.
  Future<void> retry(int index, List<String?> urls) async {
    if (_disposed) return;
    _errored.remove(index);
    final existing = _controllers.remove(index);
    if (existing != null) {
      final listener = _listeners.remove(index);
      if (listener != null) existing.removeListener(listener);
      existing.pause();
      await existing.dispose();
    }
    await _ensureInitialized(index, urls);
  }

  void pauseAll() {
    for (final c in _controllers.values) {
      c.pause();
    }
    // Stopped playing → release the audio route so the room/other apps regain
    // their output and reels audio stops bleeding in.
    _lastActivePlaying = false;
    unawaited(ReelsAudioSession.instance.deactivate());
  }

  /// Tears down every controller and clears the window maps.
  ///
  /// [reusable] controls the disposed gate:
  /// - true  (default): the pool can be reused afterwards (the caller is just
  ///   freeing the window before re-initializing the same pool, e.g.
  ///   PlayMyReelsView._freeMemoryAndInit). The disposed gate stays DOWN.
  /// - false: this is a permanent teardown via [dispose]; the disposed gate
  ///   stays UP so every late callback / in-flight initialize() is rejected and
  ///   can never resurrect a destroyed pool (no orphan controller, no audio
  ///   re-activation after the view is gone).
  ///
  /// IMPORTANT: bump [_pageGen] so any in-flight `await controller.initialize()`
  /// started before this call fails its `gen != _pageGen` check on resume and
  /// disposes its controller instead of storing it.
  Future<void> disposeAll({bool reusable = true}) async {
    _pageGen++;
    // Empty window ⇒ every in-flight initialize() continuation is stale and
    // self-disposes instead of repopulating the maps we're about to clear.
    _window = const {};

    // Snapshot BEFORE clearing maps. If we dispose() first and clear() second,
    // a Flutter frame firing in between will get a disposed controller from
    // pool[index] and the widget's initState tries addListener on it → crash.
    final controllers = Map.of(_controllers);
    final listeners = Map.of(_listeners);

    _controllers.clear();
    _listeners.clear();
    _initializing.clear();
    _loadStartTimes.clear();
    _errored.clear();
    _currentIndex = -1;
    _lastEmittedDuration = Duration.zero;
    _lastActivePlaying = false;

    final futures = <Future>[];
    for (final entry in controllers.entries) {
      final listener = listeners[entry.key];
      if (listener != null) entry.value.removeListener(listener);
      entry.value.pause();
      futures.add(entry.value.dispose());
    }
    await Future.wait(futures);
  }

  /// Re-arms a pool that was previously [dispose]d so it can serve a new view.
  /// Only the reuse path (PlayMyReelsView._freeMemoryAndInit /
  /// InitializeFeedControllersEvent) calls this — never an internal teardown.
  void reactivate() {
    _disposed = false;
  }

  /// Permanent teardown. Leaves the disposed gate UP so all guards stay armed.
  Future<void> dispose() async {
    _disposed = true;
    await disposeAll(reusable: false);
    await ReelsAudioSession.instance.deactivate();
  }
}
