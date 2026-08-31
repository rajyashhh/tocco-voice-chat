import 'package:general/src/core/index.dart';

class RefreshIndicatorWidget extends StatelessWidget {
  const RefreshIndicatorWidget({
    super.key,
    required this.onRefresh,
    required this.child,
    this.color,
  });
  final Future<void> Function() onRefresh;
  final Widget child;

  /// Spinner color override. Room/live screens pass the theme-independent
  /// [ColorManager.roomGold] so the indicator never follows the app theme
  /// (owner rule); everywhere else the theme-driven [ColorManager.primary]
  /// default keeps the existing look.
  final Color? color;
  @override
  Widget build(BuildContext context) {
    return RefreshIndicator.adaptive(
      strokeWidth: 2.0,
      color: color ?? ColorManager.primary,
      backgroundColor: ColorManager.scaffoldBg,
      onRefresh: onRefresh,
      child: child,
    );
  }
}
