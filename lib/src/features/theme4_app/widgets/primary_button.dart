import 'package:flutter/material.dart';

import '../app/theme.dart';

/// A rounded, gradient call-to-action button with a subtle press animation.
///
/// Set [outlined] for a white pill with a coloured label (used for the
/// secondary login options).
class PrimaryButton extends StatefulWidget {
  final String label;
  final IconData? icon;
  final VoidCallback? onPressed;
  final bool outlined;
  final Gradient gradient;
  final double height;

  const PrimaryButton({
    super.key,
    required this.label,
    this.icon,
    this.onPressed,
    this.outlined = false,
    this.gradient = AppColors.brand,
    this.height = 56,
  });

  @override
  State<PrimaryButton> createState() => _PrimaryButtonState();
}

class _PrimaryButtonState extends State<PrimaryButton> {
  bool _down = false;

  void _set(bool v) => setState(() => _down = v);

  @override
  Widget build(BuildContext context) {
    final radius = BorderRadius.circular(AppRadii.pill);
    final enabled = widget.onPressed != null;

    final content = Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (widget.icon != null) ...[
          Icon(widget.icon,
              size: 20,
              color: widget.outlined ? AppColors.ink : Colors.white),
          const SizedBox(width: 10),
        ],
        Text(
          widget.label,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: widget.outlined ? AppColors.ink : Colors.white,
          ),
        ),
      ],
    );

    return GestureDetector(
      onTapDown: enabled ? (_) => _set(true) : null,
      onTapUp: enabled ? (_) => _set(false) : null,
      onTapCancel: enabled ? () => _set(false) : null,
      onTap: widget.onPressed,
      child: AnimatedScale(
        scale: _down ? 0.97 : 1.0,
        duration: const Duration(milliseconds: 120),
        curve: Curves.easeOut,
        child: Container(
          height: widget.height,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: widget.outlined ? null : widget.gradient,
            color: widget.outlined ? Colors.white : null,
            borderRadius: radius,
            border: widget.outlined
                ? Border.all(color: AppColors.hairline, width: 1.4)
                : null,
            boxShadow: widget.outlined ? null : AppShadows.soft,
          ),
          child: content,
        ),
      ),
    );
  }
}
