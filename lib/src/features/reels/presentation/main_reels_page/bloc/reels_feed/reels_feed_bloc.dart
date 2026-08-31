import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:general/reels_viewer/reels_viewer.dart';
import 'package:general/src/core/cache/image_cache_manager.dart';
import 'package:general/src/core/cache/reels_cache_manager.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/utils/video_controller_pool.dart';

part 'reels_feed_event.dart';
part 'reels_feed_state.dart';

typedef FetchReelsCallback = Future<Either<NetworkExceptions, BaseResponse<List<ReelsEntity>>>> Function(int page, {String userId});
typedef FetchOneReelCallback = Future<Either<NetworkExceptions, BaseResponse<ReelsEntity>>> Function(int reelId);

class ReelsFeedBloc extends Bloc<ReelsFeedEvent, ReelsFeedState> {
  final FetchReelsCallback fetchReels;

  /// Optional single-reel refetch used by [retryReel]: a 404 on retry usually
  /// means the media URL expired — re-initializing the SAME stale URL can
  /// never recover. When set, retry refreshes the reel's URL first.
  final FetchOneReelCallback? fetchOneReel;
  final bool preloadAdjacent;

  final ValueNotifier<Duration> positionNotifier =
      ValueNotifier(Duration.zero);
  final ValueNotifier<Duration> durationNotifier =
      ValueNotifier(Duration.zero);

  /// True while the user is actively scrubbing the progress bar. While set, the
  /// pool's position updates are dropped so the bar tracks the finger, not the
  /// controller. Cleared once the controller's real position catches up to the
  /// seek target (resync on end), see [_onPositionFromPool].
  bool _isSeeking = false;
  // Guards pagination so a burst of page-changes near the end can't fire two
  // overlapping fetches (which skipped a page and dropped reels). Independent of
  // requestState because pagination fetches run with isLoading=false.
  bool _isFetching = false;

  /// The position we last seeked to. Used to detect when the controller has
  /// caught up so seeking can be released and live updates resume.
  Duration _seekTarget = Duration.zero;

  late final VideoControllerPool _pool = VideoControllerPool(
    onPositionUpdate: _onPositionFromPool,
    preloadAdjacent: preloadAdjacent,
    onDurationReady: (dur) => durationNotifier.value = dur,
    onError: (index) {
      if (!isClosed) add(FeedReelErroredEvent(index));
    },
  )..onControllerReady = _onControllerReadyFromPool;

  /// Pool init completions land off the event loop; republish the controllers
  /// map ON the loop so the PageView rebinds the fresh controller immediately.
  void _onControllerReadyFromPool(int index) {
    if (!isClosed) add(const FeedControllersUpdatedEvent());
  }

  /// Pool position sink. Suppressed while seeking; once the controller catches
  /// up to the seek target (within ~300ms) we release the seek and resume live
  /// position tracking.
  void _onPositionFromPool(Duration pos) {
    if (_isSeeking) {
      final caughtUp = (pos - _seekTarget).abs() <=
          const Duration(milliseconds: 300);
      if (caughtUp) {
        _isSeeking = false;
        positionNotifier.value = pos;
        if (!isClosed) add(const UpdateIsFeedSeekingEvent(false));
      }
      return;
    }
    positionNotifier.value = pos;
  }

  Timer? _preloadDebounceTimer;

  /// Dedicated retry timer for the preload-while-buffering path. MUST be
  /// separate from [_preloadDebounceTimer]: they used to share one field, so a
  /// page-change settle canceled the buffering retry (and vice versa) and the
  /// deferred preload was silently lost.
  Timer? _bufferingRetryTimer;
  final Connectivity _connectivity = Connectivity();

  ReelsFeedBloc({
    required this.fetchReels,
    this.fetchOneReel,
    this.preloadAdjacent = true,
  }) : super(ReelsFeedState(scrollCtrl: PageController())) {
    on<FetchFeedEvent>(_onFetch);
    on<RefreshFeedEvent>(_onRefresh);
    on<FeedPageChangedEvent>(_onPageChanged);
    on<SeekFeedVideoEvent>(_onSeekVideo);
    on<DisposeFeedControllersEvent>(_onDisposeControllers);
    on<InitializeFeedControllersEvent>(_onInitializeControllers);
    on<LocalAddReelToFeedEvent>(_onLocalAddReel);
    on<PlayTappedFeedReelEvent>(_onPlayTappedReel);
    on<UpdateFeedReelEvent>(_onUpdateReel);
    on<DeleteFeedReelEvent>(_onDeleteReel);
    on<UpdateFeedReelsListEvent>(_onUpdateReelsList);
    on<FeedVideoProgressEvent>((event, emit) {
      emit(state.copyWith(videoPosition: event.position));
    });
    on<UpdateIsFeedSeekingEvent>((event, emit) {
      _isSeeking = event.isSeeking;
      if (!event.isSeeking) _seekTarget = Duration.zero;
      emit(state.copyWith(isSeeking: event.isSeeking));
    });
    on<FilterChangedDisposeFeedEvent>(_onFilterChangedDispose);
    on<PauseFeedControllersEvent>(_onPauseControllers);
    on<ResumeFeedControllerEvent>(_onResumeController);
    on<ResetFeedEvent>(_onResetFeed);
    on<FeedReelErroredEvent>(_onReelErrored);
    on<RetryFeedReelDoneEvent>(_onRetryDone);
    on<FeedControllersUpdatedEvent>((event, emit) {
      emit(state.copyWith(
        videoControllers:
            Map<int, VideoPlayerController>.from(_pool.controllers),
      ));
    });
  }

  VideoControllerPool get pool => _pool;

  /// Reel ID → list index map for O(1) cross-feed lookups.
  Map<int, int> _idToIndex = {};

  /// Returns the list index for the given reel ID, or -1 if not found. O(1).
  int indexOfReelId(int reelId) => _idToIndex[reelId] ?? -1;

  void _rebuildIdIndex(List<ReelsEntity> reels) {
    final map = <int, int>{};
    for (int i = 0; i < reels.length; i++) {
      final id = reels[i].id;
      if (id != null) map[id] = i;
    }
    _idToIndex = map;
  }

  Future<void> _onFetch(
    FetchFeedEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    // Single in-flight fetch at a time (covers both the loading initial fetch
    // and the silent pagination fetch).
    if (_isFetching) return;
    _isFetching = true;

    if (event.isLoading) {
      emit(state.copyWith(requestState: RequestState.loading));
    }

    final result =
        await fetchReels(state.currentPagination, userId: event.userId);

    await result.fold((failure) async {
      // Roll back the optimistic page bump so the failed page is retried next
      // time instead of being silently skipped (leaving a gap in the feed).
      final rolledBack =
          state.currentPagination > 1 && !event.isLoading && !event.onRefresh
              ? state.currentPagination - 1
              : state.currentPagination;
      emit(state.copyWith(
        currentPagination: rolledBack,
        message: NetworkExceptions.getErrorMessage(failure),
        requestState: handleErrorResponse(failure),
      ));
    }, (success) async {
      final newReels = success.data ?? [];

      // Merge ONCE keyed by reel.id, first-writer-wins for ids already present:
      // the already-displayed (possibly optimistically-edited) copy is kept and a
      // stale server copy on a later page never clobbers a local like/follow edit.
      // New ids are appended in server order. Reels with a null id can't be keyed
      // so they're preserved as-is (kept at most as they arrive). One merged list
      // is both emitted and preloaded — no second divergent computation.
      // A default Dart map literal is a LinkedHashMap: iteration follows
      // insertion order, which is exactly the merge order we rely on below.
      final merged = <int, ReelsEntity>{};
      final nullIdReels = <ReelsEntity>[];
      for (final reel in [...state.reels, ...newReels]) {
        final id = reel.id;
        if (id == null) {
          nullIdReels.add(reel);
        } else {
          merged.putIfAbsent(id, () => reel);
        }
      }
      final mergedList = [...merged.values, ...nullIdReels];

      _preloadFromIndex(mergedList, state.currentIndex);

      // lastPage inference: if the API omits pagination metadata, DON'T hard-stop
      // at 1 (that froze pagination after the first page). Instead allow one more
      // page when this page returned items — fetching naturally terminates when a
      // page comes back empty.
      final int inferredLastPage = success.paginates?.lastPage ??
          (newReels.isNotEmpty
              ? state.currentPagination + 1
              : state.currentPagination);

      emit(state.copyWith(
        reels: mergedList,
        lastPage: inferredLastPage,
        requestState: handleLoadedResponse(success.data),
      ));

      if (event.onRefresh) {
        emit(state.copyWith(videoControllers: const {}));
        add(const FeedPageChangedEvent(0));
      } else if (event.initializeControllers) {
        add(const InitializeFeedControllersEvent());
        add(const FeedPageChangedEvent(0));
      } else if (_pool.currentIndex >= 0 &&
          _pool[state.currentIndex] == null &&
          mergedList.isNotEmpty) {
        // First data for a feed whose page-change already fired against an
        // EMPTY list (following tab opened before its fetch landed): nothing
        // else would ever init the active controller — the feed sat frozen on
        // thumbnails until a manual swipe. Re-fire for the current index now
        // that URLs exist. Gated on `_pool.currentIndex >= 0` so a pure grid
        // fetch (profile reels thumbnails, no player open) never spins up
        // video controllers.
        _pool.reactivate();
        add(FeedPageChangedEvent(
            state.currentIndex.clamp(0, mergedList.length - 1)));
      }
    });

    _isFetching = false;
  }

  Future<void> _onRefresh(
    RefreshFeedEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    _pool.disposeAll();
    _autoRetried.clear();
    _urlRefreshed.clear();
    _isFetching = false;
    // Refresh must restart from page 1 AND drop the old list — the merge is
    // first-writer-wins, so keeping the old reels meant fresh top-of-feed
    // content never actually replaced them.
    emit(state.copyWith(
      reels: const [],
      currentIndex: 0,
      currentPagination: 1,
      lastPage: 1,
      videoControllers: const {},
      erroredIndices: const {},
    ));
    add(const FetchFeedEvent(onRefresh: true));
  }

  Future<void> _onPageChanged(
    FeedPageChangedEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    final urls = state.reels.map((r) => r.url).toList();

    // New reel: reset scrub state. Duration is now PUSHED by the pool via
    // onDurationReady (handles late init too) — no stale synchronous read here.
    _isSeeking = false;
    _seekTarget = Duration.zero;
    durationNotifier.value = Duration.zero;
    positionNotifier.value = Duration.zero;

    // Synchronous window move; controller readiness arrives via
    // onControllerReady → FeedControllersUpdatedEvent. NEVER await network
    // inits here — a cold init took seconds and stalled the whole feed while
    // a second swipe re-entered mid-await (wrong reel played, jank).
    _pool.onPageChanged(newIndex: event.newIndex, urls: urls);

    emit(state.copyWith(
      currentIndex: event.newIndex,
      videoControllers: Map<int, VideoPlayerController>.from(_pool.controllers),
      isSeeking: false,
    ));

    // Use jumpToPage (not animateToPage): animateToPage fires onPageChanged
    // for every intermediate page during the scroll animation, which triggers
    // new PageChangedEvents → new animateToPage calls → auto-scroll cascade.
    if (state.scrollCtrl.positions.length == 1 &&
        state.scrollCtrl.page?.round() != event.newIndex) {
      state.scrollCtrl.jumpToPage(event.newIndex);
    }

    _schedulePreloadAfterSettle();

    if (event.newIndex >= state.reels.length - 5 &&
        state.currentPagination < state.lastPage &&
        !_isFetching) {
      final nextPage = state.currentPagination + 1;
      emit(state.copyWith(currentPagination: nextPage));
      add(const FetchFeedEvent(isLoading: false));
    }
  }

  Future<void> _onSeekVideo(
    SeekFeedVideoEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    final controller = _pool[state.currentIndex];
    if (controller != null) {
      // Suppress pool position emits until the controller catches up to this
      // target (see _onPositionFromPool); the bar reflects the seek target now.
      _isSeeking = true;
      _seekTarget = event.position;
      await controller.seekTo(event.position);
      positionNotifier.value = event.position;
      emit(state.copyWith(isSeeking: true));
    }
  }

  Future<void> _onDisposeControllers(
    DisposeFeedControllersEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    _pool.disposeAll();
    emit(state.copyWith(
      isDisposed: true,
      videoControllers: const {},
      erroredIndices: const {},
    ));
  }

  Future<void> _onInitializeControllers(
    InitializeFeedControllersEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    // Canonical reuse entry point: re-arm the pool in case it was previously
    // permanently disposed, then clear its window for a fresh start.
    _pool.reactivate();
    _pool.disposeAll();
    emit(state.copyWith(
      videoControllers: const {},
      isDisposed: false,
      erroredIndices: const {},
    ));
  }

  Future<void> _onLocalAddReel(
    LocalAddReelToFeedEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    _pool.disposeAll();
    final updatedReels = List<ReelsEntity>.of(state.reels);
    updatedReels.insert(0, event.reel);
    emit(state.copyWith(
      reels: updatedReels,
      videoControllers: const {},
      requestState: RequestState.loaded,
      erroredIndices: const {},
    ));

    if (state.scrollCtrl.positions.length == 1) {
      state.scrollCtrl.jumpToPage(0);
    }
    add(const FeedPageChangedEvent(0));
  }

  Future<void> _onPlayTappedReel(
    PlayTappedFeedReelEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    final oldCtrl = state.scrollCtrl;
    final newCtrl = PageController(initialPage: event.index);
    emit(state.copyWith(
      currentIndex: event.index,
      scrollCtrl: newCtrl,
    ));
    // Defer: the live PageView is still attached to oldCtrl until it rebuilds
    // against newCtrl. Disposing an attached controller mid-frame throws
    // "used after being disposed" from the detaching PageView.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (oldCtrl.hasClients) return; // still attached somewhere — leave it.
      oldCtrl.dispose();
    });
  }

  Future<void> _onUpdateReel(
    UpdateFeedReelEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    final updatedReels = List<ReelsEntity>.from(state.reels);
    if (event.index >= 0 && event.index < updatedReels.length) {
      updatedReels[event.index] = updatedReels[event.index].copyWith(
        description: event.description,
      );
      emit(state.copyWith(reels: updatedReels));
    }
  }

  Future<void> _onDeleteReel(
    DeleteFeedReelEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    final updatedReels = List<ReelsEntity>.from(state.reels);
    if (event.index >= 0 && event.index < updatedReels.length) {
      updatedReels.removeAt(event.index);
      _pool.disposeAll();
      emit(state.copyWith(
        reels: updatedReels,
        videoControllers: const {},
        erroredIndices: const {},
      ));
    }
  }

  void _onUpdateReelsList(
    UpdateFeedReelsListEvent event,
    Emitter<ReelsFeedState> emit,
  ) {
    emit(state.copyWith(reels: event.reels));
  }

  void _onResetFeed(
    ResetFeedEvent event,
    Emitter<ReelsFeedState> emit,
  ) {
    _pool.disposeAll();
    _idToIndex = {};
    _autoRetried.clear();
    _urlRefreshed.clear();
    _isFetching = false;
    emit(state.copyWith(
      reels: const [],
      currentIndex: 0,
      currentPagination: 1,
      lastPage: 1,
      requestState: RequestState.idle,
      message: '',
      videoControllers: const {},
      isDisposed: false,
      erroredIndices: const {},
    ));
  }

  Future<void> _onFilterChangedDispose(
    FilterChangedDisposeFeedEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    _pool.disposeAll();
    emit(state.copyWith(videoControllers: const {}, erroredIndices: const {}));
  }

  Future<void> _onPauseControllers(
    PauseFeedControllersEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    // Pause is always safe to do here. pauseAll() also deactivates the audio
    // session so the route is released (room regains bluetooth, no reels bleed).
    _pool.pauseAll();
  }

  Future<void> _onResumeController(
    ResumeFeedControllerEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    // ONE-master: the pool/feed no longer calls controller.play(). Desired
    // playing is owned by ReelViewerBloc and applied by the active widget; the
    // screen dispatches PlayReelEvent alongside this event. Here we make sure
    // the active controller will be audible (mute-aware) once the widget plays
    // it — non-active controllers stay silent.
    _pool.setMuted(_pool.isMuted);

    // SELF-HEAL on resume: if this feed was torn down while another surface
    // owned the screen (tab switch disposed it, PlayMyReelsView freed it,
    // refresh raced it), the active index has NO controller and nothing else
    // would ever rebuild it — the feed came back frozen on a thumbnail.
    // Re-arm the pool and re-fire the page-change for the current index.
    if (state.reels.isNotEmpty &&
        _pool.activeController == null &&
        _pool[state.currentIndex] == null) {
      _pool.reactivate();
      add(FeedPageChangedEvent(state.currentIndex));
    }
  }

  /// Indices we have already auto-retried once, so a permanently-broken reel
  /// falls back to the manual retry/skip overlay instead of looping forever.
  final Set<int> _autoRetried = {};

  Future<void> _onReelErrored(
    FeedReelErroredEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    if (state.erroredIndices.contains(event.index)) return;
    emit(state.copyWith(
      erroredIndices: {...state.erroredIndices, event.index},
    ));

    // Owner spec: every video must play. A reel that fails to init/play is
    // auto-recovered once (cache-first via the pool) so a transient storage
    // hiccup or a black/logo frame doesn't leave it stuck. If it fails again
    // the manual retry/skip overlay (WF3) takes over.
    if (!_autoRetried.contains(event.index)) {
      _autoRetried.add(event.index);
      await Future<void>.delayed(const Duration(milliseconds: 600));
      if (isClosed) return;
      await retryReel(event.index);
    }
  }

  /// Reel ids for which a fresh-URL refetch is already in flight or done once
  /// this session (bounded: one refresh per errored reel per feed lifetime, so
  /// a broad backend outage can't trigger a request storm).
  final Set<int> _urlRefreshed = {};

  /// Clears the error flag for [index] and re-initializes its controller via
  /// the pool. On the SECOND failure for the same reel the stored URL is
  /// treated as expired: refetch the reel (fresh signed URL) once, replace it
  /// in state, then retry against the new URL.
  Future<void> retryReel(int index) async {
    final reelId = (index >= 0 && index < state.reels.length)
        ? state.reels[index].id
        : null;

    // Expired-URL path: this reel already failed a plain retry.
    String? freshUrl;
    if (fetchOneReel != null &&
        reelId != null &&
        _pool.hasError(index) &&
        _autoRetried.contains(index) &&
        _urlRefreshed.add(reelId)) {
      final result = await fetchOneReel!(reelId);
      if (isClosed) return;
      result.fold((_) {}, (success) {
        final fresh = success.data;
        if (fresh?.url != null && fresh!.url!.isNotEmpty) {
          freshUrl = fresh.url;
          final updated = List<ReelsEntity>.of(state.reels);
          final liveIndex = indexOfReelId(reelId);
          if (liveIndex >= 0) {
            updated[liveIndex] = updated[liveIndex].copyWith(url: fresh.url);
            add(UpdateFeedReelsListEvent(updated));
          }
        }
      });
    }

    // Build the URL list locally: the UpdateFeedReelsListEvent above is still
    // queued, so state.reels may not carry the fresh URL yet.
    final urls = state.reels.map((r) => r.url).toList();
    if (freshUrl != null && index >= 0 && index < urls.length) {
      urls[index] = freshUrl;
    }
    await _pool.retry(index, urls);
    if (isClosed) return;
    add(RetryFeedReelDoneEvent(index));
  }

  Future<void> _onRetryDone(
    RetryFeedReelDoneEvent event,
    Emitter<ReelsFeedState> emit,
  ) async {
    // If the retry cleared the error, allow a future auto-retry for this index.
    if (!_pool.hasError(event.index)) _autoRetried.remove(event.index);
    final next = {...state.erroredIndices}..remove(event.index);
    emit(state.copyWith(
      erroredIndices: next,
      videoControllers: Map<int, VideoPlayerController>.from(_pool.controllers),
    ));
  }

  // Preloading
  void _preloadFromIndex(List<ReelsEntity> reels, int startIndex) {
    unawaited(_preloadFromIndexAsync(reels, startIndex));
  }

  Future<void> _preloadFromIndexAsync(
      List<ReelsEntity> reels, int startIndex) async {
    // While the current video is buffering, defer the WIDE look-ahead (its own
    // timer — sharing _preloadDebounceTimer let a page-change cancel this
    // retry forever) but still warm the single NEXT reel: on weak connections
    // that one file is exactly what makes the next swipe instant.
    final currentCtrl = _pool[state.currentIndex];
    if (currentCtrl != null && currentCtrl.value.isBuffering) {
      final nextUrl = (startIndex + 1 < reels.length)
          ? reels[startIndex + 1].url
          : null;
      if (nextUrl != null && nextUrl.isNotEmpty) {
        unawaited(ReelsCacheManager()
            .getSingleFile(nextUrl)
            .then((_) {}, onError: (_) {}));
      }
      _bufferingRetryTimer?.cancel();
      _bufferingRetryTimer = Timer(const Duration(seconds: 2), () {
        // Live state, not the stale closure args — the user may have moved.
        _preloadFromIndex(state.reels, state.currentIndex);
      });
      return;
    }

    final connectivityResults = await _connectivity.checkConnectivity();
    final isWifi = connectivityResults.contains(ConnectivityResult.wifi);
    final isMobile = connectivityResults.contains(ConnectivityResult.mobile);
    if (!isWifi && !isMobile) return;

    final int videoLimit = isWifi ? 2 : 1;
    const int thumbnailLimit = 10;
    final cacheManager = ReelsCacheManager();
    final assetCacheManager = AssetCacheManager();
    int videosScheduled = 0;
    int thumbnailsCached = 0;

    // BOUNDED-CONCURRENCY look-ahead: video prefetches are kicked off without
    // awaiting (fire-and-forget) but capped at [videoLimit], so look-ahead never
    // blocks on the first (possibly slow) download — they download in parallel.
    // Thumbnails stay fire-and-forget capped at [thumbnailLimit].
    for (int i = startIndex + 1; i < reels.length; i++) {
      if (videosScheduled >= videoLimit &&
          thumbnailsCached >= thumbnailLimit) {
        break;
      }
      final reel = reels[i];

      if (thumbnailsCached < thumbnailLimit &&
          reel.subFrame != null && reel.subFrame!.isNotEmpty) {
        unawaited(assetCacheManager
            .getCachedAsset(reel.subFrame!)
            .then((_) {}, onError: (_) {}));
        thumbnailsCached++;
      }

      if (videosScheduled < videoLimit &&
          reel.url != null && reel.url!.isNotEmpty) {
        unawaited(cacheManager
            .getSingleFile(reel.url!)
            .then((_) {}, onError: (_) {}));
        videosScheduled++;
      }
    }

    // Owner spec: cache the video FILES well ahead (~8-10) so returning to or
    // jumping to a reel plays from disk — not a fresh storage stream. This is
    // throttled (one file at a time, sequential) so it never competes with the
    // active controller or the near-window parallel prefetch above. It only
    // downloads files that are NOT already cached.
    unawaited(_prefetchFilesAhead(reels, startIndex, cacheManager));
  }

  /// Sequential, cancellable look-ahead that warms the on-disk cache for up to
  /// [_aheadFileBudget] reels past [startIndex], one file at a time. Bails as
  /// soon as a newer page-change supersedes this run so we never thrash the
  /// network on rapid swipes.
  static const int _aheadFileBudget = 10;
  int _prefetchGen = 0;

  Future<void> _prefetchFilesAhead(
    List<ReelsEntity> reels,
    int startIndex,
    ReelsCacheManager cacheManager,
  ) async {
    final gen = ++_prefetchGen;
    int done = 0;
    for (int i = startIndex + 1;
        i < reels.length && done < _aheadFileBudget;
        i++) {
      if (isClosed || gen != _prefetchGen) return;
      final url = reels[i].url;
      if (url == null || url.isEmpty) continue;

      // Cache-first: skip anything already on disk.
      final cached = await cacheManager.getCachedFileOrNull(url);
      if (cached != null) {
        done++;
        continue;
      }
      if (isClosed || gen != _prefetchGen) return;

      try {
        await cacheManager.getSingleFile(url);
      } catch (_) {
        // Best-effort: a failed prefetch is retried on the next look-ahead.
      }
      done++;
    }
  }

  void _schedulePreloadAfterSettle() {
    _preloadDebounceTimer?.cancel();
    // Short settle (was 1s): start warming the on-disk cache almost immediately
    // after the swipe lands so the next reels play from disk — TikTok-style.
    _preloadDebounceTimer = Timer(const Duration(milliseconds: 250), () {
      _preloadFromIndex(state.reels, state.currentIndex);
    });
  }

  @override
  void onChange(Change<ReelsFeedState> change) {
    super.onChange(change);
    if (!identical(change.nextState.reels, change.currentState.reels)) {
      _rebuildIdIndex(change.nextState.reels);
    }
  }

  @override
  Future<void> close() async {
    _preloadDebounceTimer?.cancel();
    _bufferingRetryTimer?.cancel();
    // pool.dispose() is async now: disposes controllers + deactivates the audio
    // session. Await so the route is released before the bloc tears down.
    await _pool.dispose();
    state.scrollCtrl.dispose();
    positionNotifier.dispose();
    durationNotifier.dispose();
    return super.close();
  }
}
