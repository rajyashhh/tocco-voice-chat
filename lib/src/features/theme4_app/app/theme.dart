import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// App-wide colours, gradients and the [ThemeData] used by [MaterialApp].
///
/// The visual language is a soft purple → pink → blue gradient with rounded
/// cards, rounded buttons and gentle shadows.
class AppColors {
  AppColors._();

  static const Color purple = Color(0xFF7C3AED);
  static const Color pink = Color(0xFFEC4899);
  static const Color blue = Color(0xFF3B82F6);
  static const Color indigo = Color(0xFF6366F1);
  static const Color cyan = Color(0xFF22D3EE);
  static const Color rose = Color(0xFFF43F5E);

  static const Color scaffold = Color(0xFFF6F5FB);
  static const Color surface = Colors.white;
  static const Color ink = Color(0xFF1E1B2E);
  static const Color subtle = Color(0xFF8B8696);
  static const Color hairline = Color(0xFFEDEAF4);

  /// The signature brand gradient (purple → pink → blue).
  static const LinearGradient brand = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [purple, pink, blue],
  );

  static const LinearGradient sunset = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [rose, pink, purple],
  );

  static const LinearGradient ocean = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [indigo, cyan, blue],
  );

  /// Full-screen background wash that sits behind frosted content.
  static const LinearGradient backdrop = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFFF3EAFB), Color(0xFFFDEFF6), Color(0xFFEAF1FD)],
  );
}

class AppRadii {
  AppRadii._();
  static const double sm = 12;
  static const double md = 18;
  static const double lg = 24;
  static const double pill = 999;
}

class AppShadows {
  AppShadows._();

  static List<BoxShadow> soft = [
    BoxShadow(
      color: AppColors.purple.withValues(alpha: 0.08),
      blurRadius: 24,
      offset: const Offset(0, 10),
    ),
  ];

  static List<BoxShadow> card = [
    BoxShadow(
      color: const Color(0xFF1E1B2E).withValues(alpha: 0.05),
      blurRadius: 18,
      offset: const Offset(0, 8),
    ),
  ];
}

class AppTheme {
  AppTheme._();

  static ThemeData get light {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.purple,
        primary: AppColors.purple,
        secondary: AppColors.pink,
      ),
      scaffoldBackgroundColor: AppColors.scaffold,
    );

    return base.copyWith(
      textTheme: GoogleFonts.poppinsTextTheme(base.textTheme).apply(
        bodyColor: AppColors.ink,
        displayColor: AppColors.ink,
      ),
      splashColor: AppColors.purple.withValues(alpha: 0.10),
      highlightColor: AppColors.purple.withValues(alpha: 0.04),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        foregroundColor: AppColors.ink,
      ),
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: FadeUpwardsPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
        },
      ),
    );
  }
}
