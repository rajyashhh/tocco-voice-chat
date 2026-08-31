import 'package:general/src/core/index.dart';

class BodyThemeBackground extends StatelessWidget {
  const BodyThemeBackground({super.key, this.fallbackColor});

  /// Base color for the last-resort gradient when no body theme is cached.
  /// Room/live screens pass the theme-independent [ColorManager.roomGold] so
  /// the fallback never follows the app theme (owner rule); null keeps the
  /// theme-driven [ColorManager.primary] default.
  final Color? fallbackColor;

  @override
  Widget build(BuildContext context) {
    final themeType = HiveManager().getData<String>(
          KeysManager.USER_BOX,
          KeysManager.BODY_THEME_TYPE_KEY,
        ) ??
        '';

    if (themeType == 'image') {
      final image = HiveManager().getData<String>(
            KeysManager.USER_BOX,
            KeysManager.BODY_THEME_IMAGE_KEY,
          ) ??
          '';
      if (image.isNotEmpty) {
        return ImageViewWidget(
          url: image,
          height: ScreenUtil().screenHeight,
          width: ScreenUtil().screenWidth,
          boxFit: BoxFit.cover,
        );
      }
    } else if (themeType == 'color') {
      final colorHex = HiveManager().getData<String>(
            KeysManager.USER_BOX,
            KeysManager.BODY_THEME_COLOR_KEY,
          ) ??
          '';
      final color = Methods.safeHexColor(colorHex);
      if (color != null) {
        return Container(color: color);
      }
    } else if (themeType == 'gradient') {
      final g1 = HiveManager().getData<String>(
            KeysManager.USER_BOX,
            KeysManager.BODY_THEME_GRADIENT_ONE_KEY,
          ) ??
          '';
      final g2 = HiveManager().getData<String>(
            KeysManager.USER_BOX,
            KeysManager.BODY_THEME_GRADIENT_TWO_KEY,
          ) ??
          '';
      final g3 = HiveManager().getData<String>(
            KeysManager.USER_BOX,
            KeysManager.BODY_THEME_GRADIENT_THREE_KEY,
          ) ??
          '';
      final gradientColors = [
        Methods.safeHexColor(g1),
        Methods.safeHexColor(g2),
        Methods.safeHexColor(g3),
      ].whereType<Color>().toList();
      if (gradientColors.length >= 2) {
        return Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: gradientColors,
            ),
          ),
        );
      }
      // Gradient invalid (fewer than 2 valid colors): fall back to the body
      // theme color before the generic ColorManager.primary fallback below.
      final bodyColor = Methods.safeHexColor(
        HiveManager().getData<String>(
          KeysManager.USER_BOX,
          KeysManager.BODY_THEME_COLOR_KEY,
        ),
      );
      if (bodyColor != null) {
        return Container(color: bodyColor);
      }
    }

    return Container(
      decoration: BoxDecoration(
        gradient: GradientHelper.buildGradientFromHeaderColor(
            fallbackColor ?? ColorManager.primary,
            endColor:
                fallbackColor != null ? ColorManager.roomSurface : null),
      ),
    );
  }
}
