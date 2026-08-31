import 'package:flutter/material.dart';
import 'package:general/src/core/constants/color_manager.dart';

/// Renders the first letter (or two for compound names) of a person/group name
/// on a deterministic background color. Used instead of the app logo whenever
/// a user/group has no picture, so the placeholder reads as theirs.
///
/// The background color is picked from a fixed palette by hashing the name, so
/// the same name always renders the same color across the app.
class InitialsAvatar extends StatelessWidget {
  const InitialsAvatar({
    super.key,
    required this.name,
    required this.size,
    this.borderRadius,
    this.fontSize,
  });

  final String name;
  final double size;
  final BorderRadius? borderRadius;
  final double? fontSize;

  static const List<Color> _palette = <Color>[
    Color(0xFF26A69A),
    Color(0xFFEF5350),
    Color(0xFF42A5F5),
    Color(0xFFAB47BC),
    Color(0xFFFFA726),
    Color(0xFF66BB6A),
    Color(0xFF7E57C2),
    Color(0xFFEC407A),
    Color(0xFF5C6BC0),
    Color(0xFFFF7043),
    Color(0xFF26C6DA),
    Color(0xFF8D6E63),
  ];

  String _initials() {
    final trimmed = name.trim();
    if (trimmed.isEmpty) return '?';
    final parts = trimmed.split(RegExp(r'\s+'));
    final first = parts.first.characters.isEmpty
        ? ''
        : parts.first.characters.first;
    if (parts.length >= 2) {
      final second = parts[1].characters.isEmpty
          ? ''
          : parts[1].characters.first;
      return (first + second).toUpperCase();
    }
    return first.toUpperCase();
  }

  Color _backgroundFor(String key) {
    if (key.isEmpty) return _palette.first;
    var hash = 0;
    for (final code in key.codeUnits) {
      hash = (hash * 31 + code) & 0x7fffffff;
    }
    return _palette[hash % _palette.length];
  }

  @override
  Widget build(BuildContext context) {
    final initials = _initials();
    final bg = _backgroundFor(name);
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: bg,
        borderRadius: borderRadius ?? BorderRadius.circular(size / 2),
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: TextStyle(
          color: ColorManager.onDark,
          fontWeight: FontWeight.w600,
          fontSize: fontSize ?? (size * 0.42),
          height: 1,
        ),
      ),
    );
  }
}
