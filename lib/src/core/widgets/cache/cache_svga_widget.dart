import 'package:flutter_svga/flutter_svga.dart';
import 'package:general/src/core/cache/svga_movie_cache.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/utils/app_lifecycle_signal.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/room.dart';
import 'package:shimmer/shimmer.dart';
import 'package:visibility_detector/visibility_detector.dart';

class CacheSvgaWidget extends StatefulWidget {
  final String url;
  final double? height, width;
  final bool isShowGift;
  final BoxFit? boxFit;
  final bool? isNeedLoading;
  final BoxShape? shape;
  final double? radius;
  final bool isStopErrorAndLoadingFrame;
  final void Function()? detectError;
  final bool isStopForProfileRoom;
  final bool isAsset;

  /// When true, the looping animation is paused while the widget is off-screen
  /// or the app is backgrounded, and resumed when visible + foregrounded.
  /// Only honored for the looping case ([isShowGift] == false); the one-shot
  /// gift path is never gated. Defaults to false so gift / emoji / boom
  /// instances are unaffected.
  final bool pauseWhenHidden;

  const CacheSvgaWidget({
    required this.url,
    this.height,
    this.boxFit,
    this.width,
    this.isNeedLoading,
    this.shape,
    this.radius,
    this.detectError,
    this.isShowGift = false,
    this.isStopErrorAndLoadingFrame = true,
    this.isStopForProfileRoom = true,
    this.isAsset = false,
    this.pauseWhenHidden = false,
    super.key,
  });

  @override
  CacheSvgaWidgetState createState() => CacheSvgaWidgetState();
}

class CacheSvgaWidgetState extends State<CacheSvgaWidget>
    with TickerProviderStateMixin {
  SVGAAnimationController? _animationController;

  final ValueNotifier<bool> _isLoading = ValueNotifier(true);
  final ValueNotifier<String?> _isError = ValueNotifier(null);
  bool _isDisposed = false;

  // ---- Looping-frame gating (only when widget.pauseWhenHidden == true) ----
  late final GlobalKey _visibilityKey =
      GlobalKey(debugLabel: 'svga_frame_gate');
  bool _isVisible = true;
  bool _isRepeating = false;

  bool get _gateEnabled => widget.pauseWhenHidden && !widget.isShowGift;

  @override
  void initState() {
    super.initState();
    if (_gateEnabled) {
      AppLifecycleSignal.isForeground.addListener(_applyGate);
    }
    _loadSvga();
  }

  @override
  void didUpdateWidget(CacheSvgaWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.url != oldWidget.url) {
      _loadSvga();
    }
  }

  Future<void> _loadSvga() async {
    if (_isDisposed) return;

    _isLoading.value = true;
    _isError.value = null;

    try {
      final MovieEntity? movie;

      if (widget.isAsset) {
        movie = await SvgaMovieCache.instance.loadFromAsset(widget.url);
      } else {
        final url = widget.url.contains('https')
            ? widget.url
            : EndPoints.getImage(widget.url);
        movie = await SvgaMovieCache.instance.loadFromUrl(url);
      }

      if (movie != null) {
        _disposeAnimationController();

        if (_isDisposed) return;

        _animationController = SVGAAnimationController(vsync: this)
          ..videoItem = movie;

        if (widget.isShowGift) {
          _animationController?.forward().whenComplete(() {
            _handleGiftQueue();
            _animationController?.videoItem = null;
          });
        } else if (_gateEnabled) {
          // Start (or stay paused) according to current visibility + lifecycle.
          _isRepeating = false;
          _applyGate();
        } else {
          _animationController?.repeat();
        }

        if (mounted) _isLoading.value = false;
      } else {
        _handleErrorWithGiftFallback("svga_file_not_found");
      }
    } catch (error) {
      _handleErrorWithGiftFallback("failed_to_load_svga");
    }
  }

  void _handleGiftQueue() {
    di<GiftBloc>().add(
      const ShowGiftsEvent(
        pathGift: "",
        isShowGift: false,
        giftType: ShowGiftType.svga,
      ),
    );

    if (GiftController().normalGiftsToShow.isNotEmpty) {
      GiftController().normalGiftsToShow.removeAt(0);

      if (GiftController().normalGiftsToShow.isEmpty) {
        di<GiftBloc>().add(const ShowGiftsEvent(
          pathGift: "",
          isShowGift: false,
          giftType: ShowGiftType.svga,
        ));
      } else {
        final gift = GiftController().normalGiftsToShow[0];
        _showNextGift(gift);
      }
    }
  }

  void _showNextGift(Map<String, dynamic> gift) {
    final type = gift['giftType'];
    final path = gift['pathGift'];
    final isFamous = gift['isFamousGift'];
    final wappelImage = gift['wappel']['wappelImage'] ?? '';

    Future.delayed(
      Duration(milliseconds: type == 'svga' ? 650 : 100),
      () {
        if (type == 'alpha') {
          di<AlphaGiftManagerBloc>().add(
            ShowAlphaGift(imgFile: path, isFamousGift: isFamous),
          );
        } else {
          di<GiftBloc>().add(
            ShowGiftsEvent(
              isShowGift: true,
              pathGift: path,
              isFamousGift: isFamous,
              giftType: _mapGiftType(type),
            ),
          );
        }

        if (wappelImage.isNotEmpty) {
          ShowEntroWidget.showEntro.value = gift['wappel'];
        }
      },
    );
  }

  ShowGiftType _mapGiftType(String type) {
    switch (type) {
      case 'alpha':
        return ShowGiftType.alpha;
      case 'vap':
        return ShowGiftType.vap;
      case 'mp4':
        return ShowGiftType.mp4;
      default:
        return ShowGiftType.svga;
    }
  }

  void _handleErrorWithGiftFallback(String message) {
    _handleGiftQueue();
    if (mounted) {
      _isLoading.value = false;
      widget.detectError?.call();
      _isError.value = message;
    }
  }

  /// Resume the loop when visible + foregrounded, pause it otherwise.
  /// Never touches the one-shot gift path (guarded by [_gateEnabled], which is
  /// false when [widget.isShowGift] is true). Idempotent: guarded by
  /// [_isRepeating] so we never double-start or get stuck stopped.
  void _applyGate() {
    if (!_gateEnabled || _isDisposed) return;
    final controller = _animationController;
    if (controller == null) return;

    final shouldRun = _isVisible && AppLifecycleSignal.isForeground.value;

    if (shouldRun) {
      if (!_isRepeating) {
        _isRepeating = true;
        controller.repeat();
      }
    } else {
      if (_isRepeating) {
        _isRepeating = false;
      }
      // stop() halts ticking without clearing; last frame stays painted
      // because SVGAImage uses clearsAfterStop: false.
      controller.stop();
    }
  }

  void _disposeAnimationController() {
    _animationController?.stop();
    _animationController?.dispose();
    _animationController = null;
    _isRepeating = false;
  }

  @override
  void dispose() {
    _isDisposed = true;
    if (widget.pauseWhenHidden) {
      AppLifecycleSignal.isForeground.removeListener(_applyGate);
    }
    _disposeAnimationController();
    _isLoading.dispose();
    _isError.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<bool>(
      valueListenable: _isLoading,
      builder: (context, isLoading, child) {
        if (isLoading &&
            (widget.isStopErrorAndLoadingFrame ||
                widget.isStopForProfileRoom)) {
          return widget.isNeedLoading == true
              ? Shimmer.fromColors(
                  baseColor: ColorManager.baseColor,
                  highlightColor: ColorManager.highlightColor,
                  child: Container(
                    height: widget.height ?? 200,
                    width: widget.width ?? 200,
                    decoration: BoxDecoration(
                      shape: widget.shape ?? BoxShape.rectangle,
                      color: Colors.grey[300],
                      borderRadius: widget.shape == BoxShape.circle
                          ? null
                          : BorderRadius.circular(widget.radius ?? 0),
                    ),
                  ),
                )
              : SizedBox(
                  height: widget.height ?? 200,
                  width: widget.width ?? 200,
                );
        }

        return ValueListenableBuilder<String?>(
          valueListenable: _isError,
          builder: (context, isError, child) {
            if (isError != null &&
                (widget.isStopErrorAndLoadingFrame ||
                    widget.isStopForProfileRoom)) {
              return SizedBox(
                height: widget.height ?? 200,
                width: widget.width ?? 200,
              );
            }

            if (_isDisposed || _animationController == null) {
              return const SizedBox();
            }

            final Widget image = SizedBox(
              height: widget.height,
              width: widget.width,
              child: RepaintBoundary(
                child: SVGAImage(
                  _animationController!,
                  fit: widget.boxFit ?? BoxFit.contain,
                  clearsAfterStop: false,
                ),
              ),
            );

            if (!_gateEnabled) return image;

            return VisibilityDetector(
              key: _visibilityKey,
              onVisibilityChanged: (info) {
                if (_isDisposed) return;
                final visible = info.visibleFraction > 0;
                if (visible == _isVisible) return;
                _isVisible = visible;
                _applyGate();
              },
              child: image,
            );
          },
        );
      },
    );
  }
}
