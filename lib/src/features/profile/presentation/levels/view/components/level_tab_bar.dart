part of '../level_page.dart';

class LevelTabBar extends StatelessWidget {
  final TabController controller;

  const LevelTabBar({required this.controller, super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: context.paddingSymmetric(horizontal: 10, vertical: 5),
      margin: context.paddingAll(15),
      decoration: BoxDecoration(
          color: ColorManager.white.withValues(alpha: (0.1)),
          borderRadius: 30.radius),
      child: TabBar(
        controller: controller,
        indicatorSize: TabBarIndicatorSize.tab,
        overlayColor: WidgetStateProperty.all(ColorManager.transparent),
        dividerHeight: 0,
        indicator: BoxDecoration(
          color: ColorManager.white,
          borderRadius: 25.radius,
        ),
        // The selected pill is a fixed WHITE chip on the fixed-dark level
        // screen, so its label must stay dark ink under every theme — never
        // the theme-branching textPrimary (light on the dark default).
        labelColor: ColorManager.black,
        splashFactory: NoSplash.splashFactory,
        indicatorColor: ColorManager.transparent,
        labelPadding: context.paddingSymmetric(horizontal: 12, vertical: 5),
        unselectedLabelStyle: context.bodyMedium
            .colorExt(ColorManager.onDark.withValues(alpha: (0.8))),
        labelStyle: context.bodyMedium.w600.colorExt(ColorManager.black),
        tabs: [
          Text(
            StringManager.wealth.tr(),
          ),
          Text(
            StringManager.charm.tr(),
          ),
          Text(
            StringManager.charge.tr(),
          ),
        ],
      ),
    );
  }
}
