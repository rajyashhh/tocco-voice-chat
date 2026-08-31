part of '../vip_screen.dart';

class TabBarBody extends StatelessWidget {
  const TabBarBody({
    super.key,
    required this.controller,
    required this.length,
    required this.vipCenterEntity,
  });
  final TabController controller;
  final List<VipCenterEntity> vipCenterEntity;
  final int length;
  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: ScreenUtil().screenWidth,
      child: TabBar(
        controller: controller,
        indicatorSize: TabBarIndicatorSize.label,
        dividerHeight: 0,
        tabAlignment: TabAlignment.start,
        indicator: MDIndicator(
            indicatorColor: controller.index == 0
                ? ColorManager.textPrimary
                : ColorManager.white,
            indicatorWidth: 17.w,
            indicatorHeight: 3.5.h,
            radius: 20),
        isScrollable: true,
        unselectedLabelStyle: context.bodySmall
            .colorExt(
              controller.index == 0
                  ? ColorManager.secondaryText
                  : ColorManager.onDark.withValues(
                      alpha: (0.6),
                    ),
            )
            .w600,
        labelStyle: context.bodyMedium
            .colorExt(controller.index == 0
                ? ColorManager.textPrimary
                : ColorManager.onDark)
            .w600,
        tabs: List.generate(length + 1, (index) {
          if (index == 0) {
            return Text(
              StringManager.vipCenter.tr(),
            );
          }
          return Text(vipCenterEntity[index - 1].name ?? '');
        }),
      ),
    );
  }
}
