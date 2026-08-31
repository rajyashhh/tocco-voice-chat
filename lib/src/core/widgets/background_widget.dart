import 'package:general/src/core/index.dart';

class BackgroundImgWidget extends StatelessWidget {
  final Widget child;
  final String? img;
  final bool? resize;
  final double? bottom;

  const BackgroundImgWidget({
    required this.child,
    this.bottom,
    this.img,
    super.key,
    this.resize,
  });

  @override
  Widget build(BuildContext context) {
    // Same central gate as _MainLayout: only real image/gradient designs
    // switch this scaffold to transparent (the panel layer paints the page).
    final isBodyThemeEnabled = ColorManager.hasBodyThemeDesign;
    if (isBodyThemeEnabled) {
      return _buildBodyTheme(context);
    }

    return _buildDefaultBackground(context);
  }

  Widget _buildBodyTheme(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: resize ?? true,
      backgroundColor: ColorManager.transparent,
      body: child,
    );
  }

  Widget _buildDefaultBackground(BuildContext context) {
    final background = HiveManager().getData(
          KeysManager.USER_BOX,
          KeysManager.BACKGROUND_KEY,
        ) ??
        "";
    final backgroundType = HiveManager().getData(
          KeysManager.USER_BOX,
          KeysManager.BACKGROUND_TYPE_KEY,
        ) ??
        "";

    Widget backgroundWidget;
    Methods.printLog("Background: $background, Type: $backgroundType");
    if (background != null && background != "" && !_isColor(background)) {
      backgroundWidget = ShaderMask(
        shaderCallback: (bounds) {
          return LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              ColorManager.black,
              ColorManager.black.withValues(alpha: 0.7),
              ColorManager.black.withValues(alpha: 0.3),
              ColorManager.transparent,
            ],
            stops: const [0.0, 0.3, 0.7, 1.0],
          ).createShader(bounds);
        },
        blendMode: BlendMode.dstIn,
        child: ImageViewWidget(
          url: background,
          height: 220.h,
          width: ScreenUtil().screenWidth,
          boxFit: BoxFit.cover,
        ),
      );
    } else if (background != null && background != "" && _isColor(background)) {
      backgroundWidget = Container(
        height: 220.h,
        decoration: BoxDecoration(
          gradient:
              GradientHelper.buildGradientFromHeaderColor(ColorManager.primary),
        ),
      );
    } else {
      backgroundWidget = Container(
        height: 220.h,
        decoration: BoxDecoration(
          gradient:
              GradientHelper.buildGradientFromHeaderColor(ColorManager.primary),
        ),
      );
    }

    return Scaffold(
      resizeToAvoidBottomInset: resize ?? true,
      backgroundColor: ColorManager.scaffoldBg,
      body: Stack(
        children: [
          SizedBox(
            height: 220.h,
            width: ScreenUtil().screenWidth,
            child: backgroundWidget,
          ),
          child,
        ],
      ),
    );
  }

  bool _isColor(String value) {
    return RegExp(r'^#?([0-9a-fA-F]{6}|[0-9a-fA-F]{8})$').hasMatch(value);
  }
}

class GradientHelper {
  /// [endColor] pins the final stop; room/live callers pass the
  /// theme-independent [ColorManager.roomSurface] so the fade never follows
  /// the app theme (owner rule). Defaults to the theme-driven
  /// [ColorManager.background].
  static LinearGradient buildGradientFromHeaderColor(Color baseColor,
      {Color? endColor}) {
    // Dark default (التصميم 1, Xena reference): lightening the neon-violet
    // accent would paint a pastel band over the violet-night page. Its header
    // fades a faint violet glow straight into the page gradient instead —
    // one continuous night surface. Room/live callers pass an explicit
    // [endColor] (pinned identity) and keep the legacy ramp.
    if (endColor == null &&
        !ConstantsManager.isTheme1 &&
        !ConstantsManager.isTheme2 &&
        !ConstantsManager.isTheme3) {
      return const LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [
          Color(0xFF2A1B4D),
          Color(0xFF1A1030),
          ColorManager.defaultDarkBackground,
        ],
        stops: [0.0, 0.55, 1.0],
      );
    }
    return LinearGradient(
      begin: Alignment.topCenter,
      end: Alignment.bottomCenter,
      colors: [
        baseColor,
        _lighten(baseColor, 0.3),
        _lighten(baseColor, 0.6),
        endColor ?? ColorManager.background,
      ],
      stops: const [0.0, 0.3, 0.7, 1.0],
    );
  }

  static LinearGradient buildGradientFromBottomColor(Color baseColor) {
    // Dark default (التصميم 1): lightening its #05060A nav ink used to blow it
    // up into a saturated mid-blue band that clashed with the dark page (owner
    // report). Keep the bar inside the same dark navy family instead.
    if (!ConstantsManager.isTheme1 &&
        !ConstantsManager.isTheme2 &&
        !ConstantsManager.isTheme3) {
      return const LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [
          ColorManager.defaultNavBg,
          ColorManager.defaultDarkBackground,
        ],
      );
    }
    return LinearGradient(
      begin: Alignment.topCenter,
      end: Alignment.bottomCenter,
      colors: [
        ColorManager.background,
        _lighten(baseColor, 0.4),
        _lighten(baseColor, 0.3),
        baseColor,
      ],
      stops: const [0.0, 0.6, 1.0, 1.0],
    );
  }

  static LinearGradient buildGradientFromRightColor(Color baseColor) {
    return LinearGradient(
      colors: [
        ColorManager.primary,
        ColorManager.primary.withValues(alpha: (0.7)),
        ColorManager.background
      ],
      stops: const [
        0.0,
        0.6,
        1.0,
      ],
    );
  }

  static Color _lighten(Color color, double amount) {
    assert(amount >= 0 && amount <= 1);

    final hsl = HSLColor.fromColor(color);
    final hslLight = hsl.withLightness(
      (hsl.lightness + amount).clamp(0.0, 1.0),
    );
    return hslLight.toColor();
  }
}
