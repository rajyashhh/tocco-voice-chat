import 'package:flutter/material.dart';

typedef MiniOverlayBuilder = Widget Function({
  required VoidCallback onRestore,
  required VoidCallback onClose,
});

class UTDMinimizeConfig {
  final VoidCallback? onClose;
  final MiniOverlayBuilder? overlayBuilder;
  final double overlayWidth;
  final double overlayHeight;
  final double overlayBottomOffset;
  final double overlayRightOffset;

  final String? roomImage;
  final bool showMicToggle;
  final bool showLeaveButton;
  final Color? soundWaveColor;
  final double borderRadius;

  const UTDMinimizeConfig({
    this.onClose,
    this.overlayBuilder,
    this.overlayWidth = 120,
    this.overlayHeight = 120,
    this.overlayBottomOffset = 120,
    this.overlayRightOffset = 16,
    this.roomImage,
    this.showMicToggle = true,
    this.showLeaveButton = true,
    this.soundWaveColor,
    this.borderRadius = 16,
  });
}
