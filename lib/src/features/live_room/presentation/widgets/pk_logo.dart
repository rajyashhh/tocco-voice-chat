import 'package:general/src/core/index.dart';

/// PK feature branding — a FIXED pink→blue identity (Mico-style), deliberately
/// NOT theme tokens: like Mico, the PK mark looks identical under every app
/// theme, so these constants live with the feature instead of ColorManager.
class PkColors {
  const PkColors._();

  static const Color pink = Color(0xFFFF2D8A);
  static const Color blue = Color(0xFF31A8FF);

  /// The signature pink→blue sweep used by the logo and PK action buttons.
  static const LinearGradient gradient = LinearGradient(
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
    colors: [pink, blue],
  );
}

/// The "PK" wordmark drawn as gradient text (no image asset exists) — heavy
/// weight with a light italic slant, pink fading into blue. Scales with [size]
/// (the font size), so the same widget serves the controls-bar button, the
/// sheet header and the matching dialog.
class PkLogo extends StatelessWidget {
  /// Font size of the wordmark.
  final double size;

  const PkLogo({super.key, this.size = 24});

  @override
  Widget build(BuildContext context) {
    return ShaderMask(
      blendMode: BlendMode.srcIn,
      shaderCallback: (rect) => PkColors.gradient.createShader(rect),
      child: Text(
        'PK',
        textDirection: TextDirection.ltr,
        style: TextStyle(
          // The shader replaces this color; white keeps full opacity.
          color: Colors.white,
          fontSize: size,
          height: 1,
          fontWeight: FontWeight.w900,
          fontStyle: FontStyle.italic,
          letterSpacing: size * 0.04,
        ),
      ),
    );
  }
}
