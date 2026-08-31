import 'dart:async';

import 'package:general/src/core/index.dart';
import 'package:utd_live_room_kit/utd_live_room_kit.dart' as live;

import 'pk_logo.dart';

/// Mico-style "matching now" dialog shown while the host waits in the random
/// PK queue: the host's avatar on the right, a mystery "?" opponent on the
/// left, the PK mark between them, a LOCAL 120s countdown and a cancel button.
///
/// The engine's own queue TTL is 150s — we deliberately close first (120s) so
/// the user never stares at a dead dialog. Outcomes:
///  - [live.UTDPkMatchState.matched]  → pop + success toast (the kit renders
///    the battle by itself);
///  - [live.UTDPkMatchState.timeout] → pop + "no opponent" toast;
///  - local countdown reaches 0      → [live.UTDPkController.cancelRandomMatch]
///    (idempotent — a concurrent pairing still wins) + pop + toast;
///  - cancel button                  → cancelRandomMatch + pop.
///
/// The controller and theme are passed EXPLICITLY from a call site that sits
/// under [live.UTDRoomScope] — the dialog's own context is above the scope
/// (grey-screen bug, owner 2026-08-08).
class LivePkMatchingDialog {
  static Future<void> show(
    BuildContext context,
    live.UTDRoomController controller,
    live.UTDRoomTheme theme,
  ) {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      barrierColor: Colors.black54,
      builder: (_) => _MatchingDialogBody(controller: controller, theme: theme),
    );
  }
}

class _MatchingDialogBody extends StatefulWidget {
  final live.UTDRoomController controller;
  final live.UTDRoomTheme theme;

  const _MatchingDialogBody({required this.controller, required this.theme});

  @override
  State<_MatchingDialogBody> createState() => _MatchingDialogBodyState();
}

class _MatchingDialogBodyState extends State<_MatchingDialogBody> {
  static const int _countdownSeconds = 120;

  int _secondsLeft = _countdownSeconds;
  Timer? _timer;
  bool _closing = false;

  live.UTDPkController? get _pk => widget.controller.pkController;

  @override
  void initState() {
    super.initState();
    _pk?.matchState.addListener(_onMatchState);
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted) return;
      if (_secondsLeft <= 1) {
        _onLocalTimeout();
      } else {
        setState(() => _secondsLeft--);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _pk?.matchState.removeListener(_onMatchState);
    super.dispose();
  }

  void _onMatchState() {
    if (!mounted || _closing) return;
    final state = _pk?.matchState.value;
    switch (state) {
      case live.UTDPkMatchState.matched:
        _close();
        Methods.showToast(context, message: StringManager.pkMatchFound.tr());
        break;
      case live.UTDPkMatchState.timeout:
        _close();
        Methods.showToast(
          context,
          message: StringManager.pkNoOpponentFound.tr(),
          isError: true,
        );
        break;
      default:
        break; // matching/idle/cancelled — cancelled is our own pop path.
    }
  }

  void _onLocalTimeout() {
    if (_closing) return;
    // The engine allows 150s; we leave the queue ourselves at 120s. If a
    // pairing already won the race the call is a no-op and `_pk_matched`
    // still lands (the kit shows the battle even after this dialog is gone).
    unawaited(_pk?.cancelRandomMatch());
    _close();
    Methods.showToast(
      context,
      message: StringManager.pkNoOpponentFound.tr(),
      isError: true,
    );
  }

  void _onCancelPressed() {
    if (_closing) return;
    unawaited(_pk?.cancelRandomMatch());
    _close();
  }

  void _close() {
    if (_closing || !mounted) return;
    _closing = true;
    _timer?.cancel();
    Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    final theme = widget.theme;
    final me = MyDataModel.getInstance();
    final myImage = (me.profile?.image ?? '').isEmpty
        ? ''
        : EndPoints.getImage(me.profile?.image ?? '');

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: EdgeInsets.symmetric(horizontal: 32.w),
      child: Container(
        padding: EdgeInsets.fromLTRB(20.w, 24.h, 20.w, 20.h),
        decoration: BoxDecoration(
          color: theme.sheetBackground,
          borderRadius: 24.radius,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              StringManager.pkMatchingTitle.tr(),
              style: context.bodyLarge.bold.colorExt(theme.onSurface),
            ),
            20.hBox,
            // Fixed LTR: mystery opponent LEFT, the local host RIGHT
            // (owner spec, mirrors Mico) — independent of app locale.
            Row(
              textDirection: TextDirection.ltr,
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _mysteryOpponent(theme),
                Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const PkLogo(size: 26),
                    4.hBox,
                    ShaderMask(
                      blendMode: BlendMode.srcIn,
                      shaderCallback: (rect) =>
                          PkColors.gradient.createShader(rect),
                      child: Icon(
                        Icons.compare_arrows_rounded,
                        color: Colors.white,
                        size: 28.sp,
                      ),
                    ),
                  ],
                ),
                UserImage(
                  imageSize: 64.h,
                  borderRadius: 50.radius,
                  image: myImage,
                  displayName: me.name ?? '',
                ),
              ],
            ),
            20.hBox,
            Text(
              StringManager.pkSearchingOpponent.tr(),
              textAlign: TextAlign.center,
              style: context.bodyMedium
                  .colorExt(theme.onSurface.withValues(alpha: 0.8)),
            ),
            10.hBox,
            Text(
              _format(_secondsLeft),
              style: context.bodyLarge.bold.colorExt(PkColors.pink).size(22),
            ),
            18.hBox,
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: _onCancelPressed,
                style: OutlinedButton.styleFrom(
                  foregroundColor: theme.onSurface,
                  side: BorderSide(
                    color: theme.onSurface.withValues(alpha: 0.3),
                  ),
                  shape: RoundedRectangleBorder(borderRadius: 24.radius),
                  padding: EdgeInsets.symmetric(vertical: 10.h),
                ),
                child: Text(
                  StringManager.cancel.tr(),
                  style: context.bodyMedium.w600.colorExt(theme.onSurface),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _mysteryOpponent(live.UTDRoomTheme theme) {
    return Container(
      width: 64.h,
      height: 64.h,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: theme.onSurface.withValues(alpha: 0.08),
        border: Border.all(
          color: theme.onSurface.withValues(alpha: 0.2),
        ),
      ),
      child: Center(
        child: Text(
          '?',
          style: TextStyle(
            color: theme.onSurface.withValues(alpha: 0.6),
            fontSize: 30.sp,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  String _format(int seconds) {
    final m = (seconds ~/ 60).toString().padLeft(2, '0');
    final s = (seconds % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }
}