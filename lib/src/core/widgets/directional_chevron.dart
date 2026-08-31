import 'package:flutter/material.dart';

/// Direction-aware iOS chevrons that mirror correctly in RTL (Arabic/Urdu).
///
/// The raw [Icons.arrow_back_ios] / [Icons.arrow_forward_ios] glyphs are NOT
/// auto-mirrored by the framework, so a hard-coded "forward" chevron keeps
/// pointing right (and "back" left) even in an RTL layout — the arrow then
/// points the wrong way for Arabic users. These widgets pick the glyph from the
/// ambient [Directionality] so the visual direction is always correct, from a
/// single source of truth shared by every theme (default / theme_1..3).
///
/// - [BackChevron]     → the "go back" affordance (leading app-bar button).
/// - [ForwardChevron]  → the "drill-in / trailing" affordance (list rows).
class BackChevron extends StatelessWidget {
  const BackChevron({super.key, this.size, this.color});

  final double? size;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final isRtl = Directionality.of(context) == TextDirection.rtl;
    return Icon(
      isRtl ? Icons.arrow_forward_ios : Icons.arrow_back_ios,
      size: size,
      color: color,
    );
  }
}

class ForwardChevron extends StatelessWidget {
  const ForwardChevron({super.key, this.size, this.color});

  final double? size;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final isRtl = Directionality.of(context) == TextDirection.rtl;
    return Icon(
      isRtl ? Icons.arrow_back_ios : Icons.arrow_forward_ios,
      size: size,
      color: color,
    );
  }
}
