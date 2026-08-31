import 'package:general/src/core/index.dart';

class LightTheme {
  static bool get _isDarkDefault =>
      !ConstantsManager.isTheme1 &&
      !ConstantsManager.isTheme2 &&
      !ConstantsManager.isTheme3;

  /// Built fresh on each access so it reflects the CURRENT [ColorManager]
  /// values (admin/panel overrides applied at runtime). A `static final`
  /// froze the ThemeData at first access, ignoring later panel colors.
  static ThemeData get theme => ThemeData(
    fontFamily: "AppFont",
    // default (التصميم 1) is a full dark identity (Xena violet-night):
    // brightness dark keeps Material defaults (dividers, icons, overlays)
    // inside the dark family for every widget that reads the theme.
    brightness: _isDarkDefault ? Brightness.dark : Brightness.light,
    colorScheme: _isDarkDefault
        ? ColorScheme.dark(
            primary: ColorManager.primary,
            secondary: ColorManager.defaultAccentMagenta,
            surface: ColorManager.defaultDarkSurface,
            onSurface: ColorManager.defaultDarkTextPrimary,
          )
        : ColorScheme.light(
            primary: ColorManager.primary,
            secondary: ColorManager.secondaryColor,
          ),
    // The app-wide default scaffold fill is each variant's own page color so
    // every screen that doesn't set an explicit background still matches its
    // identity. default (التصميم 1) is the dark variant → violet-night fill.
    scaffoldBackgroundColor: ConstantsManager.isTheme1
        ? ColorManager.theme1BackgroundAlt
        : ConstantsManager.isTheme2
            ? ColorManager.theme2BackgroundAlt
            : ConstantsManager.isTheme3
                ? ColorManager.offWhite
                : ColorManager.defaultDarkBackground,
    appBarTheme: AppBarTheme(
      backgroundColor: _isDarkDefault
          ? ColorManager.defaultDarkBackground
          : ColorManager.black,
      surfaceTintColor: ColorManager.transparent,
      foregroundColor:
          _isDarkDefault ? ColorManager.defaultDarkTextPrimary : null,
      iconTheme: _isDarkDefault
          ? const IconThemeData(color: ColorManager.defaultDarkTextPrimary)
          : null,
    ),
    bottomNavigationBarTheme: BottomNavigationBarThemeData(
      backgroundColor: ColorManager.bottomNavColor,
    ),
    // Dialogs / sheets / cards / menus: the dark default paints its elevated
    // violet surface so every showDialog/showModalBottomSheet that relies on
    // theme defaults lands in the family automatically.
    dialogTheme: _isDarkDefault
        ? DialogThemeData(
            backgroundColor: ColorManager.defaultDarkSurface,
            surfaceTintColor: ColorManager.transparent,
            shape: RoundedRectangleBorder(borderRadius: 20.radius),
          )
        : null,
    bottomSheetTheme: _isDarkDefault
        ? BottomSheetThemeData(
            backgroundColor: ColorManager.defaultDarkSurface,
            surfaceTintColor: ColorManager.transparent,
            modalBackgroundColor: ColorManager.defaultDarkSurface,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.only(
                topLeft: 20.radiusCircular,
                topRight: 20.radiusCircular,
              ),
            ),
          )
        : null,
    cardTheme: _isDarkDefault
        ? CardThemeData(
            color: ColorManager.defaultDarkSurface,
            surfaceTintColor: ColorManager.transparent,
            shape: RoundedRectangleBorder(borderRadius: 16.radius),
          )
        : null,
    popupMenuTheme: _isDarkDefault
        ? PopupMenuThemeData(
            color: ColorManager.defaultDarkSurfaceAlt,
            surfaceTintColor: ColorManager.transparent,
            textStyle: TextStyle(
              color: ColorManager.defaultDarkTextPrimary,
              fontFamily: 'AppFont',
              fontSize: 14.sp,
            ),
          )
        : null,
    snackBarTheme: _isDarkDefault
        ? SnackBarThemeData(
            backgroundColor: ColorManager.defaultDarkSurfaceAlt,
            contentTextStyle: TextStyle(
              color: ColorManager.defaultDarkTextPrimary,
              fontFamily: 'AppFont',
              fontSize: 14.sp,
            ),
          )
        : null,
    tabBarTheme: _isDarkDefault
        ? const TabBarThemeData(
            labelColor: ColorManager.defaultDarkTextPrimary,
            unselectedLabelColor: ColorManager.defaultDarkTextSecondary,
            indicatorColor: ColorManager.defaultAccentMagenta,
          )
        : null,
    iconTheme: _isDarkDefault
        ? const IconThemeData(color: ColorManager.defaultDarkTextPrimary)
        : null,
    listTileTheme: _isDarkDefault
        ? const ListTileThemeData(
            textColor: ColorManager.defaultDarkTextPrimary,
            iconColor: ColorManager.defaultDarkTextSecondary,
          )
        : null,
    textTheme: TextTheme(
      titleLarge: TextStyle(
        fontSize: 20.sp,
        fontWeight: FontWeight.w600,
        color: ColorManager.textPrimary,
        fontFamily: 'AppFont',
        fontFamilyFallback: const ['SegoeUI', 'sans-serif'],
      ),
      bodyLarge: TextStyle(
        fontSize: 16.sp,
        color: ColorManager.textPrimary,
        fontFamily: 'AppFont',
        fontFamilyFallback: const ['SegoeUI', 'sans-serif'],
      ),
      bodyMedium: TextStyle(
        fontSize: 14.sp,
        color: ColorManager.textPrimary,
        fontFamily: 'AppFont',
        fontFamilyFallback: const ['SegoeUI', 'sans-serif'],
      ),
      bodySmall: TextStyle(
        fontSize: 12.sp,
        color: ColorManager.textPrimary.withValues(alpha: 0.60),
        fontFamily: 'AppFont',
        fontFamilyFallback: const ['SegoeUI', 'sans-serif'],
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      // Dark default gets its violet field fill so the white textPrimary
      // stays readable; the light variants keep the legacy light-gray fill.
      fillColor: _isDarkDefault
          ? ColorManager.defaultDarkField
          : ColorManager.grayLight.withValues(alpha: 0.7),
      hintStyle: _isDarkDefault
          ? TextStyle(
              color: ColorManager.defaultDarkTextSecondary,
              fontFamily: 'AppFont',
              fontSize: 14.sp,
            )
          : null,
      enabledBorder: OutlineInputBorder(
        borderRadius: 30.radius,
        borderSide: const BorderSide(
          width: 1.5,
          color: ColorManager.transparent,
        ),
      ),
      border: OutlineInputBorder(
        borderRadius: 30.radius,
        borderSide: const BorderSide(
          width: 1.5,
          color: ColorManager.transparent,
        ),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: 30.radius,
        borderSide: BorderSide(
          width: 1.5,
          color: _isDarkDefault
              ? ColorManager.defaultAccent
              : ColorManager.grayLight.withValues(alpha: 0.7),
        ),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: 30.radius,
        borderSide: const BorderSide(
          color: ColorManager.redAccount,
          width: 1.5,
        ),
      ),
    ),
    dividerTheme: DividerThemeData(
      color: _isDarkDefault
          ? ColorManager.defaultDarkBorder
          : ColorManager.grey,
      thickness: 1.0,
    ),
    checkboxTheme: CheckboxThemeData(
      side: BorderSide.none,
      fillColor: const WidgetStatePropertyAll(ColorManager.checkBoxColor),
      checkColor: const WidgetStatePropertyAll(
        ColorManager.black,
      ),
      shape: RoundedRectangleBorder(
        side: BorderSide.none,
        borderRadius: 10.5.radius,
      ),
    ),
    pageTransitionsTheme: const PageTransitionsTheme(
      builders: {
        TargetPlatform.android: FadeUpwardsPageTransitionsBuilder(),
        TargetPlatform.iOS: FadeUpwardsPageTransitionsBuilder(),
      },
    ),
  );
}
