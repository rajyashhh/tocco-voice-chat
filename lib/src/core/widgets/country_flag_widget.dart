import 'package:country_flags/country_flags.dart';

import '../index.dart';

/// Single source of truth for rendering a country flag anywhere in the app.
///
/// Resolution order:
///  1. [iso] (ISO 3166-1 alpha-2 or alpha-3) -> offline SVG from the bundled
///     `country_flags` library. Covers every country in the world with zero
///     network calls and zero manually-uploaded images.
///  2. [fallbackUrl] -> network image (legacy server-uploaded flags).
///  3. Nothing renderable -> collapses to [SizedBox.shrink] (never a grey
///     placeholder box).
class CountryFlagWidget extends StatelessWidget {
  final String? iso;
  final String? fallbackUrl;
  final double? width;
  final double? height;
  final double radius;
  final BoxFit? boxFit;

  const CountryFlagWidget({
    super.key,
    this.iso,
    this.fallbackUrl,
    this.width,
    this.height,
    this.radius = 3,
    this.boxFit,
  });

  /// True when this widget will actually paint something, so callers can
  /// decide whether to reserve layout space (spacing, separators...).
  static bool canRender({String? iso, String? fallbackUrl}) =>
      _flagCode(iso) != null || (fallbackUrl ?? '').isNotEmpty;

  static String? _flagCode(String? iso) {
    final code = iso?.trim() ?? '';
    if (code.length < 2 || code.length > 3) return null;
    return FlagCode.fromCountryCode(code.toUpperCase());
  }

  @override
  Widget build(BuildContext context) {
    final flagCode = _flagCode(iso);
    if (flagCode != null) {
      return CountryFlag.fromCountryCode(
        iso!.trim().toUpperCase(),
        theme: ImageTheme(
          width: width,
          height: height,
          shape: RoundedRectangle(radius),
        ),
      );
    }

    final url = fallbackUrl ?? '';
    if (url.isEmpty) return const SizedBox.shrink();

    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: ImageViewWidget(
        url: EndPoints.getImage(url),
        width: width,
        height: height,
        boxFit: boxFit ?? BoxFit.cover,
      ),
    );
  }
}
