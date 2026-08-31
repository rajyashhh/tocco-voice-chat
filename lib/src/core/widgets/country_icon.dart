import '../index.dart';

/// Thin wrapper kept for the many existing call sites. Rendering is delegated
/// to [CountryFlagWidget]: ISO code -> offline flag library first, then the
/// legacy network [country] url, else collapses (never a grey placeholder).
class CountryIcon extends StatelessWidget {
  /// Legacy server flag URL/path (fallback only).
  final String country;

  /// ISO 3166-1 code (alpha-2/alpha-3) — preferred source for the flag.
  final String? iso;

  final String? countryId;

  final double? imageSize;
  final double? width;
  final double? height;
  final BoxFit? boxFit;
  final BoxBorder? border;
  final BorderRadiusGeometry? borderRadius;

  const CountryIcon({
    required this.country,
    this.iso,
    this.countryId,
    this.boxFit,
    this.imageSize,
    this.width,
    this.height,
    this.border,
    this.borderRadius,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    final radius = borderRadius is BorderRadius
        ? (borderRadius as BorderRadius).topLeft.x
        : 5.0;
    return CountryFlagWidget(
      iso: iso,
      fallbackUrl: country,
      radius: radius,
      boxFit: boxFit,
      width: imageSize ?? width ?? 25.w,
      height: imageSize ?? height ?? 22.w,
    );
  }
}